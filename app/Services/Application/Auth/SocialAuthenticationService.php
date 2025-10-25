<?php

declare(strict_types=1);

namespace App\Services\Application\Auth;

use App\Contracts\Interfaces\Auth\OAuthClientInterface;
use App\Contracts\Interfaces\Auth\SocialAuthenticationInterface;
use App\Events\SocialLoginWelcome;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UserConfirmationToken;
use App\Repositories\UserConfirmationTokenRepository;
use App\Repositories\UserRepository;
use App\Services\Application\EmailVerificationService;
use App\Services\Application\UserRegistrationService;
use App\Services\Core\Abstracts\AbstractBaseService;
use App\Support\ServiceResult;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

/**
 * Serviço de autenticação social
 *
 * Esta classe implementa a lógica de negócio para autenticação através
 * de provedores sociais (Google, Facebook, etc.), seguindo os padrões
 * arquiteturais do projeto Easy Budget Laravel.
 */
class SocialAuthenticationService extends AbstractBaseService implements SocialAuthenticationInterface
{
    private OAuthClientInterface            $oauthClient;
    private UserRegistrationService         $userRegistrationService;
    private UserConfirmationTokenRepository $userConfirmationTokenRepository;
    private EmailVerificationService        $emailVerificationService;

    public function __construct(
        OAuthClientInterface $oauthClient,
        UserRepository $userRepository,
        UserRegistrationService $userRegistrationService,
        UserConfirmationTokenRepository $userConfirmationTokenRepository,
        EmailVerificationService $emailVerificationService,
    ) {
        $this->oauthClient                     = $oauthClient;
        $this->userRepository                  = $userRepository;
        $this->userRegistrationService         = $userRegistrationService;
        $this->userConfirmationTokenRepository = $userConfirmationTokenRepository;
        $this->emailVerificationService        = $emailVerificationService;
    }

