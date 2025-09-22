<?php

namespace app\database\services;

use app\database\entitiesORM\AddressEntity;
use app\database\entitiesORM\CommonDataEntity;
use app\database\entitiesORM\ContactEntity;
use app\database\entitiesORM\PlanEntity;
use app\database\entitiesORM\PlanSubscriptionEntity;
use app\database\entitiesORM\ProviderEntity;
use app\database\entitiesORM\TenantEntity;
use app\database\entitiesORM\UserConfirmationTokenEntity;
use app\database\entitiesORM\UserEntity;
use app\database\entitiesORM\UserRolesEntity;
use app\database\entitiesJoin\ProviderFullEntityJoin;
use app\database\models\Address;
use app\database\models\CommonData;
use app\database\models\Contact;
use app\database\models\Plan;
use app\database\models\PlanSubscription;
use app\database\models\Provider;
use app\database\models\Tenant;
use app\database\models\User;
use app\database\models\UserConfirmationToken;
use app\database\models\UserRoles;
use core\dbal\EntityNotFound;
use core\library\Session;
use Doctrine\DBAL\Connection;
use Exception;
use RuntimeException;

class UserRegistrationService
{
    /**
     * Summary of table
     * @var string
     */
    protected string $tableUsers    = 'users';
    private mixed    $authenticated;

    public function __construct(
        private readonly Connection $connection,
        private Tenant $tenant,
        private User $user,
        private Provider $provider,
        private UserRoles $userRoles,
        private Plan $plan,
        private PlanSubscription $planSubscription,
        private UserConfirmationToken $userConfirmationToken,
        private NotificationService $notificationService,
        private CommonData $commonData,
        private Contact $contact,
        private Address $address,
        private SharedService $sharedService,
    ) {
        if ( Session::has( 'auth' ) ) {
            $this->authenticated = Session::get( 'auth' );
        }

    }

    /**
     * Reenvia e-mail de confirmação.
     *
     * @param string $email E-mail do usuário.
     * @return array<string, mixed> Resultado da operação.
     */
    public function resendConfirmation( string $email ): array
    {
        try {
            /** @var array<string, mixed> $result */
            $result = $this->connection->transactional( fn() => $this->transactionalResendConfirmation( $email ) );
            return $result;
        } catch ( Exception $e ) {
            throw new RuntimeException( "Falha ao enviar e-mail de confirmação, tente novamente mais tarde ou entre em contato com suporte!", 0, $e );
        }
    }

    /**
     * Recuperação de senha esquecida.
     *
     * @param string $email E-mail do usuário.
     * @return array<string, mixed> Resultado da operação.
     */
    public function forgotPassword( string $email ): array
    {
        try {
            $newPassword = generateRandomPassword();
            /** @var array<string, mixed> $result */
            $result = $this->connection->transactional( fn() => $this->transactionalForgotPassword( $email, $newPassword ) );
            return $result;
        } catch ( Exception $e ) {
            throw new RuntimeException( "Falha ao recuperar a senha, tente novamente mais tarde ou entre em contato com suporte!", 0, $e );
        }
    }

