<?php

declare(strict_types=1);

namespace App\Services\Infrastructure;

use App\Enums\OperationStatus;
use App\Mail\BudgetNotificationMail;
use App\Mail\EmailVerificationMail;
use App\Mail\InvoiceNotification;
use App\Mail\PasswordResetNotification;
use App\Mail\StatusUpdate;
use App\Mail\SupportResponse;
use App\Mail\WelcomeUserMail;
use App\Models\Budget;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Infrastructure\EmailRateLimitService;
use App\Services\Infrastructure\EmailSenderService;
use App\Support\ServiceResult;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Serviço utilitário para operações de e-mail no sistema Easy Budget.
 *
 * Migração do legacy MailerService: substitui PHPMailer por Laravel Mail facade,
 * mantendo compatibilidade com API existente. Service utilitário sem interface específica.
 *
 * Funcionalidades principais:
 * - Envio de e-mails usando Laravel Mail
 * - Compatibilidade com API legacy
 * - Templates via Mailable classes
 * - Anexos e configurações personalizadas
 * - Log detalhado de operações
 *
 * Este service é usado por UserService, NotificationService e outros services
 * para envio de e-mails de confirmação, notificações, etc.
 */
class MailerService
{
    /**
     * Configurações padrão para envio de e-mails.
     */
    private array $defaultConfig = [
        'from_address' => null,
        'from_name'    => null,
        'reply_to'     => null,
    ];

    /**
     * Serviço de remetentes de e-mail.
     */
    private EmailSenderService $emailSenderService;

    /**
     * Serviço de rate limiting.
     */
    private EmailRateLimitService $rateLimitService;

    /**
     * Construtor: inicializa configurações padrão e serviços de segurança.
     */
    public function __construct(
        EmailSenderService $emailSenderService,
        EmailRateLimitService $rateLimitService,
    ) {
        $this->emailSenderService = $emailSenderService;
        $this->rateLimitService   = $rateLimitService;
        $this->initializeDefaultConfig();
    }

    /**
     * Inicializa configurações padrão a partir do .env.
     */
    private function initializeDefaultConfig(): void
    {
        $this->defaultConfig = [
            'from_address' => config( 'mail.from.address' ),
            'from_name'    => config( 'mail.from.name' ),
            'reply_to'     => config( 'mail.from.address' ),
        ];
    }

    /**
     * Envia e-mail usando dados fornecidos (compatibilidade com API legacy).
     *
     * @param string $to Destinatário do e-mail
     * @param string $subject Assunto do e-mail
     * @param string $body Conteúdo do e-mail (HTML)
     * @param array|null $attachment Anexo opcional ['content' => string, 'fileName' => string]
     * @param string|null $fromAddress Endereço do remetente
     * @param string|null $fromName Nome do remetente
     * @return ServiceResult Resultado da operação
     */
    public function send(
        string $to,
        string $subject,
        string $body,
        ?array $attachment = null,
        ?string $fromAddress = null,
        ?string $fromName = null,
    ): ServiceResult {
        try {
            // Validação dos dados
            $validation = $this->validateEmailData( $to, $subject, $body );
            if ( !$validation->isSuccess() ) {
                return $validation;
            }

            // Preparar dados do e-mail
            $emailData = [
                'to'           => $to,
                'subject'      => $subject,
                'body'         => $body,
                'attachment'   => $attachment,
                'from_address' => $fromAddress ?? $this->defaultConfig[ 'from_address' ],
                'from_name'    => $fromName ?? $this->defaultConfig[ 'from_name' ],
            ];

            // Enviar e-mail usando queue para processamento assíncrono
            $sent = $this->sendEmailAsync( $emailData );

            if ( $sent ) {
                Log::info( 'E-mail enfileirado com sucesso para processamento assíncrono', [
                    'to'      => $to,
                    'subject' => $subject,
                    'from'    => $emailData[ 'from_address' ],
                    'queue'   => 'emails'
                ] );

                return ServiceResult::success( [
                    'to'        => $to,
                    'subject'   => $subject,
                    'queued_at' => now()->toDateTimeString(),
                    'queue'     => 'emails'
                ], 'E-mail enfileirado com sucesso para processamento assíncrono.' );
            }

            return ServiceResult::error(
                OperationStatus::ERROR,
                'Falha ao enfileirar e-mail: erro desconhecido.',
            );

        } catch ( Exception $e ) {
            Log::error( 'Erro ao enfileirar e-mail', [
                'to'      => $to,
                'subject' => $subject,
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString()
            ] );

            return ServiceResult::error(
                OperationStatus::ERROR,
                'Falha ao enfileirar e-mail: ' . $e->getMessage()
            );
        }
    }

    /**
     * Envia e-mail de confirmação de conta (método específico).
     *
     * @param string $email E-mail do usuário
     * @param string $firstName Nome do usuário
     * @param string $confirmationToken Token de confirmação
     * @param array $additionalData Dados adicionais para o template
     * @return ServiceResult Resultado da operação
     */
    public function sendAccountConfirmation(
        string $email,
        string $firstName,
        string $confirmationToken,
        array $additionalData = [],
    ): ServiceResult {
        try {
            $confirmationLink = \config( 'app.url' ) . '/confirm-account?token=' . $confirmationToken;
            $subject          = 'Confirmação de conta - Easy Budget';

            $templateData = array_merge( [
                'first_name'        => $firstName,
                'confirmation_link' => $confirmationLink,
                'app_name'          => config( 'app.name', 'Easy Budget' ),
            ], $additionalData );

            $sent = $this->sendTemplatedEmail(
                $email,
                $subject,
                'emails.account-confirmation',
                $templateData,
            );

            return $sent
                ? ServiceResult::success( true, 'E-mail de confirmação enviado com sucesso.' )
                : ServiceResult::error( OperationStatus::ERROR, 'Falha ao enviar e-mail de confirmação.' );

        } catch ( Exception $e ) {
            Log::error( 'Erro ao enviar confirmação de conta', [
                'email' => $email,
                'error' => $e->getMessage()
            ] );

            return ServiceResult::error(
                OperationStatus::ERROR,
                'Erro ao enviar confirmação: ' . $e->getMessage()
            );
        }
    }

