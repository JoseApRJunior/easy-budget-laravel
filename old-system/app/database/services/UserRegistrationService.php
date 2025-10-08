<?php

namespace app\database\services;

use app\database\entities\AddressEntity;
use app\database\entities\CommonDataEntity;
use app\database\entities\ContactEntity;
use app\database\entities\PlanEntity;
use app\database\entities\PlanSubscriptionEntity;
use app\database\entities\ProviderEntity;
use app\database\entities\TenantEntity;
use app\database\entities\UserConfirmationTokenEntity;
use app\database\entities\UserEntity;
use app\database\entities\UserRolesEntity;
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
use core\library\Twig;
use Doctrine\DBAL\Connection;
use Exception;
use RuntimeException;

class UserRegistrationService
{
    /**
     * Summary of table
     * @var string
     */
    protected string $tableUsers = 'users';
    private $authenticated;

    public function __construct(
        private readonly Connection $connection,
        private Tenant $tenant,
        private User $user,
        private Provider $provider,
        private UserRoles $userRoles,
        private Plan $plan,
        private PlanSubscription $planSubscription,
        private UserConfirmationToken $userConfirmationToken,
        private MailerService $mailer,
        private Twig $twig,
        private CommonData $commonData,
        private Contact $contact,
        private Address $address,
        private SharedService $sharedService,
    ) {
        if (Session::has('auth')) {
            $this->authenticated = Session::get('auth');
        }

    }

