<?php

declare(strict_types=1);

namespace App\Services\Application;

use App\DTOs\Provider\ProviderRegistrationDTO;
use App\Enums\OperationStatus;
use App\Events\PasswordResetRequested;
use App\Events\UserRegistered;
use App\Models\User;
use App\Models\UserConfirmationToken;
use App\Repositories\TenantRepository;
use App\Repositories\UserConfirmationTokenRepository;
use App\Repositories\UserRepository;
use App\Services\Application\EmailVerificationService;
use App\Services\Application\ProviderManagementService;
use App\Services\Application\UserConfirmationTokenService;
use App\Services\Core\Abstracts\AbstractBaseService;
use App\Support\ServiceResult;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

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
    protected ProviderManagementService       $providerManagementService;
    protected EmailVerificationService        $emailVerificationService;
    protected TenantRepository                $tenantRepository;

    public function __construct(
        UserRepository $userRepository,
        UserConfirmationTokenService $userConfirmationTokenService,
        UserConfirmationTokenRepository $userConfirmationTokenRepository,
        ProviderManagementService $providerManagementService,
        EmailVerificationService $emailVerificationService,
        TenantRepository $tenantRepository,
    ) {
        parent::__construct($userRepository);
        $this->userRepository                  = $userRepository;
        $this->userConfirmationTokenService    = $userConfirmationTokenService;
        $this->userConfirmationTokenRepository = $userConfirmationTokenRepository;
        $this->providerManagementService       = $providerManagementService;
        $this->emailVerificationService        = $emailVerificationService;
        $this->tenantRepository                = $tenantRepository;
    }

    /**
     * Registra um novo usuário no sistema com lógica completa.
     *
     * @param ProviderRegistrationDTO $dto DTO com dados do usuário
     * @param bool $isSocialRegistration Se é um registro via rede social (default: false)
     * @return ServiceResult Resultado da operação
     */
    public function registerUser(ProviderRegistrationDTO $dto, bool $isSocialRegistration = false): ServiceResult
    {
        return $this->safeExecute(function () use ($dto, $isSocialRegistration) {
            DB::beginTransaction();

            // Delegar toda a lógica de criação para ProviderManagementService
            $this->logStep('Delegando criação completa para ProviderManagementService');

            $registrationResult = $this->providerManagementService->createProviderFromRegistration($dto);

            if (!$registrationResult->isSuccess()) {
                DB::rollBack();
                return $registrationResult;
            }

            $results = $registrationResult->getData();

            DB::commit();

            // Processar token de verificação e evento apenas para registros normais
            if (!$isSocialRegistration) {
                $tokenResult = $this->processEmailVerification($results['user']);
                $token       = $tokenResult ? $tokenResult->getData()['token'] : null;

                Event::dispatch(new UserRegistered(
                    $results['user'],
                    $results['tenant'],
                    $token,
                ));
            } else {
                $token = null;
                $this->logStep('Registro social - pulando verificação de e-mail (Google já verifica)');
            }

            $this->logStep('Registro concluído com sucesso', [
                'user_id'         => $results['user']->id,
                'email'           => $results['user']->email,
                'tenant_id'       => $results['tenant']->id,
                'plan_id'         => $results['plan']->id,
                'provider_id'     => $results['provider']->id,
                'subscription_id' => $results['subscription']->id,
            ]);

            return $this->success([
                'user'         => $results['user'],
                'tenant'       => $results['tenant'],
                'provider'     => $results['provider'],
                'plan'         => $results['plan'],
                'subscription' => $results['subscription'],
                'message'      => 'Registro realizado com sucesso! Bem-vindo ao Easy Budget.'
            ], 'Usuário registrado com sucesso.');
        }, 'Erro no registro de usuário.');
    }

    /**
     * Registra um log para um passo específico.
     *
     * @param string $message Mensagem do log
     * @param array $context Contexto adicional
     */
    private function logStep(string $message, array $context = []): void
    {
        Log::info($message, $context);
    }

    /**
     * Processa a criação do token de verificação de e-mail.
     *
     * @param User $user Usuário
     * @return ServiceResult|null Resultado do processamento
     */
    private function processEmailVerification(User $user): ?ServiceResult
    {
        $this->logStep('Criando token de verificação de e-mail...', ['user_id' => $user->id]);
        $tokenResult = $this->userConfirmationTokenService->createEmailVerificationToken($user);

        if (!$tokenResult->isSuccess()) {
            $this->logStep('Falha ao criar token de verificação, mas usuário foi registrado', [
                'user_id' => $user->id,
                'error'   => $tokenResult->getMessage(),
            ]);
            return null;
        }

        $this->logStep('Token de verificação criado com sucesso', ['user_id' => $user->id]);
        return $tokenResult;
    }

    /**
     * Solicita redefinição de senha para um usuário.
     *
     * @param string $email E-mail do usuário
     * @return ServiceResult Resultado da operação
     */
    public function requestPasswordReset(string $email): ServiceResult
    {
        return $this->safeExecute(function () use ($email) {
            // Buscar usuário por e-mail
            $user = $this->userRepository->findByEmail($email);
            if (!$user) {
                // Não revelar se o e-mail existe ou não por segurança
                return $this->success(
                    null,
                    'Se o e-mail existir em nosso sistema, você receberá instruções de redefinição.',
                );
            }

            // Criar token de redefinição em formato base64url
            $token     = generateSecureTokenUrl();
            $expiresAt = now()->addMinutes((int) config('auth.passwords.users.expire', 60));

            $this->userConfirmationTokenRepository->create([
                'user_id'    => $user->id,
                'token'      => $token,
                'expires_at' => $expiresAt,
                'type'       => 'password_reset',
            ]);

            // Buscar tenant do usuário
            $tenant = null;
            if ($user->tenant_id) {
                $tenant = $this->tenantRepository->find($user->tenant_id);
            }

            // Disparar evento para envio de e-mail de redefinição
            Event::dispatch(new PasswordResetRequested($user, $token, $tenant));

            Log::info('Solicitação de redefinição de senha processada com eventos', [
                'user_id' => $user->id,
                'email'   => $user->email,
            ]);

            return $this->success(
                null,
                'Instruções de redefinição de senha foram enviadas para seu e-mail.',
            );
        }, 'Erro ao solicitar redefinição de senha.');
    }
}