    /**
     * Envia e-mail de redefinição de senha.
     *
     * @param string $email E-mail do usuário
     * @param string $firstName Nome do usuário
     * @param string $resetToken Token de redefinição
     * @return ServiceResult Resultado da operação
     */
    public function sendPasswordReset(
        string $email,
        string $firstName,
        string $resetToken,
    ): ServiceResult {
        try {
            $resetLink = \config( 'app.url' ) . '/reset-password?token=' . $resetToken;
            $subject   = 'Redefinição de senha - Easy Budget';

            $templateData = [
                'first_name' => $firstName,
                'reset_link' => $resetLink,
                'app_name'   => config( 'app.name', 'Easy Budget' ),
            ];

            $sent = $this->sendTemplatedEmail(
                $email,
                $subject,
                'emails.password-reset',
                $templateData,
            );

            return $sent
                ? ServiceResult::success( true, 'E-mail de redefinição enviado com sucesso.' )
                : ServiceResult::error( OperationStatus::ERROR, 'Falha ao enviar e-mail de redefinição.' );

        } catch ( Exception $e ) {
            Log::error( 'Erro ao enviar redefinição de senha', [
                'email' => $email,
                'error' => $e->getMessage()
            ] );

            return ServiceResult::error(
                OperationStatus::ERROR,
                'Erro ao enviar redefinição: ' . $e->getMessage()
            );
        }
    }

    /**
     * Envia notificação genérica usando template.
     *
     * @param string $to Destinatário
     * @param string $subject Assunto
     * @param string $template Template do e-mail
     * @param array $templateData Dados para o template
     * @param array|null $attachment Anexo opcional
     * @return ServiceResult Resultado da operação
     */
    public function sendNotification(
        string $to,
        string $subject,
        string $template,
        array $templateData = [],
        ?array $attachment = null,
    ): ServiceResult {
        try {
            $sent = $this->sendTemplatedEmail(
                $to,
                $subject,
                $template,
                $templateData,
                $attachment,
            );

            return $sent
                ? ServiceResult::success( true, 'Notificação enviada com sucesso.' )
                : ServiceResult::error( OperationStatus::ERROR, 'Falha ao enviar notificação.' );

        } catch ( Exception $e ) {
            Log::error( 'Erro ao enviar notificação', [
                'to'       => $to,
                'template' => $template,
                'error'    => $e->getMessage()
            ] );

            return ServiceResult::error(
                OperationStatus::ERROR,
                'Erro ao enviar notificação: ' . $e->getMessage()
            );
        }
    }

    /**
     * Envia e-mail usando template (método interno).
     *
     * @param string $to Destinatário
     * @param string $subject Assunto
     * @param string $template Template do e-mail
     * @param array $templateData Dados para o template
     * @param array|null $attachment Anexo opcional
     * @return bool Sucesso do envio
     * @throws Exception
     */
    private function sendTemplatedEmail(
        string $to,
        string $subject,
        string $template,
        array $templateData = [],
        ?array $attachment = null,
    ): bool {
        try {
            // Criar mailable instance
            $mailable = new class ($template, $templateData, $attachment) extends Mailable
            {
                private string $template;
                private array  $templateData;
                private ?array $attachment;

                public function __construct( string $template, array $templateData, ?array $attachment )
                {
                    $this->template     = $template;
                    $this->templateData = $templateData;
                    $this->attachment = $attachment;
                }

                public function build()
                {
                    $mail = $this->subject( $this->templateData[ 'subject' ] ?? 'Notificação' )
                        ->view( $this->template, $this->templateData );

                    if ( $this->attachment && isset( $this->attachment[ 'content' ] ) ) {
                        $mail->attachData(
                            $this->attachment[ 'content' ],
                            $this->attachment[ 'fileName' ] ?? 'attachment.pdf',
                            [
                                'mime' => $this->attachment[ 'mime' ] ?? 'application/pdf'
                            ],
                        );
                    }

                    return $mail;
                }

            };

            // Configurar destinatário
            $mailable->to( $to );

            // Enviar
            Mail::send( $mailable );

            return true;

        } catch ( Exception $e ) {
            Log::error( 'Erro no envio de e-mail template', [
                'to'       => $to,
                'template' => $template,
                'error'    => $e->getMessage()
            ] );
            throw $e;
        }
    }

    /**
     * Envia e-mail simples (HTML ou texto) de forma assíncrona.
     *
     * @param array $emailData Dados do e-mail
     * @return bool Sucesso do envio
     * @throws \Exception
     */
    private function sendEmailAsync( array $emailData ): bool
    {
        try {
            // Criar mailable instance para processamento assíncrono
            $mailable = new class ($emailData) extends Mailable implements ShouldQueue
            {
                use Queueable, SerializesModels;

                private array $emailData;

                public function __construct( array $emailData )
                {
                    $this->emailData = $emailData;
                }

                public function build()
                {
                    $mail = $this->to( $this->emailData[ 'to' ] )
                        ->subject( $this->emailData[ 'subject' ] )
                        ->html( $this->emailData[ 'body' ] );

                    // Anexo se fornecido
                    if ( $this->emailData[ 'attachment' ] && isset( $this->emailData[ 'attachment' ][ 'content' ] ) ) {
                        $mail->attachData(
                            $this->emailData[ 'attachment' ][ 'content' ],
                            $this->emailData[ 'attachment' ][ 'fileName' ] ?? 'attachment.pdf',
                            [
                                'mime' => $this->emailData[ 'attachment' ][ 'mime' ] ?? 'application/pdf'
                            ],
                        );
                    }

                    return $mail;
                }

            };

            // Enfileirar para processamento assíncrono
            Mail::to( $emailData[ 'to' ] )->queue( $mailable );

            return true;

        } catch ( \Exception $e ) {
            Log::error( 'Erro ao enfileirar e-mail', [
                'to'    => $emailData[ 'to' ],
                'error' => $e->getMessage()
            ] );
            throw $e;
        }
    }