    public function registerWithProvider(array $data)
    {
        try {
            return $this->connection->transactional(function () use ($data) {
                // Sessão criar tenant
                // popula model Tenant
                // Gerar um nome de tenant único
                $timestamp = time();
                $randomString = substr(md5($timestamp . uniqid()), 0, 8);

                $tenantEntity = TenantEntity::create([
                    'name' => $data[ 'first_name' ] . '_' . $timestamp . '_' . $randomString,
                ]);

                // Criar Tenant e retorna o id
                $result = $this->tenant->create($tenantEntity);
                if ($result[ 'status' ] === 'error') {
                    return [
                        'status' => 'error',
                        'message' => 'Erro ao criar a conta.',
                    ];
                }
                $tenantId = $result[ 'data' ][ 'id' ];
                // Fim da sessão criar tenant

                // Sessão criar user
                // Verifica se o email do user já está cadastrado
                $checkObj = $this->user->getUserByEmail($data[ 'email' ]);
                if (!$checkObj instanceof EntityNotFound) {
                    return [
                        'status' => 'error',
                        'message' => 'Este e-mail já está registrado!',
                    ];
                }
                // popula model UserEntity
                $userEntity = UserEntity::create([
                    'tenant_id' => $tenantId,
                    'email' => $data[ 'email' ],
                    'is_active' => false,
                    'password' => password_hash($data[ 'password' ], PASSWORD_DEFAULT),
                ]);
                // Criar User e retorna o id
                $result = $this->user->create($userEntity);
                // verifica se o user foi criado com sucesso
                if ($result[ 'status' ] === 'error') {
                    return [
                        'status' => 'error',
                        'message' => 'Erro ao criar a conta.',
                    ];
                }
                $userId = $result[ 'data' ][ 'id' ];
                // Fim da sessão criar user

                // Sessão criar common data
                // popula model CommonDataEntity
                $commonDataEntity = CommonDataEntity::create([
                    'tenant_id' => $tenantId,
                    'first_name' => $data[ 'first_name' ],
                    'last_name' => $data[ 'last_name' ],
                ]);
                // Criar CommonData e retorna o id
                $result = $this->commonData->create($commonDataEntity);
                // verifica se o CommonData foi criado com sucesso
                if ($result[ 'status' ] === 'error') {
                    return [
                        'status' => 'error',
                        'message' => 'Erro ao criar a conta.',
                    ];
                }
                $commonDataId = $result[ 'data' ][ 'id' ];
                // Fim da sessão criar common data

                // Sessão criar contact
                // popula model ContactEntity
                $contactEntity = ContactEntity::create([
                    'tenant_id' => $tenantId,
                    'email' => $data[ 'email' ],
                    'phone' => $data[ 'phone' ],
                    'phone_business' => $data[ 'phone' ],
                ]);
                // Criar Contact e retorna o id
                $result = $this->contact->create($contactEntity);
                // verifica se o Contact foi criado com sucesso
                if ($result[ 'status' ] === 'error') {
                    return [
                        'status' => 'error',
                        'message' => 'Erro ao criar a conta.',
                    ];
                }
                $contactId = $result[ 'data' ][ 'id' ];
                // Fim da sessão criar contact

                // Sessão criar address
                // popula model AddressEntity
                $addressEntity = AddressEntity::create([
                    'tenant_id' => $tenantId,
                ]);
                // Criar Address e retorna o id
                $result = $this->address->create($addressEntity);
                // verifica se o Address foi criado com sucesso
                if ($result[ 'status' ] === 'error') {
                    return [
                        'status' => 'error',
                        'message' => 'Erro ao criar a conta.',
                    ];
                }
                $addressId = $result[ 'data' ][ 'id' ];
                // Fim da sessão criar address

                // Sessão criar provider
                // popula model ProviderEntity
                $providerEntity = ProviderEntity::create([
                    'tenant_id' => $tenantId,
                    'user_id' => $userId,
                    'common_data_id' => $commonDataId,
                    'contact_id' => $contactId,
                    'address_id' => $addressId,
                    'terms_accepted' => $data[ 'terms_accepted' ],
                ]);
                // Criar Provider e retorna o id do provider
                $result = $this->provider->create($providerEntity);
                // verifica se o provider foi criado com sucesso
                if ($result[ 'status' ] === 'error') {
                    return [
                        'status' => 'error',
                        'message' => 'Erro ao criar a conta.',
                    ];
                }
                $providerId = $result[ 'data' ][ 'id' ];
                // Fim da sessão criar provider

                // Sessão criar userRoles
                // popula model UserRolesEntity
                $userRolesEntity = UserRolesEntity::create([
                    'tenant_id' => $tenantId,
                    'user_id' => $userId,
                    'role_id' => 3, // ID da role 'provider' na tabela roles
                ]);
                //  Criar UserRoles e retorna o id do userRoles
                $result = $this->userRoles->create($userRolesEntity);
                // verifica se o userRoles foi criado com sucesso
                if ($result[ 'status' ] === 'error') {
                    return [
                        'status' => 'error',
                        'message' => 'Erro ao criar a conta.',
                    ];
                }
                // Fim da sessão criar userRoles

                // Sessão criar planSubscription
                // Busca ID do plano na tabela plans
                /** @var PlanEntity $plan */
                $plan = $this->plan->getActivePlanBySlug($data[ 'plans' ]);

                // popula model PlanSubscriptionEntity
                $planSubscriptionEntity = PlanSubscriptionEntity::create([
                    'tenant_id' => $tenantId,
                    'provider_id' => $providerId,
                    'plan_id' => 1,
                    'status' => 'active',
                    'transaction_amount' => $plan->price,
                    'payment_method' => 'free',
                    'start_date' => (new \DateTime())->format('Y-m-d H:i:s'),
                ]);
                // Criar PlanSubscription inicial free e retorna o id do planSubscription
                $result = $this->planSubscription->create($planSubscriptionEntity);

                // verifica se o planSubscription foi criado com sucesso
                if ($result[ 'status' ] === 'error') {
                    return [
                        'status' => 'error',
                        'message' => 'Erro ao criar o plano.',
                    ];
                }

                if ($plan->slug != "free") {
                    $planSubscriptionEntity = PlanSubscriptionEntity::create([
                        'tenant_id' => $tenantId,
                        'provider_id' => $providerId,
                        'plan_id' => $plan->id,
                        'status' => 'pending',
                        'transaction_amount' => $plan->price,
                        'start_date' => (new \DateTime())->format('Y-m-d H:i:s'),
                    ]);
                    // Criar PlanSubscription e retorna o id do planSubscription
                    $result = $this->planSubscription->create($planSubscriptionEntity);

                    // verifica se o planSubscription foi criado com sucesso
                    if ($result[ 'status' ] === 'error') {
                        return [
                            'status' => 'error',
                            'message' => 'Erro ao criar o plano.',
                        ];
                    }
                }
                // Fim da sessão criar planSubscription

                // Sessão criar userConfirmationToken
                // Gera um token para confirmação de conta
                [ $token, $expiresDate ] = generateTokenExpirate('+30 minutes');
                // popula model UserConfirmationTokenEntity
                $userConfirmationTokenEntity = UserConfirmationTokenEntity::create([
                    'tenant_id' => $tenantId,
                    'user_id' => $userId,
                    'token' => $token,
                    'expires_at' => $expiresDate,
                ]);
                // Criar UserConfirmationTokens e retorna o id do userConfirmationToken
                $result = $this->userConfirmationToken->create($userConfirmationTokenEntity);
                // verifica se o userConfirmationToken foi criado com sucesso
                if ($result[ 'status' ] === 'error') {
                    return [
                        'status' => 'error',
                        'message' => 'Erro ao criar o token de confirmação.',
                    ];
                }
                // Fim da sessão criar userConfirmationToken

                // Sessão enviar e-mail de confirmação
                // 1. Gera o link de confirmação
                $confirmationLink = env('APP_URL') . '/confirm-account?token=' . $token;
                // 2. Prepara os dados do e-mail
                $subject = "Sua conta foi criada com sucesso! Confirme seu e-mail, para ativar sua conta Easy Budget.";

                // 3. Renderiza o corpo do e-mail com Twig
                $body = $this->twig->env->render('emails/new-user.twig', [
                    'first_name' => $data[ 'first_name' ],
                    'confirmationLink' => $confirmationLink,
                ]);

                $sent = $this->mailer->send(
                    $data[ 'email' ],
                    $subject,
                    $body,
                );

                if (!$sent) {
                    return [
                        'status' => 'error',
                        'message' => 'Falha ao enviar o e-mail de confirmação.',
                    ];
                }
                // Fim da sessão enviar e-mail de confirmação

                return [
                    'status' => $result[ 'status' ] === 'success' ? 'success' : 'error',
                    'message' => $result[ 'status' ] === 'success' ? 'Usuário registrado com sucesso!' : 'Não foi possivel registrar o usuário.',
                    'data' => [
                        'provider_id' => $providerId,
                        'tenant_id' => $tenantId,
                        'user_id' => $userId,
                        'provider' => $data,
                    ],
                ];
            });

        } catch (Exception $e) {
            throw new RuntimeException("Falha ao registrar o usuário, tente novamente mais tarde ou entre em contato com suporte!", 0, $e);
        }
    }

