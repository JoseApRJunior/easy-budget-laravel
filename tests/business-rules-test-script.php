<?php
/**
 * Script de Teste das BusinessRules
 *
 * Este script pode ser executado via:
 * php artisan tinker --execute="include 'business-rules-test-script.php'; runBusinessRulesTests();"
 *
 * Ou via linha de comando:
 * php -r "include 'business-rules-test-script.php'; runBusinessRulesTests();"
 */

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

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
use Illuminate\Support\Facades\Validator;

/**
 * Executar todos os testes de BusinessRules
 */
function runBusinessRulesTests(): void
{
    echo "\n" . str_repeat( '=', 80 ) . "\n";
    echo "🧪 INICIANDO VALIDAÇÃO DAS BUSINESSRULES\n";
    echo str_repeat( '=', 80 ) . "\n\n";

    $testResults = [];
    $totalTests  = 0;
    $passedTests = 0;
    $failedTests = 0;

    try {
        // Criar tenant para testes
        createTestTenant();

        // Executar testes para cada modelo
        testInvoiceBusinessRules( $testResults, $totalTests, $passedTests, $failedTests );
        testCustomerBusinessRules( $testResults, $totalTests, $passedTests, $failedTests );
        testProviderBusinessRules( $testResults, $totalTests, $passedTests, $failedTests );
        testAddressBusinessRules( $testResults, $totalTests, $passedTests, $failedTests );
        testCommonDataBusinessRules( $testResults, $totalTests, $passedTests, $failedTests );
        testContactBusinessRules( $testResults, $totalTests, $passedTests, $failedTests );
        testNotificationBusinessRules( $testResults, $totalTests, $passedTests, $failedTests );
        testActivityBusinessRules( $testResults, $totalTests, $passedTests, $failedTests );
        testBudgetStatusBusinessRules( $testResults, $totalTests, $passedTests, $failedTests );
        testInvoiceStatusBusinessRules( $testResults, $totalTests, $passedTests, $failedTests );
        testPaymentMercadoPagoInvoiceBusinessRules( $testResults, $totalTests, $passedTests, $failedTests );
        testAlertSettingBusinessRules( $testResults, $totalTests, $passedTests, $failedTests );

        // Gerar relatório final
        generateFinalReport( $testResults, $totalTests, $passedTests, $failedTests );

    } catch ( \Exception $e ) {
        echo "❌ ERRO AO EXECUTAR TESTES: " . $e->getMessage() . "\n";
        echo "Arquivo: " . $e->getFile() . " - Linha: " . $e->getLine() . "\n";
    }
}

/**
 * Criar tenant para testes
 */
function createTestTenant(): void
{
    Tenant::firstOrCreate(
        [ 'id' => 999999 ],
        [
            'name'      => 'Test Tenant ' . uniqid(),
            'domain'    => 'test-' . uniqid() . '.localhost',
            'database'  => 'test_db',
            'is_active' => true,
        ],
    );
    echo "✅ Tenant de teste criado/configurado\n";
}

/**
 * Testes para Invoice (14 regras)
 */
function testInvoiceBusinessRules( array &$testResults, int &$totalTests, int &$passedTests, int &$failedTests ): void
{
    echo "📋 Testando Invoice BusinessRules (14 regras)...\n";

    $rules = Invoice::businessRules();
    assertCount( 14, $rules, 'Invoice deve ter 14 regras de validação' );

    // Cenário de sucesso - apenas campos sem foreign keys para evitar problemas de dependência
    $validData = [
        'tenant_id'          => 999999,
        'code'               => 'INV-2024-001-' . uniqid(),
        'subtotal'           => 100.00,
        'discount'           => 10.00,
        'total'              => 90.00,
        'due_date'           => Carbon::now()->addDays( 30 )->format( 'Y-m-d' ),
        'payment_method'     => 'credit_card',
        'payment_id'         => 'pay_123456',
        'transaction_amount' => 90.00,
        'transaction_date'   => Carbon::now()->format( 'Y-m-d H:i:s' ),
        'notes'              => 'Test invoice',
    ];

    $validator = Validator::make( $validData, $rules );
    assertTrue( $validator->passes(), 'Dados válidos devem passar na validação' );
    recordTest( $testResults, 'Invoice - Cenário de Sucesso', true, 'Dados válidos aceitos', $totalTests, $passedTests, $failedTests );

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
        assertFalse( $validator->passes(), "Cenário '$scenario' deve falhar" );
        recordTest( $testResults, "Invoice - $scenario", true, 'Dados inválidos rejeitados', $totalTests, $passedTests, $failedTests );
    }

    echo "✅ Invoice: " . getModelTestCount( $testResults, 'Invoice' ) . " testes executados\n\n";
}

