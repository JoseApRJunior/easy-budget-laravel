<?php

declare(strict_types=1);

namespace app\database\servicesORM;

use app\database\entitiesORM\SupportEntity;
use app\database\services\MailerService;
use app\database\services\PdfService;
use app\enums\OperationStatus;
use app\interfaces\ServiceNoTenantInterface;
use app\support\ServiceResult;
use core\library\Session;
use core\library\Twig;
use Exception;
use RuntimeException;

/**
 * Serviço para gerenciamento de notificações via e-mail e outros canais.
 *
 * Implementa ServiceNoTenantInterface para operações de envio de notificações.
 * Mantém métodos legados como public, adaptando retornos para ServiceResult.
 * Usa Twig para templates, MailerService para envio, PdfService para anexos.
 */
class NotificationService implements ServiceNoTenantInterface
{
    public function __construct(
        private MailerService $mailerService,
        private PdfService $pdfService,
        private Twig $twig,
    ) {}

    /**
     * Busca uma notificação pelo ID (não suportado para notificações efêmeras).
     *
     * @param int $id ID da notificação
     * @return ServiceResult Erro, pois notificações são efêmeras
     */
    public function getById( int $id ): ServiceResult
    {
        return ServiceResult::error(
            OperationStatus::NOT_SUPPORTED,
            'NotificationService não armazena notificações para consulta por ID.',
            null,
        );
    }

    /**
     * Lista notificações (não suportado para notificações efêmeras).
     *
     * @param array<string, mixed> $filters Filtros opcionais
     * @return ServiceResult Lista vazia
     */
    public function list( array $filters = [] ): ServiceResult
    {
        return ServiceResult::success(
            [],
            'NotificationService não armazena notificações para listagem.',
            null,
        );
    }

    /**
     * Cria/envia uma nova notificação (método genérico).
     *
     * @param array<string, mixed> $data Dados da notificação (type, to, subject, body, etc.)
     * @return ServiceResult Resultado da operação
     */
    public function create( array $data ): ServiceResult
    {
        try {
            $validation = $this->validate( $data );
            if ( !$validation->isSuccess() ) {
                return $validation;
            }

            $type = $data[ 'type' ] ?? 'email';

            switch ( $type ) {
                case 'email':
                    $result = $this->sendEmail(
                        $data[ 'to' ],
                        $data[ 'subject' ],
                        $data[ 'body' ],
                        $data[ 'attachment' ] ?? null
                    );
                    break;
                default:
                    return ServiceResult::error(
                        OperationStatus::INVALID_DATA,
                        'Tipo de notificação não suportado.',
                        null,
                    );
            }

            if ( $result ) {
                return ServiceResult::success(
                    [ 
                        'type'    => $type,
                        'sent_at' => date( 'Y-m-d H:i:s' ),
                        'success' => true,
                    ],
                    'Notificação enviada com sucesso.',
                );
            } else {
                return ServiceResult::error(
                    OperationStatus::ERROR,
                    'Falha ao enviar notificação.',
                    null,
                );
            }
        } catch ( Exception $e ) {
            return ServiceResult::error(
                OperationStatus::ERROR,
                'Falha ao enviar notificação: ' . $e->getMessage()
            );
        }
    }

    /**
     * Atualiza uma notificação (não suportado).
     *
     * @param int $id ID da notificação
     * @param array<string, mixed> $data Dados para atualização
     * @return ServiceResult Erro, pois não suportado
     */
    public function update( int $id, array $data ): ServiceResult
    {
        return ServiceResult::error(
            OperationStatus::NOT_SUPPORTED,
            'NotificationService não suporta atualização de notificações.',
            null,
        );
    }

    /**
     * Remove uma notificação (não suportado).
     *
     * @param int $id ID da notificação
     * @return ServiceResult Erro, pois não suportado
     */
    public function delete( int $id ): ServiceResult
    {
        return ServiceResult::error(
            OperationStatus::NOT_SUPPORTED,
            'NotificationService não suporta remoção de notificações.',
            null,
        );
    }

