<?php

namespace core\support;

use core\library\Twig;
use PHPMailer\PHPMailer\PHPMailer;
use RuntimeException;
use Throwable;

class Email
{
    private string|array $to;
    private string       $from;
    private string       $fromName;
    private string       $subject;
    private string       $template;
    private array        $templateData = [];
    private string       $message = '';
    private PHPMailer    $mail;

    public function __construct(private ?Twig $twig = null)
    {

        $this->mail = new PHPMailer();

        // Configuração do servidor
        $this->mail->isSMTP();
        $this->mail->Host = env('EMAIL_HOST');
        $this->mail->SMTPAuth = true;
        $this->mail->Username = env('EMAIL_USERNAME');
        $this->mail->Password = env('EMAIL_PASSWORD');
        $this->mail->SMTPSecure = env('EMAIL_ENCRYPTION');
        $this->mail->Port = (int) env('EMAIL_PORT');

        // Configuração adicional
        $this->mail->isHTML(true);
        $this->mail->CharSet = 'UTF-8';

    }

    /**
     * Adiciona um anexo ao e-mail a partir de uma string.
     *
     * @param string $string O conteúdo do anexo como uma string.
     * @param string $filename O nome do arquivo a ser usado para o anexo.
     * @param string $encoding A codificação do anexo (ex: 'base64', 'binary'). Padrão é 'base64'.
     * @param string $type O tipo MIME do anexo (ex: 'application/pdf'). Padrão é 'application/octet-stream'.
     * @return Email
     */
    public function addStringAttachment(string $string, string $filename, string $encoding = 'base64', string $type = 'application/octet-stream'): Email
    {
        $this->mail->addStringAttachment($string, $filename, $encoding, $type);

        return $this;
    }

    public function from(string $from, string $name = ''): Email
    {
        $this->from = $from;

        $this->fromName = $name;

        return $this;
    }

    public function to(string|array $to): Email
    {
        $this->to = $to;

        return $this;
    }

    public function template(string $template, array $templateData = []): Email
    {
        $this->template = $template;
        $this->templateData = $templateData;

        return $this;
    }

    public function subject(string $subject): Email
    {
        $this->subject = $subject;

        return $this;
    }

    public function message(string $message): Email
    {
        $this->message = $message;

        return $this;
    }

    private function addAddress()
    {
        if (is_array($this->to)) {
            foreach ($this->to as $to) {
                $this->mail->addAddress($to);
            }
        }

        if (is_string($this->to)) {
            $this->mail->addAddress($this->to);
        }
    }

    private function sendWithTemplate(): string
    {
        return $this->twig->env->render("emails/{$this->template}.twig", $this->templateData);
    }

    public function send()
    {
        try {
            $this->mail->setFrom($this->from, $this->fromName);
            $this->addAddress();
            $this->mail->isHTML(true);
            $this->mail->CharSet = 'UTF-8';
            $this->mail->Subject = $this->subject;
            $this->mail->Body = !empty($this->template) ? $this->sendWithTemplate() : $this->message;
            $this->mail->AltBody = strip_tags($this->message);

            $result[ 'status' ] = $this->mail->send() === true ? 'success' : 'error';

            return [
                'status' => $result[ 'status' ] === 'success' ? 'success' : 'error',
                'message' => $result[ 'status' ] === 'success' ? 'E-mail enviado com sucesso' : 'Falha ao enviar e-mail',
                'data' => [
                    'to' => $this->to,
                    'from' => $this->from,
                    'subject' => $this->subject,
                    'template' => $this->template,
                    'message' => $this->message,
                ],
            ];
        } catch (Throwable $e) {
            throw new RuntimeException('Erro ao enviar e-mail: ' . $e->getMessage() . $this->mail->ErrorInfo);
        }
    }

}
