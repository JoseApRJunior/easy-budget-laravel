<?php

declare(strict_types=1);

namespace App\Services\Application;

use App\Enums\OperationStatus;
use App\Events\UserRegistered;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UserConfirmationToken;
use App\Repositories\TenantRepository;
use App\Repositories\UserConfirmationTokenRepository;
use App\Repositories\UserRepository;
use App\Services\Application\Abstracts\BaseTenantService;
use App\Services\Core\Abstracts\AbstractBaseService;
use App\Support\ServiceResult;
use Exception;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Serviço para registro completo de usuários no sistema Easy Budget.
 *
 * Este serviço migra a lógica do UserRegistrationService legacy para a nova arquitetura,
 * mantendo compatibilidade com o processo existente enquanto implementa as melhores
 * práticas do Laravel e do padrão de serviços do projeto.
 *
 * Funcionalidades principais:
 * - Registro completo de usuários com tenant isolation
 * - Integração com UserService, TenantService e MailerService
 * - Validação usando Laravel validation
 * - Compatibilidade com processo legacy
 * - Criação automática de tenants para novos usuários
 * - Envio de e-mails de confirmação usando eventos
 * - Gerenciamento de tokens de confirmação
 * - Recuperação de senha
 * - Confirmação de conta
 *
 * O serviço é registrado como singleton no container DI e pode ser injetado
 * em controllers e outros serviços conforme necessário.
 */
class UserRegistrationService extends AbstractBaseService
{
    protected UserRepository                  $userRepository;
    protected TenantRepository                $tenantRepository;
    protected UserConfirmationTokenRepository $userConfirmationTokenRepository;

    public function __construct(
        UserRepository $userRepository,
        TenantRepository $tenantRepository,
        UserConfirmationTokenRepository $userConfirmationTokenRepository,
    ) {
        $this->userRepository                  = $userRepository;
        $this->tenantRepository                = $tenantRepository;
        $this->userConfirmationTokenRepository = $userConfirmationTokenRepository;
    }

    /**
     * Registra um novo usuário no sistema.
     *
     * Este método cria o usuário, tenant associado e dispara evento para
     * envio de e-mail de boas-vindas ao invés de chamar MailerService diretamente.
     *
     * @param array $userData Dados do usuário
     * @return ServiceResult Resultado da operação
     */
    public function registerUser( array $userData ): ServiceResult
    {
        try {
            DB::beginTransaction();

            // Validação dos dados
            $validation = $this->validateUserData( $userData );
            if ( !$validation->isSuccess() ) {
                return $validation;
            }

            // Criar tenant primeiro
            $tenantResult = $this->createTenant( $userData );
            if ( !$tenantResult->isSuccess() ) {
                return $tenantResult;
            }

            $tenant = $tenantResult->getData();

            // Criar usuário
            $userResult = $this->createUser( $userData, $tenant );
            if ( !$userResult->isSuccess() ) {
                DB::rollBack();
                return $userResult;
            }

            $user = $userResult->getData();

            // Criar token de confirmação se necessário
            if ( config( 'app.email_verification_required', true ) ) {
                $tokenResult = $this->createConfirmationToken( $user, $tenant );
                if ( !$tokenResult->isSuccess() ) {
                    DB::rollBack();
                    return $tokenResult;
                }
            }

            DB::commit();

            // Disparar evento para envio de e-mail de boas-vindas
            // AO INVÉS de chamar MailerService diretamente
            Event::dispatch( new UserRegistered( $user, $tenant ) );

            Log::info( 'Usuário registrado com sucesso usando eventos', [
                'user_id'   => $user->id,
                'email'     => $user->email,
                'tenant_id' => $tenant->id,
            ] );

            return ServiceResult::success( [
                'user'    => $user,
                'tenant'  => $tenant,
                'message' => 'Usuário registrado com sucesso. E-mail de boas-vindas será enviado em segundo plano.'
            ], 'Usuário registrado com sucesso.' );

        } catch ( Exception $e ) {
            DB::rollBack();

            Log::error( 'Erro ao registrar usuário', [
                'error'     => $e->getMessage(),
                'user_data' => $userData,
            ] );

            return ServiceResult::error(
                OperationStatus::ERROR,
                'Erro ao registrar usuário: ' . $e->getMessage()
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

            // Criar token de redefinição
            $token     = Str::random( 64 );
            $expiresAt = now()->addMinutes( config( 'auth.passwords.users.expire', 60 ) );

            $resetToken = new UserConfirmationToken( [
                'user_id'    => $user->id,
                'token'      => $token,
                'expires_at' => $expiresAt,
                'type'       => 'password_reset',
            ] );

            $this->userConfirmationTokenRepository->save( $resetToken );

            // Buscar tenant do usuário
            $tenant = null;
            if ( $user->tenant_id ) {
                $tenant = $this->tenantRepository->findById( $user->tenant_id );
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
     * Valida dados do usuário para registro.
     *
     * @param array $userData Dados do usuário
     * @return ServiceResult Resultado da validação
     */
    private function validateUserData( array $userData ): ServiceResult
    {
        $validator = Validator::make( $userData, [
            'name'         => 'required|string|max:255',
            'email'        => 'required|email|unique:users,email',
            'password'     => 'required|string|min:8|confirmed',
            'company_name' => 'required|string|max:255',
        ] );

        if ( $validator->fails() ) {
            return ServiceResult::error(
                OperationStatus::INVALID_DATA,
                'Dados de usuário inválidos: ' . implode( ', ', $validator->errors()->all() )
            );
        }

        return ServiceResult::success( null, 'Dados válidos.' );
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
            $tenant = new Tenant( [
                'name'      => $userData[ 'company_name' ],
                'is_active' => true,
            ] );

            $savedTenant = $this->tenantRepository->save( $tenant );

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
            $user = new User( [
                'tenant_id' => $tenant->id,
                'name'      => $userData[ 'name' ],
                'email'     => $userData[ 'email' ],
                'password'  => Hash::make( $userData[ 'password' ] ),
                'is_active' => true,
            ] );

            $savedUser = $this->userRepository->save( $user );

            return ServiceResult::success( $savedUser, 'Usuário criado com sucesso.' );

        } catch ( Exception $e ) {
            return ServiceResult::error(
                OperationStatus::ERROR,
                'Erro ao criar usuário: ' . $e->getMessage()
            );
        }
    }

    /**
     * Cria token de confirmação para o usuário.
     *
     * @param User $user Usuário
     * @param Tenant $tenant Tenant do usuário
     * @return ServiceResult Resultado da operação
     */
    private function createConfirmationToken( User $user, Tenant $tenant ): ServiceResult
    {
        try {
            $token     = Str::random( 64 );
            $expiresAt = now()->addMinutes( config( 'auth.verification.expire', 60 ) );

            $confirmationToken = new UserConfirmationToken( [
                'user_id'    => $user->id,
                'tenant_id'  => $tenant->id,
                'token'      => $token,
                'expires_at' => $expiresAt,
                'type'       => 'email_verification',
            ] );

            $this->userConfirmationTokenRepository->save( $confirmationToken );

            return ServiceResult::success( $confirmationToken, 'Token de confirmação criado.' );

        } catch ( Exception $e ) {
            return ServiceResult::error(
                OperationStatus::ERROR,
                'Erro ao criar token de confirmação: ' . $e->getMessage()
            );
        }
    }

}
