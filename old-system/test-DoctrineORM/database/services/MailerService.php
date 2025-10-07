<?php

namespace app\database\services;

use PHPMailer\PHPMailer\Exception as PHPMailerException;
use PHPMailer\PHPMailer\PHPMailer;
use RuntimeException;

class MailerService
{
    private PHPMailer $mailer;

    public function __construct()
    {
        $this->mailer = new PHPMailer(true); // Habilita exceções
        $this->configure();
    }

    private function configure(): void
    {
        // Configurações do servidor de e-mail (SMTP) do .env
        $this->mailer->isSMTP();
        $this->mailer->Host = env('EMAIL_HOST');
        $this->mailer->SMTPAuth = true;
        $this->mailer->Username = env('EMAIL_USERNAME');
        $this->mailer->Password = env('EMAIL_PASSWORD');
        $this->mailer->SMTPSecure = env('EMAIL_ENCRYPTION');
        $this->mailer->Port = (int) env('EMAIL_PORT');
        $this->mailer->CharSet = 'UTF-8';
    }

    /**
     * Envia um e-mail.
     *
     * @param string $to Destinatário
     * @param string $subject Assunto
     * @param string $body Corpo do e-mail
     * @param array<string, mixed>|null $attachment Anexo opcional
     * @param string|null $fromAddress Endereço do remetente
     * @param string|null $fromName Nome do remetente
     * @return bool Sucesso do envio
     */
    public function send(string $to, string $subject, string $body, ?array $attachment = null, string $fromAddress = null, string $fromName = null): bool
    {
        try {
            $this->mailer->setFrom($fromAddress ?? env('EMAIL_FROM'), $fromName ?? env('EMAIL_FROM_NAME'));

            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();

            $this->mailer->addAddress($to);
            $this->mailer->Subject = $subject;
            $this->mailer->isHTML(true);
            $this->mailer->Body = $body;
            $this->mailer->AltBody = strip_tags($body);

            if ($attachment) {
                $this->mailer->addStringAttachment($attachment[ 'content' ], $attachment[ 'fileName' ]);
            }

            return $this->mailer->send();
        } catch (PHPMailerException $e) {
            throw new RuntimeException("Falha ao enviar o e-mail: " . $this->mailer->ErrorInfo, 0, $e);
        }
    }

}
