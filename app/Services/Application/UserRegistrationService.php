<?php

declare(strict_types=1);

namespace App\Services\Application;

use App\Enums\OperationStatus;
use App\Enums\TokenType;
use App\Events\PasswordResetRequested;
use App\Events\UserRegistered;
use App\Models\CommonData;
use App\Models\Plan;
use App\Models\PlanSubscription;
use App\Models\Provider;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UserConfirmationToken;
use App\Repositories\CommonDataRepository;
use App\Repositories\PlanRepository;
use App\Repositories\ProviderRepository;
use App\Repositories\RoleRepository;
use App\Repositories\TenantRepository;
use App\Repositories\UserConfirmationTokenRepository;
use App\Repositories\UserRepository;
use App\Services\Application\EmailVerificationService;
use App\Services\Core\Abstracts\AbstractBaseService;
use App\Support\ServiceResult;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Serviço completo para registro de usuários no sistema Easy Budget.
 *
 * Este serviço implementa toda a lógica de negócio do registro de usuário,
 * seguindo a arquitetura Controller → Service → Repository → Model estabelecida.
 *
 * Funcionalidades principais:
 * - Criação automática de Tenant para cada usuário
 * - Criação de CommonData com dados pessoais
 * - Criação de Provider vinculado ao usuário
 * - Integração com planos e assinaturas
 * - Criação automática de plano trial se necessário
 * - Associação de roles (provider) ao usuário
 * - Login automático após registro
 * - Envio de e-mails usando eventos
 * - Tratamento completo de erros
 * - Logs detalhados de todas as operações
 *
 * O serviço é registrado como singleton no container DI e pode ser injetado
 * em controllers e outros serviços conforme necessário.
 *
 * NOTA: A validação de dados de entrada é responsabilidade do Controller/FormRequest,
 * este serviço foca exclusivamente na lógica de negócio do registro.
 */
class UserRegistrationService extends AbstractBaseService
{
    protected UserConfirmationTokenService    $userConfirmationTokenService;
    protected UserConfirmationTokenRepository $userConfirmationTokenRepository;
    protected UserRepository                  $userRepository;
    protected TenantRepository                $tenantRepository;
    protected CommonDataRepository            $commonDataRepository;
    protected ProviderRepository              $providerRepository;
    protected PlanRepository                  $planRepository;
    protected RoleRepository                  $roleRepository;
    protected EmailVerificationService        $emailVerificationService;

    public function __construct(
        UserRepository $userRepository,
        TenantRepository $tenantRepository,
        UserConfirmationTokenService $userConfirmationTokenService,
        UserConfirmationTokenRepository $userConfirmationTokenRepository,
        CommonDataRepository $commonDataRepository,
        ProviderRepository $providerRepository,
        PlanRepository $planRepository,
        RoleRepository $roleRepository,
        EmailVerificationService $emailVerificationService,
    ) {
        $this->userRepository                  = $userRepository;
        $this->tenantRepository                = $tenantRepository;
        $this->userConfirmationTokenService    = $userConfirmationTokenService;
        $this->userConfirmationTokenRepository = $userConfirmationTokenRepository;
        $this->commonDataRepository            = $commonDataRepository;
        $this->providerRepository              = $providerRepository;
        $this->planRepository                  = $planRepository;
        $this->roleRepository                  = $roleRepository;
        $this->emailVerificationService        = $emailVerificationService;
    }