    /**
     * Valida dados da notificação.
     *
     * @param array<string, mixed> $data Dados a validar
     * @param bool $isUpdate Se é atualização (não aplicável)
     * @return ServiceResult Resultado da validação
     */
    public function validate( array $data, bool $isUpdate = false ): ServiceResult
    {
        $errors = [];

        if ( empty( $data[ 'type' ] ) ) {
            $errors[] = 'Tipo de notificação é obrigatório.';
        } elseif ( !in_array( $data[ 'type' ], [ 'email' ] ) ) {
            $errors[] = 'Tipo de notificação inválido.';
        }

        if ( !empty( $data[ 'type' ] ) ) {
            switch ( $data[ 'type' ] ) {
                case 'email':
                    if ( empty( $data[ 'to' ] ) ) $errors[] = 'Destinatário é obrigatório.';
                    if ( empty( $data[ 'subject' ] ) ) $errors[] = 'Assunto é obrigatório.';
                    if ( empty( $data[ 'body' ] ) ) $errors[] = 'Corpo da mensagem é obrigatório.';
                    break;
            }
        }

        if ( !empty( $errors ) ) {
            return ServiceResult::error(
                OperationStatus::INVALID_DATA,
                'Dados inválidos: ' . implode( ', ', $errors )
            );
        }

        return ServiceResult::success( null, 'Dados válidos.' );
    }

    // Métodos legados adaptados para ServiceResult

    /**
     * Envia e-mail de notificação de status do orçamento para aprovação do cliente.
     *
     * @param object $authenticated Usuário autenticado
     * @param object $budget Dados do orçamento
     * @param object $customer Dados do cliente
     * @param array $pdf PDF anexo
     * @param string $token Token de confirmação
     * @return ServiceResult Resultado do envio
     */
    public function sendEmailApprovalBudgetNotification(
        object $authenticated,
        object $budget,
        object $customer,
        array $pdf,
        string $token,
    ): ServiceResult {
        try {
            $link = env( 'APP_URL' ) . "/budgets/choose-budget-status/code/{$budget->code}/token/{$token}";

            $emailData = [ 
                'first_name'         => $customer->first_name,
                'link'               => $link,
                'budget_code'        => $budget->code,
                'budget_description' => $budget->description ?? 'Não informada',
                'budget_total'       => number_format( (float) $budget->total, 2, ',', '.' ),
            ];

            $customerEmail = $customer->email_business ?? $customer->email;
            $subject       = "Seu orçamento {$budget->code} está pronto para visualização";

            $body = $this->twig->env->render( 'emails/notification-status-approval.twig', [ 
                'emailData'  => $emailData,
                'company'    => $authenticated,
                'urlSuporte' => buildUrl( '/support' ),
            ] );

            $sent = $this->mailerService->send(
                $customerEmail,
                $subject,
                $body,
                $pdf,
            );

            return $sent
                ? ServiceResult::success( true, 'Notificação de aprovação enviada com sucesso.' )
                : ServiceResult::error( OperationStatus::ERROR, 'Falha ao enviar notificação de aprovação.' );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao enviar notificação: ' . $e->getMessage() );
        }
    }

    /**
     * Envia um novo token de acesso ao orçamento para o cliente.
     *
     * @param object $authenticated Usuário autenticado
     * @param object $budget Dados do orçamento
     * @param object $customer Dados do cliente
     * @param string $token Novo token
     * @return ServiceResult Resultado do envio
     */
    public function sendNewTokenForBudgetNotification(
        object $authenticated,
        object $budget,
        object $customer,
        string $token,
    ): ServiceResult {
        try {
            $link = env( 'APP_URL' ) . "/budgets/choose-budget-status/code/{$budget->code}/token/{$token}";

            $emailData = [ 
                'first_name'         => $customer->first_name,
                'link'               => $link,
                'budget_code'        => $budget->code,
                'budget_description' => $budget->description ?? 'Não informada',
                'budget_total'       => number_format( (float) $budget->total, 2, ',', '.' ),
            ];

            $customerEmail = $customer->email_business ?? $customer->email;
            $subject       = "Novo Link de Acesso ao Orçamento #{$budget->code}";

            $body = $this->twig->env->render( 'emails/notification-new-token-budget.twig', [ 
                'emailData'  => $emailData,
                'company'    => $authenticated,
                'urlSuporte' => buildUrl( '/support' ),
            ] );

            $sent = $this->mailerService->send( $customerEmail, $subject, $body );

            return $sent
                ? ServiceResult::success( true, 'Novo token enviado com sucesso.' )
                : ServiceResult::error( OperationStatus::ERROR, 'Falha ao enviar novo token.' );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao enviar novo token: ' . $e->getMessage() );
        }
    }