    public function confirmAccount(string $token)
    {
        try {
            return $this->connection->transactional(function () use ($token) {

                // Sessão UserConfirmationToken
                $userConfirmationToken = $this->userConfirmationToken->getTokenInfo($token);

                if ($userConfirmationToken instanceof EntityNotFound) {
                    return [
                        'status' => 'error',
                        'message' => 'Token inexistente!',
                    ];
                }

                // Verificar se o token expirou
                /** @var UserConfirmationTokenEntity $userConfirmationToken */
                $expiresAt = $userConfirmationToken->expires_at;
                $now = new \DateTime();
                if ($expiresAt < $now) {
                    return [
                        'status' => 'error',
                        'message' => 'expired',
                    ];
                }
                // Fim da sessão UserConfirmationToken

                // Sessão User
                // Busca user associado ao token
                $userFind = $this->user->getUserById($userConfirmationToken->user_id, $userConfirmationToken->tenant_id);
                $userArray = $userFind->toArray();
                $userArray[ 'is_active' ] = true;
                // popula model UserEntity
                $userEntity = UserEntity::create($userArray);
                // Atualizar user
                $result = $this->user->update($userEntity);

                if ($result[ 'status' ] === 'error') {
                    return [
                        'status' => 'error',
                        'message' => 'Erro ao ativar a conta.',
                    ];
                }
                // Fim da sessão User

                // Sessão UserConfirmationToken
                // Remover o token usado
                $result = $this->userConfirmationToken->delete($userConfirmationToken->id, $userConfirmationToken->tenant_id);

                if ($result[ 'status' ] === 'error') {
                    return [
                        'status' => 'error',
                        'message' => 'Erro ao remover o token.',
                    ];
                }
                // Fim da sessão UserConfirmationToken

                return [
                    'status' => $result[ 'status' ] === 'success' ? 'success' : 'error',
                    'message' => $result[ 'status' ] === 'success' ? 'Conta ativada com sucesso.' : 'Conta não ativado.',
                    'data' => [
                        'user' => $userEntity,
                    ],
                ];
            });
        } catch (Exception $e) {
            throw new RuntimeException("Falha ao confirmar a conta, tente novamente mais tarde ou entre em contato com suporte!", 0, $e);
        }
    }

