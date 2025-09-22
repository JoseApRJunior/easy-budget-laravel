<?php

declare(strict_types=1);

namespace core\services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use DateTime;

/**
 * Serviço de notificações por email e SMS
 */
class NotificationService
{
    private array $config;

    public function __construct()
    {
        $this->config = [
            'smtp_host' => 'smtp.gmail.com',
            'smtp_port' => 587,
            'smtp_username' => 'noreply@easy-budget.com',
            'smtp_password' => 'app_password_here',
            'from_email' => 'noreply@easy-budget.com',
            'from_name' => 'Easy Budget Monitor',
            'admin_emails' => ['admin@easy-budget.com'],
            'sms_api_key' => 'sms_api_key_here'
        ];
    }

    /**
     * Envia notificação de alerta por email
     */
    public function sendAlertEmail(string $middleware, string $severity, string $message): bool
    {
        $mail = new PHPMailer(true);

        try {
            // Configuração SMTP
            $mail->isSMTP();
            $mail->Host = $this->config['smtp_host'];
            $mail->SMTPAuth = true;
            $mail->Username = $this->config['smtp_username'];
            $mail->Password = $this->config['smtp_password'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = $this->config['smtp_port'];

            // Remetente e destinatários
            $mail->setFrom($this->config['from_email'], $this->config['from_name']);
            foreach ($this->config['admin_emails'] as $email) {
                $mail->addAddress($email);
            }

            // Conteúdo
            $mail->isHTML(true);
            $mail->Subject = "[Easy-Budget] Alerta {$severity} - {$middleware}";
            $mail->Body = $this->getEmailTemplate($middleware, $severity, $message);

            $mail->send();
            return true;
        } catch (\Exception $e) {
            error_log("Email notification failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Envia notificação SMS
     */
    public function sendAlertSMS(string $middleware, string $severity, string $message): bool
    {
        $smsMessage = "[Easy-Budget] {$severity}: {$middleware} - {$message}";
        
        // Implementação com API de SMS (exemplo Twilio/Nexmo)
        $data = [
            'to' => '+5511999999999',
            'message' => $smsMessage,
            'api_key' => $this->config['sms_api_key']
        ];

        // Simular envio (implementar API real conforme provedor)
        error_log("SMS Alert: " . $smsMessage);
        return true;
    }

    /**
     * Template HTML para emails de alerta
     */
    private function getEmailTemplate(string $middleware, string $severity, string $message): string
    {
        $color = match($severity) {
            'CRITICAL' => '#dc3545',
            'WARNING' => '#ffc107',
            default => '#17a2b8'
        };

        $icon = match($severity) {
            'CRITICAL' => '🚨',
            'WARNING' => '⚠️',
            default => 'ℹ️'
        };

        return "
        <html>
        <body style='font-family: Arial, sans-serif; margin: 0; padding: 20px; background-color: #f8f9fa;'>
            <div style='max-width: 600px; margin: 0 auto; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1);'>
                <div style='background: {$color}; color: white; padding: 20px; text-align: center;'>
                    <h1 style='margin: 0; font-size: 24px;'>{$icon} Alerta {$severity}</h1>
                </div>
                <div style='padding: 30px;'>
                    <h2 style='color: #333; margin-top: 0;'>Middleware: {$middleware}</h2>
                    <div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                        <strong>Mensagem:</strong><br>
                        {$message}
                    </div>
                    <div style='margin: 20px 0; font-size: 14px; color: #666;'>
                        <strong>Data/Hora:</strong> " . date('d/m/Y H:i:s') . "<br>
                        <strong>Sistema:</strong> Easy Budget Monitoring
                    </div>
                    <div style='text-align: center; margin-top: 30px;'>
                        <a href='http://localhost/easy-budget/admin/monitoring' 
                           style='background: {$color}; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block;'>
                            Ver Dashboard
                        </a>
                    </div>
                </div>
                <div style='background: #f8f9fa; padding: 15px; text-align: center; font-size: 12px; color: #666;'>
                    Easy Budget - Sistema de Monitoramento Automático
                </div>
            </div>
        </body>
        </html>";
    }

    /**
     * Configura destinatários de notificação
     */
    public function setNotificationRecipients(array $emails, array $phones = []): void
    {
        $this->config['admin_emails'] = $emails;
        $this->config['admin_phones'] = $phones;
    }

    /**
     * Testa configuração de email
     */
    public function testEmailConfiguration(): bool
    {
        return $this->sendAlertEmail('TestMiddleware', 'INFO', 'Teste de configuração de email');
    }
}