    /**
     * Envia e-mail de notificação sobre a mudança de status de um serviço.
     *
     * @param object $authenticated Usuário autenticado
     * @param object $service Dados do serviço
     * @param object $newServiceStatuses Status novo
     * @param object $customer Dados do cliente
     * @param string $token Token para link
     * @return ServiceResult Resultado do envio
     */
    public function sendServiceStatusUpdate(
        object $authenticated,
        object $service,
        object $newServiceStatuses,
        object $customer,
        string $token,
    ): ServiceResult {
        try {
            $templates = [ 
                'default'     => 'notification-status',
                'SCHEDULED'   => 'notification-status-scheduled',
                'IN_PROGRESS' => 'notification-status-in-progress',
                'PARTIAL'     => 'notification-status-partial',
                'ON_HOLD'     => 'notification-status-on-hold',
                'COMPLETED'   => 'notification-status-completed',
                'CANCELLED'   => 'notification-status-cancelled',
            ];

            $templateName = $templates[ $newServiceStatuses->slug ] ?? $templates[ 'default' ];

            $link = env( 'APP_URL' ) . "/services/view-service-status/code/{$service->code}/token/{$token}";

            $emailData = [ 
                'first_name'          => $customer->first_name,
                'link'                => $link,
                'service_code'        => $service->code,
                'service_status_name' => $newServiceStatuses->name,
                'service_description' => $service->description ?? 'Não informada',
                'service_total'       => number_format( (float) $service->total, 2, ',', '.' ),
            ];

            $customerEmail = $customer->email_business ?? $customer->email;
            $subject       = "Atualização do Serviço #{$service->code}: {$newServiceStatuses->name}";

            $body = $this->twig->env->render( "emails/{$templateName}.twig", [ 
                'emailData'  => $emailData,
                'company'    => $authenticated,
                'urlSuporte' => buildUrl( '/support' ),
            ] );

            $sent = $this->mailerService->send( $customerEmail, $subject, $body );

            return $sent
                ? ServiceResult::success( true, 'Notificação de status do serviço enviada.' )
                : ServiceResult::error( OperationStatus::ERROR, 'Falha ao enviar notificação de status do serviço.' );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao enviar notificação de status: ' . $e->getMessage() );
        }
    }

    /**
     * Envia notificação de nova fatura.
     *
     * @param object $authenticated Usuário autenticado
     * @param object $invoice Dados da fatura
     * @param object $customer Dados do cliente
     * @return ServiceResult Resultado do envio
     */
    public function sendNewInvoiceNotification(
        object $authenticated,
        object $invoice,
        object $customer,
    ): ServiceResult {
        try {
            $pdf = $this->pdfService->generateInvoicePdf( $authenticated, $invoice );

            $publicLink = rtrim( env( 'APP_URL' ), '/' ) . '/invoices/view/' . $invoice->public_hash;

            $customerEmail = $customer->email_business ?: $customer->email;
            $subject       = "Sua fatura {$invoice->code} foi gerada";

            $body = $this->twig->env->render( 'emails/new-invoice.twig', [ 
                'invoice'     => $invoice,
                'company'     => $authenticated,
                'public_link' => $publicLink,
                'urlSuporte'  => buildUrl( '/support' ),
            ] );

            $sent = $this->mailerService->send(
                $customerEmail,
                $subject,
                $body,
                [ 
                    'content'  => $pdf[ 'content' ],
                    'fileName' => $pdf[ 'fileName' ],
                ],
            );

            return $sent
                ? ServiceResult::success( true, 'Notificação de nova fatura enviada.' )
                : ServiceResult::error( OperationStatus::ERROR, 'Falha ao enviar notificação de nova fatura.' );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao enviar notificação de fatura: ' . $e->getMessage() );
        }
    }