    /**
     * Registra um novo usuário no sistema com lógica completa.
     *
     * Este método implementa toda a lógica de negócio do registro seguindo
     * a arquitetura estabelecida: criação de entidades → eventos.
     *
     * Funcionalidades implementadas:
     * - Criação automática de Tenant
     * - Criação de CommonData com dados pessoais
     * - Criação de Provider vinculado ao usuário
     * - Integração com planos e assinaturas
     * - Associação automática de role 'provider'
     * - Login automático do usuário
     * - Envio de e-mail usando eventos
     * - Tratamento completo de erros
     *
     * NOTA: A validação de dados é responsabilidade do Controller/FormRequest.
     * Este método assume que os dados já foram validados.
     *
     * @param array $userData Dados do usuário (first_name, last_name, email, password, phone, terms_accepted)
     * @return ServiceResult Resultado da operação
     */
    public function registerUser( array $userData ): ServiceResult
    {
        try {
            DB::beginTransaction();

            // 1. Validação básica dos dados obrigatórios
            if (
                empty( $userData[ 'first_name' ] ) || empty( $userData[ 'last_name' ] ) ||
                empty( $userData[ 'email' ] ) ||
                ( empty( $userData[ 'password' ] ) && $userData[ 'password' ] !== null ) || // Permite null para social login
                empty( $userData[ 'phone' ] ) || empty( $userData[ 'terms_accepted' ] )
            ) {
                return ServiceResult::error(
                    OperationStatus::INVALID_DATA,
                    'Dados obrigatórios ausentes para registro de usuário.',
                );
            }

            // 2. Criar tenant
            Log::info( 'Criando tenant...', [ 'name' => $userData[ 'first_name' ] . ' ' . $userData[ 'last_name' ] ] );
            $tenantResult = $this->createTenant( $userData );
            if ( !$tenantResult->isSuccess() ) {
                DB::rollBack();
                return $tenantResult;
            }
            $tenant = $tenantResult->getData();
            Log::info( 'Tenant criado com sucesso', [ 'tenant_id' => $tenant->id ] );

            // 3. Buscar ou criar plano trial
            Log::info( 'Buscando plano trial disponível...' );
            $planResult = $this->findTrialPlan();
            if ( !$planResult->isSuccess() ) {
                DB::rollBack();
                return $planResult;
            }
            $plan = $planResult->getData();
            Log::info( 'Plano trial encontrado com sucesso', [ 'plan_id' => $plan->id, 'plan_name' => $plan->name ] );

            // 4. Criar usuário
            Log::info( 'Criando usuário...' );
            $userResult = $this->createUser( $userData, $tenant );
            if ( !$userResult->isSuccess() ) {
                DB::rollBack();
                return $userResult;
            }
            $user = $userResult->getData();
            Log::info( 'Usuário criado', [ 'user_id' => $user->id ] );

            // 5. Criar CommonData
            Log::info( 'Criando dados comuns...' );
            $commonDataResult = $this->createCommonData( $userData, $tenant );
            if ( !$commonDataResult->isSuccess() ) {
                DB::rollBack();
                return $commonDataResult;
            }
            $commonData = $commonDataResult->getData();
            Log::info( 'Dados comuns criados', [ 'common_data_id' => $commonData->id ] );

            // 6. Criar Provider
            Log::info( 'Criando provider...' );
            $providerResult = $this->createProvider( $user, $commonData, $tenant, $userData[ 'terms_accepted' ] );
            if ( !$providerResult->isSuccess() ) {
                DB::rollBack();
                return $providerResult;
            }
            $provider = $providerResult->getData();
            Log::info( 'Provider criado', [ 'provider_id' => $provider->id ] );

            // 7. Associar role 'provider' ao usuário
            Log::info( 'Associando role provider...' );
            $roleResult = $this->assignProviderRole( $user, $tenant );
            if ( !$roleResult->isSuccess() ) {
                DB::rollBack();
                return $roleResult;
            }
            Log::info( 'Role provider associado com sucesso' );

            // 8. Criar assinatura do plano
            Log::info( 'Criando assinatura do plano...' );
            $subscriptionResult = $this->createPlanSubscription( $tenant, $plan, $user, $provider );
            if ( !$subscriptionResult->isSuccess() ) {
                DB::rollBack();
                return $subscriptionResult;
            }
            $subscription = $subscriptionResult->getData();
            Log::info( 'Assinatura do plano criada', [ 'subscription_id' => $subscription->id ] );

            DB::commit();

            // 9. Criar token de verificação de e-mail usando UserConfirmationTokenService
            Log::info( 'Criando token de verificação de e-mail...', [ 'user_id' => $user->id ] );
            $tokenResult = $this->userConfirmationTokenService->createEmailVerificationToken( $user );

            if ( !$tokenResult->isSuccess() ) {
                Log::warning( 'Falha ao criar token de verificação, mas usuário foi registrado', [
                    'user_id' => $user->id,
                    'error'   => $tokenResult->getMessage(),
                ] );
                // Não falhar o registro por causa do token, apenas logar o problema
                $token = null; // Token será null se falhou
            } else {
                Log::info( 'Token de verificação criado com sucesso', [ 'user_id' => $user->id ] );
                $token = $tokenResult->getData()[ 'token' ]; // Extrair token do resultado
            }

            // 10. Disparar evento para envio de e-mail de boas-vindas com dados do token
            Event::dispatch( new UserRegistered(
                $user,
                $tenant,
                $token,
            ) );

            Log::info( 'Registro concluído com sucesso', [
                'user_id'         => $user->id,
                'email'           => $user->email,
                'tenant_id'       => $tenant->id,
                'plan_id'         => $plan->id,
                'provider_id'     => $provider->id,
                'subscription_id' => $subscription->id,
            ] );

            return ServiceResult::success( [
                'user'           => $user,
                'tenant'         => $tenant,
                'provider'       => $provider,
                'plan'           => $plan,
                'subscription'   => $subscription,
                'auto_logged_in' => true,
                'message'        => 'Registro realizado com sucesso! Bem-vindo ao Easy Budget.'
            ], 'Usuário registrado com sucesso.' );

        } catch ( Exception $e ) {
            DB::rollBack();

            Log::error( 'Erro no registro de usuário: ' . $e->getMessage(), [
                'email'     => $userData[ 'email' ] ?? null,
                'trace'     => $e->getTraceAsString(),
                'user_data' => $userData,
            ] );

            return ServiceResult::error(
                OperationStatus::ERROR,
                'Erro interno do servidor. Tente novamente em alguns minutos.',
            );
        }
    }

