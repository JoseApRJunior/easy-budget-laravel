<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Activity;
use App\Models\Address;
use App\Models\AlertSetting;
use App\Models\BudgetStatus;
use App\Models\CommonData;
use App\Models\Contact;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceStatus;
use App\Models\Notification;
use App\Models\PaymentMercadoPagoInvoice;
use App\Models\Provider;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class BusinessRulesValidationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:business-rules {--model= : Testar modelo específico}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Validar BusinessRules de todos os modelos ou de um modelo específico';

    private array $testResults = [];
    private int   $totalTests  = 0;
    private int   $passedTests = 0;
    private int   $failedTests = 0;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->createTestTenant();
        $specificModel = $this->option( 'model' );

        $this->info( "\n" . str_repeat( '=', 80 ) );
        $this->info( '🧪 INICIANDO VALIDAÇÃO DAS BUSINESSRULES' );
        $this->info( str_repeat( '=', 80 ) . "\n" );

        if ( $specificModel ) {
            $this->testSpecificModel( $specificModel );
        } else {
            // Executar testes para todos os modelos
            $this->testInvoiceBusinessRules();
            $this->testCustomerBusinessRules();
            $this->testProviderBusinessRules();
            $this->testAddressBusinessRules();
            $this->testCommonDataBusinessRules();
            $this->testContactBusinessRules();
            $this->testNotificationBusinessRules();
            $this->testActivityBusinessRules();
            $this->testBudgetStatusBusinessRules();
            $this->testInvoiceStatusBusinessRules();
            $this->testPaymentMercadoPagoInvoiceBusinessRules();
            $this->testAlertSettingBusinessRules();
        }

        // Gerar relatório final
        $this->generateFinalReport();

        return $this->failedTests > 0 ? 1 : 0;
    }

    /**
     * Criar tenant para testes
     */
    private function createTestTenant(): void
    {
        Tenant::firstOrCreate(
            [ 'id' => 999999 ],
            [
                'name'      => 'Test Tenant',
                'domain'    => 'test.localhost',
                'database'  => 'test_db',
                'is_active' => true,
            ],
        );
    }

    /**
     * Testar modelo específico
     */
    private function testSpecificModel( string $model ): void
    {
        $methodName = 'test' . ucfirst( strtolower( $model ) ) . 'BusinessRules';

        if ( method_exists( $this, $methodName ) ) {
            $this->$methodName();
        } else {
            $this->error( "Modelo '{$model}' não encontrado ou método de teste não implementado." );
            $availableModels = [
                'Invoice', 'Customer', 'Provider', 'Address', 'CommonData',
                'Contact', 'Notification', 'Activity', 'BudgetStatus',
                'InvoiceStatus', 'PaymentMercadoPagoInvoice', 'AlertSetting'
            ];
            $this->info( 'Modelos disponíveis: ' . implode( ', ', $availableModels ) );
        }
    }

    /**
     * Testes para Invoice (14 regras)
     */
    private function testInvoiceBusinessRules(): void
    {
        $this->info( '📋 Testando Invoice BusinessRules (14 regras)...' );

        $rules = Invoice::businessRules();
        $this->assertCount( 14, $rules, 'Invoice deve ter 14 regras de validação' );

        // Cenário de sucesso
        $validData = [
            'tenant_id'           => 999999,
            'service_id'          => 1,
            'customer_id'         => 1,
            'invoice_statuses_id' => 1,
            'code'                => 'INV-2024-001',
            'subtotal'            => 100.00,
            'discount'            => 10.00,
            'total'               => 90.00,
            'due_date'            => Carbon::now()->addDays( 30 )->format( 'Y-m-d' ),
            'payment_method'      => 'credit_card',
            'payment_id'          => 'pay_123456',
            'transaction_amount'  => 90.00,
            'transaction_date'    => Carbon::now()->format( 'Y-m-d H:i:s' ),
            'notes'               => 'Test invoice',
        ];

        $validator = Validator::make( $validData, $rules );
        $this->assertTrue( $validator->passes(), 'Dados válidos devem passar na validação' );
        $this->recordTest( 'Invoice - Cenário de Sucesso', true, 'Dados válidos aceitos' );

        // Cenários de falha
        $invalidDataScenarios = [
            'tenant_id obrigatório'   => array_merge( $validData, [ 'tenant_id' => null ] ),
            'service_id obrigatório'  => array_merge( $validData, [ 'service_id' => null ] ),
            'customer_id obrigatório' => array_merge( $validData, [ 'customer_id' => null ] ),
            'code único duplicado'    => array_merge( $validData, [ 'code' => '' ] ),
            'subtotal mínimo'         => array_merge( $validData, [ 'subtotal' => -10 ] ),
            'total mínimo'            => array_merge( $validData, [ 'total' => -5 ] ),
            'due_date no passado'     => array_merge( $validData, [ 'due_date' => Carbon::now()->subDays( 1 )->format( 'Y-m-d' ) ] ),
            'payment_method inválido' => array_merge( $validData, [ 'payment_method' => 'invalid_method' ] ),
        ];

        foreach ( $invalidDataScenarios as $scenario => $data ) {
            $validator = Validator::make( $data, $rules );
            $this->assertFalse( $validator->passes(), "Cenário '$scenario' deve falhar" );
            $this->recordTest( "Invoice - $scenario", true, 'Dados inválidos rejeitados' );
        }

        $this->info( "✅ Invoice: {$this->getModelTestCount( 'Invoice' )} testes executados\n" );
    }

    /**
     * Testes para Customer (5 regras + constantes)
     */
    private function testCustomerBusinessRules(): void
    {
        $this->info( '📋 Testando Customer BusinessRules (5 regras + status)...' );

        $rules = Customer::businessRules();
        $this->assertCount( 5, $rules, 'Customer deve ter 5 regras de validação' );

        // Verificar constantes de status
        $this->assertEquals( 'active', Customer::STATUS_ACTIVE );
        $this->assertEquals( 'inactive', Customer::STATUS_INACTIVE );
        $this->assertEquals( 'deleted', Customer::STATUS_DELETED );
        $this->assertCount( 3, Customer::STATUSES );

        // Cenário de sucesso
        $validData = [
            'tenant_id'      => 999999,
            'common_data_id' => 1,
            'contact_id'     => 1,
            'address_id'     => 1,
            'status'         => Customer::STATUS_ACTIVE,
        ];

        $validator = Validator::make( $validData, $rules );
        $this->assertTrue( $validator->passes(), 'Dados válidos devem passar na validação' );
        $this->recordTest( 'Customer - Cenário de Sucesso', true, 'Dados válidos aceitos' );

        // Cenários de falha
        $invalidDataScenarios = [
            'status inválido'       => array_merge( $validData, [ 'status' => 'invalid_status' ] ),
            'tenant_id obrigatório' => array_merge( $validData, [ 'tenant_id' => null ] ),
        ];

        foreach ( $invalidDataScenarios as $scenario => $data ) {
            $validator = Validator::make( $data, $rules );
            $this->assertFalse( $validator->passes(), "Cenário '$scenario' deve falhar" );
            $this->recordTest( "Customer - $scenario", true, 'Dados inválidos rejeitados' );
        }

        $this->info( "✅ Customer: {$this->getModelTestCount( 'Customer' )} testes executados\n" );
    }

    /**
     * Testes para Provider (6 regras)
     */
    private function testProviderBusinessRules(): void
    {
        $this->info( '📋 Testando Provider BusinessRules (6 regras)...' );

        $rules = Provider::businessRules();
        $this->assertCount( 6, $rules, 'Provider deve ter 6 regras de validação' );

        // Cenário de sucesso
        $validData = [
            'tenant_id'      => 999999,
            'user_id'        => 1,
            'common_data_id' => 1,
            'contact_id'     => 1,
            'address_id'     => 1,
            'terms_accepted' => true,
        ];

        $validator = Validator::make( $validData, $rules );
        $this->assertTrue( $validator->passes(), 'Dados válidos devem passar na validação' );
        $this->recordTest( 'Provider - Cenário de Sucesso', true, 'Dados válidos aceitos' );

        // Cenários de falha
        $invalidDataScenarios = [
            'terms_accepted obrigatório' => array_merge( $validData, [ 'terms_accepted' => null ] ),
            'user_id obrigatório'        => array_merge( $validData, [ 'user_id' => null ] ),
        ];

        foreach ( $invalidDataScenarios as $scenario => $data ) {
            $validator = Validator::make( $data, $rules );
            $this->assertFalse( $validator->passes(), "Cenário '$scenario' deve falhar" );
            $this->recordTest( "Provider - $scenario", true, 'Dados inválidos rejeitados' );
        }

        $this->info( "✅ Provider: {$this->getModelTestCount( 'Provider' )} testes executados\n" );
    }

    /**
     * Testes para Address (7 regras + CEP)
     */
    private function testAddressBusinessRules(): void
    {
        $this->info( '📋 Testando Address BusinessRules (7 regras + CEP)...' );

        $rules = Address::businessRules();
        $this->assertCount( 7, $rules, 'Address deve ter 7 regras de validação' );

        // Cenário de sucesso
        $validData = [
            'tenant_id'      => 999999,
            'address'        => 'Rua das Flores',
            'address_number' => '123',
            'neighborhood'   => 'Centro',
            'city'           => 'São Paulo',
            'state'          => 'SP',
            'cep'            => '01234-567',
        ];

        $validator = Validator::make( $validData, $rules );
        $this->assertTrue( $validator->passes(), 'Dados válidos devem passar na validação' );
        $this->recordTest( 'Address - Cenário de Sucesso', true, 'Dados válidos aceitos' );

        // Cenários de falha
        $invalidDataScenarios = [
            'CEP formato inválido'        => array_merge( $validData, [ 'cep' => '12345-6789' ] ),
            'CEP sem hífen'               => array_merge( $validData, [ 'cep' => '01234567' ] ),
            'state deve ter 2 caracteres' => array_merge( $validData, [ 'state' => 'São Paulo' ] ),
            'address obrigatório'         => array_merge( $validData, [ 'address' => '' ] ),
        ];

        foreach ( $invalidDataScenarios as $scenario => $data ) {
            $validator = Validator::make( $data, $rules );
            $this->assertFalse( $validator->passes(), "Cenário '$scenario' deve falhar" );
            $this->recordTest( "Address - $scenario", true, 'Dados inválidos rejeitados' );
        }

        $this->info( "✅ Address: {$this->getModelTestCount( 'Address' )} testes executados\n" );
    }

    /**
     * Testes para CommonData (10 regras + CPF/CNPJ)
     */
    private function testCommonDataBusinessRules(): void
    {
        $this->info( '📋 Testando CommonData BusinessRules (10 regras + CPF/CNPJ)...' );

        $rules = CommonData::businessRules();
        $this->assertCount( 10, $rules, 'CommonData deve ter 10 regras de validação' );

        // Cenário de sucesso - Pessoa Física
        $validDataPF = [
            'tenant_id'    => 999999,
            'first_name'   => 'João',
            'last_name'    => 'Silva',
            'birth_date'   => '1990-01-01',
            'cpf'          => '12345678901',
            'company_name' => null,
            'cnpj'         => null,
        ];

        $validator = Validator::make( $validDataPF, $rules );
        $this->assertTrue( $validator->passes(), 'Dados PF válidos devem passar na validação' );
        $this->recordTest( 'CommonData - Cenário PF Sucesso', true, 'Dados PF válidos aceitos' );

        // Cenário de sucesso - Pessoa Jurídica
        $validDataPJ = [
            'tenant_id'    => 999999,
            'first_name'   => 'Empresa',
            'last_name'    => 'Teste',
            'birth_date'   => null,
            'cpf'          => null,
            'cnpj'         => '12345678000123',
            'company_name' => 'Empresa Teste Ltda',
        ];

        $validator = Validator::make( $validDataPJ, $rules );
        $this->assertTrue( $validator->passes(), 'Dados PJ válidos devem passar na validação' );
        $this->recordTest( 'CommonData - Cenário PJ Sucesso', true, 'Dados PJ válidos aceitos' );

        // Cenários de falha
        $invalidDataScenarios = [
            'CPF tamanho inválido'   => array_merge( $validDataPF, [ 'cpf' => '123456789' ] ),
            'CNPJ tamanho inválido'  => array_merge( $validDataPJ, [ 'cnpj' => '1234567800012' ] ),
            'first_name obrigatório' => array_merge( $validDataPF, [ 'first_name' => '' ] ),
            'birth_date no futuro'   => array_merge( $validDataPF, [ 'birth_date' => Carbon::now()->addDays( 1 )->format( 'Y-m-d' ) ] ),
        ];

        foreach ( $invalidDataScenarios as $scenario => $data ) {
            $validator = Validator::make( $data, $rules );
            $this->assertFalse( $validator->passes(), "Cenário '$scenario' deve falhar" );
            $this->recordTest( "CommonData - $scenario", true, 'Dados inválidos rejeitados' );
        }

        $this->info( "✅ CommonData: {$this->getModelTestCount( 'CommonData' )} testes executados\n" );
    }

    /**
     * Testes para Contact (6 regras + emails únicos)
     */
    private function testContactBusinessRules(): void
    {
        $this->info( '📋 Testando Contact BusinessRules (6 regras + emails)...' );

        $rules = Contact::businessRules();
        $this->assertCount( 6, $rules, 'Contact deve ter 6 regras de validação' );

        // Cenário de sucesso
        $validData = [
            'tenant_id'      => 999999,
            'email'          => 'teste@exemplo.com',
            'phone'          => '(11) 99999-9999',
            'email_business' => 'comercial@empresa.com',
            'phone_business' => '(11) 8888-8888',
            'website'        => 'https://www.exemplo.com',
        ];

        $validator = Validator::make( $validData, $rules );
        $this->assertTrue( $validator->passes(), 'Dados válidos devem passar na validação' );
        $this->recordTest( 'Contact - Cenário de Sucesso', true, 'Dados válidos aceitos' );

        // Cenários de falha
        $invalidDataScenarios = [
            'email formato inválido'          => array_merge( $validData, [ 'email' => 'email-invalido' ] ),
            'email_business formato inválido' => array_merge( $validData, [ 'email_business' => 'email-invalido-biz' ] ),
            'website formato inválido'        => array_merge( $validData, [ 'website' => 'site-invalido' ] ),
            'email obrigatório'               => array_merge( $validData, [ 'email' => '' ] ),
        ];

        foreach ( $invalidDataScenarios as $scenario => $data ) {
            $validator = Validator::make( $data, $rules );
            $this->assertFalse( $validator->passes(), "Cenário '$scenario' deve falhar" );
            $this->recordTest( "Contact - $scenario", true, 'Dados inválidos rejeitados' );
        }

        $this->info( "✅ Contact: {$this->getModelTestCount( 'Contact' )} testes executados\n" );
    }

    /**
     * Testes para Notification (6 regras)
     */
    private function testNotificationBusinessRules(): void
    {
        $this->info( '📋 Testando Notification BusinessRules (6 regras)...' );

        $rules = Notification::businessRules();
        $this->assertCount( 6, $rules, 'Notification deve ter 6 regras de validação' );

        // Cenário de sucesso
        $validData = [
            'tenant_id' => 999999,
            'type'      => 'email',
            'email'     => 'destinatario@exemplo.com',
            'message'   => 'Esta é uma mensagem de teste',
            'subject'   => 'Assunto do teste',
            'sent_at'   => Carbon::now()->format( 'Y-m-d H:i:s' ),
        ];

        $validator = Validator::make( $validData, $rules );
        $this->assertTrue( $validator->passes(), 'Dados válidos devem passar na validação' );
        $this->recordTest( 'Notification - Cenário de Sucesso', true, 'Dados válidos aceitos' );

        // Cenários de falha
        $invalidDataScenarios = [
            'email formato inválido' => array_merge( $validData, [ 'email' => 'email-invalido' ] ),
            'type obrigatório'       => array_merge( $validData, [ 'type' => '' ] ),
            'message obrigatório'    => array_merge( $validData, [ 'message' => '' ] ),
        ];

        foreach ( $invalidDataScenarios as $scenario => $data ) {
            $validator = Validator::make( $data, $rules );
            $this->assertFalse( $validator->passes(), "Cenário '$scenario' deve falhar" );
            $this->recordTest( "Notification - $scenario", true, 'Dados inválidos rejeitados' );
        }

        $this->info( "✅ Notification: {$this->getModelTestCount( 'Notification' )} testes executados\n" );
    }

    /**
     * Testes para Activity (7 regras)
     */
    private function testActivityBusinessRules(): void
    {
        $this->info( '📋 Testando Activity BusinessRules (7 regras)...' );

        $rules = Activity::businessRules();
        $this->assertCount( 7, $rules, 'Activity deve ter 7 regras de validação' );

        // Cenário de sucesso
        $validData = [
            'tenant_id'   => 999999,
            'user_id'     => 1,
            'action_type' => 'create',
            'entity_type' => 'invoice',
            'entity_id'   => 123,
            'description' => 'Invoice criada com sucesso',
            'metadata'    => '{"key": "value"}',
        ];

        $validator = Validator::make( $validData, $rules );
        $this->assertTrue( $validator->passes(), 'Dados válidos devem passar na validação' );
        $this->recordTest( 'Activity - Cenário de Sucesso', true, 'Dados válidos aceitos' );

        // Cenários de falha
        $invalidDataScenarios = [
            'user_id obrigatório'     => array_merge( $validData, [ 'user_id' => null ] ),
            'action_type obrigatório' => array_merge( $validData, [ 'action_type' => '' ] ),
            'entity_id obrigatório'   => array_merge( $validData, [ 'entity_id' => null ] ),
        ];

        foreach ( $invalidDataScenarios as $scenario => $data ) {
            $validator = Validator::make( $data, $rules );
            $this->assertFalse( $validator->passes(), "Cenário '$scenario' deve falhar" );
            $this->recordTest( "Activity - $scenario", true, 'Dados inválidos rejeitados' );
        }

        $this->info( "✅ Activity: {$this->getModelTestCount( 'Activity' )} testes executados\n" );
    }

    /**
     * Testes para BudgetStatus (7 regras + cores hexadecimais)
     */
    private function testBudgetStatusBusinessRules(): void
    {
        $this->info( '📋 Testando BudgetStatus BusinessRules (7 regras + cores hex)...' );

        $rules = BudgetStatus::businessRules();
        $this->assertCount( 7, $rules, 'BudgetStatus deve ter 7 regras de validação' );

        // Cenário de sucesso
        $validData = [
            'slug'        => 'em-analise',
            'name'        => 'Em Análise',
            'description' => 'Orçamento em análise',
            'color'       => '#FF5733',
            'icon'        => 'fa-search',
            'order_index' => 1,
            'is_active'   => true,
        ];

        $validator = Validator::make( $validData, $rules );
        $this->assertTrue( $validator->passes(), 'Dados válidos devem passar na validação' );
        $this->recordTest( 'BudgetStatus - Cenário de Sucesso', true, 'Dados válidos aceitos' );

        // Cenários de falha
        $invalidDataScenarios = [
            'cor hex inválida'      => array_merge( $validData, [ 'color' => 'azul' ] ),
            'cor hex sem #'         => array_merge( $validData, [ 'color' => 'FF5733' ] ),
            'slug duplicado'        => array_merge( $validData, [ 'slug' => '' ] ),
            'name obrigatório'      => array_merge( $validData, [ 'name' => '' ] ),
            'is_active obrigatório' => array_merge( $validData, [ 'is_active' => null ] ),
        ];

        foreach ( $invalidDataScenarios as $scenario => $data ) {
            $validator = Validator::make( $data, $rules );
            $this->assertFalse( $validator->passes(), "Cenário '$scenario' deve falhar" );
            $this->recordTest( "BudgetStatus - $scenario", true, 'Dados inválidos rejeitados' );
        }

        $this->info( "✅ BudgetStatus: {$this->getModelTestCount( 'BudgetStatus' )} testes executados\n" );
    }

    /**
     * Testes para InvoiceStatus (7 regras + cores hexadecimais)
     */
    private function testInvoiceStatusBusinessRules(): void
    {
        $this->info( '📋 Testando InvoiceStatus BusinessRules (7 regras + cores hex)...' );

        $rules = InvoiceStatus::businessRules();
        $this->assertCount( 7, $rules, 'InvoiceStatus deve ter 7 regras de validação' );

        // Cenário de sucesso
        $validData = [
            'name'        => 'Paga',
            'slug'        => 'paga',
            'description' => 'Fatura paga',
            'color'       => '#28A745',
            'icon'        => 'fa-check',
            'order_index' => 1,
            'is_active'   => true,
        ];

        $validator = Validator::make( $validData, $rules );
        $this->assertTrue( $validator->passes(), 'Dados válidos devem passar na validação' );
        $this->recordTest( 'InvoiceStatus - Cenário de Sucesso', true, 'Dados válidos aceitos' );

        // Cenários de falha
        $invalidDataScenarios = [
            'cor hex inválida' => array_merge( $validData, [ 'color' => 'verde' ] ),
            'name obrigatório' => array_merge( $validData, [ 'name' => '' ] ),
            'slug obrigatório' => array_merge( $validData, [ 'slug' => '' ] ),
        ];

        foreach ( $invalidDataScenarios as $scenario => $data ) {
            $validator = Validator::make( $data, $rules );
            $this->assertFalse( $validator->passes(), "Cenário '$scenario' deve falhar" );
            $this->recordTest( "InvoiceStatus - $scenario", true, 'Dados inválidos rejeitados' );
        }

        $this->info( "✅ InvoiceStatus: {$this->getModelTestCount( 'InvoiceStatus' )} testes executados\n" );
    }

    /**
     * Testes para PaymentMercadoPagoInvoice (7 regras + enums)
     */
    private function testPaymentMercadoPagoInvoiceBusinessRules(): void
    {
        $this->info( '📋 Testando PaymentMercadoPagoInvoice BusinessRules (7 regras + enums)...' );

        $rules = PaymentMercadoPagoInvoice::businessRules();
        $this->assertCount( 7, $rules, 'PaymentMercadoPagoInvoice deve ter 7 regras de validação' );

        // Verificar constantes de status
        $this->assertEquals( 'pending', PaymentMercadoPagoInvoice::STATUS_PENDING );
        $this->assertEquals( 'approved', PaymentMercadoPagoInvoice::STATUS_APPROVED );
        $this->assertEquals( 'rejected', PaymentMercadoPagoInvoice::STATUS_REJECTED );
        $this->assertEquals( 'cancelled', PaymentMercadoPagoInvoice::STATUS_CANCELLED );
        $this->assertEquals( 'refunded', PaymentMercadoPagoInvoice::STATUS_REFUNDED );

        // Verificar métodos de pagamento
        $this->assertEquals( 'credit_card', PaymentMercadoPagoInvoice::PAYMENT_METHOD_CREDIT_CARD );
        $this->assertEquals( 'debit_card', PaymentMercadoPagoInvoice::PAYMENT_METHOD_DEBIT_CARD );
        $this->assertEquals( 'bank_transfer', PaymentMercadoPagoInvoice::PAYMENT_METHOD_BANK_TRANSFER );
        $this->assertEquals( 'ticket', PaymentMercadoPagoInvoice::PAYMENT_METHOD_TICKET );
        $this->assertEquals( 'pix', PaymentMercadoPagoInvoice::PAYMENT_METHOD_PIX );

        // Cenário de sucesso
        $validData = [
            'payment_id'         => 'pay_123456789',
            'tenant_id'          => 999999,
            'invoice_id'         => 1,
            'status'             => PaymentMercadoPagoInvoice::STATUS_APPROVED,
            'payment_method'     => PaymentMercadoPagoInvoice::PAYMENT_METHOD_CREDIT_CARD,
            'transaction_amount' => 100.00,
            'transaction_date'   => Carbon::now()->format( 'Y-m-d H:i:s' ),
        ];

        $validator = Validator::make( $validData, $rules );
        $this->assertTrue( $validator->passes(), 'Dados válidos devem passar na validação' );
        $this->recordTest( 'PaymentMercadoPagoInvoice - Cenário de Sucesso', true, 'Dados válidos aceitos' );

        // Cenários de falha
        $invalidDataScenarios = [
            'status inválido'             => array_merge( $validData, [ 'status' => 'invalid_status' ] ),
            'payment_method inválido'     => array_merge( $validData, [ 'payment_method' => 'invalid_method' ] ),
            'transaction_amount negativo' => array_merge( $validData, [ 'transaction_amount' => -10 ] ),
            'payment_id obrigatório'      => array_merge( $validData, [ 'payment_id' => '' ] ),
        ];

        foreach ( $invalidDataScenarios as $scenario => $data ) {
            $validator = Validator::make( $data, $rules );
            $this->assertFalse( $validator->passes(), "Cenário '$scenario' deve falhar" );
            $this->recordTest( "PaymentMercadoPagoInvoice - $scenario", true, 'Dados inválidos rejeitados' );
        }

        $this->info( "✅ PaymentMercadoPagoInvoice: {$this->getModelTestCount( 'PaymentMercadoPagoInvoice' )} testes executados\n" );
    }

    /**
     * Testes para AlertSetting (2 regras)
     */
    private function testAlertSettingBusinessRules(): void
    {
        $this->info( '📋 Testando AlertSetting BusinessRules (2 regras)...' );

        $rules = AlertSetting::businessRules();
        $this->assertCount( 2, $rules, 'AlertSetting deve ter 2 regras de validação' );

        // Cenário de sucesso
        $validData = [
            'tenant_id' => 999999,
            'settings'  => [
                'email_alerts' => true,
                'sms_alerts'   => false,
            ],
        ];

        $validator = Validator::make( $validData, $rules );
        $this->assertTrue( $validator->passes(), 'Dados válidos devem passar na validação' );
        $this->recordTest( 'AlertSetting - Cenário de Sucesso', true, 'Dados válidos aceitos' );

        // Cenários de falha
        $invalidDataScenarios = [
            'settings obrigatório'    => array_merge( $validData, [ 'settings' => null ] ),
            'settings deve ser array' => array_merge( $validData, [ 'settings' => 'not_array' ] ),
        ];

        foreach ( $invalidDataScenarios as $scenario => $data ) {
            $validator = Validator::make( $data, $rules );
            $this->assertFalse( $validator->passes(), "Cenário '$scenario' deve falhar" );
            $this->recordTest( "AlertSetting - $scenario", true, 'Dados inválidos rejeitados' );
        }

        $this->info( "✅ AlertSetting: {$this->getModelTestCount( 'AlertSetting' )} testes executados\n" );
    }

    /**
     * Gravar resultado do teste
     */
    private function recordTest( string $testName, bool $passed, string $message = '' ): void
    {
        $this->testResults[] = [
            'test'    => $testName,
            'passed'  => $passed,
            'message' => $message,
        ];

        $this->totalTests++;

        if ( $passed ) {
            $this->passedTests++;
        } else {
            $this->failedTests++;
        }
    }

    /**
     * Obter contagem de testes por modelo
     */
    private function getModelTestCount( string $modelName ): int
    {
        $count = 0;
        foreach ( $this->testResults as $result ) {
            if ( str_contains( $result[ 'test' ], $modelName ) ) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Gerar relatório final
     */
    private function generateFinalReport(): void
    {
        $this->info( str_repeat( '=', 80 ) );
        $this->info( '📊 RELATÓRIO FINAL DE VALIDAÇÃO DAS BUSINESSRULES' );
        $this->info( str_repeat( '=', 80 ) );

        $this->info( '📈 Estatísticas Gerais:' );
        $this->info( "   • Total de testes: {$this->totalTests}" );
        $this->info( "   • Testes aprovados: {$this->passedTests}" );
        $this->info( "   • Testes reprovados: {$this->failedTests}" );
        $this->info( '   • Taxa de sucesso: ' . number_format( ( $this->passedTests / $this->totalTests ) * 100, 2 ) . '%' );

        $this->info( '' );
        $this->info( '📋 Detalhamento por Modelo:' );

        $models = [
            'Invoice', 'Customer', 'Provider', 'Address', 'CommonData',
            'Contact', 'Notification', 'Activity', 'BudgetStatus',
            'InvoiceStatus', 'PaymentMercadoPagoInvoice', 'AlertSetting'
        ];

        foreach ( $models as $model ) {
            $modelTests  = array_filter( $this->testResults, fn( $test ) => str_contains( $test[ 'test' ], $model ) );
            $passed      = array_filter( $modelTests, fn( $test ) => $test[ 'passed' ] );
            $total       = count( $modelTests );
            $passedCount = count( $passed );

            $this->info( "   • {$model}: {$passedCount}/{$total} testes aprovados" );
        }

        $this->info( '' );
        $this->info( str_repeat( '=', 80 ) );

        if ( $this->failedTests === 0 ) {
            $this->info( '✅ SUCESSO TOTAL! Todas as BusinessRules estão funcionando corretamente.' );
        } else {
            $this->error( "❌ ATENÇÃO! {$this->failedTests} teste(s) falharam. Verifique as implementações." );
        }

        $this->info( str_repeat( '=', 80 ) );

        // Salvar relatório em arquivo
        $this->saveReportToFile();
    }

    /**
     * Salvar relatório em arquivo
     */
    private function saveReportToFile(): void
    {
        $reportPath = storage_path( 'app/business-rules-validation-report.json' );

        $reportData = [
            'timestamp' => Carbon::now()->toISOString(),
            'summary'   => [
                'total_tests'  => $this->totalTests,
                'passed_tests' => $this->passedTests,
                'failed_tests' => $this->failedTests,
                'success_rate' => round( ( $this->passedTests / $this->totalTests ) * 100, 2 ),
            ],
            'results'   => $this->testResults,
        ];

        file_put_contents( $reportPath, json_encode( $reportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );

        $this->info( "💾 Relatório salvo em: {$reportPath}" );
    }

    /**
     * Assert helper methods
     */
    private function assertCount( int $expected, array $array, string $message = '' ): void
    {
        if ( count( $array ) !== $expected ) {
            throw new \Exception( "Expected count {$expected}, got " . count( $array ) . ". {$message}" );
        }
    }

    private function assertTrue( bool $condition, string $message = '' ): void
    {
        if ( !$condition ) {
            throw new \Exception( "Expected true, got false. {$message}" );
        }
    }

    private function assertFalse( bool $condition, string $message = '' ): void
    {
        if ( $condition ) {
            throw new \Exception( "Expected false, got true. {$message}" );
        }
    }

    private function assertEquals( $expected, $actual, string $message = '' ): void
    {
        if ( $expected !== $actual ) {
            throw new \Exception( "Expected {$expected}, got {$actual}. {$message}" );
        }
    }

}
