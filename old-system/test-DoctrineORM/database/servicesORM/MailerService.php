<?php

namespace app\database\servicesORM;

use app\interfaces\ServiceNoTenantInterface;
use app\support\ServiceResult;
use app\enums\OperationStatus;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
use PHPMailer\PHPMailer\PHPMailer;
use Exception;

/**
 * Serviço para envio de e-mails utilizando PHPMailer.
 */
class MailerService implements ServiceNoTenantInterface
{
    private PHPMailer $mailer;

    public function __construct()
    {
        $this->mailer = new PHPMailer( true ); // Habilita exceções
        $this->configure();
    }

    private function configure(): void
    {
        // Configurações do servidor de e-mail (SMTP) do .env
        $this->mailer->isSMTP();

        $host = env( 'EMAIL_HOST' );
        if ( $host === null ) {
            throw new Exception( 'EMAIL_HOST não está definido no arquivo de ambiente.' );
        }
        $this->mailer->Host = $host;

        $this->mailer->SMTPAuth = true;

        $username = env( 'EMAIL_USERNAME' );
        if ( $username === null ) {
            throw new Exception( 'EMAIL_USERNAME não está definido no arquivo de ambiente.' );
        }
        $this->mailer->Username = $username;

        $password = env( 'EMAIL_PASSWORD' );
        if ( $password === null ) {
            throw new Exception( 'EMAIL_PASSWORD não está definido no arquivo de ambiente.' );
        }
        $this->mailer->Password = $password;

        $encryption = env( 'EMAIL_ENCRYPTION' );
        if ( $encryption === null ) {
            throw new Exception( 'EMAIL_ENCRYPTION não está definido no arquivo de ambiente.' );
        }
        $this->mailer->SMTPSecure = $encryption;

        $port = env( 'EMAIL_PORT' );
        if ( $port === null ) {
            throw new Exception( 'EMAIL_PORT não está definido no arquivo de ambiente.' );
        }
        $this->mailer->Port = (int) $port;

        $this->mailer->CharSet = 'UTF-8';
    }

    /**
     * Busca um e-mail pelo ID.
     *
     * @param int $id ID do e-mail
     * @return ServiceResult Resultado da operação
     */
    public function getById( int $id ): ServiceResult
    {
        return ServiceResult::error( OperationStatus::NOT_SUPPORTED, 'MailerService não armazena e-mails.' );
    }

    /**
     * Lista e-mails.
     *
     * @param array<string, mixed> $filters Filtros opcionais
     * @return ServiceResult Resultado da operação
     */
    public function list( array $filters = [] ): ServiceResult
    {
        return ServiceResult::success( [] );
    }

    /**
     * Cria/envia um novo e-mail.
     *
     * @param array<string, mixed> $data Dados do e-mail (to, subject, body, etc.)
     * @return ServiceResult Resultado da operação
     */
    public function create( array $data ): ServiceResult
    {
        try {
            $validation = $this->validate( $data );
            if ( !$validation->isSuccess() ) {
                return $validation;
            }

            // Verificar se os índices obrigatórios existem
            if ( !isset( $data[ 'to' ] ) || !isset( $data[ 'subject' ] ) || !isset( $data[ 'body' ] ) ) {
                return ServiceResult::error( OperationStatus::INVALID_DATA, 'Dados obrigatórios ausentes.' );
            }

            $this->send(
                $data[ 'to' ],
                $data[ 'subject' ],
                $data[ 'body' ],
                $data[ 'attachment' ] ?? null,
                $data[ 'fromAddress' ] ?? null,
                $data[ 'fromName' ] ?? null
            );

            // Retorna sucesso com dados do e-mail enviado
            return ServiceResult::success( [ 
                'to'      => $data[ 'to' ],
                'subject' => $data[ 'subject' ],
                'sent_at' => date( 'Y-m-d H:i:s' ),
            ], 'E-mail enviado com sucesso.' );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Falha ao enviar e-mail: ' . $e->getMessage() );
        }
    }