    /**
     * Envia notificação de status de plano.
     *
     * @param object $authenticated Usuário autenticado
     * @param array $paymentData Dados do pagamento
     * @return ServiceResult Resultado da operação
     */
    public function sendPlanSubscriptionStatusUpdate(
        object $authenticated,
        array $paymentData,
    ): ServiceResult {
        try {
            $status = $paymentData[ 'status' ];
            $code   = $paymentData[ 'plan_name' ];
            $url    = '';

            $subject    = '';
            $template   = 'emails/plan_subscription/status-update.twig';
            $statusText = '';
            $alertClass = '';

            switch ( $status ) {
                case 'approved':
                    $subject = "Seu plano {$code} foi ativado!";
                    $statusText = 'Aprovado';
                    $alertClass = 'success';
                    $url = buildUrl( '/provider' );
                    break;
                case 'pending':
                case 'in_process':
                    $subject = "Seu pagamento para o plano {$code} está pendente";
                    $statusText = 'Pendente';
                    $alertClass = 'warning';
                    $url = buildUrl( '/plans/status' );
                    break;
                case 'rejected':
                case 'cancelled':
                case 'failure':
                    $subject = "Problema no pagamento do plano {$code}";
                    $statusText = 'Recusado/Cancelado';
                    $alertClass = 'danger';
                    $url = buildUrl( '/plans/status' );
                    break;
                default:
                    return ServiceResult::success( null, 'Status não requer notificação.' );
            }

            $body = $this->twig->env->render( $template, [ 
                'user_name'   => $authenticated->first_name,
                'plan_name'   => $code,
                'status_text' => $statusText,
                'alert_class' => $alertClass,
                'status'      => $status,
                'url'         => $url,
                'urlSuporte'  => buildUrl( '/support' ),
            ] );

            $result = $this->mailerService->send(
                $authenticated->email_business ?? $authenticated->email,
                $subject,
                $body,
            );

            return $result
                ? ServiceResult::success( [ 'status' => 'success' ], 'Notificação de plano enviada.' )
                : ServiceResult::error( OperationStatus::ERROR, 'Falha ao enviar notificação de plano.' );
        } catch ( Throwable $e ) {
            getDetailedErrorInfo( $e );
            logger()->error( "Falha ao enviar e-mail de notificação de status de plano.", [ 'payment_data' => $paymentData ] );

            return ServiceResult::error( OperationStatus::ERROR, 'Falha ao enviar notificação de plano.' );
        }
    }

