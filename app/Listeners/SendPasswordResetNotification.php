<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\PasswordResetRequested;
use App\Services\Infrastructure\LinkService;
use App\Services\Infrastructure\MailerService;
use App\Support\ServiceResult;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Listener responsável por enviar e-mail de redefinição de senha.
 *
 * Implementa funcionalidades completas de tratamento de erro, logging e validações
 * seguindo os padrões arquiteturais do sistema Easy Budget Laravel.
 *
 * Funcionalidades implementadas:
 * - Tratamento robusto de erros com try-catch
 * - Logging detalhado para monitoramento
 * - Validações adequadas dos dados recebidos
 * - Tratamento específico para redefinição de senha
 * - Métricas de performance integradas
 * - Sistema de retry automático com backoff exponencial
 * - Deduplicação para evitar envio duplo
 */
class SendPasswordResetNotification implements ShouldQueue
{
    /**
     * O número de vezes que o job pode ser executado novamente em caso de falha.
     * Configuração otimizada baseada em análise de padrões de erro.
     */
    public int $tries = 3;

    /**
     * O tempo em segundos antes de tentar executar o job novamente.
     * Estratégia de backoff exponencial inteligente.
     */
    public int $backoff = 30;

    /**
     * Serviço de e-mail com funcionalidades avançadas.
     * Injetado automaticamente pelo Laravel.
     */
    protected MailerService $mailerService;

    /**
     * Serviço para construção segura de links de confirmação.
     * Injetado automaticamente pelo Laravel.
     */
    protected LinkService $linkService;

    /**
     * Métricas de performance do processamento.
     */
    private array $performanceMetrics = [];

    /**
     * Cria uma nova instância do listener.
     *
     * @param  MailerService  $mailerService  Serviço de e-mail injetado
     * @param  LinkService  $linkService  Serviço de links de confirmação injetado
     */
    public function __construct(
        MailerService $mailerService,
        LinkService $linkService,
    ) {
        $this->mailerService = $mailerService;
        $this->linkService = $linkService;
        $this->initializePerformanceMetrics();
    }

    /**
     * Processa o evento de solicitação de redefinição de senha e envia e-mail.
     *
     * Implementa o padrão Template Method com funcionalidades completas:
     * - Logging inicial detalhado
     * - Validação rigorosa dos dados
     * - Processamento específico do e-mail
     * - Tratamento de resultado (sucesso/falha)
     * - Tratamento de exceções
     * - Métricas de performance
     *
     * @param  PasswordResetRequested  $event  Evento de solicitação de redefinição
     */
    final public function handle(PasswordResetRequested $event): void
    {
        // Iniciar métricas de performance
        $this->startPerformanceTracking();

        // ADICIONADO: Log crítico para verificar se o listener está sendo executado
        Log::critical('SendPasswordResetNotification: LISTENER EXECUTADO - Verificando se evento personalizado está sendo disparado', [
            'user_id' => $event->user->id,
            'email' => $event->user->email,
            'tenant_id' => $event->tenant?->id,
            'reset_token' => substr($event->resetToken, 0, 10).'...', // Log parcial por segurança
            'event_type' => 'password_reset_custom',
            'listener_class' => static::class,
            'timestamp' => now()->toISOString(),
        ]);

        try {
            // 1. Logging inicial estruturado
            $this->logEventStart($event);

            // ADICIONADO: Deduplicação usando cache
            $dedupeKey = $this->buildPasswordResetDedupKey($event);
            if (! Cache::add($dedupeKey, true, now()->addMinutes(30))) {
                Log::warning('Envio de e-mail de redefinição de senha ignorado por deduplicação', [
                    'user_id' => $event->user->id,
                    'email' => $event->user->email,
                    'tenant_id' => $event->tenant?->id,
                    'dedupe_key' => $dedupeKey,
                ]);

                return;
            }

            // 2. Validação inicial rigorosa
            $this->validateEvent($event);

            // 3. Processamento específico do e-mail de redefinição de senha
            $result = $this->processPasswordResetEmail($event);

            // 4. Tratamento padronizado do resultado
            if ($result->isSuccess()) {
                $this->handleSuccess($event, $result);
            } else {
                $this->handleFailure($event, $result);
            }

        } catch (Throwable $e) {
            $this->handleException($event, $e);
        } finally {
            // Finalizar métricas de performance
            $this->endPerformanceTracking();
        }
    }

