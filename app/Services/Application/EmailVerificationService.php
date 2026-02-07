<?php

declare(strict_types=1);

namespace App\Services\Application;

use App\Enums\OperationStatus;
use App\Events\EmailVerificationRequested;
use App\Models\User;
use App\Models\UserConfirmationToken;
use App\Repositories\UserConfirmationTokenRepository;
use App\Repositories\UserRepository;
use App\Services\Core\Abstracts\AbstractBaseService;
use App\Support\ServiceResult;
use Exception;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

/**
 * Serviço para gerenciamento de verificação de e-mail.
 *
 * Este serviço implementa toda a lógica de negócio para verificação de e-mail,
 * seguindo a arquitetura Controller → Service → Repository → Model estabelecida.
 *
 * Funcionalidades principais:
 * - Criação de tokens de confirmação em formato base64url com expiração de 30 minutos
 * - Remoção automática de tokens antigos do usuário
 * - Integração com sistema Laravel built-in de verificação
 * - Tratamento robusto de erros com logging detalhado
 * - Preservação do isolamento multi-tenant
 * - Uso de eventos para envio de e-mails
 *
 * O serviço utiliza o padrão de eventos para envio de e-mails, seguindo
 * a mesma arquitetura utilizada em UserRegistrationService e outros serviços.
 *
 * NOTA: A validação de dados de entrada é responsabilidade do Controller/FormRequest,
 * este serviço foca exclusivamente na lógica de negócio da verificação de e-mail.
 */
class EmailVerificationService extends AbstractBaseService
{
    protected UserConfirmationTokenService $userConfirmationTokenService;

    protected UserConfirmationTokenRepository $userConfirmationTokenRepository;

    protected UserRepository $userRepository;

    public function __construct(
        UserConfirmationTokenService $userConfirmationTokenService,
        UserConfirmationTokenRepository $userConfirmationTokenRepository,
        UserRepository $userRepository,
    ) {
        parent::__construct($userConfirmationTokenRepository);
        $this->userConfirmationTokenService = $userConfirmationTokenService;
        $this->userConfirmationTokenRepository = $userConfirmationTokenRepository;
        $this->userRepository = $userRepository;
    }