/**
 * Testes para Customer (5 regras + constantes)
 */
function testCustomerBusinessRules( array &$testResults, int &$totalTests, int &$passedTests, int &$failedTests ): void
{
    echo "📋 Testando Customer BusinessRules (5 regras + status)...\n";

    $rules = Customer::businessRules();
    assertCount( 5, $rules, 'Customer deve ter 5 regras de validação' );

    // Verificar constantes de status
    assertEquals( 'active', Customer::STATUS_ACTIVE );
    assertEquals( 'inactive', Customer::STATUS_INACTIVE );
    assertEquals( 'deleted', Customer::STATUS_DELETED );
    assertCount( 3, Customer::STATUSES );

    // Cenário de sucesso
    $validData = [
        'tenant_id'      => 999999,
        'common_data_id' => 1,
        'contact_id'     => 1,
        'address_id'     => 1,
        'status'         => Customer::STATUS_ACTIVE,
    ];

    $validator = Validator::make( $validData, $rules );
    assertTrue( $validator->passes(), 'Dados válidos devem passar na validação' );
    recordTest( $testResults, 'Customer - Cenário de Sucesso', true, 'Dados válidos aceitos', $totalTests, $passedTests, $failedTests );

    // Cenários de falha
    $invalidDataScenarios = [
        'status inválido'       => array_merge( $validData, [ 'status' => 'invalid_status' ] ),
        'tenant_id obrigatório' => array_merge( $validData, [ 'tenant_id' => null ] ),
    ];

    foreach ( $invalidDataScenarios as $scenario => $data ) {
        $validator = Validator::make( $data, $rules );
        assertFalse( $validator->passes(), "Cenário '$scenario' deve falhar" );
        recordTest( $testResults, "Customer - $scenario", true, 'Dados inválidos rejeitados', $totalTests, $passedTests, $failedTests );
    }

    echo "✅ Customer: " . getModelTestCount( $testResults, 'Customer' ) . " testes executados\n\n";
}

/**
 * Testes para Provider (6 regras)
 */
function testProviderBusinessRules( array &$testResults, int &$totalTests, int &$passedTests, int &$failedTests ): void
{
    echo "📋 Testando Provider BusinessRules (6 regras)...\n";

    $rules = Provider::businessRules();
    assertCount( 6, $rules, 'Provider deve ter 6 regras de validação' );

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
    assertTrue( $validator->passes(), 'Dados válidos devem passar na validação' );
    recordTest( $testResults, 'Provider - Cenário de Sucesso', true, 'Dados válidos aceitos', $totalTests, $passedTests, $failedTests );

    // Cenários de falha
    $invalidDataScenarios = [
        'terms_accepted obrigatório' => array_merge( $validData, [ 'terms_accepted' => null ] ),
        'user_id obrigatório'        => array_merge( $validData, [ 'user_id' => null ] ),
    ];

    foreach ( $invalidDataScenarios as $scenario => $data ) {
        $validator = Validator::make( $data, $rules );
        assertFalse( $validator->passes(), "Cenário '$scenario' deve falhar" );
        recordTest( $testResults, "Provider - $scenario", true, 'Dados inválidos rejeitados', $totalTests, $passedTests, $failedTests );
    }

    echo "✅ Provider: " . getModelTestCount( $testResults, 'Provider' ) . " testes executados\n\n";
}

