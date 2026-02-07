<?php

declare(strict_types=1);

namespace App\Jobs;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Job para envio assíncrono de e-mails com funcionalidades básicas.
 *
 * Funcionalidades implementadas:
 * - Sistema de retry básico
 * - Logging de operações
 * - Tratamento de erros
 * - Suporte a anexos
 */
class SendEmailJob implements ShouldQueue
{
    use Queueable;

    /**
     * Número máximo de tentativas.
     */
    public int $tries = 3;

    /**
     * Timeout em segundos.
     */
    public int $timeout = 60;

    /**
     * Dados do e-mail.
     */
    private array $emailData;

    /**
     * Create a new job instance.
     */
    public function __construct(array $emailData)
    {
        $this->emailData = $emailData;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info('Processando envio de e-mail', [
                'to' => $this->emailData['to'],
                'subject' => $this->emailData['subject'],
            ]);

            // Enviar e-mail
            Mail::send([], [], function ($message) {
                $message->to($this->emailData['to'])
                    ->subject($this->emailData['subject'])
                    ->html($this->emailData['body']);

                // Anexo opcional
                if (isset($this->emailData['attachment'])) {
                    $attachment = $this->emailData['attachment'];
                    if (isset($attachment['content'])) {
                        $message->attachData(
                            $attachment['content'],
                            $attachment['fileName'] ?? 'attachment.pdf',
                            ['mime' => $attachment['mime'] ?? 'application/pdf'],
                        );
                    }
                }
            });

            Log::info('E-mail enviado com sucesso', [
                'to' => $this->emailData['to'],
            ]);

        } catch (Exception $e) {
            Log::error('Erro no envio de e-mail', [
                'to' => $this->emailData['to'],
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Calcula delay para retry.
     */
    public function backoff(): array
    {
        return [30, 60, 120];
    }

    /**
     * Trata falha do job.
     */
    public function failed(Exception $exception): void
    {
        Log::critical('Job de e-mail falhou permanentemente', [
            'to' => $this->emailData['to'] ?? 'unknown',
            'error' => $exception->getMessage(),
        ]);
    }
}