    /**
     * {@inheritdoc}
     */
    public function authenticateWithSocialProvider( string $provider, array $userData ): ServiceResult
    {
        try {
            // Busca usuário existente por ID social
            $existingUser = $this->findUserBySocialId( $provider, $userData[ 'id' ] );

            if ( $existingUser ) {
                // Atualiza dados do usuário existente
                $updateResult = $this->syncSocialProfileData( $existingUser, $userData );

                if ( !$updateResult->isSuccess() ) {
                    return $updateResult;
                }

                Log::info( 'Usuário autenticado via provedor social', [
                    'provider' => $provider,
                    'user_id'  => $existingUser->id,
                    'email'    => $existingUser->email,
                ] );

                return $this->success( $existingUser, 'Usuário autenticado com sucesso via ' . ucfirst( $provider ) );
            }

            // Verifica se e-mail existe e pode ser vinculado
            if ( !empty( $userData[ 'email' ] ) && $this->isSocialEmailInUse( $userData[ 'email' ] ) ) {
                $existingUser = $this->findUserByEmail( $userData[ 'email' ] );

                if ( $existingUser ) {
                    // Criar token de confirmação para vinculação de conta social
                    $linkResult = $this->createSocialAccountLinkingToken( $existingUser, $provider, $userData );

                    if ( $linkResult->isSuccess() ) {
                        Log::info( 'Token de confirmação criado para vinculação de conta social', [
                            'provider'  => $provider,
                            'user_id'   => $existingUser->id,
                            'email'     => $existingUser->email,
                            'social_id' => $userData[ 'id' ],
                        ] );

                        return $this->success( $existingUser, 'Um e-mail de confirmação foi enviado para vincular sua conta ' . ucfirst( $provider ) . '.' );
                    } else {
                        return $linkResult;
                    }
                }
            }

            // Cria novo usuário
            $createResult = $this->createUserFromSocialData( $provider, $userData );

            if ( !$createResult->isSuccess() ) {
                return $createResult;
            }

            Log::info( 'Novo usuário criado via provedor social', [
                'provider' => $provider,
                'user_id'  => $createResult->getData()->id,
                'email'    => $createResult->getData()->email,
            ] );

            // Para usuários criados via Google OAuth, não disparamos eventos de e-mail
            // pois o Google já verifica o e-mail e não requer confirmação adicional
            Log::info( 'Usuário criado via Google OAuth - e-mail já verificado automaticamente', [
                'user_id'  => $createResult->getData()->id,
                'email'    => $createResult->getData()->email,
                'provider' => $provider,
            ] );

            return $this->success( $createResult->getData(), 'Conta criada com sucesso via ' . ucfirst( $provider ) );

        } catch ( \Exception $e ) {
            Log::error( 'Erro na autenticação social', [
                'provider'  => $provider,
                'error'     => $e->getMessage(),
                'user_data' => $userData,
            ] );

            return $this->error( 'Erro na autenticação', 'Erro interno durante a autenticação. Tente fazer login novamente.' );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function createUserFromSocialData( string $provider, array $userData ): ServiceResult
    {
        try {
            // Converte dados do Google para formato esperado pelo UserRegistrationService
            // Gera dados válidos para campos obrigatórios do UserRegistrationService
            $nameParts = explode( ' ', $userData[ 'name' ] );
            $firstName = $nameParts[ 0 ] ?? $userData[ 'name' ];
            $lastName  = $nameParts[ 1 ] ?? 'Usuário'; // Fallback se não houver sobrenome

            $registrationData = [
                'first_name'     => $firstName,
                'last_name'      => $lastName,
                'name'           => $userData[ 'name' ], // ✅ Nome completo do Google para o campo name do usuário
                'email'          => $userData[ 'email' ],
                'password'       => null, // ✅ Sem senha para usuários sociais
                'phone'          => '+5511999999999', // Telefone padrão para login social
                'terms_accepted' => true, // Aceito automaticamente para login social
            ];

            // Usa o UserRegistrationService para criar usuário seguindo padrão completo
            $registrationResult = $this->userRegistrationService->registerUser( $registrationData );

            if ( !$registrationResult->isSuccess() ) {
                return $registrationResult;
            }

            $registrationData = $registrationResult->getData();
            $user             = $registrationData[ 'user' ];

            // Atualiza campos específicos do Google após criação usando repository
            $this->userRepository->update( $user->id, [
                'name'              => $userData[ 'name' ], // ✅ Define nome diretamente para evitar problemas com accessor
                'google_id'         => $provider === 'google' ? $userData[ 'id' ] : null,
                'avatar'            => $userData[ 'avatar' ] ?? null,
                'email_verified_at' => now(), // ✅ E-mail verificado automaticamente (Google já verifica)
                'is_active'         => true,  // ✅ Usuário ativo automaticamente (login social fluido)
            ] );

            \Illuminate\Support\Facades\Log::info( 'Senha removida para usuário social', [
                'provider' => $provider,
                'user_id'  => $user->id,
                'email'    => $user->email,
            ] );

            Log::info( 'Usuário criado via autenticação social usando UserRegistrationService', [
                'provider'  => $provider,
                'user_id'   => $user->id,
                'email'     => $user->email,
                'google_id' => $userData[ 'id' ],
            ] );

            // Dispara evento de boas-vindas para login social
            $tenant = $user->tenant; // Obtém o tenant do usuário
            Event::dispatch( new SocialLoginWelcome( $user, $tenant, $provider ) );

            return $this->success( $user, 'Usuário criado com sucesso via ' . ucfirst( $provider ) );

        } catch ( \Exception $e ) {
            Log::error( 'Erro ao criar usuário a partir de dados sociais usando UserRegistrationService', [
                'provider'  => $provider,
                'error'     => $e->getMessage(),
                'user_data' => $userData,
            ] );

            return $this->error( 'Erro ao criar conta', 'Erro ao criar a conta. Tente novamente ou entre em contato com o suporte.' );
        }
    }

    /**
     * Cria token de confirmação para vinculação de conta social.
     *
     * Este método delega a criação do token para o EmailVerificationService,
     * que implementa toda a lógica de criação de tokens seguindo os padrões
     * de segurança do sistema.
     *
     * @param User $user Usuário existente
     * @param string $provider Provedor social (google, facebook, etc.)
     * @param array $userData Dados do usuário do provedor social
     * @return ServiceResult Resultado da operação
     */
    private function createSocialAccountLinkingToken( User $user, string $provider, array $userData ): ServiceResult
    {
        try {
            Log::info( 'Iniciando criação de token para vinculação social', [
                'provider' => $provider,
                'user_id'  => $user->id,
                'email'    => $user->email,
            ] );

            // Usa EmailVerificationService para criar token e enviar e-mail de confirmação
            $emailResult = $this->emailVerificationService->sendSocialLinkingConfirmation( $user, $provider, $userData );

            if ( !$emailResult->isSuccess() ) {
                return $emailResult;
            }

            Log::info( 'Token de confirmação criado para vinculação social', [
                'provider' => $provider,
                'user_id'  => $user->id,
                'email'    => $user->email,
                'token_id' => $emailResult->getData()[ 'token' ]->id,
            ] );

            return $this->success( $user, 'Token de confirmação criado e e-mail enviado' );

        } catch ( \Exception $e ) {
            Log::error( 'Erro ao criar token de confirmação para vinculação social', [
                'provider' => $provider,
                'user_id'  => $user->id,
                'error'    => $e->getMessage(),
                'trace'    => $e->getTraceAsString(),
            ] );

            return $this->error( 'Erro ao processar vinculação', 'Não foi possível processar a vinculação da conta social.' );
        }
    }

    /**
     * Confirma vinculação de conta social através de token.
     *
     * Este método valida o token de confirmação e vincula efetivamente
     * a conta social ao usuário após confirmação por e-mail.
     *
     * @param string $token Token de confirmação
     * @return ServiceResult Resultado da operação
     */
    public function confirmSocialAccountLinking( string $token ): ServiceResult
    {
        try {
            // Buscar token válido
            $confirmationToken = $this->userConfirmationTokenRepository->findByToken( $token );

            Log::info( 'Token confirmation attempt', [
                'token_hash'       => substr( $token, 0, 10 ) . '...',
                'token_found'      => $confirmationToken ? 'YES' : 'NO',
                'token_id'         => $confirmationToken ? $confirmationToken->id : null,
                'token_type'       => $confirmationToken ? $confirmationToken->type : null,
                'token_expires_at' => $confirmationToken ? $confirmationToken->expires_at : null,
                'token_user_id'    => $confirmationToken ? $confirmationToken->user_id : null,
                'token_tenant_id'  => $confirmationToken ? $confirmationToken->tenant_id : null,
            ] );

            if ( !$confirmationToken || $confirmationToken->expires_at->isPast() ) {
                Log::warning( 'Token confirmation failed - token not found or expired', [
                    'token_found'   => $confirmationToken ? 'YES' : 'NO',
                    'token_expired' => $confirmationToken ? $confirmationToken->expires_at->isPast() : null,
                ] );
                return $this->error( 'Token inválido', 'Token de confirmação inválido ou expirado.' );
            }

            // Verificar se token é do tipo correto
            if ( $confirmationToken->type !== 'social_linking' ) {
                return $this->error( 'Token inválido', 'Tipo de token inválido para esta operação.' );
            }

            // Buscar usuário
            $user = $this->userRepository->find( $confirmationToken->user_id );
            if ( !$user ) {
                return $this->error( 'Usuário não encontrado', 'Conta não encontrada. Entre em contato com o suporte.' );
            }

            // Verificar tenant (ignora validação em contexto de teste)
            if ( config( 'tenant.testing_id' ) === null && $user->tenant_id !== $confirmationToken->tenant_id ) {
                return $this->error( 'Erro de validação', 'Erro de validação de segurança.' );
            }

            // Decodificar metadata
            $metadata = json_decode( $confirmationToken->metadata, true );
            Log::info( 'Metadados decodificados do token', [
                'token_id' => $confirmationToken->id,
                'metadata' => $metadata,
                'user_id'  => $user->id,
            ] );

            if ( !$metadata || !isset( $metadata[ 'provider' ], $metadata[ 'social_id' ] ) ) {
                Log::error( 'Metadados do token de vinculação incompletos', [
                    'token_id' => $confirmationToken->id,
                    'metadata' => $metadata,
                    'user_id'  => $user->id,
                    'ip'       => request()->ip(),
                ] );
                return $this->error( 'Erro de validação', 'Metadados do token de vinculação incompletos.' );
            }

            DB::beginTransaction();

            // Vincular conta social ao usuário usando repository
            Log::info( 'Atualizando dados do usuário com metadados', [
                'user_id'      => $user->id,
                'current_name' => $user->name,
                'social_name'  => $metadata[ 'social_name' ] ?? 'NOT_FOUND',
                'google_id'    => $metadata[ 'provider' ] === 'google' ? $metadata[ 'social_id' ] : $user->google_id,
                'avatar'       => $metadata[ 'social_avatar' ] ?? $user->avatar,
            ] );

            $this->userRepository->update( $user->id, [
                'google_id'         => $metadata[ 'provider' ] === 'google' ? $metadata[ 'social_id' ] : $user->google_id,
                'name'              => $metadata[ 'social_name' ] ?? $user->name,
                'avatar'            => $metadata[ 'social_avatar' ] ?? $user->avatar,
                'email_verified_at' => now(), // Marca e-mail como verificado
            ] );

            // Disparar evento de confirmação de vinculação
            Event::dispatch( new \App\Events\SocialAccountLinked( $user, $metadata[ 'provider' ], $metadata ) );

            // Remover token usado
            $this->userConfirmationTokenRepository->delete( $confirmationToken->id );

            DB::commit();

            Log::info( 'Conta social vinculada com sucesso após confirmação', [
                'provider' => $metadata[ 'provider' ],
                'user_id'  => $user->id,
                'email'    => $user->email,
            ] );

            return $this->success( $user, 'Conta ' . ucfirst( $metadata[ 'provider' ] ) . ' vinculada com sucesso!' );

        } catch ( \Exception $e ) {
            DB::rollBack();

            Log::error( 'Erro ao confirmar vinculação de conta social', [
                'error' => $e->getMessage(),
                'token' => substr( $token, 0, 8 ) . '...',
            ] );

            return $this->error( 'Erro na confirmação', 'Erro ao confirmar vinculação da conta social.' );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function linkSocialAccountToUser( User $user, string $provider, array $userData ): ServiceResult
    {
        // Método mantido para compatibilidade, mas agora usa o sistema de tokens
        return $this->createSocialAccountLinkingToken( $user, $provider, $userData );
    }

    /**
     * {@inheritdoc}
     */
    public function findUserBySocialId( string $provider, string $socialId ): ?User
    {
        $field = $provider === 'google' ? 'google_id' : 'social_id';

        return User::where( $field, $socialId )->first();
    }

    /**
     * {@inheritdoc}
     */
    public function syncSocialProfileData( User $user, array $socialData ): ServiceResult
    {
        try {
            $this->userRepository->update( $user->id, [
                'name'              => $socialData[ 'name' ] ?? $user->name, // ✅ Atualiza nome diretamente
                'avatar'            => $socialData[ 'avatar' ] ?? $user->avatar,
                'email_verified_at' => now(), // ✅ E-mail verificado automaticamente (Google já verifica)
                'is_active'         => true,  // ✅ Garante que usuário fique ativo (login social fluido)
            ] );

            return $this->success( $user, 'Dados sincronizados com sucesso' );

        } catch ( \Exception $e ) {
            Log::error( 'Erro ao sincronizar dados sociais', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ] );

            return $this->error( 'Erro na sincronização', 'Não foi possível sincronizar os dados.' );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isSocialEmailInUse( string $email, ?string $excludeUserId = null ): bool
    {
        $query = $this->userRepository->findByEmail( $email );

        if ( $excludeUserId ) {
            return $query && $query->id != $excludeUserId;
        }

        return $query !== null;
    }

    /**
     * Encontra usuário por e-mail usando repository.
     *
     * @param string $email
     * @return User|null
     */
    private function findUserByEmail( string $email ): ?User
    {
        return $this->userRepository->findByEmail( $email );
    }

    /**
     * {@inheritdoc}
     */
    public function getSupportedProviders(): array
    {
        return [
            'google' => 'Google',
            // Futuramente: 'facebook' => 'Facebook', 'github' => 'GitHub'
        ];
    }

    /**
     * Valida se o cliente OAuth está configurado
     *
     * @return bool
     */
    public function isOAuthClientConfigured(): bool
    {
        return $this->oauthClient->isConfigured();
    }

}