    public function resendConfirmation(string $email)
    {
        try {
            return $this->connection->transactional(function () use ($email) {

                // Sessão UserConfirmationToken
                // Verificar se o usuário existe
                $provider = $this->provider->getProviderFullByEmail($email);

                if ($provider instanceof EntityNotFound) {
                    return [
                        'status' => 'error',
                        'message' => 'Usuário inexistente!',
                    ];
                }

                if ($provider->is_active) {
                    return [
                        'status' => 'error',
                        'message' => 'Esta conta já está ativa.',
                        'data' => [
                            'is_active' => $provider->is_active,
                        ],
                    ];
                }
                $userConfirmationToken = $this->userConfirmationToken->findBy([ 'user_id' => $provider->user_id, 'tenant_id' => $provider->tenant_id ]);

                if (!$userConfirmationToken instanceof EntityNotFound) {

                    // Remover tokens antigos
                    /** @var UserConfirmationTokenEntity $userConfirmationToken */
                    $result = $this->userConfirmationToken->delete($userConfirmationToken->id, $provider->tenant_id);
                    if ($result[ 'status' ] === 'error') {
                        return [
                            'status' => 'error',
                            'message' => 'Erro ao remover o token.',
                        ];
                    }
                }
                // Criar UserConfirmationTokens e retorna o id do userConfirmationToken
                $result = $this->sharedService->generateNewUserConfirmationToken($provider->user_id, $provider->tenant_id);
                // verifica se o userConfirmationToken foi criado com sucesso
                if ($result[ 'status' ] === 'error') {
                    return [
                        'status' => 'error',
                        'message' => 'Erro ao criar o token de confirmação.',
                    ];
                }
                $createdUserConfirmationToken = $result[ 'data' ];
                $token = $createdUserConfirmationToken[ 'token' ];
                // Fim da sessão UserConfirmationToken

                // Sessão Email

                // 1. Gera o link de confirmação
                $confirmationLink = env('APP_URL') . '/confirm-account?token=' . $token;
                // 2. Prepara os dados do e-mail
                $subject = 'Novo link de confirmação. Confirme seu e-mail, para ativar sua conta Easy Budget.';

                // 3. Renderiza o corpo do e-mail com Twig
                $body = $this->twig->env->render('emails/new-user.twig', [
                    'first_name' => $provider->first_name,
                    'confirmationLink' => $confirmationLink,
                ]);

                $sent = $this->mailer->send(
                    $provider->email,
                    $subject,
                    $body,
                );

                if (!$sent) {
                    return [
                        'status' => 'error',
                        'message' => 'Falha ao enviar o e-mail de confirmação.',
                    ];
                }
                // Fim da sessão Email

                return [
                    'status' => $result[ 'status' ] === 'success' ? 'success' : 'error',
                    'message' => $result[ 'status' ] === 'success' ? 'E-mail de confirmação enviado com sucesso.' : 'Não foi possivel reenviar o e-mail de confirmação.',
                    'data' => [
                        'createdUserConfirmationToken' => $createdUserConfirmationToken,
                        'provider' => $provider,
                        'token' => $token,
                        'url' => env('APP_URL') . '/confirm-account?token=' . urlencode($token),
                    ],
                ];
            });
        } catch (Exception $e) {
            throw new RuntimeException("Falha ao enviar e-mail de confirmação, tente novamente mais tarde ou entre em contato com suporte!", 0, $e);
        }
    }