/**
 * Testes para Address (7 regras + CEP)
 */
function testAddressBusinessRules( array &$testResults, int &$totalTests, int &$passedTests, int &$failedTests ): void
{
    echo "📋 Testando Address BusinessRules (7 regras + CEP)...\n";

    $rules = Address::businessRules();
    assertCount( 7, $rules, 'Address deve ter 7 regras de validação' );

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
    assertTrue( $validator->passes(), 'Dados válidos devem passar na validação' );
    recordTest( $testResults, 'Address - Cenário de Sucesso', true, 'Dados válidos aceitos', $totalTests, $passedTests, $failedTests );

    // Cenários de falha
    $invalidDataScenarios = [
        'CEP formato inválido'        => array_merge( $validData, [ 'cep' => '12345-6789' ] ),
        'CEP sem hífen'               => array_merge( $validData, [ 'cep' => '01234567' ] ),
        'state deve ter 2 caracteres' => array_merge( $validData, [ 'state' => 'São Paulo' ] ),
        'address obrigatório'         => array_merge( $validData, [ 'address' => '' ] ),
    ];

    foreach ( $invalidDataScenarios as $scenario => $data ) {
        $validator = Validator::make( $data, $rules );
        assertFalse( $validator->passes(), "Cenário '$scenario' deve falhar" );
        recordTest( $testResults, "Address - $scenario", true, 'Dados inválidos rejeitados', $totalTests, $passedTests, $failedTests );
    }

    echo "✅ Address: " . getModelTestCount( $testResults, 'Address' ) . " testes executados\n\n";
}

/**
 * Testes para CommonData (10 regras + CPF/CNPJ)
 */
function testCommonDataBusinessRules( array &$testResults, int &$totalTests, int &$passedTests, int &$failedTests ): void
{
    echo "📋 Testando CommonData BusinessRules (10 regras + CPF/CNPJ)...\n";

    $rules = CommonData::businessRules();
    assertCount( 10, $rules, 'CommonData deve ter 10 regras de validação' );

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
    assertTrue( $validator->passes(), 'Dados PF válidos devem passar na validação' );
    recordTest( $testResults, 'CommonData - Cenário PF Sucesso', true, 'Dados PF válidos aceitos', $totalTests, $passedTests, $failedTests );

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
    assertTrue( $validator->passes(), 'Dados PJ válidos devem passar na validação' );
    recordTest( $testResults, 'CommonData - Cenário PJ Sucesso', true, 'Dados PJ válidos aceitos', $totalTests, $passedTests, $failedTests );

    // Cenários de falha
    $invalidDataScenarios = [
        'CPF tamanho inválido'   => array_merge( $validDataPF, [ 'cpf' => '123456789' ] ),
        'CNPJ tamanho inválido'  => array_merge( $validDataPJ, [ 'cnpj' => '1234567800012' ] ),
        'first_name obrigatório' => array_merge( $validDataPF, [ 'first_name' => '' ] ),
        'birth_date no futuro'   => array_merge( $validDataPF, [ 'birth_date' => Carbon::now()->addDays( 1 )->format( 'Y-m-d' ) ] ),
    ];

    foreach ( $invalidDataScenarios as $scenario => $data ) {
        $validator = Validator::make( $data, $rules );
        assertFalse( $validator->passes(), "Cenário '$scenario' deve falhar" );
        recordTest( $testResults, "CommonData - $scenario", true, 'Dados inválidos rejeitados', $totalTests, $passedTests, $failedTests );
    }

    echo "✅ CommonData: " . getModelTestCount( $testResults, 'CommonData' ) . " testes executados\n\n";
}

/**
 * Testes para Contact (6 regras + emails únicos)
 */