    /**
     * Atualiza um e-mail.
     *
     * @param int $id ID do e-mail
     * @param array<string, mixed> $data Dados para atualização
     * @return ServiceResult Resultado da operação
     */
    public function update( int $id, array $data ): ServiceResult
    {
        return ServiceResult::error( OperationStatus::NOT_SUPPORTED, 'MailerService não suporta atualização de e-mails.' );
    }

    /**
     * Remove um e-mail.
     *
     * @param int $id ID do e-mail
     * @return ServiceResult Resultado da operação
     */
    public function delete( int $id ): ServiceResult
    {
        return ServiceResult::error( OperationStatus::NOT_SUPPORTED, 'MailerService não suporta remoção de e-mails.' );
    }

    /**
     * Valida dados do e-mail.
     *
     * @param array<string, mixed> $data Dados a serem validados
     * @param bool $isUpdate Indica se é uma operação de atualização
     * @return ServiceResult Resultado da validação
     */
    public function validate( array $data, bool $isUpdate = false ): ServiceResult
    {
        $errors = [];

        // Destinatário obrigatório
        if ( empty( $data[ 'to' ] ) ) {
            $errors[] = 'Destinatário é obrigatório.';
        } elseif ( !filter_var( $data[ 'to' ], FILTER_VALIDATE_EMAIL ) ) {
            $errors[] = 'E-mail do destinatário inválido.';
        }

        // Assunto obrigatório
        if ( empty( $data[ 'subject' ] ) ) {
            $errors[] = 'Assunto é obrigatório.';
        }

        // Corpo obrigatório
        if ( empty( $data[ 'body' ] ) ) {
            $errors[] = 'Corpo do e-mail é obrigatório.';
        }

        // Validar anexo se fornecido
        if ( isset( $data[ 'attachment' ] ) && !is_array( $data[ 'attachment' ] ) ) {
            $errors[] = 'Anexo deve ser um array.';
        }

        if ( !empty( $errors ) ) {
            return ServiceResult::error( OperationStatus::INVALID_DATA, 'Dados inválidos: ' . implode( ', ', $errors ) );
        }

        return ServiceResult::success( null, 'Dados válidos.' );
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
     * @throws Exception
     */
    public function send( string $to, string $subject, string $body, ?array $attachment = null, string $fromAddress = null, string $fromName = null ): bool
    {
        try {
            // Verificar se fromAddress foi fornecido, senão obter do .env
            $fromAddressToUse = $fromAddress ?? env( 'EMAIL_FROM' );
            if ( $fromAddressToUse === null ) {
                throw new Exception( 'EMAIL_FROM não está definido no arquivo de ambiente.' );
            }

            // Verificar se fromName foi fornecido, senão obter do .env
            $fromNameToUse = $fromName ?? env( 'EMAIL_FROM_NAME' );
            if ( $fromNameToUse === null ) {
                throw new Exception( 'EMAIL_FROM_NAME não está definido no arquivo de ambiente.' );
            }

            $this->mailer->setFrom( $fromAddressToUse, $fromNameToUse );

            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();

            $this->mailer->addAddress( $to );
            $this->mailer->Subject = $subject;
            $this->mailer->isHTML( true );
            $this->mailer->Body    = $body;
            $this->mailer->AltBody = strip_tags( $body );

            if ( $attachment ) {
                if ( !isset( $attachment[ 'content' ] ) || !isset( $attachment[ 'fileName' ] ) ) {
                    throw new Exception( 'Anexo inválido: conteúdo ou nome do arquivo ausente.' );
                }
                $this->mailer->addStringAttachment( $attachment[ 'content' ], $attachment[ 'fileName' ] );
            }

            return $this->mailer->send();
        } catch ( PHPMailerException $e ) {
            throw new Exception( "Falha ao enviar o e-mail: " . $this->mailer->ErrorInfo, 0, $e );
        }
    }

}