    public function forgotPassword(string $email)
    {
        try {
            // Gera uma nova senha
            $newPassword = generateRandomPassword();

            // Atualiza a senha no banco de dados
            return $this->connection->transactional(
                function () use ($email, $newPassword) {

                    // Verifica se o usuário existe
                    $providerFind = $this->provider->getProviderFullByEmail($email);

                    // Se o usuário não existe, retorna uma mensagem de erro
                    if ($providerFind instanceof EntityNotFound) {
                        return [
                            'status' => 'error',
                            'message' => 'Usuário não encontrado.',
                        ];
                    }
                    // Hash da nova senha
                    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                    $providerFind->password = $hashedPassword;

                    // popula model UserEntity
                    $properties = getConstructorProperties(UserEntity::class);
                    $userEntity = UserEntity::create(removeUnnecessaryIndexes(
                        $properties,
                        [ 'created_at', 'updated_at' ],
                        (array) $providerFind,
                    ));

                    // Atualiza a senha
                    /** @var UserEntity $userEntity */
                    $result = $this->user->update($userEntity);

                    if ($result[ 'status' ] === 'error') {
                        return [
                            'status' => 'error',
                            'message' => 'Falha ao atualizar a senha.',
                        ];
                    }

                    $sent = $this->phpMailer
                        ->from(env('EMAIL_FROM'), env('EMAIL_FROM_NAME'))
                        ->to([ $email ])
                        ->message($newPassword)
                        ->template('forgot-password', [ 'date' => date("Y"), 'first_name' => $providerFind->first_name, 'url' => env('APP_URL') . '/login' ])
                        ->subject('Sua nova senha - Easy Budget')
                        ->send();

                    if (!$sent) {
                        throw new RuntimeException('Falha ao enviar e-mail com a nova senha.');
                    }

                    return [
                        'status' => $result[ 'status' ] === 'success' ? 'success' : 'error',
                        'message' => $result[ 'status' ] === 'success' ? 'Senha atualizada com sucesso.' : 'Não foi possivel atualizar a senha.',
                        'data' => [
                            'id' => $providerFind->provider_id,
                            'user' => $userEntity,
                        ],
                    ];
                }
            );
        } catch (Exception $e) {
            throw new RuntimeException("Falha ao recuperar a senha, tente novamente mais tarde ou entre em contato com suporte!", 0, $e);
        }
    }

