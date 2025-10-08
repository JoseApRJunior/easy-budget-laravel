<?php

namespace app\database\services;

use app\database\models\Invoice;
use app\database\models\ProviderCredential;
use app\database\services\EncryptionService;
use core\dbal\EntityNotFound;
use core\library\Session;
use MercadoPago\Client\Payment\PaymentClient;
use MercadoPago\MercadoPagoConfig;
use RuntimeException;

class InvoicePaymentService
{
    protected string $table = 'invoice_payments';

    private $authenticated;

    public function __construct(
        private Invoice $invoice,
        private ProviderCredential $providerCredential,
        private EncryptionService $encryptionService,
    ) {
        if ( Session::has( 'auth' ) ) {
            $this->authenticated = Session::get( 'auth' );
        }
    }

    /**
     * Cria um pagamento no Mercado Pago em nome de um prestador de serviço.
     *
     * @param integer $invoiceId O ID da fatura a ser paga.
     * @param array $paymentData Dados do pagamento vindos do frontend (token, installments, etc.).
     * @return array
     */
    public function createMercadoPagoPayment( int $invoiceId, array $paymentData, int $tenant_id ): array
    {
        // 1. Buscar a fatura para obter o valor e o ID do prestador.
        $invoice = $this->invoice->findBy( [ $invoiceId, $tenant_id ] );
        if ( $invoice instanceof EntityNotFound ) {
            throw new RuntimeException( "Fatura não encontrada." );
        }

        // 2. Buscar as credenciais do prestador de serviço associado à fatura.
        /** @var \app\database\entities\InvoiceEntity $invoice */
        $credentials = $this->providerCredential->findByProvider( $this->authenticated->provider_id, $tenant_id );
        if ( $credentials instanceof EntityNotFound ) {
            throw new RuntimeException( "Credenciais de pagamento do prestador não encontradas." );
        }

        // 3. Descriptografar o Access Token do prestador.
        $accessToken = $this->encryptionService->decrypt( $credentials->access_token_encrypted );

        // 4. Configurar o SDK do Mercado Pago para usar o Access Token do PRESTADOR.
        MercadoPagoConfig::setAccessToken( $accessToken );

        // 5. Criar a requisição de pagamento.
        $client  = new PaymentClient();
        $request = [ 
            "transaction_amount" => (float) $invoice->total,
            "token"              => $paymentData[ 'token' ],
            "description"        => "Pagamento da Fatura #" . $invoice->code,
            "installments"       => (int) $paymentData[ 'installments' ],
            "payment_method_id"  => $paymentData[ 'payment_method_id' ],
            "issuer_id"          => (int) $paymentData[ 'issuer_id' ],
            "payer"              => [ 
                "email"          => $paymentData[ 'payer' ][ 'email' ],
                "identification" => [ 
                    "type"   => $paymentData[ 'payer' ][ 'identification' ][ 'type' ],
                    "number" => $paymentData[ 'payer' ][ 'identification' ][ 'number' ]
                ]
            ],
            "external_reference" => $invoice->id,
            "notification_url"   => rtrim( env( 'APP_URL' ), '/' ) . '/webhooks/mercadopago'
        ];

        try {
            // 6. Executar a criação do pagamento.
            $payment = $client->create( $request );

            // TODO: Adicionar lógica para atualizar o status da fatura para "paga".
            // Ex: $this->invoice->updateStatus($invoice->id, 'paid');

            return [ 
                'status'  => 'success',
                'payment' => $payment,
            ];
        } catch ( \MercadoPago\Exceptions\MPApiException $e ) {
            $errorContent = json_encode( $e->getApiResponse()->getContent() );
            logger()->error( "Erro ao criar pagamento MP para a fatura {$invoice->id}: " . $errorContent );
            throw new RuntimeException( "Erro ao processar o pagamento: " . $e->getApiResponse()->getContent()[ 'message' ] );
        }
    }

}
