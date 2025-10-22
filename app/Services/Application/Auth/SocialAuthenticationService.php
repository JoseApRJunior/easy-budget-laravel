<?php

declare(strict_types=1);

namespace App\Services\Application\Auth;

use App\Contracts\Interfaces\Auth\OAuthClientInterface;
use App\Contracts\Interfaces\Auth\SocialAuthenticationInterface;
use App\Models\Tenant;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Services\Application\UserRegistrationService;
use App\Services\Core\Abstracts\AbstractBaseService;
use App\Support\ServiceResult;
use Illuminate\Support\Facades\DB;
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
    private OAuthClientInterface    $oauthClient;
    private UserRepository          $userRepository;
    private UserRegistrationService $userRegistrationService;

    public function __construct(
        OAuthClientInterface $oauthClient,
        UserRepository $userRepository,
        UserRegistrationService $userRegistrationService,
    ) {
        $this->oauthClient             = $oauthClient;
        $this->userRepository          = $userRepository;
        $this->userRegistrationService = $userRegistrationService;
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

            // Verifica se e-mail já está em uso por outro usuário
            if ( $this->isSocialEmailInUse( $userData[ 'email' ] ) ) {
                return $this->error( 'E-mail já cadastrado', 'Este e-mail já está sendo utilizado por outra conta.' );
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

            return $this->error( 'Erro na autenticação', 'Ocorreu um erro durante a autenticação. Tente novamente.' );
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
                'email'          => $userData[ 'email' ],
                'password'       => 'TempPass123!@#', // Senha temporária (será substituída)
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

            // Atualiza campos específicos do Google após criação
            $user->update( [
                'name'              => $userData[ 'name' ], // ✅ Define nome diretamente para evitar problemas com accessor
                'google_id'         => $provider === 'google' ? $userData[ 'id' ] : null,
                'avatar'            => $userData[ 'avatar' ] ?? null,
                'email_verified_at' => now(), // ✅ E-mail verificado automaticamente (Google já verifica)
                'is_active'         => true,  // ✅ Usuário ativo automaticamente (login social fluido)
            ] );

            Log::info( 'Usuário criado via autenticação social usando UserRegistrationService', [
                'provider'  => $provider,
                'user_id'   => $user->id,
                'email'     => $user->email,
                'google_id' => $userData[ 'id' ],
            ] );

            return $this->success( $user, 'Usuário criado com sucesso via ' . ucfirst( $provider ) );

        } catch ( \Exception $e ) {
            Log::error( 'Erro ao criar usuário a partir de dados sociais usando UserRegistrationService', [
                'provider'  => $provider,
                'error'     => $e->getMessage(),
                'user_data' => $userData,
            ] );

            return $this->error( 'Erro ao criar conta', 'Não foi possível criar a conta. Tente novamente.' );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function linkSocialAccountToUser( User $user, string $provider, array $userData ): ServiceResult
    {
        try {
            DB::beginTransaction();

            // Atualiza campos sociais do usuário
            $user->update( [
                'google_id'         => $provider === 'google' ? $userData[ 'id' ] : $user->google_id,
                'name'              => $userData[ 'name' ] ?? $user->name,
                'avatar'            => $userData[ 'avatar' ] ?? $user->avatar,
                'email_verified_at' => $userData[ 'verified' ] ? now() : $user->email_verified_at,
            ] );

            DB::commit();

            return $this->success( $user, 'Conta social vinculada com sucesso' );

        } catch ( \Exception $e ) {
            DB::rollBack();

            Log::error( 'Erro ao vincular conta social', [
                'provider' => $provider,
                'user_id'  => $user->id,
                'error'    => $e->getMessage(),
            ] );

            return $this->error( 'Erro ao vincular conta', 'Não foi possível vincular a conta social.' );
        }
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
            $user->update( [
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
        $query = User::where( 'email', $email );

        if ( $excludeUserId ) {
            $query->where( 'id', '!=', $excludeUserId );
        }

        return $query->exists();
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
