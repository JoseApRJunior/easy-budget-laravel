<?php

namespace app\database\services;

use app\database\entities\UserConfirmationTokenEntity;
use app\database\models\Budget;
use app\database\models\Customer;
use app\database\models\UserConfirmationToken;
use core\dbal\EntityNotFound;
use core\library\Session;
use core\library\Twig;
use core\support\Email;
use core\support\report\PdfGenerator;
use Doctrine\DBAL\Connection;
use Exception;
use RuntimeException;

class NotifyChangeOfStatus
{
    /**
     * Summary of table
     * @var string
     */
    protected string $tableUsers    = 'users';
    private          $authenticated;

    public function __construct(
        private readonly Connection $connection,
        private Customer $customer,
        private Budget $budget,
        private Email $phpMailer,
        private UserConfirmationToken $userConfirmationToken,
        private Twig $twig,
        private PdfGenerator $pdfGenerator,

    ) {
        if ( Session::has( 'auth' ) ) {
            $this->authenticated = Session::get( 'auth' );
        }

    }

    public function generate_budget_pdf_email( array $authenticated, array $customer, array $budget, array $services, array $service_items, array $latest_schedules, string $verificationHash )
    {

        $date = new \DateTime();

        $pdf_name = sprintf(
            'orcamento_%s_%s.pdf',
            $budget[ 'code' ],
            $date->format( 'Ymd_H_i_s' ),

        );

        $html = $this->twig->env->render( 'pages/budget/pdf_budget_print.twig', [ 
            'authenticated'    => (array) $authenticated,
            'budget'           => $budget,
            'customer'         => $customer,
            'services'         => $services,
            'service_items'    => $service_items,
            'latest_schedules' => $latest_schedules,
            'date'             => $date,
        ] );

        $pdfGenerated = $this->pdfGenerator->generate( $html, $pdf_name, $verificationHash );

        return [ 
            'content'  => $pdfGenerated[ 'content' ],
            'fileName' => $pdf_name
        ];

    }

    public function generate_service_pdf_email( array $authenticated, array $customer, array $budget, array $service, array $serviceItems, array $latest_schedule, string $verificationHash )
    {

        $date = new \DateTime();

        $pdf_name = sprintf(
            'servico_%s_%s.pdf',
            $service[ 'code' ],
            $date->format( 'Ymd_H_i_s' ),

        );

        $html = $this->twig->env->render( 'pages/service/pdf_service_print.twig', [ 
            'authenticated'   => $authenticated,
            'budget'          => $budget,
            'customer'        => $customer,
            'service'         => $service,
            'serviceItems'    => $serviceItems,
            'latest_schedule' => $latest_schedule,
            'date'            => $date,
        ] );

        $pdfGenerated = $this->pdfGenerator->generate( $html, $pdf_name, $verificationHash );

        return [ 
            'content'  => $pdfGenerated[ 'content' ],
            'fileName' => $pdf_name
        ];

    }

