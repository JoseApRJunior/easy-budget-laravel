<?php

namespace app\database\services;

use app\database\entities\UserConfirmationTokenEntity;
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

class NotificationStatusService
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
        private Email $phpMailer,
        private UserConfirmationToken $userConfirmationToken,
        private Twig $twig,
        private PdfGenerator $pdfGenerator,

    ) {
        if ( Session::has( 'auth' ) ) {
            $this->authenticated = Session::get( 'auth' );
        }

    }

    public function generate_budget_pdf_email( array $customer, array $budget, array $services, array $service_items )
    {

        $date = new \DateTime();

        $pdf_name = sprintf(
            'orcamento_%s_%s.pdf',
            $budget[ 'code' ],
            $date->format( 'Ymd_H_i_s' ),

        );

        $html = $this->twig->env->render( 'pages/budget/pdf_budget_show.twig', [ 
            'budget'        => $budget,
            'customer'      => $customer,
            'services'      => $services,
            'service_items' => $service_items,
            'date'          => $date,
        ] );

        $pdfGenerated = $this->pdfGenerator->generate( $html, $pdf_name );

        return [ 
            'content'  => $pdfGenerated[ 'content' ],
            'fileName' => $pdf_name
        ];

    }

    public function sendEmailNotificationStatus( $budget, $services, $service_items )
    {
        try {
            return $this->connection->transactional( function () use ($budget, $services, $service_items) {
                // TODO  testando o pdf, gerar ele e melhor para vizualizar e enviar no email
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
                    'subject'    => 'Notificação de Status do Orçamento',
                    'message'    => "Seu orçamento está pronto para ser visualizado.",
                ];

                // Fim da sessão de configuração de dados do usuário

                // Sessão de configuração de envio de e-mail
                $pdf = $this->generate_budget_pdf_email( (array) $customer, (array) $budget, $services, $service_items );

                $this->phpMailer->addStringAttachment(
                    $pdf[ 'content' ],
                    $pdf[ 'fileName' ],
                    'base64',
                    'application/pdf',
                );

                $result = $this->phpMailer
                    ->from( env( 'EMAIL_FROM' ), env( 'EMAIL_FROM_NAME' ) )
                    ->to( $data[ 'email' ] )
                    ->template( 'notification-status', [ 
                        'date'               => date( "Y" ),
                        'first_name'         => $data[ 'first_name' ],
                        'last_name'          => $data[ 'last_name' ],
                        'email'              => $data[ 'email' ],
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

}