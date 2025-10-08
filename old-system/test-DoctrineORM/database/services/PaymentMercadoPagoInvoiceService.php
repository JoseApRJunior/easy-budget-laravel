<?php

namespace app\database\services;

use app\database\entitiesORM\PaymentMercadoPagoInvoicesEntity;
use app\database\entitiesORM\ProviderCredentialEntity;
use app\database\models\Customer;
use app\database\models\Invoice;
use app\database\models\PaymentMercadoPagoInvoices;
use app\database\models\ProviderCredential;
use core\dbal\EntityNotFound;
use Doctrine\DBAL\Connection;
use Exception;
use MercadoPago\Client\Preference\PreferenceClient;
use MercadoPago\Exceptions\MPApiException;
use MercadoPago\MercadoPagoConfig;
use MercadoPago\Resources\Preference;
use RuntimeException;

class PaymentMercadoPagoInvoiceService
{
    protected string $table = 'payment_mercado_pago_invoices';

    public function __construct(
        private Invoice $invoiceModel,
        private Customer $customerModel,
        private ProviderCredential $providerCredentialModel,
        private EncryptionService $encryptionService,
        private readonly Connection $connection,
        private PaymentMercadoPagoInvoices $paymentMercadoPagoInvoicesModel,
    ) {}

    /**
     * Cria ou atualiza um registro de pagamento para uma fatura.
     * Isso garante idempotência para notificações de webhook.
     *
     * @param array<string, mixed> $webhookPaymentData Dados da API do Mercado Pago.
     * @return array<string, mixed> O resultado da operação do banco de dados.
     */
    public function createOrUpdatePayment( array $webhookPaymentData ): array
    {
        try {

            return $this->connection->transactional( function () use ($webhookPaymentData) {
                // 1. Validação de Negócio: Verifica se já existe um pagamento ativo para esta fatura.
                $existingPayments = $this->paymentMercadoPagoInvoicesModel->getPaymentsByInvoice(
                    $webhookPaymentData[ 'invoice_id' ],
                    $webhookPaymentData[ 'tenant_id' ],
                );

                // Se já existe um pagamento em andamento ou aprovado, não faz nada.
                if ( !$existingPayments instanceof EntityNotFound ) {
                    $payments         = is_array( $existingPayments ) ? $existingPayments : [ $existingPayments ];
                    $blockingStatuses = [ 'pending', 'authorized', 'in_process', 'in_mediation', 'approved' ];

                    foreach ( $payments as $payment ) {
                        /** @var PaymentMercadoPagoInvoicesEntity $payment */
                        // Bloqueia apenas se o pagamento ativo for DIFERENTE do que estamos processando agora.
                        if ( in_array( $payment->status, $blockingStatuses ) && $payment->payment_id !== $webhookPaymentData[ 'payment_id' ] ) {
                            return [ 
                                'status'  => 'success',
                                'message' => "Um pagamento para fatura já está em andamento. Nenhuma nova ação foi tomada.",
                                'data'    => $payment->toArray(),
                            ];
                        }
                    }
                }

                // 2. Busca o registro de pagamento específico do webhook.
                $existingPayment = $this->paymentMercadoPagoInvoicesModel->getPaymentId(
                    $webhookPaymentData[ 'payment_id' ],
                    $webhookPaymentData[ 'tenant_id' ],
                );

                // 3. Mapeia o status da string para o Enum.
                $webhookPaymentData[ 'status' ] = mapPaymentStatusMercadoPago( $webhookPaymentData[ 'status' ] )->value;

                // 4. Decide se deve criar ou atualizar.
                if ( $existingPayment instanceof EntityNotFound ) {
                    // CRIAR
                    $properties = getConstructorProperties( PaymentMercadoPagoInvoicesEntity::class);
                    $entity     = PaymentMercadoPagoInvoicesEntity::create( removeUnnecessaryIndexes(
                        $properties,
                        [ 'id', 'created_at', 'updated_at' ],
                        $webhookPaymentData,
                    ) );

                    return $this->paymentMercadoPagoInvoicesModel->create( $entity );
                }

                // ATUALIZAR
                /** @var PaymentMercadoPagoInvoicesEntity $existingPayment */
                if ( $existingPayment->status === $webhookPaymentData[ 'status' ] ) {
                    return [ 
                        'status'                      => 'success',
                        'message'                     => 'Pagamento já existe com o mesmo status. Nenhuma alteração necessária.',
                        'data'                        => $existingPayment->toArray(),
                        'invoicePaymentAlreadyExists' => true,
                    ];
                } else {
                    // Prepara os dados para a atualização.
                    $updateData                         = $existingPayment->toArray();
                    $updateData[ 'status' ]             = $webhookPaymentData[ 'status' ];
                    $updateData[ 'transaction_amount' ] = $webhookPaymentData[ 'transaction_amount' ];
                    $updateData[ 'payment_method' ]     = $webhookPaymentData[ 'payment_method' ];
                    $entityToUpdate                     = PaymentMercadoPagoInvoicesEntity::create( $updateData );

                    return $this->paymentMercadoPagoInvoicesModel->update( $entityToUpdate );
                }
            } );
        } catch ( Exception $e ) {
            getDetailedErrorInfo( $e );
            logger()->error( "Erro ao criar ou atualizar o pagamento: " . $e->getMessage(), [ 'exception' => $e ] );

            return [ 
                'status'  => 'error',
                'message' => 'Erro inesperado ao processar o pagamento.',
            ];

        }
    }