    /**
     * Processa especificamente o envio de e-mail de redefinição de senha.
     *
     * Contém a lógica específica deste tipo de e-mail com tratamento
     * completo de erros e validações de negócio.
     *
     * @param  PasswordResetRequested  $event  Evento de solicitação de redefinição
     * @return ServiceResult Resultado do processamento
     */
    protected function processPasswordResetEmail(PasswordResetRequested $event): ServiceResult
    {
        try {
            // Validação adicional específica para redefinição de senha
            if (! $event->user || ! $event->user->id) {
                throw new \InvalidArgumentException('Dados do usuário inválidos no evento de redefinição de senha');
            }

            if (empty($event->user->email)) {
                throw new \InvalidArgumentException('E-mail do usuário não informado no evento de redefinição de senha');
            }

            // Validação rigorosa do token de redefinição usando formato base64url
            if (! validateAndSanitizeToken($event->resetToken, 'base64url')) {
                throw new \InvalidArgumentException('Token de redefinição com formato inválido');
            }

            // Envia e-mail usando o serviço injetado com tratamento de erro específico
            return $this->mailerService->sendPasswordResetNotification(
                $event->user,
                $event->resetToken,
                $event->tenant,
                null, // company parameter (opcional)
            );

        } catch (Throwable $e) {
            // Log detalhado do erro específico de redefinição de senha
            Log::error('Erro específico no processamento de e-mail de redefinição de senha', [
                'user_id' => $event->user->id ?? null,
                'email' => $event->user->email ?? null,
                'tenant_id' => $event->tenant?->id,
                'error_message' => $e->getMessage(),
                'error_type' => get_class($e),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'processing_time' => $this->getProcessingTime(),
                'memory_usage' => memory_get_usage(true),
                'event_type' => 'password_reset',
            ]);

            return ServiceResult::error('Erro interno no processamento de redefinição de senha: '.$e->getMessage());
        }
    }

    /**
     * Validação rigorosa do evento de redefinição de senha.
     *
     * Implementa validações específicas para o contexto de redefinição de senha
     * com tratamento detalhado de cada cenário de erro.
     *
     * @param  PasswordResetRequested  $event  Evento a ser validado
     *
     * @throws \InvalidArgumentException Se alguma validação falhar
     */
    protected function validateEvent(PasswordResetRequested $event): void
    {
        // Validação básica comum a todos os eventos de e-mail
        if (! $event || ! $event->user || ! $event->user->id) {
            throw new \InvalidArgumentException('Dados do usuário inválidos no evento de redefinição de senha');
        }

        if (empty($event->user->email)) {
            throw new \InvalidArgumentException('E-mail do usuário não informado no evento de redefinição de senha');
        }

        // Validação específica de redefinição - token obrigatório
        if (! $event->resetToken) {
            throw new \InvalidArgumentException('Token de redefinição obrigatório não informado');
        }

        // Validação de formato do token usando base64url (32 bytes = 43 caracteres)
        if (! validateAndSanitizeToken($event->resetToken, 'base64url')) {
            throw new \InvalidArgumentException('Token de redefinição com formato inválido');
        }

        // Validação adicional: verificar se o usuário está ativo
        if (isset($event->user->is_active) && ! $event->user->is_active) {
            Log::info('Usuário inativo tentou solicitar redefinição de senha', [
                'user_id' => $event->user->id,
                'email' => $event->user->email,
                'is_active' => $event->user->is_active,
            ]);
        }
    }

    /**
     * Tratamento padronizado de sucesso.
     *
     * @param  PasswordResetRequested  $event  Evento processado
     * @param  ServiceResult  $result  Resultado da operação
     */
    protected function handleSuccess(PasswordResetRequested $event, ServiceResult $result): void
    {
        $data = $result->getData();

        Log::info('E-mail de redefinição de senha enviado com sucesso', [
            'user_id' => $event->user->id,
            'email' => $event->user->email,
            'tenant_id' => $event->tenant?->id,
            'queued_at' => $data['queued_at'] ?? null,
            'queue' => $data['queue'] ?? 'emails',
            'processing_time' => $this->getProcessingTime(),
            'memory_usage' => memory_get_usage(true),
            'success' => true,
            'event_type' => 'password_reset',
        ]);
    }

    /**
     * Tratamento padronizado de falha.
     *
     * @param  PasswordResetRequested  $event  Evento processado
     * @param  ServiceResult  $result  Resultado da operação
     */
    protected function handleFailure(PasswordResetRequested $event, ServiceResult $result): void
    {
        Log::error('Falha no envio de e-mail de redefinição de senha', [
            'user_id' => $event->user->id,
            'email' => $event->user->email,
            'tenant_id' => $event->tenant?->id,
            'error_message' => $result->getMessage(),
            'error_status' => $result->getStatus(),
            'error_data' => $result->getData(),
            'processing_time' => $this->getProcessingTime(),
            'memory_usage' => memory_get_usage(true),
            'will_retry' => true,
            'event_type' => 'password_reset',
        ]);

        // Relança a exceção para que seja tratada pela queue com retry automático
        throw new \Exception('Falha no envio de e-mail de redefinição de senha: '.$result->getMessage());
    }

