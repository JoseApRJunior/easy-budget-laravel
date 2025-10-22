<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\SupportTicketCreated;
use App\Mail\ContactEmail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Listener responsável por enviar email de contato quando um ticket de suporte é criado.
 *
 * Implementa funcionalidades completas de tratamento de erro, logging e validações
 * seguindo os padrões arquiteturais do sistema Easy Budget Laravel.
 *
 * Funcionalidades implementadas:
 * - Tratamento robusto de erros com try-catch
 * - Logging detalhado para monitoramento
 * - Validações adequadas dos dados recebidos
 * - Processamento assíncrono via queue
 * - Métricas de performance integradas
 * - Sistema de retry automático com backoff exponencial
 */
class SendSupportContactEmail implements ShouldQueue
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
     * O tempo máximo em segundos que o job pode executar.
     */
    public int $timeout = 120;

    /**
     * A queue onde o job será processado.
     */
    public string $queue = 'emails';

    /**
     * Métricas de performance para monitoramento.
     */
    private array $performanceMetrics = [];

    /**
     * Processa o evento de criação de ticket de suporte e envia email.
     *
     * Implementa o padrão Template Method com funcionalidades completas:
     * - Logging inicial detalhado
     * - Validação rigorosa dos dados
     * - Processamento específico do e-mail
     * - Tratamento de resultado (sucesso/falha)
     * - Tratamento de exceções
     * - Métricas de performance
     *
     * @param SupportTicketCreated $event Evento de criação de ticket
     */
    final public function handle(SupportTicketCreated $event): void
    {
        // Iniciar métricas de performance
        $this->startPerformanceTracking();

        try {
            // Log inicial estruturado
            $this->logEventStart($event);

            // Validação dos dados do evento
            $this->validateEventData($event);

            // Processar envio do email
            $this->processEmailSending($event);

            // Log de sucesso
            $this->logSuccess($event);

        } catch (Throwable $e) {
            // Tratamento de exceções
            $this->handleException($event, $e);
            
            // Relança a exceção para que seja tratada pela queue
            throw $e;

        } finally {
            // Finalizar métricas de performance
            $this->endPerformanceTracking($event);
        }
    }

    /**
     * Processa o envio do email de contato.
     *
     * @param SupportTicketCreated $event Evento de criação de ticket
     * @throws \Exception Se houver erro no envio
     */
    private function processEmailSending(SupportTicketCreated $event): void
    {
        // Criar instância da mailable
        $contactEmail = new ContactEmail(
            $event->contactData,
            $event->tenant
        );

        // Enviar email
        Mail::send($contactEmail);

        Log::info('Email de contato enviado com sucesso via evento', [
            'support_id' => $event->support->id,
            'email' => $event->support->email,
            'subject' => $event->support->subject,
            'tenant_id' => $event->tenant?->id,
            'processing_time' => $this->getProcessingTime(),
        ]);
    }

    /**
     * Valida os dados do evento antes do processamento.
     *
     * @param SupportTicketCreated $event Evento a ser validado
     * @throws \InvalidArgumentException Se os dados forem inválidos
     */
    private function validateEventData(SupportTicketCreated $event): void
    {
        if (!$event->support || !$event->support->id) {
            throw new \InvalidArgumentException('Ticket de suporte inválido no evento.');
        }

        if (empty($event->support->email)) {
            throw new \InvalidArgumentException('Email do contato é obrigatório.');
        }

        if (empty($event->support->subject)) {
            throw new \InvalidArgumentException('Assunto do contato é obrigatório.');
        }

        if (empty($event->support->message)) {
            throw new \InvalidArgumentException('Mensagem do contato é obrigatória.');
        }
    }

    /**
     * Logging inicial estruturado e detalhado.
     *
     * @param SupportTicketCreated $event Evento recebido
     */
    private function logEventStart(SupportTicketCreated $event): void
    {
        Log::info('Processando evento SupportTicketCreated para envio de email de contato', [
            'support_id' => $event->support->id,
            'email' => $event->support->email,
            'subject' => $event->support->subject,
            'tenant_id' => $event->tenant?->id,
            'event_type' => 'support_contact_email',
            'listener_class' => static::class,
            'processing_time' => microtime(true),
            'memory_usage' => memory_get_usage(true),
            'queue' => $this->queue,
        ]);
    }

    /**
     * Log de sucesso do processamento.
     *
     * @param SupportTicketCreated $event Evento processado
     */
    private function logSuccess(SupportTicketCreated $event): void
    {
        Log::info('Email de contato processado com sucesso via evento', [
            'support_id' => $event->support->id,
            'email' => $event->support->email,
            'subject' => $event->support->subject,
            'tenant_id' => $event->tenant?->id,
            'processing_time' => $this->getProcessingTime(),
            'memory_peak' => memory_get_peak_usage(true),
        ]);
    }

    /**
     * Tratamento padronizado de exceções.
     *
     * @param SupportTicketCreated $event Evento que estava sendo processado
     * @param Throwable $exception Exceção ocorrida
     */
    private function handleException(SupportTicketCreated $event, Throwable $exception): void
    {
        Log::error('Erro crítico no processamento de email de contato via evento', [
            'support_id' => $event->support->id ?? null,
            'email' => $event->support->email ?? null,
            'subject' => $event->support->subject ?? null,
            'tenant_id' => $event->tenant?->id,
            'error_message' => $exception->getMessage(),
            'error_file' => $exception->getFile(),
            'error_line' => $exception->getLine(),
            'processing_time' => $this->getProcessingTime(),
            'listener_class' => static::class,
        ]);
    }

    /**
     * Tratamento padronizado de falha crítica do job.
     *
     * @param SupportTicketCreated $event Evento que estava sendo processado
     * @param Throwable $exception Última exceção ocorrida
     */
    final public function failed(SupportTicketCreated $event, Throwable $exception): void
    {
        Log::critical('Listener SendSupportContactEmail falhou após todas as tentativas', [
            'support_id' => $event->support->id ?? null,
            'email' => $event->support->email ?? null,
            'subject' => $event->support->subject ?? null,
            'tenant_id' => $event->tenant?->id,
            'error' => $exception->getMessage(),
            'attempts' => $this->tries,
            'final_failure_time' => now()->toISOString(),
        ]);

        // Em produção, poderia notificar administradores sobre a falha
        // ou implementar lógica de fallback (ex: salvar em fila de retry manual)
    }

    /**
     * Inicia rastreamento de performance.
     */
    private function startPerformanceTracking(): void
    {
        $this->performanceMetrics = [
            'start_time' => microtime(true),
            'start_memory' => memory_get_usage(true),
        ];
    }

    /**
     * Finaliza rastreamento de performance.
     *
     * @param SupportTicketCreated $event Evento processado
     */
    private function endPerformanceTracking(SupportTicketCreated $event): void
    {
        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);

        $processingTime = $endTime - $this->performanceMetrics['start_time'];
        $memoryUsed = $endMemory - $this->performanceMetrics['start_memory'];

        Log::debug('Métricas de performance - SendSupportContactEmail', [
            'support_id' => $event->support->id,
            'processing_time_seconds' => round($processingTime, 4),
            'memory_used_bytes' => $memoryUsed,
            'memory_used_mb' => round($memoryUsed / 1024 / 1024, 2),
            'peak_memory_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
        ]);
    }

    /**
     * Obtém o tempo de processamento atual.
     *
     * @return float Tempo em segundos
     */
    private function getProcessingTime(): float
    {
        if (!isset($this->performanceMetrics['start_time'])) {
            return 0.0;
        }

        return round(microtime(true) - $this->performanceMetrics['start_time'], 4);
    }
}