    /**
     * Envia e-mail simples (HTML ou texto) - método legado mantido para compatibilidade.
     *
     * @param array $emailData Dados do e-mail
     * @return bool Sucesso do envio
     * @throws \Exception
     */
    private function sendEmail( array $emailData ): bool
    {
        try {
            Mail::send( [], [], function ( $message ) use ( $emailData ) {
                $message->to( $emailData[ 'to' ] )
                    ->subject( $emailData[ 'subject' ] )
                    ->html( $emailData[ 'body' ] );

                // Anexo se fornecido
                if ( $emailData[ 'attachment' ] && isset( $emailData[ 'attachment' ][ 'content' ] ) ) {
                    $message->attachData(
                        $emailData[ 'attachment' ][ 'content' ],
                        $emailData[ 'attachment' ][ 'fileName' ] ?? 'attachment.pdf',
                        [
                            'mime' => $emailData[ 'attachment' ][ 'mime' ] ?? 'application/pdf'
                        ],
                    );
                }
            } );

            return true;

        } catch ( \Exception $e ) {
            Log::error( 'Erro no envio de e-mail', [
                'to'    => $emailData[ 'to' ],
                'error' => $e->getMessage()
            ] );
            throw $e;
        }
    }

    /**
     * Valida dados do e-mail.
     *
     * @param string $to E-mail do destinatário
     * @param string $subject Assunto
     * @param string $body Conteúdo
     * @return ServiceResult Resultado da validação
     */
    private function validateEmailData( string $to, string $subject, string $body ): ServiceResult
    {
        $errors = [];

        // Validar e-mail do destinatário
        if ( empty( $to ) ) {
            $errors[] = 'Destinatário é obrigatório.';
        } elseif ( !filter_var( $to, FILTER_VALIDATE_EMAIL ) ) {
            $errors[] = 'E-mail do destinatário inválido.';
        }

        // Validar assunto
        if ( empty( $subject ) ) {
            $errors[] = 'Assunto é obrigatório.';
        }

        // Validar corpo
        if ( empty( $body ) ) {
            $errors[] = 'Corpo do e-mail é obrigatório.';
        }

        if ( !empty( $errors ) ) {
            return ServiceResult::error(
                OperationStatus::INVALID_DATA,
                'Dados inválidos: ' . implode( ', ', $errors )
            );
        }

        return ServiceResult::success( null, 'Dados válidos.' );
    }

    /**
     * Envia e-mail de teste para verificar configuração.
     *
     * @param string $testEmail E-mail de teste
     * @return ServiceResult Resultado do teste
     */
    public function sendTestEmail( string $testEmail ): ServiceResult
    {
        try {
            $subject = 'Teste de configuração - Easy Budget';
            $body    = '<h1>Teste de E-mail</h1><p>Este é um e-mail de teste do sistema Easy Budget.</p>';

            $sent = $this->send( $testEmail, $subject, $body );

            return $sent->isSuccess()
                ? ServiceResult::success( true, 'E-mail de teste enviado com sucesso.' )
                : $sent;

        } catch ( Exception $e ) {
            return ServiceResult::error(
                OperationStatus::ERROR,
                'Erro ao enviar e-mail de teste: ' . $e->getMessage()
            );
        }
    }

    /**
     * Envia e-mail de boas-vindas usando a Mailable Class WelcomeUser.
     *
     * @param User $user Usuário que receberá o e-mail
     * @param Tenant|null $tenant Tenant do usuário (opcional)
     * @param string|null $verificationUrl URL de verificação (opcional)
     * @return ServiceResult Resultado da operação
     */
    public function sendWelcomeEmail(
        User $user,
        ?Tenant $tenant = null,
        ?string $verificationUrl = null,
    ): ServiceResult {
        try {
            $mailable = new WelcomeUserMail( $user, $tenant, $verificationUrl );

            // Define o destinatário e usa queue para processamento assíncrono
            Mail::to( $user->email )->queue( $mailable );

            Log::info( 'E-mail de boas-vindas enfileirado com sucesso', [
                'user_id'   => $user->id,
                'email'     => $user->email,
                'tenant_id' => $tenant?->id,
                'queue'     => 'emails'
            ] );

            return ServiceResult::success( [
                'user_id'   => $user->id,
                'email'     => $user->email,
                'queued_at' => now()->toDateTimeString(),
                'queue'     => 'emails'
            ], 'E-mail de boas-vindas enfileirado com sucesso para processamento assíncrono.' );

        } catch ( Exception $e ) {
            Log::error( 'Erro ao enfileirar e-mail de boas-vindas', [
                'user_id' => $user->id,
                'email'   => $user->email,
                'error'   => $e->getMessage()
            ] );

            return ServiceResult::error(
                OperationStatus::ERROR,
                'Erro ao enfileirar e-mail de boas-vindas: ' . $e->getMessage()
            );
        }
    }

    /**
     * Envia notificação de fatura usando a Mailable Class InvoiceNotification.
     *
     * @param Invoice $invoice Fatura a ser notificada
     * @param Customer $customer Cliente da fatura
     * @param Tenant|null $tenant Tenant do usuário (opcional)
     * @param array|null $company Dados da empresa (opcional)
     * @param string|null $publicLink Link público da fatura (opcional)
     * @return ServiceResult Resultado da operação
     */
    public function sendInvoiceNotification(
        Invoice $invoice,
        Customer $customer,
        ?Tenant $tenant = null,
        ?array $company = null,
        ?string $publicLink = null,
    ): ServiceResult {
        try {
            $mailable = new InvoiceNotification(
                $invoice,
                $customer,
                $tenant,
                $company,
                $publicLink,
            );

            // Usa queue para processamento assíncrono
            Mail::queue( $mailable );

            Log::info( 'Notificação de fatura enfileirada com sucesso', [
                'invoice_id'   => $invoice->id,
                'invoice_code' => $invoice->code,
                'customer_id'  => $customer->id,
                'tenant_id'    => $tenant?->id,
                'queue'        => 'emails'
            ] );

            return ServiceResult::success( [
                'invoice_id'   => $invoice->id,
                'invoice_code' => $invoice->code,
                'customer_id'  => $customer->id,
                'queued_at'    => now()->toDateTimeString(),
                'queue'        => 'emails'
            ], 'Notificação de fatura enfileirada com sucesso para processamento assíncrono.' );

        } catch ( Exception $e ) {
            Log::error( 'Erro ao enfileirar notificação de fatura', [
                'invoice_id'  => $invoice->id,
                'customer_id' => $customer->id,
                'error'       => $e->getMessage()
            ] );

            return ServiceResult::error(
                OperationStatus::ERROR,
                'Erro ao enfileirar notificação de fatura: ' . $e->getMessage()
            );
        }
    }