    /**
     * Lógica transacional para reenvio de confirmação.
     *
     * @param string $email E-mail do usuário.
     * @return array<string, mixed> Resultado da operação.
     */
    private function transactionalResendConfirmation( string $email ): array
    {
        $provider = $this->provider->getProviderFullByEmail( $email );

        if ( $provider instanceof EntityNotFound ) {
            return [ 
                'status'  => 'error',
                'message' => 'Usuário inexistente!',
            ];
        }

        /** @var ProviderFullEntityJoin $provider */
        if ( $provider->is_active ) {
            return [ 
                'status'  => 'error',
                'message' => 'Esta conta já está ativa.',
                'data'    => [ 
                    'is_active' => $provider->is_active,
                ],
            ];
        }

        $userConfirmationToken = $this->userConfirmationToken->findBy( [ 'user_id' => $provider->user_id, 'tenant_id' => $provider->tenant_id ] );

        if ( !$userConfirmationToken instanceof EntityNotFound ) {
            /** @var UserConfirmationTokenEntity $userConfirmationToken */
            $result = $this->userConfirmationToken->delete( $userConfirmationToken->id, $provider->tenant_id );
            if ( $result[ 'status' ] === 'error' ) {
                return [ 
                    'status'  => 'error',
                    'message' => 'Erro ao remover o token.',
                ];
            }
        }

        $result = $this->sharedService->generateNewUserConfirmationToken( $provider->user_id, $provider->tenant_id );
        if ( $result[ 'status' ] === 'error' ) {
            return [ 
                'status'  => 'error',
                'message' => 'Erro ao criar o token de confirmação.',
            ];
        }

        $createdUserConfirmationToken = $result[ 'data' ];
        $token                        = $createdUserConfirmationToken[ 'token' ];

        $sent = $this->notificationService->sendResendConfirmation(
            $provider->email,
            $provider->first_name,
            $token,
        );

        if ( !$sent ) {
            return [ 
                'status'  => 'error',
                'message' => 'Falha ao enviar o e-mail de confirmação.',
            ];
        }

        return [ 
            'status'  => $result[ 'status' ] === 'success' ? 'success' : 'error',
            'message' => $result[ 'status' ] === 'success' ? 'E-mail de confirmação enviado com sucesso.' : 'Não foi possivel reenviar o e-mail de confirmação.',
            'data'    => [ 
                'createdUserConfirmationToken' => $createdUserConfirmationToken,
                'provider'                     => $provider,
                'token'                        => $token,
                'url'                          => env( 'APP_URL' ) . '/confirm-account?token=' . urlencode( $token ),
            ],
        ];
    }

    /**
     * Lógica transacional para recuperação de senha.
     *
     * @param string $email E-mail do usuário.
     * @param string $newPassword Nova senha.
     * @return array<string, mixed> Resultado da operação.
     */
    private function transactionalForgotPassword( string $email, string $newPassword ): array
    {
        $user = $this->user->getUserByEmailWithPassword( $email );

        if ( $user instanceof EntityNotFound ) {
            return [ 
                'status'  => 'error',
                'message' => 'Usuário não encontrado.',
            ];
        }

        /** @var UserEntity $user */
        $userFind       = $user->toArray();
        $hashedPassword = password_hash( $newPassword, PASSWORD_DEFAULT );
        /** @var UserEntity $user */
        $userFind[ 'password' ] = $hashedPassword;

        $properties = getConstructorProperties( UserEntity::class);
        $userEntity = UserEntity::create( removeUnnecessaryIndexes(
            $properties,
            [ 'created_at', 'updated_at' ],
            $userFind,

        ) );

        /** @var UserEntity $userEntity */
        $result = $this->user->update( $userEntity );

        if ( $result[ 'status' ] === 'error' ) {
            return [ 
                'status'  => 'error',
                'message' => 'Falha ao atualizar a senha.',
            ];
        }

        $provider = $this->provider->getProviderFullByEmail( $email );
        if ( $provider instanceof EntityNotFound ) {
            return [ 
                'status'  => 'error',
                'message' => 'Prestador de serviço não encontrado.',
            ];
        }
        /** @var ProviderFullEntityJoin $provider */
        $sent = $this->notificationService->sendPasswordReset(
            $email,
            $provider->first_name,
            $newPassword,
        );

        if ( !$sent ) {
            throw new RuntimeException( 'Falha ao enviar e-mail com a nova senha.' );
        }

        return [ 
            'status'  => $result[ 'status' ] === 'success' ? 'success' : 'error',
            'message' => $result[ 'status' ] === 'success' ? 'Senha atualizada com sucesso.' : 'Não foi possivel atualizar a senha.',
            'data'    => [ 
                'id'   => $provider->id,
                'user' => $userEntity,
            ],
        ];
    }

    /**
     * Registra um novo usuário como prestador de serviço.
     *
     * @param array<string, mixed> $data Dados do registro.
     * @return array<string, mixed> Resultado da operação.
     */
    public function registerWithProvider( array $data ): array
    {
        try {
            /** @var array<string, mixed> $result */
            $result = $this->connection->transactional( fn() => $this->transactionalRegisterWithProvider( $data ) );
            return $result;
        } catch ( Exception $e ) {
            throw new RuntimeException( "Falha ao registrar o usuário, tente novamente mais tarde ou entre em contato com suporte!", 0, $e );
        }
    }