    /**
     * Cria token de confirmação para verificação de e-mail.
     *
     * @param  User  $user  Usuário que receberá o token de verificação
     * @return ServiceResult Resultado da operação
     */
    public function createConfirmationToken(User $user): ServiceResult
    {
        return $this->safeExecute(function () use ($user) {
            Log::info('Iniciando criação de token de verificação', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

            // Usar método de conveniência para criar token de verificação de e-mail
            $tokenResult = $this->userConfirmationTokenService->createEmailVerificationToken($user);

            if (! $tokenResult->isSuccess()) {
                return $tokenResult;
            }

            // Extrair token do resultado para disparar evento
            $tokenData = $tokenResult->getData();
            $token = $tokenData['token'];

            // Disparar evento para envio de e-mail de verificação
            Event::dispatch(new EmailVerificationRequested(
                $user,
                $user->tenant,
                $token,
            ));

            return $this->success(['token' => $token], 'Token de verificação enviado com sucesso.');
        }, 'Erro ao criar token de verificação.');
    }

    /**
     * Reenvia e-mail de verificação para usuário.
     *
     * Este método reutiliza a lógica de criação de token, garantindo
     * que apenas um token ativo exista por usuário e que tokens antigos
     * sejam removidos automaticamente.
     *
     * @param  User  $user  Usuário que receberá o novo e-mail de verificação
     * @return ServiceResult Resultado da operação
     */
    public function resendConfirmationEmail(User $user): ServiceResult
    {
        try {
            // Verificar se usuário já está verificado
            if ($user->hasVerifiedEmail()) {
                Log::info('Tentativa de reenvio de e-mail para usuário já verificado', [
                    'user_id' => $user->id,
                    'tenant_id' => $user->tenant_id,
                    'email' => $user->email,
                    'email_verified_at' => $user->email_verified_at,
                ]);

                return ServiceResult::error(
                    OperationStatus::CONFLICT,
                    'E-mail já foi verificado anteriormente.',
                );
            }

            Log::info('Reenviando e-mail de verificação', [
                'user_id' => $user->id,
                'tenant_id' => $user->tenant_id,
                'email' => $user->email,
            ]);

            // Criar token de verificação usando o novo serviço
            Log::info('Criando token de verificação de e-mail...', ['user_id' => $user->id]);
            $tokenResult = $this->createConfirmationToken($user);

            if (! $tokenResult->isSuccess()) {
                Log::warning('Falha ao criar token de verificação', [
                    'user_id' => $user->id,
                    'error' => $tokenResult->getMessage(),
                ]);

                return $tokenResult;
            }

            Log::info('Token de verificação criado com sucesso', ['user_id' => $user->id]);

            return $tokenResult;

        } catch (Exception $e) {
            Log::error('Erro ao reenviar e-mail de verificação', [
                'user_id' => $user->id,
                'tenant_id' => $user->tenant_id,
                'email' => $user->email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return ServiceResult::error(
                OperationStatus::ERROR,
                'Erro interno ao reenviar e-mail de verificação. Tente novamente.',
                null,
                $e,
            );
        }
    }

    /**
     * Busca token de confirmação válido para um usuário.
     *
     * @param  string  $token  Token de confirmação
     * @return ServiceResult Resultado da operação
     */
    public function findValidToken(string $token): ServiceResult
    {
        try {
            $confirmationToken = $this->userConfirmationTokenRepository->findByToken($token);

            if (! $confirmationToken) {
                return ServiceResult::error(
                    OperationStatus::NOT_FOUND,
                    'Token de verificação não encontrado.',
                );
            }

            // Verificar se token não expirou
            if ($confirmationToken->expires_at->isPast()) {
                // Remover token expirado
                $this->userConfirmationTokenRepository->delete($confirmationToken->id);

                Log::info('Token expirado removido automaticamente', [
                    'token_id' => $confirmationToken->id,
                    'user_id' => $confirmationToken->user_id,
                    'expires_at' => $confirmationToken->expires_at,
                ]);

                return ServiceResult::error(
                    OperationStatus::CONFLICT,
                    'Token de verificação expirado. Solicite um novo.',
                );
            }

            // Buscar usuário associado ao token
            $user = $this->userRepository->find($confirmationToken->user_id);
            if (! $user) {
                return ServiceResult::error(
                    OperationStatus::NOT_FOUND,
                    'Usuário associado ao token não encontrado.',
                );
            }

            return ServiceResult::success([
                'token' => $confirmationToken,
                'user' => $user,
            ], 'Token válido encontrado.');

        } catch (Exception $e) {
            Log::error('Erro ao buscar token de verificação', [
                'token' => substr($token, 0, 10).'...', // Log parcial por segurança
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return ServiceResult::error(
                OperationStatus::ERROR,
                'Erro interno ao validar token.',
                null,
                $e,
            );
        }
    }

    /**
     * Remove token após uso bem-sucedido.
     *
     * @param  UserConfirmationToken  $token  Token a ser removido
     * @return ServiceResult Resultado da operação
     */
    public function removeToken(UserConfirmationToken $token): ServiceResult
    {
        try {
            $deleted = $this->userConfirmationTokenRepository->delete($token->id);

            if (! $deleted) {
                Log::warning('Falha ao remover token após uso', [
                    'token_id' => $token->id,
                    'user_id' => $token->user_id,
                ]);

                return ServiceResult::error(
                    OperationStatus::ERROR,
                    'Erro ao remover token usado.',
                );
            }

            Log::info('Token removido após uso bem-sucedido', [
                'token_id' => $token->id,
                'user_id' => $token->user_id,
            ]);

            return ServiceResult::success(
                null,
                'Token removido com sucesso.',
            );

        } catch (Exception $e) {
            Log::error('Erro ao remover token', [
                'token_id' => $token->id,
                'user_id' => $token->user_id,
                'error' => $e->getMessage(),
            ]);

            return ServiceResult::error(
                OperationStatus::ERROR,
                'Erro interno ao remover token.',
                null,
                $e,
            );
        }
    }

    /**
     * Limpa tokens expirados do sistema.
     *
     * Método utilitário para manutenção, pode ser chamado por jobs periódicos.
     *
     * @return ServiceResult Resultado da operação
     */
    public function cleanupExpiredTokens(): ServiceResult
    {
        try {
            $deletedCount = $this->userConfirmationTokenRepository->deleteExpired();

            Log::info('Limpeza de tokens expirados executada', [
                'tokens_removed' => $deletedCount,
            ]);

            return ServiceResult::success([
                'tokens_removed' => $deletedCount,
            ], "Limpeza executada. {$deletedCount} tokens expirados removidos.");

        } catch (Exception $e) {
            Log::error('Erro na limpeza de tokens expirados', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return ServiceResult::error(
                OperationStatus::ERROR,
                'Erro interno na limpeza de tokens.',
                null,
                $e,
            );
        }
    }

    /**
     * Define filtros suportados pelo serviço.
     */
    protected function getSupportedFilters(): array
    {
        return [
            'id',
            'user_id',
            'tenant_id',
            'token',
            'expires_at',
            'type',
            'created_at',
            'updated_at',
        ];
    }
}