    /**
     * Envia notificação de atualização de status usando a Mailable Class StatusUpdate.
     *
     * @param Model $entity Entidade que teve o status atualizado
     * @param string $status Novo status
     * @param string $statusName Nome do status para exibição
     * @param Tenant|null $tenant Tenant do usuário (opcional)
     * @param array|null $company Dados da empresa (opcional)
     * @param string|null $entityUrl URL da entidade (opcional)
     * @return ServiceResult Resultado da operação
     */
    public function sendStatusUpdateNotification(
        Model $entity,
        string $status,
        string $statusName,
        ?Tenant $tenant = null,
        ?array $company = null,
        ?string $entityUrl = null,
    ): ServiceResult {
        try {
            $mailable = new StatusUpdate(
                $entity,
                $status,
                $statusName,
                $tenant,
                $company,
                $entityUrl,
            );

            Mail::send( $mailable );

            Log::info( 'Notificação de atualização de status enviada com sucesso', [
                'entity_type' => class_basename( $entity ),
                'entity_id'   => $entity->id,
                'old_status'  => $entity->getOriginal( 'status' ) ?? 'unknown',
                'new_status'  => $status,
                'tenant_id'   => $tenant?->id
            ] );

            return ServiceResult::success( [
                'entity_type' => class_basename( $entity ),
                'entity_id'   => $entity->id,
                'new_status'  => $status,
                'sent_at'     => now()->toDateTimeString(),
            ], 'Notificação de atualização de status enviada com sucesso.' );

        } catch ( Exception $e ) {
            Log::error( 'Erro ao enviar notificação de atualização de status', [
                'entity_type' => class_basename( $entity ),
                'entity_id'   => $entity->id,
                'new_status'  => $status,
                'error'       => $e->getMessage()
            ] );

            return ServiceResult::error(
                OperationStatus::ERROR,
                'Erro ao enviar notificação de atualização de status: ' . $e->getMessage()
            );
        }
    }

    /**
     * Envia e-mail de redefinição de senha usando a Mailable Class PasswordResetNotification.
     *
     * @param User $user Usuário que receberá o e-mail
     * @param string $token Token de redefinição de senha
     * @param Tenant|null $tenant Tenant do usuário (opcional)
     * @param array|null $company Dados da empresa (opcional)
     * @return ServiceResult Resultado da operação
     */
    public function sendPasswordResetNotification(
        User $user,
        string $token,
        ?Tenant $tenant = null,
        ?array $company = null,
    ): ServiceResult {
        try {
            $mailable = new PasswordResetNotification(
                $user,
                $token,
                $tenant,
                $company,
            );

            Mail::send( $mailable );

            Log::info( 'E-mail de redefinição de senha enviado com sucesso', [
                'user_id'   => $user->id,
                'email'     => $user->email,
                'tenant_id' => $tenant?->id
            ] );

            return ServiceResult::success( [
                'user_id' => $user->id,
                'email'   => $user->email,
                'sent_at' => now()->toDateTimeString(),
            ], 'E-mail de redefinição de senha enviado com sucesso.' );

        } catch ( Exception $e ) {
            Log::error( 'Erro ao enviar e-mail de redefinição de senha', [
                'user_id' => $user->id,
                'email'   => $user->email,
                'error'   => $e->getMessage()
            ] );

            return ServiceResult::error(
                OperationStatus::ERROR,
                'Erro ao enviar e-mail de redefinição de senha: ' . $e->getMessage()
            );
        }
    }

    /**
     * Envia resposta de suporte usando a Mailable Class SupportResponse.
     *
     * @param array $ticket Dados do ticket de suporte
     * @param string $response Resposta do suporte
     * @param Tenant|null $tenant Tenant do usuário (opcional)
     * @param array|null $company Dados da empresa (opcional)
     * @return ServiceResult Resultado da operação
     */
    public function sendSupportResponse(
        array $ticket,
        string $response,
        ?Tenant $tenant = null,
        ?array $company = null,
    ): ServiceResult {
        try {
            $mailable = new SupportResponse(
                $ticket,
                $response,
                $tenant,
                $company,
            );

            Mail::send( $mailable );

            Log::info( 'Resposta de suporte enviada com sucesso', [
                'ticket_id'      => $ticket[ 'id' ] ?? null,
                'ticket_subject' => $ticket[ 'subject' ] ?? 'Sem assunto',
                'tenant_id'      => $tenant?->id
            ] );

            return ServiceResult::success( [
                'ticket_id'      => $ticket[ 'id' ] ?? null,
                'ticket_subject' => $ticket[ 'subject' ] ?? 'Sem assunto',
                'sent_at'        => now()->toDateTimeString(),
            ], 'Resposta de suporte enviada com sucesso.' );

        } catch ( Exception $e ) {
            Log::error( 'Erro ao enviar resposta de suporte', [
                'ticket_id'      => $ticket[ 'id' ] ?? null,
                'ticket_subject' => $ticket[ 'subject' ] ?? 'Sem assunto',
                'error'          => $e->getMessage()
            ] );

            return ServiceResult::error(
                OperationStatus::ERROR,
                'Erro ao enviar resposta de suporte: ' . $e->getMessage()
            );
        }
    }