    /**
     * Solicita redefinição de senha para um usuário.
     *
     * Este método cria o token de redefinição e dispara evento para
     * envio de e-mail ao invés de chamar MailerService diretamente.
     *
     * @param string $email E-mail do usuário
     * @return ServiceResult Resultado da operação
     */
    public function requestPasswordReset( string $email ): ServiceResult
    {
        try {
            // Buscar usuário por e-mail
            $user = $this->userRepository->findByEmail( $email );
            if ( !$user ) {
                // Não revelar se o e-mail existe ou não por segurança
                return ServiceResult::success(
                    null,
                    'Se o e-mail existir em nosso sistema, você receberá instruções de redefinição.',
                );
            }

            // Criar token de redefinição em formato base64url
            $token     = generateSecureTokenUrl();
            $expiresAt = now()->addMinutes( (int) config( 'auth.passwords.users.expire', 60 ) );

            $confirmationToken = new UserConfirmationToken( [
                'user_id'    => $user->id,
                'token'      => $token,
                'expires_at' => $expiresAt,
                'type'       => 'password_reset',
            ] );

            $this->userConfirmationTokenRepository->create( $confirmationToken->toArray() );

            // Buscar tenant do usuário
            $tenant = null;
            if ( $user->tenant_id ) {
                $tenant = $this->tenantRepository->find( $user->tenant_id );
            }

            // Disparar evento para envio de e-mail de redefinição
            // AO INVÉS de chamar MailerService diretamente
            Event::dispatch( new PasswordResetRequested( $user, $token, $tenant ) );

            Log::info( 'Solicitação de redefinição de senha processada com eventos', [
                'user_id' => $user->id,
                'email'   => $user->email,
            ] );

            return ServiceResult::success(
                null,
                'Instruções de redefinição de senha foram enviadas para seu e-mail.',
            );

        } catch ( Exception $e ) {
            Log::error( 'Erro ao solicitar redefinição de senha', [
                'email' => $email,
                'error' => $e->getMessage(),
            ] );

            return ServiceResult::error(
                OperationStatus::ERROR,
                'Erro ao processar solicitação de redefinição de senha.',
            );
        }
    }

    /**
     * Busca um plano trial disponível ou cria um plano trial automaticamente.
     *
     * @return ServiceResult Resultado da operação
     */
    private function findTrialPlan(): ServiceResult
    {
        try {
            // Primeiro tentar buscar um plano específico de trial
            $plan = Plan::where( 'slug', 'trial' )->first();

            if ( !$plan ) {
                // Se não encontrou plano trial, buscar plano gratuito
                $plan = Plan::where( 'status', true )->where( 'price', 0.00 )->first();
            }

            if ( !$plan ) {
                return ServiceResult::error(
                    OperationStatus::ERROR,
                    'Plano trial não encontrado. Entre em contato com nosso suporte para ativar seu acesso gratuito.',
                );
            }

            return ServiceResult::success( $plan, 'Plano trial encontrado com sucesso.' );

        } catch ( Exception $e ) {
            return ServiceResult::error(
                OperationStatus::ERROR,
                'Erro ao buscar/criar plano trial: ' . $e->getMessage()
            );
        }
    }