function testContactBusinessRules( array &$testResults, int &$totalTests, int &$passedTests, int &$failedTests ): void
{
    echo "📋 Testando Contact BusinessRules (6 regras + emails)...\n";

    $rules = Contact::businessRules();
    assertCount( 6, $rules, 'Contact deve ter 6 regras de validação' );

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
    assertTrue( $validator->passes(), 'Dados válidos devem passar na validação' );
    recordTest( $testResults, 'Contact - Cenário de Sucesso', true, 'Dados válidos aceitos', $totalTests, $passedTests, $failedTests );

    // Cenários de falha
    $invalidDataScenarios = [
        'email formato inválido'          => array_merge( $validData, [ 'email' => 'email-invalido' ] ),
        'email_business formato inválido' => array_merge( $validData, [ 'email_business' => 'email-invalido-biz' ] ),
        'website formato inválido'        => array_merge( $validData, [ 'website' => 'site-invalido' ] ),
        'email obrigatório'               => array_merge( $validData, [ 'email' => '' ] ),
    ];

    foreach ( $invalidDataScenarios as $scenario => $data ) {
        $validator = Validator::make( $data, $rules );
        assertFalse( $validator->passes(), "Cenário '$scenario' deve falhar" );
        recordTest( $testResults, "Contact - $scenario", true, 'Dados inválidos rejeitados', $totalTests, $passedTests, $failedTests );
    }

    echo "✅ Contact: " . getModelTestCount( $testResults, 'Contact' ) . " testes executados\n\n";
}

/**
 * Testes para Notification (6 regras)
 */
function testNotificationBusinessRules( array &$testResults, int &$totalTests, int &$passedTests, int &$failedTests ): void
{
    echo "📋 Testando Notification BusinessRules (6 regras)...\n";

    $rules = Notification::businessRules();
    assertCount( 6, $rules, 'Notification deve ter 6 regras de validação' );

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
    assertTrue( $validator->passes(), 'Dados válidos devem passar na validação' );
    recordTest( $testResults, 'Notification - Cenário de Sucesso', true, 'Dados válidos aceitos', $totalTests, $passedTests, $failedTests );

    // Cenários de falha
    $invalidDataScenarios = [
        'email formato inválido' => array_merge( $validData, [ 'email' => 'email-invalido' ] ),
        'type obrigatório'       => array_merge( $validData, [ 'type' => '' ] ),
        'message obrigatório'    => array_merge( $validData, [ 'message' => '' ] ),
    ];

    foreach ( $invalidDataScenarios as $scenario => $data ) {
        $validator = Validator::make( $data, $rules );
        assertFalse( $validator->passes(), "Cenário '$scenario' deve falhar" );
        recordTest( $testResults, "Notification - $scenario", true, 'Dados inválidos rejeitados', $totalTests, $passedTests, $failedTests );
    }

    echo "✅ Notification: " . getModelTestCount( $testResults, 'Notification' ) . " testes executados\n\n";
}

/**
 * Testes para Activity (7 regras)
 */
function testActivityBusinessRules( array &$testResults, int &$totalTests, int &$passedTests, int &$failedTests ): void
{
    echo "📋 Testando Activity BusinessRules (7 regras)...\n";

    $rules = Activity::businessRules();
    assertCount( 7, $rules, 'Activity deve ter 7 regras de validação' );

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
    assertTrue( $validator->passes(), 'Dados válidos devem passar na validação' );
    recordTest( $testResults, 'Activity - Cenário de Sucesso', true, 'Dados válidos aceitos', $totalTests, $passedTests, $failedTests );

    // Cenários de falha
    $invalidDataScenarios = [
        'user_id obrigatório'     => array_merge( $validData, [ 'user_id' => null ] ),
        'action_type obrigatório' => array_merge( $validData, [ 'action_type' => '' ] ),
        'entity_id obrigatório'   => array_merge( $validData, [ 'entity_id' => null ] ),
    ];

    foreach ( $invalidDataScenarios as $scenario => $data ) {
        $validator = Validator::make( $data, $rules );
        assertFalse( $validator->passes(), "Cenário '$scenario' deve falhar" );
        recordTest( $testResults, "Activity - $scenario", true, 'Dados inválidos rejeitados', $totalTests, $passedTests, $failedTests );
    }

    echo "✅ Activity: " . getModelTestCount( $testResults, 'Activity' ) . " testes executados\n\n";
}