    public function updatePassword(string $password)
    {
        try {

            // Atualiza a senha no banco de dados
            return $this->connection->transactional(
                function () use ($password) {

                    $userArray = $this->authenticated->toArray();
                    $userArray[ 'password' ] = password_hash($password, PASSWORD_DEFAULT);

                    // popula model UserEntity
                    // popula model UserEntity
                    $properties = getConstructorProperties(UserEntity::class);
                    $userEntity = UserEntity::create(removeUnnecessaryIndexes(
                        $properties,
                        [ 'created_at', 'updated_at' ],
                        $userArray,
                    ));
                    // Atualiza a senha
                    /** @var UserEntity $userEntity */
                    $result = $this->user->update($userEntity);

                    if ($result[ 'status' ] === 'error') {
                        return [
                            'status' => 'error',
                            'message' => 'Falha ao atualizar a senha.',
                        ];
                    }

                    // Gerar novo token
                    [ $token, $expiresDate ] = generateTokenExpirate();

                    // popula model UserConfirmationTokenEntity
                    $userConfirmationTokenEntity = UserConfirmationTokenEntity::create([
                        'user_id' => $userArray[ 'user_id' ],
                        'tenant_id' => $userArray[ 'tenant_id' ],
                        'token' => $token,
                        'expires_at' => $expiresDate,
                    ]);

                    // Salvar novo token
                    $result = $this->userConfirmationToken->create($userConfirmationTokenEntity);

                    if ($result[ 'status' ] === 'error') {
                        return [
                            'status' => 'error',
                            'message' => 'Erro ao criar o token!',
                        ];
                    }

                    // Construir o link de confirmação
                    $blockLink = env('APP_URL') . '/block-account?token=' . urlencode($token);

                    // Enviar novo e-mail de confirmação
                    $sent = $this->phpMailer
                        ->from(env('EMAIL_FROM'), env('EMAIL_FROM_NAME'))
                        ->to([ $userArray[ 'email' ] ])
                        ->message($password)
                        ->template(
                            'new-password',
                            [
                                'date' => date("Y"),
                                'first_name' => $userArray[ 'first_name' ],
                                'blockLink' => $blockLink,
                                'url' => env('APP_URL') . '/login',
                            ],
                        )
                        ->subject('Sua nova senha - Easy Budget')
                        ->send();

                    if (!$sent) {
                        throw new RuntimeException('Falha ao enviar e-mail com a nova senha.');
                    }

                    return [
                        'status' => $result[ 'status' ] === 'success' ? 'success' : 'error',
                        'message' => $result[ 'status' ] === 'success' ? 'Senha atualizada com sucesso.' : 'Não foi possivel atualizar a senha.',
                        'data' => [
                            'id' => $userArray[ 'provider_id' ],
                            'user' => $userEntity,
                        ],
                    ];
                }
            );
        } catch (Exception $e) {
            throw new RuntimeException("Falha ao recuperar a senha, tente novamente mais tarde ou entre em contato com suporte!", 0, $e);
        }
    }

    public function blockAccount(string $token)
    {
        try {
            return $this->connection->transactional(function () use ($token) {
                // Sessão UserConfirmationToken
                $userConfirmationToken = $this->userConfirmationToken->getTokenInfo($token);

                if ($userConfirmationToken instanceof EntityNotFound) {
                    return [
                        'status' => 'error',
                        'message' => 'Token inexistente!',
                    ];
                }

                // Verificar se o token expirou
                /** @var UserConfirmationTokenEntity $userConfirmationToken */
                $expiresAt = $userConfirmationToken->expires_at;
                $now = new \DateTime();
                if ($expiresAt < $now) {
                    return [
                        'status' => 'error',
                        'message' => 'expired',
                    ];
                }

                // Busca user associado ao token
                $userFind = $this->user->getUserById($userConfirmationToken->user_id, $userConfirmationToken->tenant_id);
                // converte para array
                $userArray = $userFind->toArray();
                // Bloqueia a conta do usuário
                $userArray[ 'is_active' ] = false;
                // popula model UserEntity
                /** @var UserEntity $userEntity */
                $userEntity = UserEntity::create($userArray);
                // Atualizar user
                $result = $this->user->update($userEntity);

                if ($result[ 'status' ] === 'error') {
                    return [
                        'status' => 'error',
                        'message' => 'Erro ao bloquear a conta.',
                    ];
                }

                // Remover o token usado
                $this->connection->delete('user_confirmation_tokens', [ 'id' => $userConfirmationToken->id ]);

                return [
                    'status' => $result[ 'status' ] === 'success' ? 'success' : 'error',
                    'message' => $result[ 'status' ] === 'success' ? 'Conta bloqueada com sucesso' : 'Não foi possivel bloquear a conta.',
                    'data' => [
                        'id' => $userArray[ 'provider_id' ],
                        'user' => $userEntity,
                    ],
                ];
            });
        } catch (Exception $e) {
            throw new RuntimeException("Falha ao bloquear a conta, tente novamente mais tarde ou entre em contato com suporte!", 0, $e);
        }
    }

}