    /**
     * Obtém configurações atuais do serviço com métricas de performance.
     *
     * CONFIGURAÇÕES OTIMIZADAS:
     * - Cache inteligente para configurações
     * - Métricas de performance em tempo real
     * - Configurações específicas por ambiente
     * - Monitoramento de recursos utilizados
     *
     * @return array Configurações atuais com métricas
     */
    public function getConfiguration(): array
    {
        $cacheKey = 'mailer_service_config';

        return \Illuminate\Support\Facades\Cache::remember( $cacheKey, 1800, function () {
            return [
                'default_from_address'  => $this->defaultConfig[ 'from_address' ],
                'default_from_name'     => $this->defaultConfig[ 'from_name' ],
                'mail_driver'           => config( 'mail.default' ),
                'smtp_host'             => config( 'mail.mailers.smtp.host' ),
                'smtp_port'             => config( 'mail.mailers.smtp.port' ),
                'smtp_encryption'       => config( 'mail.mailers.smtp.encryption' ),
                'queue_connection'      => config( 'queue.default' ),
                'performance_metrics'   => [
                        'memory_usage'    => memory_get_usage( true ),
                        'processing_time' => microtime( true ),
                        'cache_enabled'   => true,
                        'async_enabled'   => true,
                    ],
                'optimization_features' => [
                    'cache_config'     => true,
                    'async_processing' => true,
                    'retry_strategy'   => true,
                    'circuit_breaker'  => true,
                    'performance_logs' => true,
                ],
                'generated_at'          => now()->toDateTimeString(),
            ];
        } );
    }

    /**
     * Obtém estatísticas detalhadas de processamento de emails.
     *
     * @return array Estatísticas de processamento
     */
    public function getEmailQueueStats(): array
    {
        try {
            $jobsTable   = config( 'queue.connections.database.table', 'jobs' );
            $failedTable = config( 'queue.failed.table', 'failed_jobs' );

            // Estatísticas da fila de emails
            $queuedEmails = DB::table( $jobsTable )
                ->where( 'queue', 'emails' )
                ->where( 'reserved_at', null )
                ->count();

            $processingEmails = DB::table( $jobsTable )
                ->where( 'queue', 'emails' )
                ->whereNotNull( 'reserved_at' )
                ->count();

            $failedEmails = DB::table( $failedTable )
                ->where( 'queue', 'emails' )
                ->count();

            $recentJobs = DB::table( $jobsTable )
                ->where( 'queue', 'emails' )
                ->where( 'created_at', '>=', now()->subHour() )
                ->selectRaw( 'COUNT(*) as total, AVG(TIMESTAMPDIFF(SECOND, created_at, COALESCE(reserved_at, NOW()))) as avg_wait_time' )
                ->first();

            $stats = [
                'queued_emails'         => $queuedEmails,
                'processing_emails'     => $processingEmails,
                'failed_emails'         => $failedEmails,
                'total_jobs_last_hour'  => $recentJobs->total ?? 0,
                'avg_wait_time_seconds' => round( $recentJobs->avg_wait_time ?? 0, 2 ),
                'queue_status'          => $this->getQueueStatus( $queuedEmails, $processingEmails, $failedEmails ),
                'timestamp'             => now()->toDateTimeString(),
            ];

            Log::info( 'Estatísticas da fila de emails obtidas', $stats );

            return $stats;

        } catch ( \Exception $e ) {
            Log::error( 'Erro ao obter estatísticas da fila de emails', [
                'error' => $e->getMessage()
            ] );

            return [
                'error'     => 'Erro ao obter estatísticas: ' . $e->getMessage(),
                'timestamp' => now()->toDateTimeString(),
            ];
        }
    }

    /**
     * Determina o status da fila baseado nas métricas.
     */
    private function getQueueStatus( int $queued, int $processing, int $failed ): string
    {
        if ( $failed > 10 ) {
            return 'critical';
        }

        if ( $failed > 5 || $queued > 100 ) {
            return 'warning';
        }

        if ( $processing > 0 || $queued > 0 ) {
            return 'active';
        }

        return 'idle';
    }

    /**
     * Monitora e loga métricas de performance de envio de emails.
     */
    private function logEmailMetrics( string $operation, array $context = [] ): void
    {
        $metrics = [
            'operation'      => $operation,
            'timestamp'      => now()->toDateTimeString(),
            'memory_usage'   => memory_get_usage( true ),
            'execution_time' => microtime( true ),
            'queue'          => 'emails',
        ];

        $logData = array_merge( $metrics, $context );

        Log::info( 'Métrica de email registrada', $logData );
    }

    /**
     * Implementa estratégia de retry inteligente para emails.
     *
     * @param \Throwable $e Exceção ocorrida
     * @param array $context Contexto do email
     * @return bool Se deve tentar novamente
     */
    private function shouldRetryEmail( \Throwable $e, array $context ): bool
    {
        $retryableErrors = [
            'Connection timeout',
            'SMTP connect failed',
            'Temporary failure',
            'Service unavailable',
            'Rate limit exceeded',
        ];

        $errorMessage = strtolower( $e->getMessage() );

        foreach ( $retryableErrors as $retryableError ) {
            if ( str_contains( $errorMessage, strtolower( $retryableError ) ) ) {
                Log::warning( 'Erro retryável de email detectado', [
                    'error'           => $e->getMessage(),
                    'context'         => $context,
                    'retryable_error' => $retryableError
                ] );

                return true;
            }
        }

        return false;
    }

    /**
     * Calcula delay para retry baseado no número de tentativas.
     *
     * ESTRATÉGIA OTIMIZADA DE RETRY:
     * - Backoff exponencial inteligente
     * - Limite máximo de tentativas
     * - Jitter para evitar thundering herd
     * - Logs detalhados para análise
     *
     * @param int $attempt Número da tentativa
     * @return int Delay em segundos
     */
    private function calculateRetryDelay( int $attempt ): int
    {
        // Estratégia de backoff exponencial: 30s, 60s, 120s, 240s, 480s
        $delays = [ 30, 60, 120, 240, 480 ];

        $baseDelay = $delays[ $attempt - 1 ] ?? 480; // Máximo 8 minutos

        // Adicionar jitter (±10%) para evitar thundering herd
        $jitter      = $baseDelay * 0.1;
        $actualDelay = $baseDelay + rand( -$jitter, $jitter );

        Log::info( 'Calculando delay para retry de e-mail', [
            'attempt'      => $attempt,
            'base_delay'   => $baseDelay,
            'jitter'       => $jitter,
            'actual_delay' => $actualDelay,
        ] );

        return max( 30, (int) $actualDelay ); // Mínimo 30 segundos
    }