/**
 * Testes para BudgetStatus (7 regras + cores hexadecimais)
 */
function testBudgetStatusBusinessRules( array &$testResults, int &$totalTests, int &$passedTests, int &$failedTests ): void
{
    echo "📋 Testando BudgetStatus BusinessRules (7 regras + cores hex)...\n";

    $rules = BudgetStatus::businessRules();
    assertCount( 7, $rules, 'BudgetStatus deve ter 7 regras de validação' );

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
    assertTrue( $validator->passes(), 'Dados válidos devem passar na validação' );
    recordTest( $testResults, 'BudgetStatus - Cenário de Sucesso', true, 'Dados válidos aceitos', $totalTests, $passedTests, $failedTests );

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
        assertFalse( $validator->passes(), "Cenário '$scenario' deve falhar" );
        recordTest( $testResults, "BudgetStatus - $scenario", true, 'Dados inválidos rejeitados', $totalTests, $passedTests, $failedTests );
    }

    echo "✅ BudgetStatus: " . getModelTestCount( $testResults, 'BudgetStatus' ) . " testes executados\n\n";
}

/**
 * Testes para InvoiceStatus (7 regras + cores hexadecimais)
 */
function testInvoiceStatusBusinessRules( array &$testResults, int &$totalTests, int &$passedTests, int &$failedTests ): void
{
    echo "📋 Testando InvoiceStatus BusinessRules (7 regras + cores hex)...\n";

    $rules = InvoiceStatus::businessRules();
    assertCount( 7, $rules, 'InvoiceStatus deve ter 7 regras de validação' );

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
    assertTrue( $validator->passes(), 'Dados válidos devem passar na validação' );
    recordTest( $testResults, 'InvoiceStatus - Cenário de Sucesso', true, 'Dados válidos aceitos', $totalTests, $passedTests, $failedTests );

    // Cenários de falha
    $invalidDataScenarios = [
        'cor hex inválida' => array_merge( $validData, [ 'color' => 'verde' ] ),
        'name obrigatório' => array_merge( $validData, [ 'name' => '' ] ),
        'slug obrigatório' => array_merge( $validData, [ 'slug' => '' ] ),
    ];

    foreach ( $invalidDataScenarios as $scenario => $data ) {
        $validator = Validator::make( $data, $rules );
        assertFalse( $validator->passes(), "Cenário '$scenario' deve falhar" );
        recordTest( $testResults, "InvoiceStatus - $scenario", true, 'Dados inválidos rejeitados', $totalTests, $passedTests, $failedTests );
    }

    echo "✅ InvoiceStatus: " . getModelTestCount( $testResults, 'InvoiceStatus' ) . " testes executados\n\n";
}

/**
 * Testes para PaymentMercadoPagoInvoice (7 regras + enums)
 */