    /**
     * Cria uma preferência de pagamento (link de pagamento) no Mercado Pago.
     *
     * @param string $invoiceCode Código da fatura.
     * @param int $tenantId ID do tenant.
     * @return array<string, mixed> O link de pagamento (init_point).
     */
    public function createMercadoPagoPreference( string $invoiceCode, int $tenantId ): array
    {
        // 1. Buscar a fatura completa para obter os dados do cliente e do prestador.
        $invoice = $this->invoiceModel->getInvoiceFullByCode( $invoiceCode, $tenantId );
        if ( $invoice instanceof EntityNotFound ) {
            return [ 
                'status'  => 'error',
                'message' => 'Fatura não encontrada.',
            ];
        }

        // 2. Buscar os dados do cliente associado à fatura.
        $customer = $this->customerModel->getCustomerFullById( $invoice->customer_id, $tenantId );
        if ( $customer instanceof EntityNotFound ) {
            return [ 
                'status'  => 'error',
                'message' => 'Cliente não encontrado.',
            ];
        }

        // 3. Buscar as credenciais do prestador de serviço associado à fatura.
        $credentials = $this->providerCredentialModel->findByProvider( $tenantId );
        if ( $credentials instanceof EntityNotFound ) {
            return [ 
                'status'  => 'error',
                'message' => 'Credenciais do prestador não encontradas, entre em contato com o suporte.',
            ];
        }

        // 4. Descriptografar o Access Token do prestador e configurar o SDK do Mercado Pago para usar o Access Token do PRESTADOR.
        /** @var ProviderCredentialEntity $credentials  */
        $this->authenticate( $credentials->access_token_encrypted );

        // 5. Criar a requisição da preferência de pagamento.
        $client = new PreferenceClient();

        $externalReference = json_encode( [ 
            'user_id'            => $invoice->user_id,
            'provider_id'        => $invoice->provider_id,
            'invoice_id'         => $invoice->id,
            'tenant_id'          => $tenantId,
            'customer_id'        => $invoice->customer_id,
            'service_id'         => $invoice->service_id,
            'public_hash'        => $invoice->public_hash,
            'invoice_code'       => $invoice->code,
            'transaction_amount' => $invoice->total,
        ] );
        $webhookUrl        = buildUrl( "/webhooks/mercadopago/invoices", true );
        $preferenceData    = [ 
            "items"              => [ 
                [ 
                    "title"       => "Pagamento da Fatura #{$invoice->code}",
                    "quantity"    => 1,
                    "currency_id" => "BRL",
                    "unit_price"  => (float) $invoice->total,
                    "description" => "Referente aos serviços prestados.",
                ],
            ],
            "payer"              => [ 
                "first_name" => $customer->first_name,
                "last_name"  => $customer->last_name,
                "email"      => $customer->email_business ?? $customer->email,
            ],
            'payment_methods'    => [ 
                "excluded_payment_methods" => [],
                "installments"             => 12,
                "default_installments"     => 1,
            ],
            'external_reference' => $externalReference,
            "back_urls"          => [ 
                'success' => buildUrl( '/invoices/status', true ),
                'failure' => buildUrl( '/invoices/status', true ),
                'pending' => buildUrl( '/invoices/status', true ),
            ],
            "auto_return"        => "approved",
            "notification_url"   => $webhookUrl,
        ];

        try {
            // 6. Executar a criação do pagamento.
            $preference = $client->create( $preferenceData );

            return [ 
                'status'      => 'success',
                'message'     => 'Preferência de pagamento criada com sucesso',
                'data'        => $preference,
                'payment_url' => $preference->init_point,
            ];
        } catch ( MPApiException $e ) {
            $errorContent = json_encode( $e->getApiResponse()->getContent() );
            logger()->error( "Erro ao criar pagamento MP para a fatura {$invoice->id}: " . $errorContent );

            throw new RuntimeException( "Erro ao processar o pagamento: " . $e->getApiResponse()->getContent()[ 'message' ] );
        }

    }

    /**
     * Autentica com o Mercado Pago usando o token de acesso criptografado.
     *
     * @param string $accessTokenEcrypted Token de acesso criptografado.
     * @return void
     */
    protected function authenticate( string $accessTokenEcrypted ): void
    {
        $mpAccessToken = $this->encryptionService->decrypt( $accessTokenEcrypted );
        if ( !$mpAccessToken ) {
            throw new Exception( "Token de acesso do Mercado Pago não configurado." );
        }

        if ( env( 'APP_ENV' ) === 'development' ) {
            MercadoPagoConfig::setRuntimeEnviroment( MercadoPagoConfig::LOCAL );
        } else {
            MercadoPagoConfig::setRuntimeEnviroment( MercadoPagoConfig::SERVER );
        }
        MercadoPagoConfig::setAccessToken( $mpAccessToken );
    }

}