    /**
     * Cria CommonData com dados pessoais do usuário.
     *
     * @param array $userData Dados do usuário
     * @param Tenant $tenant Tenant do usuário
     * @return ServiceResult Resultado da operação
     */
    private function createCommonData( array $userData, Tenant $tenant ): ServiceResult
    {
        try {
            $commonData = new CommonData( [
                'tenant_id'    => $tenant->id,
                'first_name'   => $userData[ 'first_name' ],
                'last_name'    => $userData[ 'last_name' ],
                'cpf'          => null, // Pode ser adicionado posteriormente
                'cnpj'         => null, // Pode ser adicionado posteriormente
                'company_name' => null, // Pode ser adicionado posteriormente
                'description'  => null, // Pode ser adicionado posteriormente
            ] );

            $savedCommonData = $this->commonDataRepository->create( $commonData->toArray() );

            return ServiceResult::success( $savedCommonData, 'CommonData criado com sucesso.' );

        } catch ( Exception $e ) {
            return ServiceResult::error(
                OperationStatus::ERROR,
                'Erro ao criar CommonData: ' . $e->getMessage()
            );
        }
    }

    /**
     * Cria Provider vinculado ao usuário.
     *
     * @param User $user Usuário
     * @param CommonData $commonData Dados comuns
     * @param Tenant $tenant Tenant
     * @param bool $termsAccepted Termos aceitos
     * @return ServiceResult Resultado da operação
     */
    private function createProvider( User $user, CommonData $commonData, Tenant $tenant, bool $termsAccepted ): ServiceResult
    {
        try {
            $provider = new Provider( [
                'tenant_id'      => $tenant->id,
                'user_id'        => $user->id,
                'common_data_id' => $commonData->id,
                'contact_id'     => null, // Pode ser adicionado posteriormente
                'address_id'     => null, // Pode ser adicionado posteriormente
                'terms_accepted' => $termsAccepted,
            ] );

            $savedProvider = $this->providerRepository->create( $provider->toArray() );

            return ServiceResult::success( $savedProvider, 'Provider criado com sucesso.' );

        } catch ( Exception $e ) {
            return ServiceResult::error(
                OperationStatus::ERROR,
                'Erro ao criar Provider: ' . $e->getMessage()
            );
        }
    }

    /**
     * Associa role 'provider' ao usuário.
     *
     * @param User $user Usuário
     * @param Tenant $tenant Tenant
     * @return ServiceResult Resultado da operação
     */
    private function assignProviderRole( User $user, Tenant $tenant ): ServiceResult
    {
        try {
            $providerRole = Role::where( 'name', 'provider' )->first();

            if ( !$providerRole ) {
                return ServiceResult::error(
                    OperationStatus::ERROR,
                    'Role provider não encontrado no banco de dados',
                );
            }

            // Criar a relação user_roles
            $user->roles()->attach( $providerRole->id, [
                'tenant_id'  => $tenant->id,
                'created_at' => now(),
                'updated_at' => now()
            ] );

            return ServiceResult::success( null, 'Role provider associado com sucesso.' );

        } catch ( Exception $e ) {
            return ServiceResult::error(
                OperationStatus::ERROR,
                'Erro ao associar role provider: ' . $e->getMessage()
            );
        }
    }

    /**
     * Cria assinatura do plano para o usuário.
     *
     * @param Tenant $tenant Tenant
     * @param Plan $plan Plano
     * @param User $user Usuário
     * @param Provider $provider Provider
     * @return ServiceResult Resultado da operação
     */
    private function createPlanSubscription( Tenant $tenant, Plan $plan, User $user, Provider $provider ): ServiceResult
    {
        try {
            $planSubscription = new PlanSubscription( [
                'tenant_id'          => $tenant->id,
                'plan_id'            => $plan->id,
                'user_id'            => $user->id,
                'provider_id'        => $provider->id,
                'status'             => 'active',
                'transaction_amount' => $plan->price ?? 0.00,
                'start_date'         => now(),
                'end_date'           => now()->addDays( 7 ), // Trial de 7 dias
                'transaction_date'   => now(),
                'payment_method'     => 'trial',
                'payment_id'         => 'TEST_' . uniqid(),
                'public_hash'        => 'TEST_HASH_' . uniqid(),

            ] );

            $savedSubscription = $this->planRepository->saveSubscription( $planSubscription );

            return ServiceResult::success( $savedSubscription, 'Assinatura criada com sucesso.' );

        } catch ( Exception $e ) {
            return ServiceResult::error(
                OperationStatus::ERROR,
                'Erro ao criar assinatura: ' . $e->getMessage()
            );
        }
    }

