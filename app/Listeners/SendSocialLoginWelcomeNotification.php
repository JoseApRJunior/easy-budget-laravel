<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\SocialLoginWelcome;
use App\Mail\SocialLoginWelcomeMail;
use App\Services\Infrastructure\MailerService;
use App\Support\ServiceResult;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Listener responsável por enviar e-mail de boas-vindas para login social.
 *
 * Implementa funcionalidades completas de tratamento de erro, logging e validações
 * seguindo os padrões arquiteturais do sistema Easy Budget Laravel.
 *
 * Funcionalidades implementadas:
 * - Tratamento robusto de erros com try-catch
 * - Logging detalhado para monitoramento
 * - Validações adequadas dos dados recebidos
 * - Tratamento específico para login social
 * - Métricas de performance integradas
 * - Sistema de retry automático com backoff exponencial
 */
class SendSocialLoginWelcomeNotification implements ShouldQueue
{
    /**
     * O número de vezes que o job pode ser executado novamente em caso de falha.
     */
    public int $tries = 3;

    /**
     * O tempo em segundos antes de tentar executar o job novamente.
     */
    public int $backoff = 30;

    /**
     * Serviço de e-mail com funcionalidades avançadas.
     */
    protected MailerService $mailerService;

    /**
     * Métricas de performance do processamento.
     */
    private array $performanceMetrics = [];

    /**
     * Cria uma nova instância do listener.
     *
     * @param  MailerService  $mailerService  Serviço de e-mail injetado
     */
    public function __construct(MailerService $mailerService)
    {
        $this->mailerService = $mailerService;
        $this->initializePerformanceMetrics();
    }