    /**
     * Confirma a conta do usuário.
     *
     * @param string $token Token de confirmação.
     * @return array<string, mixed> Resultado da operação.
     */
    public function confirmAccount( string $token ): array
    {
        try {
            /** @var array<string, mixed> $result */
            $result = $this->connection->transactional( fn() => $this->transactionalConfirmAccount( $token ) );
            return $result;
        } catch ( Exception $e ) {
            throw new RuntimeException( "Falha ao confirmar a conta, tente novamente mais tarde ou entre em contato com suporte!", 0, $e );
        }
    }

    /**
     * Atualiza a senha do usuário.
     *
     * @param string $password Nova senha.
     * @return array<string, mixed> Resultado da operação.
     */
    public function updatePassword( string $password ): array
    {
        try {
            /** @var array<string, mixed> $result */
            $result = $this->connection->transactional( fn() => $this->transactionalUpdatePassword( $password ) );
            return $result;
        } catch ( Exception $e ) {
            throw new RuntimeException( "Falha ao recuperar a senha, tente novamente mais tarde ou entre em contato com suporte!", 0, $e );
        }
    }

    /**
     * Bloqueia a conta do usuário.
     *
     * @param string $token Token de confirmação.
     * @return array<string, mixed> Resultado da operação.
     */
    public function blockAccount( string $token ): array
    {
        try {
            /** @var array<string, mixed> $result */
            $result = $this->connection->transactional( fn() => $this->transactionalBlockAccount( $token ) );
            return $result;
        } catch ( Exception $e ) {
            throw new RuntimeException( "Falha ao bloquear a conta, tente novamente mais tarde ou entre em contato com suporte!", 0, $e );
        }
    }