    public function sendEmailNotificationStatusApproval( object $budget, object $customer, $pdf )
    {
        try {
            return $this->connection->transactional( function () use ($budget, $customer, $pdf) {
                // Sessão criar userConfirmationToken
                // Gera um token para confirmação de conta
                [ $token, $expiresDate ] = generateTokenExpirate( '+7 days' );
                // popula model UserConfirmationTokenEntity
                $userConfirmationTokenEntity = UserConfirmationTokenEntity::create( [ 
                    'tenant_id'  => $this->authenticated->tenant_id,
                    'user_id'    => $this->authenticated->user_id,
                    'token'      => $token,
                    'expires_at' => $expiresDate,
                ] );

                // Criar UserConfirmationTokens e retorna o id do userConfirmationToken
                $result = $this->userConfirmationToken->create( $userConfirmationTokenEntity );
                // verifica se o userConfirmationToken foi criado com sucesso
                if ( $result[ 'status' ] === 'error' ) {
                    return [ 
                        'status'  => 'error',
                        'message' => 'Erro ao criar o token de confirmação.'
                    ];
                }
                // Fim da sessão criar userConfirmationToken

                // Sessão de configuração de dados do usuário

                $link = env( 'APP_URL' ) . '/budgets/choose-budget-status/code/' . $budget->code . '/token/' . $token;

                $data = [ 
                    'first_name' => $customer->first_name,
                    'last_name'  => $customer->last_name,
                    'link'       => $link,
                    'email'      => $customer->email_business ?? $customer->email,
                    'subject'    => 'Notificação de Status do Orçamento',
                    'message'    => "Seu orçamento está pronto para ser visualizado.",
                ];

                // Fim da sessão de configuração de dados do usuário

                // Sessão de configuração de envio de e-mail

                $this->phpMailer->addStringAttachment(
                    $pdf[ 'content' ],
                    $pdf[ 'fileName' ],
                    'base64',
                    'application/pdf',
                );

                $result = $this->phpMailer
                    ->from( env( 'EMAIL_FROM' ), env( 'EMAIL_FROM_NAME' ) )
                    ->to( $data[ 'email' ] )
                    ->template( 'notification-status-approval', [ 
                        'date'               => date( "Y" ),
                        'first_name'         => $data[ 'first_name' ],
                        'last_name'          => $data[ 'last_name' ],
                        'email'              => $data[ 'email' ],
                        'company_name'       => $this->authenticated->company_name,
                        'link'               => $data[ 'link' ],
                        'url'                => env( 'APP_URL' ), // Adiciona a URL base do aplicativo
                        'budget_code'        => $budget->code,
                        'budget_status_name' => $budget->status_name,
                        'budget_description' => $budget->description ?? 'Não informada', // Garante que sempre haja um valor
                        'budget_total'       => number_format( $budget->total, 2, ',', '.' ), // Formata o valor
                    ] )
                    ->subject( $data[ 'subject' ] )
                    ->message( $data[ 'message' ] )
                    ->send();

                // Fim da sessão de configuração de envio de e-mail

                return [ 
                    'status'  => $result[ 'status' ] === 'success' ? 'success' : 'error',
                    'message' => $result[ 'status' ] === 'success' ? 'Notificação enviada com sucesso.' : 'Não foi possivel enviar a notificação.',
                    'data'    => [ 
                        'id'         => $result[ 'data' ][ 'id' ],
                        'first_name' => $data[ 'first_name' ],
                        'last_name'  => $data[ 'last_name' ],
                        'email'      => $data[ 'email' ],
                        'subject'    => $data[ 'subject' ],
                        'message'    => $data[ 'message' ],
                    ]
                ];
            } );
        } catch ( Exception $e ) {
            throw new RuntimeException( "Falha ao enviar notificação de status, tente novamente mais tarde ou entre em contato com suporte!", 0, $e );
        }
    }

