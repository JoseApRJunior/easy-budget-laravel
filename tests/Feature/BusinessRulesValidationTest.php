<?php

declare(strict_types=1);

namespace Tests\Feature;

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
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;
use Carbon\Carbon;

class BusinessRulesValidationTest extends TestCase
{
    use RefreshDatabase;

    private array $testResults = [];
    private int   $totalTests  = 0;
    private int   $passedTests = 0;
    private int   $failedTests = 0;

    /**
     * Teste principal de validação das BusinessRules
     */
    public function test_business_rules_validation(): void
    {
        $this->createTestTenant();

        echo "\n" . str_repeat( '=', 80 ) . "\n";
        echo "🧪 INICIANDO VALIDAÇÃO DAS BUSINESSRULES\n";
        echo str_repeat( '=', 80 ) . "\n\n";

        // Executar testes para cada modelo
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

        // Gerar relatório final
        $this->generateFinalReport();
    }

    /**
     * Criar tenant para testes
     */
    private function createTestTenant(): void
    {
        Tenant::create( [
            'id'        => 999999,
            'name'      => 'Test Tenant',
            'domain'    => 'test.localhost',
            'database'  => 'test_db',
            'is_active' => true,
        ] );
    }

    /**
     * Testes para Invoice (14 regras)
     */
    private function testInvoiceBusinessRules(): void
    {
        echo "📋 Testando Invoice BusinessRules (14 regras)...\n";

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
            'due_date'            => now()->addDays( 30 )->format( 'Y-m-d' ),
            'payment_method'      => 'credit_card',
            'payment_id'          => 'pay_123456',
            'transaction_amount'  => 90.00,
            'transaction_date'    => now()->format( 'Y-m-d H:i:s' ),
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
            'due_date no passado'     => array_merge( $validData, [ 'due_date' => now()->subDays( 1 )->format( 'Y-m-d' ) ] ),
            'payment_method inválido' => array_merge( $validData, [ 'payment_method' => 'invalid_method' ] ),
        ];

        foreach ( $invalidDataScenarios as $scenario => $data ) {
            $validator = Validator::make( $data, $rules );
            $this->assertFalse( $validator->passes(), "Cenário '$scenario' deve falhar" );
            $this->recordTest( "Invoice - $scenario", true, 'Dados inválidos rejeitados' );
        }