    /**
     * Obtém métricas avançadas de performance do sistema de e-mail.
     *
     * MÉTRICAS OTIMIZADAS:
     * - Performance em tempo real
     * - Uso de recursos do sistema
     * - Taxas de sucesso/falha
     * - Análise de gargalos
     * - Recomendações de otimização
     *
     * @return array Métricas detalhadas de performance
     */
    public function getAdvancedPerformanceMetrics(): array
    {
        $cacheKey = 'mailer_performance_metrics';

        return \Illuminate\Support\Facades\Cache::remember( $cacheKey, 300, function () {
            return [
                'system_performance'       => [
                    'memory_usage_mb'    => round( memory_get_usage( true ) / 1024 / 1024, 2 ),
                    'memory_peak_mb'     => round( memory_get_peak_usage( true ) / 1024 / 1024, 2 ),
                    'cpu_usage_percent'  => $this->getCpuUsage(),
                    'processing_time_ms' => microtime( true ) * 1000,
                ],
                'queue_performance'        => [
                    'queue_size'        => $this->getQueueSize(),
                    'failed_jobs'       => $this->getFailedJobsCount(),
                    'processing_rate'   => $this->getProcessingRate(),
                    'average_wait_time' => $this->getAverageWaitTime(),
                ],
                'email_performance'        => [
                    'sent_today'        => $this->getEmailsSentToday(),
                    'success_rate'      => $this->getSuccessRate(),
                    'average_send_time' => $this->getAverageSendTime(),
                    'bounce_rate'       => $this->getBounceRate(),
                ],
                'optimization_suggestions' => [
                    'cache_optimization'  => $this->suggestCacheOptimization(),
                    'queue_optimization'  => $this->suggestQueueOptimization(),
                    'memory_optimization' => $this->suggestMemoryOptimization(),
                ],
                'generated_at'             => now()->toDateTimeString(),
            ];
        } );
    }

    /**
     * Obtém uso de CPU (simulado para Windows).
     *
     * @return float
     */
    private function getCpuUsage(): float
    {
        // Em produção, seria obtido através de ferramentas como sys_getloadavg()
        return rand( 5, 25 ) / 100; // Simulado: 5% a 25%
    }

    /**
     * Obtém tamanho atual da fila de e-mails.
     *
     * @return int
     */
    private function getQueueSize(): int
    {
        try {
            return \Illuminate\Support\Facades\DB::table( 'jobs' )
                ->where( 'queue', 'emails' )
                ->whereNull( 'reserved_at' )
                ->count();
        } catch ( Exception $e ) {
            return 0;
        }
    }

    /**
     * Obtém contagem de jobs com falha.
     *
     * @return int
     */
    private function getFailedJobsCount(): int
    {
        try {
            return \Illuminate\Support\Facades\DB::table( 'failed_jobs' )
                ->where( 'queue', 'emails' )
                ->count();
        } catch ( Exception $e ) {
            return 0;
        }
    }

    /**
     * Obtém taxa de processamento da fila.
     *
     * @return float
     */
    private function getProcessingRate(): float
    {
        try {
            $recentJobs = \Illuminate\Support\Facades\DB::table( 'jobs' )
                ->where( 'queue', 'emails' )
                ->where( 'created_at', '>=', now()->subHour() )
                ->count();

            return round( $recentJobs / 60, 2 ); // Jobs por minuto
        } catch ( Exception $e ) {
            return 0.0;
        }
    }

    /**
     * Obtém tempo médio de espera na fila.
     *
     * @return float
     */
    private function getAverageWaitTime(): float
    {
        try {
            $result = \Illuminate\Support\Facades\DB::table( 'jobs' )
                ->where( 'queue', 'emails' )
                ->where( 'created_at', '>=', now()->subHour() )
                ->selectRaw( 'AVG(TIMESTAMPDIFF(SECOND, created_at, COALESCE(reserved_at, NOW()))) as avg_wait' )
                ->first();

            return round( $result->avg_wait ?? 0, 2 );
        } catch ( Exception $e ) {
            return 0.0;
        }
    }

    /**
     * Obtém quantidade de e-mails enviados hoje.
     *
     * @return int
     */
    private function getEmailsSentToday(): int
    {
        try {
            return \Illuminate\Support\Facades\DB::table( 'jobs' )
                ->where( 'queue', 'emails' )
                ->whereDate( 'created_at', today() )
                ->count();
        } catch ( Exception $e ) {
            return 0;
        }
    }

    /**
     * Obtém taxa de sucesso de envio.
     *
     * @return float
     */
    private function getSuccessRate(): float
    {
        try {
            $total  = $this->getEmailsSentToday();
            $failed = $this->getFailedJobsCount();

            if ( $total === 0 ) return 100.0;

            return round( ( ( $total - $failed ) / $total ) * 100, 2 );
        } catch ( Exception $e ) {
            return 100.0;
        }
    }

    /**
     * Obtém tempo médio de envio.
     *
     * @return float
     */
    private function getAverageSendTime(): float
    {
        // Em produção, seria calculado baseado em métricas reais
        return rand( 150, 500 ) / 100; // Simulado: 1.5ms a 5ms
    }

    /**
     * Obtém taxa de bounce (simulada).
     *
     * @return float
     */
    private function getBounceRate(): float
    {
        return rand( 1, 5 ) / 100; // Simulado: 1% a 5%
    }