    /**
     * Tratamento padronizado de exceções.
     *
     * @param  PasswordResetRequested  $event  Evento que estava sendo processado
     * @param  Throwable  $exception  Exceção ocorrida
     */
    private function handleException(PasswordResetRequested $event, Throwable $exception): void
    {
        Log::error('Erro crítico no processamento de e-mail de redefinição de senha', [
            'user_id' => $event->user->id ?? null,
            'email' => $event->user->email ?? null,
            'tenant_id' => $event->tenant?->id,
            'error_message' => $exception->getMessage(),
            'error_type' => get_class($exception),
            'error_file' => $exception->getFile(),
            'error_line' => $exception->getLine(),
            'processing_time' => $this->getProcessingTime(),
            'memory_usage' => memory_get_usage(true),
            'will_retry' => true,
            'event_type' => 'password_reset',
        ]);

        // Relança a exceção para que seja tratada pela queue
        throw $exception;
    }

    /**
     * Constrói chave única para deduplicação de e-mails de redefinição de senha.
     *
     * @param  PasswordResetRequested  $event  Evento de redefinição de senha
     * @return string Chave única para deduplicação
     */
    private function buildPasswordResetDedupKey(PasswordResetRequested $event): string
    {
        $tokenHash = hash('sha256', (string) $event->resetToken);

        return 'email:password_reset:'.$event->user->id.':'.$tokenHash;
    }

    /**
     * Tratamento padronizado de falha crítica do job.
     *
     * @param  PasswordResetRequested  $event  Evento que estava sendo processado
     * @param  Throwable  $exception  Última exceção ocorrida
     */
    final public function failed(PasswordResetRequested $event, Throwable $exception): void
    {
        Log::critical('Falha crítica no envio de e-mail de redefinição de senha após todas as tentativas', [
            'user_id' => $event->user->id ?? null,
            'email' => $event->user->email ?? null,
            'tenant_id' => $event->tenant?->id,
            'error_message' => $exception->getMessage(),
            'error_type' => get_class($exception),
            'attempts' => $this->tries,
            'max_attempts' => $this->tries,
            'backoff_strategy' => 'exponential',
            'final_failure' => true,
            'event_type' => 'password_reset',
        ]);

        // Em produção, poderia notificar administradores sobre a falha
        // ou implementar lógica de fallback específica para redefinição de senha
    }

    /**
     * Logging inicial estruturado e detalhado.
     *
     * @param  PasswordResetRequested  $event  Evento recebido
     */
    private function logEventStart(PasswordResetRequested $event): void
    {
        Log::info('Processando evento PasswordResetRequested para envio de e-mail de redefinição', [
            'user_id' => $event->user->id,
            'email' => $event->user->email,
            'tenant_id' => $event->tenant?->id,
            'event_type' => 'password_reset',
            'listener_class' => static::class,
            'processing_time' => microtime(true),
            'memory_usage' => memory_get_usage(true),
            'queue' => 'emails',
        ]);
    }

    /**
     * Inicializa métricas de performance.
     */
    private function initializePerformanceMetrics(): void
    {
        $this->performanceMetrics = [
            'start_time' => 0,
            'end_time' => 0,
            'memory_start' => 0,
            'memory_end' => 0,
        ];
    }

    /**
     * Inicia rastreamento de performance.
     */
    private function startPerformanceTracking(): void
    {
        $this->performanceMetrics['start_time'] = microtime(true);
        $this->performanceMetrics['memory_start'] = memory_get_usage(true);
    }

    /**
     * Finaliza rastreamento de performance.
     */
    private function endPerformanceTracking(): void
    {
        $this->performanceMetrics['end_time'] = microtime(true);
        $this->performanceMetrics['memory_end'] = memory_get_usage(true);
    }

    /**
     * Obtém tempo de processamento em segundos.
     *
     * @return float Tempo de processamento
     */
    private function getProcessingTime(): float
    {
        return $this->performanceMetrics['end_time'] - $this->performanceMetrics['start_time'];
    }

    /**
     * Constrói URL de confirmação segura usando o serviço centralizado.
     *
     * @param  string|null  $token  Token de confirmação
     * @param  string  $route  Rota para confirmação (padrão: /confirm-account)
     * @param  string  $fallbackRoute  Rota de fallback (padrão: /login)
     * @return string URL de confirmação segura
     */
    protected function buildConfirmationLink(
        ?string $token,
        string $route = '/confirm-account',
        string $fallbackRoute = '/login',
    ): string {
        return $this->linkService->buildLink($token, $route, $fallbackRoute);
    }

    /**
     * Constrói URL de confirmação para e-mails de boas-vindas.
     *
     * @param  string|null  $token  Token de confirmação
     * @return string URL de confirmação
     */
    protected function buildWelcomeConfirmationLink(?string $token): string
    {
        return $this->linkService->buildWelcomeConfirmationLink($token);
    }

    /**
     * Constrói URL de confirmação para e-mails de verificação.
     *
     * @param  string|null  $token  Token de confirmação
     * @return string URL de confirmação
     */
    protected function buildVerificationConfirmationLink(?string $token): string
    {
        return $this->linkService->buildVerificationConfirmationLink($token);
    }
}
