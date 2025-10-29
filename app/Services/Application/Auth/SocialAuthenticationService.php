<?php

declare(strict_types=1);

namespace App\Services\Application\Auth;

use App\Contracts\Interfaces\Auth\OAuthClientInterface;
use App\Contracts\Interfaces\Auth\SocialAuthenticationInterface;
use App\Events\SocialAccountLinked;
use App\Events\SocialLoginWelcome;
use App\Models\Tenant;
use App\Models\User;
use App\Repositories\UserRepository;
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
    private OAuthClientInterface    $oauthClient;
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
            $existingUser = $this->findUserBySocialId( $provider, $userData[ 'id' ] );

            if ( $existingUser ) {
                return $this->handleExistingUser( $existingUser, $provider, $userData );
            }

            if ( !empty( $userData[ 'email' ] ) && $this->isSocialEmailInUse( $userData[ 'email' ] ) ) {
                return $this->handleEmailInUse( $userData, $provider );
            }

            return $this->createNewSocialUser( $provider, $userData );

        } catch ( \Exception $e ) {
            Log::error( 'Erro na autenticação social', [
                'provider'  => $provider,
                'error'     => $e->getMessage(),
                'user_data' => $userData,
            ] );

            return $this->error( 'Erro na autenticação', 'Ocorreu um erro durante a autenticação. Tente novamente.' );
        }
    }

    private function handleExistingUser( User $user, string $provider, array $userData ): ServiceResult
    {
        $updateResult = $this->syncSocialProfileData( $user, $userData );

        if ( !$updateResult->isSuccess() ) {
            return $updateResult;
        }

        Log::info( 'Usuário autenticado via provedor social', [
            'provider' => $provider,
            'user_id'  => $user->id,
            'email'    => $user->email,
        ] );

        return $this->success( $user, 'Usuário autenticado com sucesso via ' . ucfirst( $provider ) );
    }

    private function handleEmailInUse( array $userData, string $provider ): ServiceResult
    {
        $existingUser = $this->findUserByEmail( $userData[ 'email' ] );

        if ( !$existingUser ) {
            return $this->createNewSocialUser( $provider, $userData );
        }

        $linkResult = $this->linkSocialAccountToUser( $existingUser, $provider, $userData );

        if ( $linkResult->isSuccess() ) {
            Event::dispatch( new SocialAccountLinked( $existingUser, $provider, $userData ) );

            Log::info( 'Conta social vinculada a usuário existente', [
                'provider'  => $provider,
                'user_id'   => $existingUser->id,
                'email'     => $existingUser->email,
                'social_id' => $userData[ 'id' ],
            ] );

            return $this->success( $existingUser, 'Conta vinculada com sucesso via ' . ucfirst( $provider ) );
        }

        return $linkResult;
    }

    private function createNewSocialUser( string $provider, array $userData ): ServiceResult
    {
        $createResult = $this->createUserFromSocialData( $provider, $userData );

        if ( !$createResult->isSuccess() ) {
            return $createResult;
        }

        $user = $createResult->getData();

        Log::info( 'Novo usuário criado via provedor social', [
            'provider'     => $provider,
            'user_id'      => $user->id,
            'email'        => $user->email,
            'social_login' => true,
        ] );

        return $this->success( $user, 'Conta criada com sucesso via ' . ucfirst( $provider ) );
    }

    /**
     * {@inheritdoc}
     */
    public function createUserFromSocialData( string $provider, array $userData ): ServiceResult
    {
        try {
            $registrationData   = $this->prepareRegistrationData( $userData );
            $registrationResult = $this->userRegistrationService->registerUser( $registrationData, true );

            if ( !$registrationResult->isSuccess() ) {
                return $registrationResult;
            }

            $registrationData = $registrationResult->getData();
            $user             = $registrationData[ 'user' ];

            return $this->finalizeSocialUserCreation( $user, $provider, $userData );

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
     * Prepara dados de registro a partir dos dados sociais.
     */
    private function prepareRegistrationData( array $userData ): array
    {
        $nameParts = explode( ' ', $userData[ 'name' ] );
        $firstName = $nameParts[ 0 ] ?? $userData[ 'name' ];

        // Captura todas as palavras restantes como sobrenome
        $lastNameParts = array_slice( $nameParts, 1 );
        $lastName      = implode( ' ', $lastNameParts ) ?: 'Usuário';

        return [
            'first_name'     => $firstName,
            'last_name'      => $lastName,
            'name'           => $userData[ 'name' ],
            'email'          => $userData[ 'email' ],
            'password'       => null,
            'phone'          => '+5511999999999',
            'terms_accepted' => true,
        ];
    }

    /**
     * Finaliza a criação do usuário social.
     */
    private function finalizeSocialUserCreation( User $user, string $provider, array $userData ): ServiceResult
    {
        $user->update( [
            'name'              => $userData[ 'name' ],
            'google_id'         => $provider === 'google' ? $userData[ 'id' ] : null,
            'avatar'            => $userData[ 'avatar' ] ?? null,
            'google_data'       => $userData,
            'email_verified_at' => now(),
            'is_active'         => true,
        ] );

        Log::info( 'Usuário criado via autenticação social', [
            'provider'     => $provider,
            'user_id'      => $user->id,
            'email'        => $user->email,
            'google_id'    => $userData[ 'id' ],
            'social_login' => true,
        ] );

        $this->dispatchWelcomeEvent( $user, $provider );

        return $this->success( $user, 'Usuário criado com sucesso via ' . ucfirst( $provider ) );
    }

    /**
     * Dispara evento de boas-vindas para login social.
     */
    private function dispatchWelcomeEvent( User $user, string $provider ): void
    {
        $tenant = $user->tenant;
        Event::dispatch( new SocialLoginWelcome( $user, $tenant, $provider ) );
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
                'name'              => $socialData[ 'name' ] ?? $user->name,
                'avatar'            => $socialData[ 'avatar' ] ?? $user->avatar,
                'google_data'       => $socialData,
                'email_verified_at' => now(),
                'is_active'         => true,
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
     * Encontra usuário por e-mail.
     *
     * @param string $email
     * @return User|null
     */
    private function findUserByEmail( string $email ): ?User
    {
        return User::where( 'email', $email )->first();
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