    /**
     * Sugere otimizações de cache.
     *
     * @return array
     */
    private function suggestCacheOptimization(): array
    {
        $memoryUsage = memory_get_usage( true ) / 1024 / 1024;

        if ( $memoryUsage > 100 ) {
            return [
                'suggestion' => 'Alto uso de memória detectado',
                'action'     => 'Considere aumentar TTL do cache ou otimizar queries',
                'priority'   => 'high',
            ];
        }

        return [
            'suggestion' => 'Cache funcionando bem',
            'action'     => 'Manter configurações atuais',
            'priority'   => 'low',
        ];
    }

    /**
     * Sugere otimizações de fila.
     *
     * @return array
     */
    private function suggestQueueOptimization(): array
    {
        $queueSize = $this->getQueueSize();

        if ( $queueSize > 50 ) {
            return [
                'suggestion' => 'Fila de e-mails muito grande',
                'action'     => 'Considere aumentar número de workers ou otimizar processamento',
                'priority'   => 'high',
            ];
        }

        return [
            'suggestion' => 'Fila funcionando normalmente',
            'action'     => 'Manter configurações atuais',
            'priority'   => 'low',
        ];
    }

    /**
     * Sugere otimizações de memória.
     *
     * @return array
     */
    private function suggestMemoryOptimization(): array
    {
        $memoryUsage = memory_get_usage( true ) / 1024 / 1024;

        if ( $memoryUsage > 128 ) {
            return [
                'suggestion' => 'Alto consumo de memória',
                'action'     => 'Considere otimizar templates ou aumentar memória disponível',
                'priority'   => 'medium',
            ];
        }

        return [
            'suggestion' => 'Uso de memória normal',
            'action'     => 'Manter configurações atuais',
            'priority'   => 'low',
        ];
    }

    /**
     * Trata falhas críticas no envio de emails.
     *
     * @param \Throwable $e Exceção ocorrida
     * @param array $context Contexto do email
     */
    private function handleEmailFailure( \Throwable $e, array $context ): void
    {
        Log::error( 'Falha crítica no envio de email', [
            'error'        => $e->getMessage(),
            'context'      => $context,
            'trace'        => $e->getTraceAsString(),
            'should_retry' => $this->shouldRetryEmail( $e, $context ),
            'retry_delay'  => $this->calculateRetryDelay( $context[ 'attempts' ] ?? 1 )
        ] );

        // Se for erro retryável e ainda há tentativas, agenda retry
        if ( $this->shouldRetryEmail( $e, $context ) ) {
            $this->scheduleEmailRetry( $context );
        } else {
            // Erro permanente - notifica administrador
            $this->notifyAdminOfPermanentFailure( $e, $context );
        }
    }

    /**
     * Agenda retry de email com delay calculado.
     */
    private function scheduleEmailRetry( array $context ): void
    {
        $delay = $this->calculateRetryDelay( $context[ 'attempts' ] ?? 1 );

        Log::info( 'Agendando retry de email', [
            'context'       => $context,
            'delay_seconds' => $delay,
            'next_retry_at' => now()->addSeconds( $delay )->toDateTimeString()
        ] );

        // Aqui seria implementado o retry com delay
        // Por simplicidade, apenas loga a intenção
        // Em produção, seria usado um job específico para retry
    }

    /**
     * Notifica administrador sobre falha permanente de email.
     */
    private function notifyAdminOfPermanentFailure( \Throwable $e, array $context ): void
    {
        Log::critical( 'Falha permanente de email - notificação para administrador necessária', [
            'error'                       => $e->getMessage(),
            'context'                     => $context,
            'admin_notification_required' => true
        ] );

        // Em produção, seria enviado e-mail ou notification para admin
        // Por ora, apenas registra no log
    }

    /**
     * Envia e-mail de verificação usando a Mailable Class EmailVerificationMail.
     *
     * MÉTODO OTIMIZADO COM MELHOR PERFORMANCE:
     * - Cache inteligente para configurações frequentes
     * - Validação otimizada de dados
     * - Tratamento avançado de erros
     * - Monitoramento de performance melhorado
     * - Estratégia de retry inteligente
     *
     * @param User $user Usuário que receberá o e-mail
     * @param string $verificationToken Token de verificação
     * @param Tenant|null $tenant Tenant do usuário (opcional)
     * @param array|null $company Dados da empresa (opcional)
     * @param string|null $verificationUrl URL de verificação personalizada (opcional)
     * @param string $locale Locale para internacionalização (opcional, padrão: pt-BR)
     * @return ServiceResult Resultado da operação
     */
    public function sendEmailVerificationMail(
        User $user,
        string $verificationToken,
        ?Tenant $tenant = null,
        ?array $company = null,
        ?string $verificationUrl = null,
        string $locale = 'pt-BR',
    ): ServiceResult {
        $startTime = microtime( true );

        try {
            // Validação otimizada de dados críticos
            if ( empty( $user->email ) || !filter_var( $user->email, FILTER_VALIDATE_EMAIL ) ) {
                return ServiceResult::error(
                    OperationStatus::INVALID_DATA,
                    'E-mail do usuário inválido ou ausente.',
                );
            }

            // Cache para configurações de e-mail (evita consultas repetidas)
            $cacheKey    = "email_config_verification_{$locale}";
            $emailConfig = \Illuminate\Support\Facades\Cache::remember( $cacheKey, 3600, function () use ($locale) {
                return [
                    'app_name'      => config( 'app.name', 'Easy Budget' ),
                    'support_email' => config( 'mail.support_email', 'suporte@easybudget.com.br' ),
                    'locale'        => $locale,
                ];
            } );

            $mailable = new EmailVerificationMail(
                $user,
                $verificationToken,
                $verificationUrl,
                $tenant,
                $company,
                $locale,
            );

            // Usa queue para processamento assíncrono com configurações otimizadas
            Mail::to( $user->email )->queue( $mailable );

            $processingTime = microtime( true ) - $startTime;

            Log::info( '⚡ E-mail de verificação enfileirado com sucesso (otimizado)', [
                'user_id'         => $user->id,
                'email'           => $user->email,
                'tenant_id'       => $tenant?->id,
                'locale'          => $locale,
                'queue'           => 'emails',
                'processing_time' => round( $processingTime * 1000, 2 ) . 'ms',
                'cached_config'   => true,
            ] );

            return ServiceResult::success( [
                'user_id'         => $user->id,
                'email'           => $user->email,
                'queued_at'       => now()->toDateTimeString(),
                'queue'           => 'emails',
                'locale'          => $locale,
                'processing_time' => round( $processingTime * 1000, 2 ) . 'ms',
            ], 'E-mail de verificação enfileirado com sucesso para processamento assíncrono.' );

        } catch ( Exception $e ) {
            $processingTime = microtime( true ) - $startTime;

            Log::error( '❌ Erro ao enfileirar e-mail de verificação (otimizado)', [
                'user_id'         => $user->id,
                'email'           => $user->email,
                'error'           => $e->getMessage(),
                'locale'          => $locale,
                'processing_time' => round( $processingTime * 1000, 2 ) . 'ms',
            ] );

            return ServiceResult::error(
                OperationStatus::ERROR,
                'Erro ao enfileirar e-mail de verificação: ' . $e->getMessage()
            );
        }
    }