    /**
     * Envia notificação de status de fatura.
     *
     * @param object $customer Cliente
     * @param object $authenticated Autenticado
     * @param array $invoiceData Dados da fatura
     * @param array $payment Dados do pagamento
     * @return ServiceResult Resultado da operação
     */
    public function sendInvoiceStatusUpdate(
        object $customer,
        object $authenticated,
        array $invoiceData,
        array $payment,
    ): ServiceResult {
        try {
            $status = $payment[ 'status' ];
            $code   = $payment[ 'code' ];

            $subject    = '';
            $template   = 'emails/invoice/status-update.twig';
            $statusText = '';
            $alertClass = '';

            $statusEnum = mapPaymentStatusToInvoiceStatus( $status );

            switch ( $statusEnum->value ) {
                case 'paid':
                    $subject = "Sua fatura #{$code} foi paga!";
                    $statusText = 'Paga';
                    $alertClass = 'success';
                    $invoiceUrl = buildUrl( "/invoices/view/" . $invoiceData[ 'public_hash' ] );
                    $paymentUrl = null;
                    break;
                case 'pending':
                    $subject = "Fatura #{$code} aguardando pagamento";
                    $statusText = 'Pendente';
                    $alertClass = 'warning';
                    $invoiceUrl = buildUrl( "/invoices/view/" . $invoiceData[ 'public_hash' ] );
                    $paymentUrl = buildUrl( "/invoices/pay/" . $invoiceData[ 'public_hash' ] );
                    break;
                case 'overdue':
                    $subject = "Fatura #{$code} está vencida";
                    $statusText = 'Vencida';
                    $alertClass = 'danger';
                    $invoiceUrl = buildUrl( "/invoices/view/" . $invoiceData[ 'public_hash' ] );
                    $paymentUrl = buildUrl( "/invoices/pay/" . $invoiceData[ 'public_hash' ] );
                    break;
                case 'cancelled':
                    $subject = "Fatura #{$code} foi cancelada";
                    $statusText = 'Cancelada';
                    $alertClass = 'danger';
                    $invoiceUrl = buildUrl( "/invoices/view/" . $invoiceData[ 'public_hash' ] );
                    $paymentUrl = null;
                    break;
                default:
                    return ServiceResult::success( null, 'Status não requer notificação.' );
            }

            $body = $this->twig->env->render( $template, [ 
                'customer_name'      => $customer->first_name,
                'company'            => $authenticated,
                'invoice_code'       => $code,
                'transaction_amount' => number_format( (float) $invoiceData[ 'transaction_amount' ], 2, ',', '.' ),
                'due_date'           => $invoiceData[ 'due_date' ] ?? null,
                'status_text'        => $statusText,
                'alert_class'        => $alertClass,
                'status'             => $status,
                'invoice_url'        => $invoiceUrl,
                'payment_url'        => $paymentUrl,
                'urlSuporte'         => buildUrl( '/support' ),
            ] );

            $customerEmail = $customer->email_business ?? $customer->email;
            $result        = $this->mailerService->send( $customerEmail, $subject, $body );

            return $result
                ? ServiceResult::success( [ 'status' => 'success' ], 'Notificação de fatura enviada.' )
                : ServiceResult::error( OperationStatus::ERROR, 'Falha ao enviar notificação de fatura.' );
        } catch ( Throwable $e ) {
            logger()->error( "Falha ao enviar e-mail de notificação de status da fatura.", [ 'invoice_data' => $invoiceData ] );

            return ServiceResult::error( OperationStatus::ERROR, 'Falha ao enviar notificação de fatura.' );
        }
    }

    /**
     * Envia e-mail de suporte.
     *
     * @param SupportEntity $support Entidade de suporte
     * @return ServiceResult Resultado do envio
     */
    public function sendSupportEmail( SupportEntity $support ): ServiceResult
    {
        try {
            $emailData = [ 
                'first_name' => $support->first_name,
                'email'      => $support->email,
                'message'    => $support->message,
                'subject'    => $support->subject,
            ];

            $body = $this->twig->env->render( 'emails/support.twig', [ 
                'emailData'  => $emailData,
                'urlSuporte' => buildUrl( '/support' ),
            ] );

            $sent = $this->mailerService->send(
                $support->email,
                $support->subject,
                $body,
            );

            return $sent
                ? ServiceResult::success( true, 'E-mail de suporte enviado.' )
                : ServiceResult::error( OperationStatus::ERROR, 'Falha ao enviar e-mail de suporte.' );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao enviar e-mail de suporte: ' . $e->getMessage() );
        }
    }

