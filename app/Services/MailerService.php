<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\OperationStatus;
use App\Support\ServiceResult;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

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
     * Construtor: inicializa configurações padrão.
     */
    public function __construct()
    {
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

            // Enviar e-mail
            $sent = $this->sendEmail( $emailData );

            if ( $sent ) {
                Log::info( 'E-mail enviado com sucesso', [ 
                    'to'      => $to,
                    'subject' => $subject,
                    'from'    => $emailData[ 'from_address' ]
                ] );

                return ServiceResult::success( [ 
                    'to'      => $to,
                    'subject' => $subject,
                    'sent_at' => now()->toDateTimeString(),
                ], 'E-mail enviado com sucesso.' );
            }

            return ServiceResult::error(
                OperationStatus::ERROR,
                'Falha ao enviar e-mail: erro desconhecido.',
            );

        } catch ( Exception $e ) {
            Log::error( 'Erro ao enviar e-mail', [ 
                'to'      => $to,
                'subject' => $subject,
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString()
            ] );

            return ServiceResult::error(
                OperationStatus::ERROR,
                'Falha ao enviar e-mail: ' . $e->getMessage()
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
     * Envia e-mail simples (HTML ou texto).
     *
     * @param array $emailData Dados do e-mail
     * @return bool Sucesso do envio
     * @throws Exception
     */
    private function sendEmail( array $emailData ): bool
    {
        try {
            Mail::send( [], [], function ($message) use ($emailData) {
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

        } catch ( Exception $e ) {
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
     * Obtém configurações atuais do serviço.
     *
     * @return array Configurações atuais
     */
    public function getConfiguration(): array
    {
        return [ 
            'default_from_address' => $this->defaultConfig[ 'from_address' ],
            'default_from_name'    => $this->defaultConfig[ 'from_name' ],
            'mail_driver'          => config( 'mail.default' ),
            'smtp_host'            => config( 'mail.mailers.smtp.host' ),
            'smtp_port'            => config( 'mail.mailers.smtp.port' ),
            'smtp_encryption'      => config( 'mail.mailers.smtp.scheme' ),
        ];
    }

}