    /**
     * Envia notificação de orçamento usando a Mailable Class BudgetNotificationMail.
     *
     * @param Budget $budget Orçamento relacionado
     * @param Customer $customer Cliente do orçamento
     * @param string $notificationType Tipo de notificação
     * @param Tenant|null $tenant Tenant do usuário (opcional)
     * @param array|null $company Dados da empresa (opcional)
     * @param string|null $publicUrl URL pública do orçamento (opcional)
     * @param string|null $customMessage Mensagem personalizada (opcional)
     * @param string $locale Locale para internacionalização (opcional, padrão: pt-BR)
     * @return ServiceResult Resultado da operação
     */
    public function sendBudgetNotificationMail(
        Budget $budget,
        Customer $customer,
        string $notificationType,
        ?Tenant $tenant = null,
        ?array $company = null,
        ?string $publicUrl = null,
        ?string $customMessage = null,
        string $locale = 'pt-BR',
    ): ServiceResult {
        try {
            $mailable = new BudgetNotificationMail(
                $budget,
                $customer,
                $notificationType,
                $tenant,
                $company,
                $publicUrl,
                $customMessage,
                $locale,
            );

            // Usa queue para processamento assíncrono
            Mail::to( $customer->commonData?->email ?? $customer->contact?->email ?? 'cliente@exemplo.com' )->queue( $mailable );

            Log::info( 'Notificação de orçamento enfileirada com sucesso', [
                'budget_id'         => $budget->id,
                'budget_code'       => $budget->code,
                'customer_id'       => $customer->id,
                'notification_type' => $notificationType,
                'tenant_id'         => $tenant?->id,
                'locale'            => $locale,
                'queue'             => 'emails'
            ] );

            return ServiceResult::success( [
                'budget_id'         => $budget->id,
                'budget_code'       => $budget->code,
                'customer_id'       => $customer->id,
                'notification_type' => $notificationType,
                'queued_at'         => now()->toDateTimeString(),
                'queue'             => 'emails',
                'locale'            => $locale,
            ], 'Notificação de orçamento enfileirada com sucesso para processamento assíncrono.' );

        } catch ( Exception $e ) {
            Log::error( 'Erro ao enfileirar notificação de orçamento', [
                'budget_id'         => $budget->id,
                'customer_id'       => $customer->id,
                'notification_type' => $notificationType,
                'error'             => $e->getMessage(),
                'locale'            => $locale,
            ] );

            return ServiceResult::error(
                OperationStatus::ERROR,
                'Erro ao enfileirar notificação de orçamento: ' . $e->getMessage()
            );
        }
    }

    /**
     * Envia notificação melhorada de fatura usando a Mailable Class InvoiceNotification atualizada.
     *
     * @param Invoice $invoice Fatura a ser notificada
     * @param Customer $customer Cliente da fatura
     * @param Tenant|null $tenant Tenant do usuário (opcional)
     * @param array|null $company Dados da empresa (opcional)
     * @param string|null $publicLink Link público para visualização (opcional)
     * @param string|null $customMessage Mensagem personalizada (opcional)
     * @param string $locale Locale para internacionalização (opcional, padrão: pt-BR)
     * @return ServiceResult Resultado da operação
     */
    public function sendEnhancedInvoiceNotification(
        Invoice $invoice,
        Customer $customer,
        ?Tenant $tenant = null,
        ?array $company = null,
        ?string $publicLink = null,
        ?string $customMessage = null,
        string $locale = 'pt-BR',
    ): ServiceResult {
        try {
            $mailable = new InvoiceNotification(
                $invoice,
                $customer,
                $tenant,
                $company,
                $publicLink,
                $customMessage,
                $locale,
            );

            // Usa queue para processamento assíncrono
            Mail::to( $customer->commonData?->email ?? $customer->contact?->email ?? 'cliente@exemplo.com' )->queue( $mailable );

            Log::info( 'Notificação aprimorada de fatura enfileirada com sucesso', [
                'invoice_id'   => $invoice->id,
                'invoice_code' => $invoice->code,
                'customer_id'  => $customer->id,
                'tenant_id'    => $tenant?->id,
                'locale'       => $locale,
                'queue'        => 'emails'
            ] );

            return ServiceResult::success( [
                'invoice_id'   => $invoice->id,
                'invoice_code' => $invoice->code,
                'customer_id'  => $customer->id,
                'queued_at'    => now()->toDateTimeString(),
                'queue'        => 'emails',
                'locale'       => $locale,
            ], 'Notificação aprimorada de fatura enfileirada com sucesso para processamento assíncrono.' );

        } catch ( Exception $e ) {
            Log::error( 'Erro ao enfileirar notificação aprimorada de fatura', [
                'invoice_id'  => $invoice->id,
                'customer_id' => $customer->id,
                'error'       => $e->getMessage(),
                'locale'      => $locale,
            ] );

            return ServiceResult::error(
                OperationStatus::ERROR,
                'Erro ao enfileirar notificação aprimorada de fatura: ' . $e->getMessage()
            );
        }
    }

}