    public function sendEmailNotificationStatusService( array $service, string $token, string $template_type = 'default', $authenticated = null )
    {
        if ( $this->authenticated === null ) {
            $this->authenticated = $authenticated;
        }
        try {
            return $this->connection->transactional( function () use ($service, $token, $template_type) {

                $templates = [ 
                    'default'     => 'notification-status',
                    'scheduled'   => 'notification-status-scheduled',
                    'in_progress' => 'notification-status-in-progress',
                    'partial'     => 'notification-status-partial',
                    'on_hold'     => 'notification-status-on-hold',
                    'completed'   => 'notification-status-completed',
                    'cancelled'   => 'notification-status-cancelled',
                    // Adicione outros templates aqui
                ];

                $template_name = $templates[ $template_type ] ?? $templates[ 'default' ];
                $subject       = "Notificação de Status do Serviço: " . ucfirst( $service[ 'status_name' ] );
                $message       = "O status do seu serviço foi atualizado.";

                $budget = $this->budget->getBudgetFullById( $service[ 'budget_id' ], $this->authenticated->tenant_id );
                // Verificar se o orçamento existe
                if ( $budget instanceof EntityNotFound ) {
                    return [ 
                        'status'  => 'error',
                        'message' => 'Orçamento não encontrado.'
                    ];
                }

                // Sessão de configuração de dados do usuário
                $customer = $this->customer->getCustomerFullbyId( $budget->customer_id, $this->authenticated->tenant_id );
                if ( $customer instanceof EntityNotFound ) {
                    return [ 
                        'status'  => 'error',
                        'message' => 'Cliente não encontrado.',
                    ];
                }

                $link = env( 'APP_URL' ) . '/services/view-service-status/code/' . $service[ 'code' ] . '/token/' . $token;

                $data = [ 
                    'first_name' => $customer->first_name,
                    'last_name'  => $customer->last_name,
                    'link'       => $link,
                    'email'      => $customer->email_business ?? $customer->email,
                    'subject'    => $subject,
                    'message'    => $message,
                ];

                // Fim da sessão de configuração de dados do usuário

                $result = $this->phpMailer
                    ->from( env( 'EMAIL_FROM' ), env( 'EMAIL_FROM_NAME' ) )
                    ->to( $data[ 'email' ] )
                    ->template( $template_name, [ 
                        'date'                => date( "Y" ),
                        'first_name'          => $data[ 'first_name' ],
                        'last_name'           => $data[ 'last_name' ],
                        'email'               => $data[ 'email' ],
                        'company_name'        => $this->authenticated->company_name,
                        'link'                => $data[ 'link' ],
                        'url'                 => env( 'APP_URL' ), // Adiciona a URL base do aplicativo
                        'service_code'        => $service[ 'code' ],
                        'service_status_name' => $service[ 'status_name' ],
                        'service_description' => $service[ 'description' ] ?? 'Não informada', // Garante que sempre haja um valor
                        'service_total'       => number_format( $service[ 'total' ], 2, ',', '.' ), // Formata o valor
                    ] )
                    ->subject( $data[ 'subject' ] )
                    ->message( $data[ 'message' ] )
                    ->send();

                // Fim da sessão de configuração de envio de e-mail

                return [ 
                    'status'  => $result[ 'status' ] === 'success' ? 'success' : 'error',
                    'message' => $result[ 'status' ] === 'success' ? 'Notificação enviada com sucesso.' : 'Não foi possivel enviar a notificação.',
                    'data'    => [ 
                        'id'         => $result[ 'data' ][ 'id' ],
                        'first_name' => $data[ 'first_name' ],
                        'last_name'  => $data[ 'last_name' ],
                        'email'      => $data[ 'email' ],
                        'subject'    => $data[ 'subject' ],
                        'message'    => $data[ 'message' ],
                    ]
                ];
            } );
        } catch ( Exception $e ) {
            throw new RuntimeException( "Falha ao enviar notificação de status, tente novamente mais tarde ou entre em contato com suporte!", 0, $e );
        }
    }

    public function sendNewTokenForBudgetEmail( object $budget, string $token, object $authenticated = null )
    {
        if ( $this->authenticated === null ) {
            $this->authenticated = $authenticated;
        }
        try {
            return $this->connection->transactional( function () use ($budget, $token) {

                $customer = $this->customer->getCustomerFullbyId( $budget->customer_id, $this->authenticated->tenant_id );
                if ( $customer instanceof EntityNotFound ) {
                    return [ 
                        'status'  => 'error',
                        'message' => 'Cliente não encontrado.',
                    ];
                }

                $link = env( 'APP_URL' ) . '/budgets/choose-budget-status/code/' . $budget->code . '/token/' . $token;

                $data = [ 
                    'first_name' => $customer->first_name,
                    'last_name'  => $customer->last_name,
                    'link'       => $link,
                    'email'      => $customer->email_business ?? $customer->email,
                    'subject'    => 'Novo Link de Acesso ao Orçamento',
                    'message'    => 'Seu link de acesso ao orçamento foi renovado.',
                ];

                $result = $this->phpMailer
                    ->from( env( 'EMAIL_FROM' ), env( 'EMAIL_FROM_NAME' ) )
                    ->to( $data[ 'email' ] )
                    ->template( 'notification-new-token-budget', [ 
                        'date'               => date( "Y" ),
                        'first_name'         => $data[ 'first_name' ],
                        'last_name'          => $data[ 'last_name' ],
                        'company_name'       => $this->authenticated->company_name,
                        'link'               => $data[ 'link' ],
                        'budget_code'        => $budget->code,
                        'budget_description' => $budget->description ?? 'Não informada',
                        'budget_total'       => number_format( $budget->total, 2, ',', '.' ),
                    ] )
                    ->subject( $data[ 'subject' ] )
                    ->message( $data[ 'message' ] )
                    ->send();

                return [ 
                    'status'  => $result[ 'status' ] === 'success' ? 'success' : 'error',
                    'message' => $result[ 'status' ] === 'success' ? 'Notificação enviada com sucesso.' : 'Não foi possível enviar a notificação.',
                    'data'    => $result[ 'data' ],
                ];
            } );
        } catch ( Exception $e ) {
            throw new RuntimeException( "Falha ao enviar notificação de novo token, tente novamente mais tarde ou entre em contato com suporte!", 0, $e );
        }
    }

}