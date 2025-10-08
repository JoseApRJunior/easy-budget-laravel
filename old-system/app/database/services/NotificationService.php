<?php

namespace app\database\services;

use app\database\entities\SupportEntity;
use core\library\Twig;

class NotificationService
{

    public function __construct(
        private MailerService $mailer,
        private PdfService $pdfService,
        private Twig $twig,
    ) {}

    // todo testar todas as notificaçoes

    /**
     * Envia e-mail de notificação de status do orçamento para aprovação do cliente.
     * Este método agora espera o token e o PDF já gerados.
     *
     * @param object $authenticated Objeto com os dados do usuário autenticado.
     * @param object $budget Objeto com os dados do orçamento.
     * @param object $customer Objeto com os dados do cliente.
     * @param array $pdf Array contendo 'content' e 'fileName' do PDF.
     * @param string $token O token de confirmação para o link.
     * @return bool Retorna true se o e-mail foi enviado com sucesso, false caso contrário.
     */
    public function sendEmailApprovalBudgetNotification( object $authenticated, object $budget, object $customer, array $pdf, string $token ): bool
    {
        $link = env( 'APP_URL' ) . "/budgets/choose-budget-status/code/{$budget->code}/token/{$token}";

        $emailData     = [ 
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

        return $this->mailer->send(
            $customerEmail,
            $subject,
            $body,
            $pdf,
        );

    }

    /**
     * Envia um novo token de acesso ao orçamento para o cliente.
     *
     * @param object $authenticated Objeto com os dados do usuário autenticado.
     * @param object $budget Array com os dados do orçamento.
     * @param object $customer Array com os dados do cliente.
     * @param string $token O novo token de acesso.
     * @return bool
     */
    public function sendNewTokenForBudgetNotification( object $authenticated, object $budget, object $customer, string $token ): bool
    {
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

        return $this->mailer->send( $customerEmail, $subject, $body );
    }

    /**
     * Envia e-mail de notificação sobre a mudança de status de um serviço.
     *
     * @param object $authenticated Objeto com os dados do usuário autenticado.
     * @param object $service Objeto com os dados do serviço.
     * @param object $customer Objeto com os dados do cliente.
     * @param string $token Token para o link de visualização.
     * @return bool
     */
    public function sendServiceStatusUpdate( object $authenticated, object $service, object $newServiceStatuses, object $customer, string $token ): bool
    {
        $templates = [ 
            'default'     => 'notification-status',
            'SCHEDULED'   => 'notification-status-scheduled',
            'IN_PROGRESS' => 'notification-status-in-progress',
            'PARTIAL'     => 'notification-status-partial',
            'ON_HOLD'     => 'notification-status-on-hold',
            'COMPLETED'   => 'notification-status-completed',
            'CANCELLED'   => 'notification-status-cancelled',
        ];

        $template_name = $templates[ $newServiceStatuses->slug ] ?? $templates[ 'default' ];

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

        $body = $this->twig->env->render( "emails/{$template_name}.twig", [ 
            'emailData'  => $emailData,
            'company'    => $authenticated,
            'urlSuporte' => buildUrl( '/support' ),
        ] );

        return $this->mailer->send( $customerEmail, $subject, $body );
    }

    public function sendNewInvoiceNotification( object $authenticated, object $invoice, object $customer ): bool
    {
        // todo tem um erro o email tem que ser inviado para o customer
        // 1. Gera o PDF da fatura
        $pdf = $this->pdfService->generateInvoicePdf( $authenticated, $invoice );

        // Gera o link público para pagamento
        $publicLink = rtrim( env( 'APP_URL' ), '/' ) . '/invoices/view/' . $invoice->public_hash;

        // 2. Prepara os dados do e-mail
        $customerEmail = $customer->email_business ?: $customer->email;
        $subject       = "Sua fatura {$invoice->code} foi gerada";

        // 3. Renderiza o corpo do e-mail com Twig
        $body = $this->twig->env->render( 'emails/new-invoice.twig', [ 
            'invoice'     => $invoice,
            'company'     => $authenticated,
            'public_link' => $publicLink,
            'urlSuporte'  => buildUrl( '/support' ),
        ] );

        // 4. Envia o e-mail
        return $this->mailer->send(
            $customerEmail,
            $subject,
            $body,
            [ 
                'content'  => $pdf[ 'content' ],
                'fileName' => $pdf[ 'fileName' ],
            ],
        );
    }

    /**
     * Envia um e-mail de notificação sobre o status de assinatura de um plano.
     *
     * @param object $authenticated Objeto com os dados do usuário autenticado.
     * @param array $paymentData Array contendo os dados do pagamento.
     * @return array Retorna um array com o status e a mensagem da operação.
     */
    public function sendPlanSubscriptionStatusUpdate( object $authenticated, array $paymentData ): array
    {
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
                    logger()->info( "Nenhuma notificação por e-mail enviada para o status de pagamento '{$status}'." );

                    break;
            }

            $body   = $this->twig->env->render( $template, [ 
                'user_name'   => $authenticated->first_name,
                'plan_name'   => $code,
                'status_text' => $statusText,
                'alert_class' => $alertClass,
                'status'      => $status,
                'url'         => $url,
                'urlSuporte'  => buildUrl( '/support' ),
            ] );
            $result = $this->mailer->send( $authenticated->email_business ?? $authenticated->email, $subject, $body );

            return [ 
                'status'  => $result == true ? 'success' : 'error',
                'message' => $result == true ? 'E-mail enviado com sucesso.' : 'Falha ao enviar e-mail.',
            ];

        } catch ( \Throwable $e ) {
            getDetailedErrorInfo( $e );
            logger()->error( "Falha ao enviar e-mail de notificação de status de plano.", [ 'payment_data' => $paymentData ] );

            return [ 
                'status'  => 'error',
                'message' => 'Falha ao enviar e-mail de notificação de status de plano.',
            ];
        }
    }

    /**
     * Envia um e-mail de notificação sobre o status de uma fatura.
     *
     * @param object $customer Objeto com os dados do cliente.
     * @param array $invoiceData Array contendo os dados da fatura.
     * @return array Retorna um array com o status e a mensagem da operação.
     */
    public function sendInvoiceStatusUpdate( object $customer, object $authenticated, array $invoiceData, array $payment ): array
    {
        try {

            $status = $payment[ 'status' ];
            $code   = $payment[ 'code' ];

            $subject    = '';
            $template   = 'emails/invoice/status-update.twig';
            $statusText = '';
            $alertClass = '';

            switch ( mapPaymentStatusToInvoiceStatus( $status )->value ) {
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
                    logger()->info( "Nenhuma notificação por e-mail enviada para o status de fatura '{$status}'." );

                    return [ 
                        'status'  => 'success',
                        'message' => 'Status não requer notificação.',
                    ];
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
            $result        = $this->mailer->send( $customerEmail, $subject, $body );

            return [ 
                'status'  => $result == true ? 'success' : 'error',
                'message' => $result == true ? 'E-mail enviado com sucesso.' : 'Falha ao enviar e-mail.',
            ];

        } catch ( \Throwable $e ) {
            logger()->error( "Falha ao enviar e-mail de notificação de status da fatura.", [ 'invoice_data' => $invoiceData ] );

            return [ 
                'status'  => 'error',
                'message' => 'Falha ao enviar e-mail de notificação de status da fatura.',
            ];
        }
    }

    public function sendSupportEmail( SupportEntity $support ): bool
    {
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

        return $this->mailer->send(
            $support->email,
            $support->subject,
            $body,
        );

    }

}