    /**
     * Envia e-mail de confirmação de conta.
     *
     * @param string $email E-mail
     * @param string $firstName Nome
     * @param string $token Token
     * @return ServiceResult Resultado do envio
     */
    public function sendAccountConfirmation( string $email, string $firstName, string $token ): ServiceResult
    {
        try {
            $confirmationLink = env( 'APP_URL' ) . '/confirm-account?token=' . $token;
            $subject          = "Sua conta foi criada com sucesso! Confirme seu e-mail para ativar sua conta Easy Budget.";

            $body = $this->twig->env->render( 'emails/new-user.twig', [ 
                'first_name'       => $firstName,
                'confirmationLink' => $confirmationLink,
            ] );

            $sent = $this->mailerService->send( $email, $subject, $body );

            return $sent
                ? ServiceResult::success( true, 'Confirmação de conta enviada.' )
                : ServiceResult::error( OperationStatus::ERROR, 'Falha ao enviar confirmação de conta.' );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao enviar confirmação: ' . $e->getMessage() );
        }
    }

    /**
     * Envia e-mail de reenvio de confirmação.
     *
     * @param string $email E-mail
     * @param string $firstName Nome
     * @param string $token Token
     * @return ServiceResult Resultado do envio
     */
    public function sendResendConfirmation( string $email, string $firstName, string $token ): ServiceResult
    {
        try {
            $confirmationLink = env( 'APP_URL' ) . '/confirm-account?token=' . $token;
            $subject          = 'Novo link de confirmação. Confirme seu e-mail para ativar sua conta Easy Budget.';

            $body = $this->twig->env->render( 'emails/new-user.twig', [ 
                'first_name'       => $firstName,
                'confirmationLink' => $confirmationLink,
            ] );

            $sent = $this->mailerService->send( $email, $subject, $body );

            return $sent
                ? ServiceResult::success( true, 'Reenvio de confirmação enviado.' )
                : ServiceResult::error( OperationStatus::ERROR, 'Falha ao reenviar confirmação.' );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao reenviar confirmação: ' . $e->getMessage() );
        }
    }

    /**
     * Envia e-mail de recuperação de senha.
     *
     * @param string $email E-mail
     * @param string $firstName Nome
     * @param string $newPassword Nova senha
     * @return ServiceResult Resultado do envio
     */
    public function sendPasswordReset( string $email, string $firstName, string $newPassword ): ServiceResult
    {
        try {
            $body = $this->twig->env->render( 'emails/forgot-password.twig', [ 
                'message'    => $newPassword,
                'first_name' => $firstName,
                'url'        => env( 'APP_URL' ) . '/login',
                'date'       => date( "Y" )
            ] );

            $sent = $this->mailerService->send(
                $email,
                'Sua nova senha - Easy Budget',
                $body,
                null,
                env( 'EMAIL_FROM' ),
                env( 'EMAIL_FROM_NAME' ),
            );

            return $sent
                ? ServiceResult::success( true, 'Recuperação de senha enviada.' )
                : ServiceResult::error( OperationStatus::ERROR, 'Falha ao enviar recuperação de senha.' );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao enviar recuperação: ' . $e->getMessage() );
        }
    }

    /**
     * Envia e-mail de alteração de senha.
     *
     * @param string $email E-mail
     * @param string $firstName Nome
     * @param string $blockToken Token para bloqueio
     * @return ServiceResult Resultado do envio
     */
    public function sendPasswordChanged( string $email, string $firstName, string $blockToken ): ServiceResult
    {
        try {
            $blockLink = env( 'APP_URL' ) . '/block-account?token=' . urlencode( $blockToken );

            $body = $this->twig->env->render( 'emails/new-password.twig', [ 
                'date'       => date( "Y" ),
                'first_name' => $firstName,
                'blockLink'  => $blockLink,
                'url'        => env( 'APP_URL' ) . '/login',
            ] );

            $sent = $this->mailerService->send(
                $email,
                'Sua nova senha - Easy Budget',
                $body,
                null,
                env( 'EMAIL_FROM' ),
                env( 'EMAIL_FROM_NAME' ),
            );

            return $sent
                ? ServiceResult::success( true, 'Alteração de senha notificada.' )
                : ServiceResult::error( OperationStatus::ERROR, 'Falha ao notificar alteração de senha.' );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao notificar alteração: ' . $e->getMessage() );
        }
    }

}