    /**
     * Lógica transacional para registro de usuário.
     *
     * @param array<string, mixed> $data Dados do registro.
     * @return array<string, mixed> Resultado da operação.
     */
    private function transactionalRegisterWithProvider( array $data ): array
    {
        // Criar tenant
        $timestamp    = time();
        $randomString = substr( md5( $timestamp . uniqid() ), 0, 8 );
        $tenantEntity = TenantEntity::create( [ 'name' => $data[ 'first_name' ] . '_' . $timestamp . '_' . $randomString ] );
        $result       = $this->tenant->create( $tenantEntity );
        if ( $result[ 'status' ] === 'error' ) return [ 'status' => 'error', 'message' => 'Erro ao criar a conta.' ];
        $tenantId = $result[ 'data' ][ 'id' ];

        // Verificar email
        $checkObj = $this->user->getUserByEmail( $data[ 'email' ] );
        if ( !$checkObj instanceof EntityNotFound ) return [ 'status' => 'error', 'message' => 'Este e-mail já está registrado!' ];

        // Criar user
        $userEntity = UserEntity::create( [ 'tenant_id' => $tenantId, 'email' => $data[ 'email' ], 'is_active' => false, 'password' => password_hash( $data[ 'password' ], PASSWORD_DEFAULT ) ] );
        $result     = $this->user->create( $userEntity );
        if ( $result[ 'status' ] === 'error' ) return [ 'status' => 'error', 'message' => 'Erro ao criar a conta.' ];
        $userId = $result[ 'data' ][ 'id' ];

        // Criar common data
        $commonDataEntity = CommonDataEntity::create( [ 'tenant_id' => $tenantId, 'first_name' => $data[ 'first_name' ], 'last_name' => $data[ 'last_name' ] ] );
        $result           = $this->commonData->create( $commonDataEntity );
        if ( $result[ 'status' ] === 'error' ) return [ 'status' => 'error', 'message' => 'Erro ao criar a conta.' ];
        $commonDataId = $result[ 'data' ][ 'id' ];

        // Criar contact
        $contactEntity = ContactEntity::create( [ 'tenant_id' => $tenantId, 'email' => $data[ 'email' ], 'phone' => $data[ 'phone' ], 'phone_business' => $data[ 'phone' ] ] );
        $result        = $this->contact->create( $contactEntity );
        if ( $result[ 'status' ] === 'error' ) return [ 'status' => 'error', 'message' => 'Erro ao criar a conta.' ];
        $contactId = $result[ 'data' ][ 'id' ];

        // Criar address
        $addressEntity = AddressEntity::create( [ 'tenant_id' => $tenantId ] );
        $result        = $this->address->create( $addressEntity );
        if ( $result[ 'status' ] === 'error' ) return [ 'status' => 'error', 'message' => 'Erro ao criar a conta.' ];
        $addressId = $result[ 'data' ][ 'id' ];

        // Criar provider
        $providerEntity = ProviderEntity::create( [ 'tenant_id' => $tenantId, 'user_id' => $userId, 'common_data_id' => $commonDataId, 'contact_id' => $contactId, 'address_id' => $addressId, 'terms_accepted' => $data[ 'terms_accepted' ] ] );
        $result         = $this->provider->create( $providerEntity );
        if ( $result[ 'status' ] === 'error' ) return [ 'status' => 'error', 'message' => 'Erro ao criar a conta.' ];
        $providerId = $result[ 'data' ][ 'id' ];

        // Criar userRoles
        $userRolesEntity = UserRolesEntity::create( [ 'tenant_id' => $tenantId, 'user_id' => $userId, 'role_id' => 3 ] );
        $result          = $this->userRoles->create( $userRolesEntity );
        if ( $result[ 'status' ] === 'error' ) return [ 'status' => 'error', 'message' => 'Erro ao criar a conta.' ];

        // Criar planSubscription
        $plan = $this->plan->getActivePlanBySlug( $data[ 'plan' ] );
        if ( $plan instanceof EntityNotFound ) return [ 'status' => 'error', 'message' => 'Plano não encontrado.' ];
        /** @var PlanEntity $plan */
        $planSubscriptionEntity = PlanSubscriptionEntity::create( [ 'tenant_id' => $tenantId, 'provider_id' => $providerId, 'plan_id' => 1, 'status' => 'active', 'transaction_amount' => $plan->price, 'payment_method' => 'free', 'start_date' => ( new \DateTime() )->format( 'Y-m-d H:i:s' ) ] );
        $result                 = $this->planSubscription->create( $planSubscriptionEntity );
        if ( $result[ 'status' ] === 'error' ) return [ 'status' => 'error', 'message' => 'Erro ao criar o plano.' ];

        if ( $plan->slug != "free" ) {
            $planSubscriptionEntity = PlanSubscriptionEntity::create( [ 'tenant_id' => $tenantId, 'provider_id' => $providerId, 'plan_id' => $plan->id, 'status' => 'pending', 'transaction_amount' => $plan->price, 'start_date' => ( new \DateTime() )->format( 'Y-m-d H:i:s' ) ] );
            $result                 = $this->planSubscription->create( $planSubscriptionEntity );
            if ( $result[ 'status' ] === 'error' ) return [ 'status' => 'error', 'message' => 'Erro ao criar o plano.' ];
        }

        // Criar token
        [ $token, $expiresDate ]     = generateTokenExpirate( '+30 minutes' );
        $userConfirmationTokenEntity = UserConfirmationTokenEntity::create( [ 'tenant_id' => $tenantId, 'user_id' => $userId, 'token' => $token, 'expires_at' => $expiresDate ] );
        $result                      = $this->userConfirmationToken->create( $userConfirmationTokenEntity );
        if ( $result[ 'status' ] === 'error' ) return [ 'status' => 'error', 'message' => 'Erro ao criar o token de confirmação.' ];

        // Enviar email
        $sent = $this->notificationService->sendAccountConfirmation( $data[ 'email' ], $data[ 'first_name' ], $token );
        if ( !$sent ) return [ 'status' => 'error', 'message' => 'Falha ao enviar o e-mail de confirmação.' ];

        return [ 'status' => 'success', 'message' => 'Usuário registrado com sucesso!', 'data' => [ 'provider_id' => $providerId, 'tenant_id' => $tenantId, 'user_id' => $userId, 'provider' => $data ] ];
    }

