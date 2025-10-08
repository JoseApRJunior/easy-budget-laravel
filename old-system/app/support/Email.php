<?php

namespace app\support;

use Exception;
use PHPMailer\PHPMailer\PHPMailer;
use RuntimeException;

class Email
{
    private string|array $to;
    private string       $from;
    private string       $fromName;
    private string       $subject;
    private string       $template;
    private array        $templateData = [];
    private string       $message;
    private PHPMailer    $mail;

    public function __construct()
    {

        $this->mail = new PHPMailer();

        // Configuração do servidor
        $this->mail->isSMTP();
        $this->mail->Host       = env( 'EMAIL_HOST' );
        $this->mail->SMTPAuth   = true;
        $this->mail->Username   = env( 'EMAIL_USERNAME' );
        $this->mail->Password   = env( 'EMAIL_PASSWORD' );
        $this->mail->SMTPSecure = env( 'EMAIL_ENCRYPTION' );
        $this->mail->Port       = env( 'EMAIL_PORT' );

        // Configuração adicional
        $this->mail->isHTML( true );
        $this->mail->CharSet = 'UTF-8';

    }

    public function from( string $from, string $name = '' ): Email
    {
        $this->from = $from;

        $this->fromName = $name;

        return $this;
    }

    public function to( string|array $to ): Email
    {
        $this->to = $to;

        return $this;
    }

    public function template( string $template, array $templateData = [] ): Email
    {
        $this->template     = $template;
        $this->templateData = $templateData;

        return $this;
    }

    public function subject( string $subject ): Email
    {
        $this->subject = $subject;
        return $this;
    }

    public function message( string $message ): Email
    {
        $this->message = $message;
        return $this;
    }

    private function addAddress()
    {
        if ( is_array( $this->to ) ) {
            foreach ( $this->to as $to ) {
                $this->mail->addAddress( $to );
            }
        }

        if ( is_string( $this->to ) ) {
            $this->mail->addAddress( $this->to );
        }
    }

    private function sendWithTemplate(): string
    {
        $file = "../app/views/emails/{$this->template}.html";
        if ( !file_exists( $file ) ) {
            throw new Exception( "O template $this->template não existe." );
        }

        $template = file_get_contents( $file );

        // Adiciona a mensagem (que contém o link) aos dados do template
        $this->templateData[ 'message' ] = $this->message;

        $dataTemplate = [];
        foreach ( $this->templateData as $key => $data ) {
            // Use chaves duplas para melhor distinção no template
            $dataTemplate[ "{{$key}}" ] = $data;
        }

        // Substitui as variáveis no template
        $processedTemplate = str_replace( array_keys( $dataTemplate ), array_values( $dataTemplate ), $template );

        return $processedTemplate;
    }

    public function send()
    {
        try {
            $this->mail->setFrom( $this->from, $this->fromName );
            $this->addAddress();
            $this->mail->isHTML( true );
            $this->mail->CharSet = 'UTF-8';
            $this->mail->Subject = $this->subject;
            $this->mail->Body    = ( !empty( $this->template ) ) ? $this->sendWithTemplate() : $this->message;
            $this->mail->AltBody = strip_tags( $this->message );

            $result = $this->mail->send();

            if ( !$result ) {
                return "Falha ao enviar e-mail: Entre em contato com suporte ";
            }

            return true;
        } catch ( Exception $e ) {
            throw new RuntimeException( 'Erro ao enviar e-mail: ' . $e->getMessage() . $this->mail->ErrorInfo );
        }
    }

}