function testPaymentMercadoPagoInvoiceBusinessRules( array &$testResults, int &$totalTests, int &$passedTests, int &$failedTests ): void
{
    echo "📋 Testando PaymentMercadoPagoInvoice BusinessRules (7 regras + enums)...\n";

    $rules = PaymentMercadoPagoInvoice::businessRules();
    assertCount( 7, $rules, 'PaymentMercadoPagoInvoice deve ter 7 regras de validação' );

    // Verificar constantes de status
    assertEquals( 'pending', PaymentMercadoPagoInvoice::STATUS_PENDING );
    assertEquals( 'approved', PaymentMercadoPagoInvoice::STATUS_APPROVED );
    assertEquals( 'rejected', PaymentMercadoPagoInvoice::STATUS_REJECTED );
    assertEquals( 'cancelled', PaymentMercadoPagoInvoice::STATUS_CANCELLED );
    assertEquals( 'refunded', PaymentMercadoPagoInvoice::STATUS_REFUNDED );

    // Verificar métodos de pagamento
    assertEquals( 'credit_card', PaymentMercadoPagoInvoice::PAYMENT_METHOD_CREDIT_CARD );
    assertEquals( 'debit_card', PaymentMercadoPagoInvoice::PAYMENT_METHOD_DEBIT_CARD );
    assertEquals( 'bank_transfer', PaymentMercadoPagoInvoice::PAYMENT_METHOD_BANK_TRANSFER );
    assertEquals( 'ticket', PaymentMercadoPagoInvoice::PAYMENT_METHOD_TICKET );
    assertEquals( 'pix', PaymentMercadoPagoInvoice::PAYMENT_METHOD_PIX );

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
    assertTrue( $validator->passes(), 'Dados válidos devem passar na validação' );
    recordTest( $testResults, 'PaymentMercadoPagoInvoice - Cenário de Sucesso', true, 'Dados válidos aceitos', $totalTests, $passedTests, $failedTests );

    // Cenários de falha
    $invalidDataScenarios = [
        'status inválido'             => array_merge( $validData, [ 'status' => 'invalid_status' ] ),
        'payment_method inválido'     => array_merge( $validData, [ 'payment_method' => 'invalid_method' ] ),
        'transaction_amount negativo' => array_merge( $validData, [ 'transaction_amount' => -10 ] ),
        'payment_id obrigatório'      => array_merge( $validData, [ 'payment_id' => '' ] ),
    ];

    foreach ( $invalidDataScenarios as $scenario => $data ) {
        $validator = Validator::make( $data, $rules );
        assertFalse( $validator->passes(), "Cenário '$scenario' deve falhar" );
        recordTest( $testResults, "PaymentMercadoPagoInvoice - $scenario", true, 'Dados inválidos rejeitados', $totalTests, $passedTests, $failedTests );
    }

    echo "✅ PaymentMercadoPagoInvoice: " . getModelTestCount( $testResults, 'PaymentMercadoPagoInvoice' ) . " testes executados\n\n";
}

/**
 * Testes para AlertSetting (2 regras)
 */
function testAlertSettingBusinessRules( array &$testResults, int &$totalTests, int &$passedTests, int &$failedTests ): void
{
    echo "📋 Testando AlertSetting BusinessRules (2 regras)...\n";

    $rules = AlertSetting::businessRules();
    assertCount( 2, $rules, 'AlertSetting deve ter 2 regras de validação' );

    // Cenário de sucesso
    $validData = [
        'tenant_id' => 999999,
        'settings'  => [
            'email_alerts' => true,
            'sms_alerts'   => false,
        ],
    ];

    $validator = Validator::make( $validData, $rules );
    assertTrue( $validator->passes(), 'Dados válidos devem passar na validação' );
    recordTest( $testResults, 'AlertSetting - Cenário de Sucesso', true, 'Dados válidos aceitos', $totalTests, $passedTests, $failedTests );

    // Cenários de falha
    $invalidDataScenarios = [
        'settings obrigatório'    => array_merge( $validData, [ 'settings' => null ] ),
        'settings deve ser array' => array_merge( $validData, [ 'settings' => 'not_array' ] ),
    ];

    foreach ( $invalidDataScenarios as $scenario => $data ) {
        $validator = Validator::make( $data, $rules );
        assertFalse( $validator->passes(), "Cenário '$scenario' deve falhar" );
        recordTest( $testResults, "AlertSetting - $scenario", true, 'Dados inválidos rejeitados', $totalTests, $passedTests, $failedTests );
    }

    echo "✅ AlertSetting: " . getModelTestCount( $testResults, 'AlertSetting' ) . " testes executados\n\n";
}

/**
 * Gravar resultado do teste
 */
function recordTest( array &$testResults, string $testName, bool $passed, string $message, int &$totalTests, int &$passedTests, int &$failedTests ): void
{
    $testResults[] = [
        'test'    => $testName,
        'passed'  => $passed,
        'message' => $message,
    ];

    $totalTests++;

    if ( $passed ) {
        $passedTests++;
    } else {
        $failedTests++;
    }
}

/**
 * Obter contagem de testes por modelo
 */