    /**
     * Lógica transacional para confirmação de conta.
     *
     * @param string $token Token de confirmação.
     * @return array<string, mixed> Resultado da operação.
     */
    private function transactionalConfirmAccount( string $token ): array
    {
        $userConfirmationToken = $this->userConfirmationToken->getTokenInfo( $token );
        if ( $userConfirmationToken instanceof EntityNotFound ) return [ 'status' => 'error', 'message' => 'Token inexistente!' ];

        /** @var UserConfirmationTokenEntity $userConfirmationToken */
        $expiresAt = $userConfirmationToken->expires_at;
        $now       = new \DateTime();
        if ( $expiresAt < $now ) return [ 'status' => 'error', 'message' => 'expired' ];

        $userFind                 = $this->user->getUserById( $userConfirmationToken->user_id, $userConfirmationToken->tenant_id );
        $userArray                = $userFind->toArray();
        $userArray[ 'is_active' ] = true;
        $userEntity               = UserEntity::create( $userArray );
        $result                   = $this->user->update( $userEntity );
        if ( $result[ 'status' ] === 'error' ) return [ 'status' => 'error', 'message' => 'Erro ao ativar a conta.' ];

        $result = $this->userConfirmationToken->delete( $userConfirmationToken->id, $userConfirmationToken->tenant_id );
        if ( $result[ 'status' ] === 'error' ) return [ 'status' => 'error', 'message' => 'Erro ao remover o token.' ];

        return [ 'status' => 'success', 'message' => 'Conta ativada com sucesso.', 'data' => [ 'user' => $userEntity ] ];
    }

    /**
     * Lógica transacional para atualização de senha.
     *
     * @param string $password Nova senha.
     * @return array<string, mixed> Resultado da operação.
     */
    private function transactionalUpdatePassword( string $password ): array
    {
        $userArray               = $this->authenticated->toArray();
        $userArray[ 'password' ] = password_hash( $password, PASSWORD_DEFAULT );

        $properties = getConstructorProperties( UserEntity::class);
        $userEntity = UserEntity::create( removeUnnecessaryIndexes( $properties, [ 'created_at', 'updated_at' ], $userArray ) );
        $result     = $this->user->update( $userEntity );
        if ( $result[ 'status' ] === 'error' ) return [ 'status' => 'error', 'message' => 'Falha ao atualizar a senha.' ];

        [ $token, $expiresDate ]     = generateTokenExpirate();
        $userConfirmationTokenEntity = UserConfirmationTokenEntity::create( [ 'user_id' => $userArray[ 'user_id' ], 'tenant_id' => $userArray[ 'tenant_id' ], 'token' => $token, 'expires_at' => $expiresDate ] );
        $result                      = $this->userConfirmationToken->create( $userConfirmationTokenEntity );
        if ( $result[ 'status' ] === 'error' ) return [ 'status' => 'error', 'message' => 'Erro ao criar o token!' ];

        $sent = $this->notificationService->sendPasswordChanged( $userArray[ 'email' ], $userArray[ 'first_name' ], $token );
        if ( !$sent ) throw new RuntimeException( 'Falha ao enviar e-mail com a nova senha.' );

        return [ 'status' => 'success', 'message' => 'Senha atualizada com sucesso.', 'data' => [ 'id' => $userArray[ 'provider_id' ], 'user' => $userEntity ] ];
    }

    /**
     * Lógica transacional para bloqueio de conta.
     *
     * @param string $token Token de confirmação.
     * @return array<string, mixed> Resultado da operação.
     */
    private function transactionalBlockAccount( string $token ): array
    {
        $userConfirmationToken = $this->userConfirmationToken->getTokenInfo( $token );
        if ( $userConfirmationToken instanceof EntityNotFound ) return [ 'status' => 'error', 'message' => 'Token inexistente!' ];
        /** @var UserConfirmationTokenEntity $userConfirmationToken */
        $expiresAt = $userConfirmationToken->expires_at;
        $now       = new \DateTime();
        if ( $expiresAt < $now ) return [ 'status' => 'error', 'message' => 'expired' ];

        $userFind                 = $this->user->getUserById( $userConfirmationToken->user_id, $userConfirmationToken->tenant_id );
        $userArray                = $userFind->toArray();
        $userArray[ 'is_active' ] = false;
        $userEntity               = UserEntity::create( $userArray );
        $result                   = $this->user->update( $userEntity );
        if ( $result[ 'status' ] === 'error' ) return [ 'status' => 'error', 'message' => 'Erro ao bloquear a conta.' ];

        $this->connection->delete( 'user_confirmation_tokens', [ 'id' => $userConfirmationToken->id ] );

        return [ 'status' => 'success', 'message' => 'Conta bloqueada com sucesso', 'data' => [ 'id' => $userArray[ 'provider_id' ], 'user' => $userEntity ] ];
    }

}