        echo "✅ Invoice: {$this->getModelTestCount( 'Invoice' )} testes executados\n\n";
    }

    /**
     * Testes para Customer (5 regras + constantes)
     */
    private function testCustomerBusinessRules(): void
    {
        echo "📋 Testando Customer BusinessRules (5 regras + status)...\n";

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

        echo "✅ Customer: {$this->getModelTestCount( 'Customer' )} testes executados\n\n";
    }

    /**
     * Testes para Provider (6 regras)
     */
    private function testProviderBusinessRules(): void
    {
        echo "📋 Testando Provider BusinessRules (6 regras)...\n";

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

        echo "✅ Provider: {$this->getModelTestCount( 'Provider' )} testes executados\n\n";
    }

    /**
     * Testes para Address (7 regras + CEP)
     */
    private function testAddressBusinessRules(): void
    {
        echo "📋 Testando Address BusinessRules (7 regras + CEP)...\n";

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

        echo "✅ Address: {$this->getModelTestCount( 'Address' )} testes executados\n\n";
    }

    /**
     * Testes para CommonData (10 regras + CPF/CNPJ)
     */
    private function testCommonDataBusinessRules(): void
    {
        echo "📋 Testando CommonData BusinessRules (10 regras + CPF/CNPJ)...\n";

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
            'birth_date no futuro'   => array_merge( $validDataPF, [ 'birth_date' => now()->addDays( 1 )->format( 'Y-m-d' ) ] ),
        ];

        foreach ( $invalidDataScenarios as $scenario => $data ) {
            $validator = Validator::make( $data, $rules );
            $this->assertFalse( $validator->passes(), "Cenário '$scenario' deve falhar" );
            $this->recordTest( "CommonData - $scenario", true, 'Dados inválidos rejeitados' );
        }

        echo "✅ CommonData: {$this->getModelTestCount( 'CommonData' )} testes executados\n\n";
    }

    /**
     * Testes para Contact (6 regras + emails únicos)
     */
    private function testContactBusinessRules(): void
    {
        echo "📋 Testando Contact BusinessRules (6 regras + emails)...\n";

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

        echo "✅ Contact: {$this->getModelTestCount( 'Contact' )} testes executados\n\n";
    }

    /**
     * Testes para Notification (6 regras)
     */
    private function testNotificationBusinessRules(): void
    {
        echo "📋 Testando Notification BusinessRules (6 regras)...\n";

        $rules = Notification::businessRules();
        $this->assertCount( 6, $rules, 'Notification deve ter 6 regras de validação' );

        // Cenário de sucesso
        $validData = [
            'tenant_id' => 999999,
            'type'      => 'email',
            'email'     => 'destinatario@exemplo.com',
            'message'   => 'Esta é uma mensagem de teste',
            'subject'   => 'Assunto do teste',
            'sent_at'   => now()->format( 'Y-m-d H:i:s' ),
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

        echo "✅ Notification: {$this->getModelTestCount( 'Notification' )} testes executados\n\n";
    }

    /**
     * Testes para Activity (7 regras)
     */
    private function testActivityBusinessRules(): void
    {
        echo "📋 Testando Activity BusinessRules (7 regras)...\n";

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

        echo "✅ Activity: {$this->getModelTestCount( 'Activity' )} testes executados\n\n";
    }

    /**
     * Testes para BudgetStatus (7 regras + cores hexadecimais)
     */
    private function testBudgetStatusBusinessRules(): void
    {
        echo "📋 Testando BudgetStatus BusinessRules (7 regras + cores hex)...\n";

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

        echo "✅ BudgetStatus: {$this->getModelTestCount( 'BudgetStatus' )} testes executados\n\n";
    }

    /**
     * Testes para InvoiceStatus (7 regras + cores hexadecimais)
     */
    private function testInvoiceStatusBusinessRules(): void
    {
        echo "📋 Testando InvoiceStatus BusinessRules (7 regras + cores hex)...\n";

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

        echo "✅ InvoiceStatus: {$this->getModelTestCount( 'InvoiceStatus' )} testes executados\n\n";
    }

    /**
     * Testes para PaymentMercadoPagoInvoice (7 regras + enums)
     */
    private function testPaymentMercadoPagoInvoiceBusinessRules(): void
    {
        echo "📋 Testando PaymentMercadoPagoInvoice BusinessRules (7 regras + enums)...\n";

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
            'transaction_date'   => now()->format( 'Y-m-d H:i:s' ),
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

        echo "✅ PaymentMercadoPagoInvoice: {$this->getModelTestCount( 'PaymentMercadoPagoInvoice' )} testes executados\n\n";
    }

    /**
     * Testes para AlertSetting (2 regras)
     */
    private function testAlertSettingBusinessRules(): void
    {
        echo "📋 Testando AlertSetting BusinessRules (2 regras)...\n";

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

        echo "✅ AlertSetting: {$this->getModelTestCount( 'AlertSetting' )} testes executados\n\n";
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
        echo str_repeat( '=', 80 ) . "\n";
        echo "📊 RELATÓRIO FINAL DE VALIDAÇÃO DAS BUSINESSRULES\n";
        echo str_repeat( '=', 80 ) . "\n";

        echo "📈 Estatísticas Gerais:\n";
        echo "   • Total de testes: {$this->totalTests}\n";
        echo "   • Testes aprovados: {$this->passedTests}\n";
        echo "   • Testes reprovados: {$this->failedTests}\n";
        echo "   • Taxa de sucesso: " . number_format( ( $this->passedTests / $this->totalTests ) * 100, 2 ) . "%\n\n";

        echo "📋 Detalhamento por Modelo:\n";

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

            echo "   • {$model}: {$passedCount}/{$total} testes aprovados\n";
        }

        echo "\n" . str_repeat( '=', 80 ) . "\n";

        if ( $this->failedTests === 0 ) {
            echo "✅ SUCESSO TOTAL! Todas as BusinessRules estão funcionando corretamente.\n";
        } else {
            echo "❌ ATENÇÃO! {$this->failedTests} teste(s) falharam. Verifique as implementações.\n";
        }

        echo str_repeat( '=', 80 ) . "\n";

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
            'timestamp' => now()->toISOString(),
            'summary'   => [
                'total_tests'  => $this->totalTests,
                'passed_tests' => $this->passedTests,
                'failed_tests' => $this->failedTests,
                'success_rate' => round( ( $this->passedTests / $this->totalTests ) * 100, 2 ),
            ],
            'results'   => $this->testResults,
        ];

        file_put_contents( $reportPath, json_encode( $reportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );

        echo "💾 Relatório salvo em: {$reportPath}\n";
    }

}