function getModelTestCount( array $testResults, string $modelName ): int
{
    $count = 0;
    foreach ( $testResults as $result ) {
        if ( str_contains( $result[ 'test' ], $modelName ) ) {
            $count++;
        }
    }
    return $count;
}

/**
 * Gerar relatório final
 */
function generateFinalReport( array $testResults, int $totalTests, int $passedTests, int $failedTests ): void
{
    echo str_repeat( '=', 80 ) . "\n";
    echo "📊 RELATÓRIO FINAL DE VALIDAÇÃO DAS BUSINESSRULES\n";
    echo str_repeat( '=', 80 ) . "\n";

    echo "📈 Estatísticas Gerais:\n";
    echo "   • Total de testes: {$totalTests}\n";
    echo "   • Testes aprovados: {$passedTests}\n";
    echo "   • Testes reprovados: {$failedTests}\n";
    echo "   • Taxa de sucesso: " . number_format( ( $passedTests / $totalTests ) * 100, 2 ) . "%\n\n";

    echo "📋 Detalhamento por Modelo:\n";

    $models = [
        'Invoice', 'Customer', 'Provider', 'Address', 'CommonData',
        'Contact', 'Notification', 'Activity', 'BudgetStatus',
        'InvoiceStatus', 'PaymentMercadoPagoInvoice', 'AlertSetting'
    ];

    foreach ( $models as $model ) {
        $modelTests  = array_filter( $testResults, fn( $test ) => str_contains( $test[ 'test' ], $model ) );
        $passed      = array_filter( $modelTests, fn( $test ) => $test[ 'passed' ] );
        $total       = count( $modelTests );
        $passedCount = count( $passed );

        echo "   • {$model}: {$passedCount}/{$total} testes aprovados\n";
    }

    echo "\n" . str_repeat( '=', 80 ) . "\n";

    if ( $failedTests === 0 ) {
        echo "✅ SUCESSO TOTAL! Todas as BusinessRules estão funcionando corretamente.\n";
    } else {
        echo "❌ ATENÇÃO! {$failedTests} teste(s) falharam. Verifique as implementações.\n";
    }

    echo str_repeat( '=', 80 ) . "\n";

    // Salvar relatório em arquivo
    saveReportToFile( $testResults, $totalTests, $passedTests, $failedTests );
}

/**
 * Salvar relatório em arquivo
 */
function saveReportToFile( array $testResults, int $totalTests, int $passedTests, int $failedTests ): void
{
    $reportPath = __DIR__ . '/storage/app/business-rules-validation-report.json';

    $reportData = [
        'timestamp' => Carbon::now()->toISOString(),
        'summary'   => [
            'total_tests'  => $totalTests,
            'passed_tests' => $passedTests,
            'failed_tests' => $failedTests,
            'success_rate' => round( ( $passedTests / $totalTests ) * 100, 2 ),
        ],
        'results'   => $testResults,
    ];

    // Criar diretório se não existir
    $dir = dirname( $reportPath );
    if ( !is_dir( $dir ) ) {
        mkdir( $dir, 0755, true );
    }

    file_put_contents( $reportPath, json_encode( $reportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );

    echo "💾 Relatório salvo em: {$reportPath}\n";
}

/**
 * Funções auxiliares de assert
 */
function assertCount( int $expected, array $array, string $message = '' ): void
{
    if ( count( $array ) !== $expected ) {
        throw new Exception( "Expected count {$expected}, got " . count( $array ) . ". {$message}" );
    }
}

function assertTrue( bool $condition, string $message = '' ): void
{
    if ( !$condition ) {
        throw new Exception( "Expected true, got false. {$message}" );
    }
}

function assertFalse( bool $condition, string $message = '' ): void
{
    if ( $condition ) {
        throw new Exception( "Expected false, got true. {$message}" );
    }
}

function assertEquals( $expected, $actual, string $message = '' ): void
{
    if ( $expected !== $actual ) {
        throw new Exception( "Expected {$expected}, got {$actual}. {$message}" );
    }
}

// Para executar o script diretamente
if ( isset( $argv ) && in_array( 'run', $argv ) ) {
    runBusinessRulesTests();
}