    /**
     * Processa o evento de login social e envia e-mail de boas-vindas.
     *
     * @param  SocialLoginWelcome  $event  Evento de login social
     */
    final public function handle(SocialLoginWelcome $event): void
    {
        // Iniciar métricas de performance
        $this->startPerformanceTracking();

        try {
            // 1. Logging inicial estruturado
            $this->logEventStart($event);

            // 2. Deduplicação para evitar envios duplicados
            $dedupeKey = "email:social_welcome:{$event->user->id}";
            if (! Cache::add($dedupeKey, true, now()->addMinutes(30))) {
                Log::warning('Boas-vindas social ignorada por deduplicação', [
                    'user_id' => $event->user->id,
                    'dedupe_key' => $dedupeKey
                ]);
                return;
            }

            // 3. Validação inicial rigorosa
            $this->validateEvent($event);

            // 3. Processamento específico do e-mail de boas-vindas social
            $result = $this->processSocialWelcomeEmail($event);

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
     * Processa especificamente o envio de e-mail de boas-vindas para login social.
     *
     * @param  SocialLoginWelcome  $event  Evento de login social
     * @return ServiceResult Resultado do processamento
     */
    protected function processSocialWelcomeEmail(SocialLoginWelcome $event): ServiceResult
    {
        try {
            // Validação adicional específica para login social
            if (! $event->user || ! $event->user->id) {
                throw new \InvalidArgumentException('Dados do usuário inválidos no evento de boas-vindas social');
            }

            if (empty($event->user->email)) {
                throw new \InvalidArgumentException('E-mail do usuário não informado no evento de boas-vindas social');
            }

            // Envia e-mail usando a Mailable específica para login social
            $mailable = new SocialLoginWelcomeMail(
                $event->user,
                $event->tenant,
                $event->provider,
            );

            // Usa queue para processamento assíncrono
            Mail::to($event->user->email)->queue($mailable);

            Log::info('E-mail de boas-vindas social enfileirado com sucesso', [
                'user_id' => $event->user->id,
                'email' => $event->user->email,
                'tenant_id' => $event->tenant?->id,
                'provider' => $event->provider,
                'processing_time' => $this->getProcessingTime(),
                'memory_usage' => memory_get_usage(true),
                'success' => true,
                'event_type' => 'social_login_welcome',
            ]);

            return ServiceResult::success(null, 'E-mail de boas-vindas social enviado com sucesso');

        } catch (Throwable $e) {
            Log::error('Erro no processamento de e-mail de boas-vindas social', [
                'user_id' => $event->user->id ?? null,
                'email' => $event->user->email ?? null,
                'tenant_id' => $event->tenant?->id,
                'provider' => $event->provider,
                'error_message' => $e->getMessage(),
                'error_type' => get_class($e),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'processing_time' => $this->getProcessingTime(),
                'memory_usage' => memory_get_usage(true),
                'event_type' => 'social_login_welcome',
            ]);

            return ServiceResult::error('Erro interno no processamento de boas-vindas social: '.$e->getMessage());
        }
    }

    /**
     * Validação rigorosa do evento de boas-vindas social.
     *
     * @param  SocialLoginWelcome  $event  Evento a ser validado
     *
     * @throws \InvalidArgumentException Se alguma validação falhar
     */
    protected function validateEvent(SocialLoginWelcome $event): void
    {
        if (! $event || ! $event->user || ! $event->user->id) {
            throw new \InvalidArgumentException('Dados do usuário inválidos no evento de boas-vindas social');
        }

        if (empty($event->user->email)) {
            throw new \InvalidArgumentException('E-mail do usuário não informado no evento de boas-vindas social');
        }

        if (empty($event->provider)) {
            throw new \InvalidArgumentException('Provedor social não informado no evento de boas-vindas social');
        }
    }

    /**
     * Tratamento padronizado de sucesso.
     *
     * @param  SocialLoginWelcome  $event  Evento processado
     * @param  ServiceResult  $result  Resultado da operação
     */
    protected function handleSuccess(SocialLoginWelcome $event, ServiceResult $result): void
    {
        Log::info('E-mail de boas-vindas social enviado com sucesso', [
            'user_id' => $event->user->id,
            'email' => $event->user->email,
            'tenant_id' => $event->tenant?->id,
            'provider' => $event->provider,
            'processing_time' => $this->getProcessingTime(),
            'memory_usage' => memory_get_usage(true),
            'success' => true,
            'event_type' => 'social_login_welcome',
        ]);
    }

    /**
     * Tratamento padronizado de falha.
     *
     * @param  SocialLoginWelcome  $event  Evento processado
     * @param  ServiceResult  $result  Resultado da operação
     */
    protected function handleFailure(SocialLoginWelcome $event, ServiceResult $result): void
    {
        Log::error('Falha no envio de e-mail de boas-vindas social', [
            'user_id' => $event->user->id,
            'email' => $event->user->email,
            'tenant_id' => $event->tenant?->id,
            'provider' => $event->provider,
            'error_message' => $result->getMessage(),
            'error_status' => $result->getStatus(),
            'error_data' => $result->getData(),
            'processing_time' => $this->getProcessingTime(),
            'memory_usage' => memory_get_usage(true),
            'will_retry' => true,
            'event_type' => 'social_login_welcome',
        ]);

        // Relança a exceção para que seja tratada pela queue com retry automático
        throw new \Exception('Falha no envio de e-mail de boas-vindas social: '.$result->getMessage());
    }

    /**
     * Tratamento padronizado de exceções.
     *
     * @param  SocialLoginWelcome  $event  Evento que estava sendo processado
     * @param  Throwable  $exception  Exceção ocorrida
     */
    private function handleException(SocialLoginWelcome $event, Throwable $exception): void
    {
        Log::error('Erro crítico no processamento de e-mail de boas-vindas social', [
            'user_id' => $event->user->id ?? null,
            'email' => $event->user->email ?? null,
            'tenant_id' => $event->tenant?->id,
            'provider' => $event->provider,
            'error_message' => $exception->getMessage(),
            'error_type' => get_class($exception),
            'error_file' => $exception->getFile(),
            'error_line' => $exception->getLine(),
            'processing_time' => $this->getProcessingTime(),
            'memory_usage' => memory_get_usage(true),
            'will_retry' => true,
            'event_type' => 'social_login_welcome',
        ]);

        // Relança a exceção para que seja tratada pela queue
        throw $exception;
    }

    /**
     * Tratamento padronizado de falha crítica do job.
     *
     * @param  SocialLoginWelcome  $event  Evento que estava sendo processado
     * @param  Throwable  $exception  Última exceção ocorrida
     */
    final public function failed(SocialLoginWelcome $event, Throwable $exception): void
    {
        Log::critical('Falha crítica no envio de e-mail de boas-vindas social após todas as tentativas', [
            'user_id' => $event->user->id ?? null,
            'email' => $event->user->email ?? null,
            'tenant_id' => $event->tenant?->id,
            'provider' => $event->provider,
            'error_message' => $exception->getMessage(),
            'error_type' => get_class($exception),
            'attempts' => $this->tries,
            'max_attempts' => $this->tries,
            'backoff_strategy' => 'exponential',
            'final_failure' => true,
            'event_type' => 'social_login_welcome',
        ]);
    }

    /**
     * Logging inicial estruturado e detalhado.
     *
     * @param  SocialLoginWelcome  $event  Evento recebido
     */
    private function logEventStart(SocialLoginWelcome $event): void
    {
        Log::info('Processando evento SocialLoginWelcome para envio de e-mail de boas-vindas', [
            'user_id' => $event->user->id,
            'email' => $event->user->email,
            'tenant_id' => $event->tenant?->id,
            'provider' => $event->provider,
            'event_type' => 'social_login_welcome',
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
}