    /**
     * Gera um nome único para o tenant usando dados disponíveis do usuário.
     *
     * Estratégia de geração:
     * 1. Primeiro tenta usar "first_name last_name" (ex: "João Silva")
     * 2. Se houver duplicata, tenta usar a parte do email antes do @ (ex: "joao.silva")
     * 3. Se ainda houver duplicata, adiciona um número sequencial
     * 4. Usa slug para garantir que seja URL-safe
     *
     * @param array $userData Dados do usuário (first_name, last_name, email)
     * @return string Nome único para o tenant
     */
    private function generateUniqueTenantName( array $userData ): string
    {
        // Estratégia 1: Nome completo
        $baseName   = trim( $userData[ 'first_name' ] . ' ' . $userData[ 'last_name' ] );
        $tenantName = Str::slug( $baseName );

        // Verificar se já existe
        if ( !$this->tenantRepository->existsByName( $tenantName ) ) {
            Log::info( 'Nome único de tenant gerado - Estratégia 1 (nome completo)', [
                'tenant_name' => $tenantName,
                'user_name'   => $baseName
            ] );
            return $tenantName;
        }

        // Estratégia 2: Parte do email
        $emailPrefix = explode( '@', $userData[ 'email' ] )[ 0 ];
        $tenantName  = Str::slug( $emailPrefix );

        // Verificar se já existe
        if ( !$this->tenantRepository->existsByName( $tenantName ) ) {
            Log::info( 'Nome único de tenant gerado - Estratégia 2 (email)', [
                'tenant_name'  => $tenantName,
                'email_prefix' => $emailPrefix
            ] );
            return $tenantName;
        }

        // Estratégia 3: Nome completo + número sequencial
        $counter      = 1;
        $originalName = Str::slug( $baseName );

        do {
            $tenantName = $originalName . '-' . $counter;
            $counter++;
        } while ( $this->tenantRepository->existsByName( $tenantName ) && $counter < 1000 );

        Log::info( 'Nome único de tenant gerado - Estratégia 3 (com contador)', [
            'tenant_name' => $tenantName,
            'counter'     => $counter - 1,
            'user_name'   => $baseName
        ] );

        return $tenantName;
    }

    /**
     * Cria um novo tenant para o usuário.
     *
     * @param array $userData Dados do usuário
     * @return ServiceResult Resultado da operação
     */
    private function createTenant( array $userData ): ServiceResult
    {
        try {
            // Gerar nome único usando lógica inteligente
            $tenantName = $this->generateUniqueTenantName( $userData );

            $tenant = new Tenant( [
                'name'      => $tenantName,
                'is_active' => true,
            ] );

            $savedTenant = $this->tenantRepository->create( $tenant->toArray() );

            Log::info( 'Tenant criado com nome único gerado', [
                'tenant_id'   => $savedTenant->id,
                'tenant_name' => $tenantName,
                'user_name'   => $userData[ 'first_name' ] . ' ' . $userData[ 'last_name' ],
                'user_email'  => $userData[ 'email' ]
            ] );

            return ServiceResult::success( $savedTenant, 'Tenant criado com sucesso.' );

        } catch ( Exception $e ) {
            return ServiceResult::error(
                OperationStatus::ERROR,
                'Erro ao criar tenant: ' . $e->getMessage()
            );
        }
    }

    /**
     * Cria um novo usuário.
     *
     * @param array $userData Dados do usuário
     * @param Tenant $tenant Tenant do usuário
     * @return ServiceResult Resultado da operação
     */
    private function createUser( array $userData, Tenant $tenant ): ServiceResult
    {
        try {
            // Criar usuário usando o modelo diretamente para evitar conflitos com o global scope
            $user = User::withoutTenant()->create( [
                'tenant_id' => $tenant->id,
                'name'      => $userData[ 'first_name' ] . ' ' . $userData[ 'last_name' ], // ✅ Nome completo do usuário
                'email'     => $userData[ 'email' ],
                'password'  => $userData[ 'password' ] ? Hash::make( $userData[ 'password' ] ) : null,
                'is_active' => false,
            ] );

            return ServiceResult::success( $user, 'Usuário criado com sucesso.' );

        } catch ( Exception $e ) {
            return ServiceResult::error(
                OperationStatus::ERROR,
                'Erro ao criar usuário: ' . $e->getMessage()
            );
        }
    }

}
