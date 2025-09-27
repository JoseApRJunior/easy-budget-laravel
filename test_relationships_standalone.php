<?php
declare(strict_types=1);

/**
 * TESTE DE VALIDAÇÃO DE RELACIONAMENTOS DOS MODELOS (VERSÃO STANDALONE)
 *
 * Script standalone para validar relacionamentos e funcionalidades dos modelos principais
 * do sistema Easy Budget Laravel.
 *
 * EXECUÇÃO:
 * php test_relationships_standalone.php
 *
 * @author Kilo Code
 * @version 1.0.0
 */

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make( \Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\RolePermission;
use App\Models\BudgetStatus;
use App\Models\InvoiceStatus;
use App\Models\Customer;
use App\Models\Budget;
use App\Models\Invoice;
use App\Models\Role;
use App\Models\Permission;
use App\Models\Activity;
use App\Models\Notification;
use App\Models\Contact;
use App\Models\Provider;
use App\Models\Address;
use App\Models\CommonData;
use App\Models\Tenant;
use App\Models\User;
use Exception;

class RelationshipValidator
{
    private array $results     = [];
    private int   $testsRun    = 0;
    private int   $testsPassed = 0;
    private int   $testsFailed = 0;

    /**
     * Executa todos os testes de relacionamento
     */
    public function runAllTests(): array
    {
        echo "\n" . str_repeat( "=", 80 ) . "\n";
        echo "🧪 TESTE DE VALIDAÇÃO DE RELACIONAMENTOS DOS MODELOS\n";
        echo str_repeat( "=", 80 ) . "\n\n";

        try {
            // Testes básicos de modelos
            $this->testModelInstantiation();
            $this->testBusinessRules();

            // Testes de relacionamentos
            $this->testRolePermissionRelationships();
            $this->testBudgetStatusRelationships();
            $this->testInvoiceStatusRelationships();
            $this->testCustomerRelationships();
            $this->testBudgetRelationships();
            $this->testInvoiceRelationships();
            $this->testReverseRelationships();
            $this->testActivityRelationships();
            $this->testContactRelationships();

            // Testes de scopes
            $this->testCustomScopes();

            // Testes de constantes
            $this->testCustomerConstants();

        } catch ( Exception $e ) {
            $this->addResult( 'GERAL', 'EXCEÇÃO CRÍTICA', false, $e->getMessage() );
        }

        return $this->generateReport();
    }

    /**
     * Testa instanciação básica dos modelos
     */
    private function testModelInstantiation(): void
    {
        echo "📋 Testando instanciação dos modelos...\n";

        $models = [
            'RolePermission' => RolePermission::class,
            'BudgetStatus'   => BudgetStatus::class,
            'InvoiceStatus'  => InvoiceStatus::class,
            'Customer'       => Customer::class,
            'Budget'         => Budget::class,
            'Invoice'        => Invoice::class,
            'Role'           => Role::class,
            'Permission'     => Permission::class,
            'Activity'       => Activity::class,
            'Notification'   => Notification::class,
            'Contact'        => Contact::class,
        ];

        foreach ( $models as $name => $class ) {
            try {
                $instance = new $class();
                $this->addResult( 'ModelInstantiation', "$name model", true, 'Instanciação OK' );
                $this->testsRun++;
                $this->testsPassed++;
            } catch ( Exception $e ) {
                $this->addResult( 'ModelInstantiation', "$name model", false, $e->getMessage() );
                $this->testsRun++;
                $this->testsFailed++;
            }
        }
        echo "\n";
    }

    /**
     * Testa BusinessRules dos modelos
     */
    private function testBusinessRules(): void
    {
        echo "📋 Testando BusinessRules dos modelos...\n";

        $models = [
            'RolePermission' => RolePermission::class,
            'BudgetStatus'   => BudgetStatus::class,
            'InvoiceStatus'  => InvoiceStatus::class,
            'Customer'       => Customer::class,
            'Budget'         => Budget::class,
            'Invoice'        => Invoice::class,
            'Role'           => Role::class,
            'Permission'     => Permission::class,
            'Activity'       => Activity::class,
            'Notification'   => Notification::class,
            'Contact'        => Contact::class,
        ];

        foreach ( $models as $name => $class ) {
            try {
                $rules = $class::businessRules();
                $this->addResult( 'BusinessRules', "$name::businessRules()", true, 'Retornou array com ' . count( $rules ) . ' regras' );
                $this->testsRun++;
                $this->testsPassed++;
            } catch ( Exception $e ) {
                $this->addResult( 'BusinessRules', "$name::businessRules()", false, $e->getMessage() );
                $this->testsRun++;
                $this->testsFailed++;
            }
        }
        echo "\n";
    }

    /**
     * Testa relacionamentos do RolePermission
     */
    private function testRolePermissionRelationships(): void
    {
        echo "🔗 Testando relacionamentos RolePermission...\n";

        try {
            // Testa se o modelo tem os relacionamentos definidos
            $rolePermission = new RolePermission();

            // Verifica se os métodos de relacionamento existem
            $this->assertMethodExists( $rolePermission, 'role', 'RolePermission->role()' );
            $this->assertMethodExists( $rolePermission, 'permission', 'RolePermission->permission()' );
            $this->assertMethodExists( $rolePermission, 'tenant', 'RolePermission->tenant()' );

            $this->addResult( 'RolePermission', 'belongsTo relationships', true, 'Métodos de relacionamento existem' );

        } catch ( Exception $e ) {
            $this->addResult( 'RolePermission', 'belongsTo relationships', false, $e->getMessage() );
        }

        echo "\n";
    }

    /**
     * Testa relacionamentos do BudgetStatus
     */
    private function testBudgetStatusRelationships(): void
    {
        echo "🔗 Testando relacionamentos BudgetStatus...\n";

        try {
            $budgetStatus = new BudgetStatus();

            // Verifica se o método de relacionamento existe
            $this->assertMethodExists( $budgetStatus, 'budgets', 'BudgetStatus->budgets()' );

            // Testa scope personalizado
            $this->assertMethodExists( $budgetStatus, 'scopeActiveStatus', 'BudgetStatus::activeStatus()' );

            $this->addResult( 'BudgetStatus', 'hasMany and scopes', true, 'Relacionamentos e scopes existem' );

        } catch ( Exception $e ) {
            $this->addResult( 'BudgetStatus', 'hasMany and scopes', false, $e->getMessage() );
        }

        echo "\n";
    }

    /**
     * Testa relacionamentos do InvoiceStatus
     */
    private function testInvoiceStatusRelationships(): void
    {
        echo "🔗 Testando relacionamentos InvoiceStatus...\n";

        try {
            $invoiceStatus = new InvoiceStatus();

            // Verifica se o método de relacionamento existe
            $this->assertMethodExists( $invoiceStatus, 'invoices', 'InvoiceStatus->invoices()' );

            $this->addResult( 'InvoiceStatus', 'hasMany relationship', true, 'Relacionamento existe' );

        } catch ( Exception $e ) {
            $this->addResult( 'InvoiceStatus', 'hasMany relationship', false, $e->getMessage() );
        }

        echo "\n";
    }

    /**
     * Testa relacionamentos do Customer
     */
    private function testCustomerRelationships(): void
    {
        echo "🔗 Testando relacionamentos Customer...\n";

        try {
            $customer = new Customer();

            // Verifica relacionamentos belongsTo
            $this->assertMethodExists( $customer, 'tenant', 'Customer->tenant()' );
            $this->assertMethodExists( $customer, 'commonData', 'Customer->commonData()' );
            $this->assertMethodExists( $customer, 'contact', 'Customer->contact()' );
            $this->assertMethodExists( $customer, 'address', 'Customer->address()' );

            // Verifica relacionamentos hasMany
            $this->assertMethodExists( $customer, 'budgets', 'Customer->budgets()' );
            $this->assertMethodExists( $customer, 'invoices', 'Customer->invoices()' );

            $this->addResult( 'Customer', 'belongsTo and hasMany', true, 'Todos os relacionamentos existem' );

        } catch ( Exception $e ) {
            $this->addResult( 'Customer', 'belongsTo and hasMany', false, $e->getMessage() );
        }

        echo "\n";
    }

    /**
     * Testa relacionamentos do Budget
     */
    private function testBudgetRelationships(): void
    {
        echo "🔗 Testando relacionamentos Budget...\n";

        try {
            $budget = new Budget();

            // Verifica relacionamentos belongsTo
            $this->assertMethodExists( $budget, 'tenant', 'Budget->tenant()' );
            $this->assertMethodExists( $budget, 'customer', 'Budget->customer()' );
            $this->assertMethodExists( $budget, 'budgetStatus', 'Budget->budgetStatus()' );
            $this->assertMethodExists( $budget, 'userConfirmationToken', 'Budget->userConfirmationToken()' );

            // Verifica relacionamentos hasMany
            $this->assertMethodExists( $budget, 'services', 'Budget->services()' );

            $this->addResult( 'Budget', 'belongsTo and hasMany', true, 'Todos os relacionamentos existem' );

        } catch ( Exception $e ) {
            $this->addResult( 'Budget', 'belongsTo and hasMany', false, $e->getMessage() );
        }

        echo "\n";
    }

    /**
     * Testa relacionamentos do Invoice
     */
    private function testInvoiceRelationships(): void
    {
        echo "🔗 Testando relacionamentos Invoice...\n";

        try {
            $invoice = new Invoice();

            // Verifica relacionamentos belongsTo
            $this->assertMethodExists( $invoice, 'tenant', 'Invoice->tenant()' );
            $this->assertMethodExists( $invoice, 'customer', 'Invoice->customer()' );
            $this->assertMethodExists( $invoice, 'invoiceStatus', 'Invoice->invoiceStatus()' );
            $this->assertMethodExists( $invoice, 'service', 'Invoice->service()' );

            // Verifica relacionamentos hasMany
            $this->assertMethodExists( $invoice, 'invoiceItems', 'Invoice->invoiceItems()' );

            $this->addResult( 'Invoice', 'belongsTo and hasMany', true, 'Todos os relacionamentos existem' );

        } catch ( Exception $e ) {
            $this->addResult( 'Invoice', 'belongsTo and hasMany', false, $e->getMessage() );
        }

        echo "\n";
    }

    /**
     * Testa relacionamentos reversos
     */
    private function testReverseRelationships(): void
    {
        echo "🔗 Testando relacionamentos reversos...\n";

        try {
            $role       = new Role();
            $permission = new Permission();

            // Verifica relacionamentos reversos
            $this->assertMethodExists( $role, 'permissions', 'Role->permissions()' );
            $this->assertMethodExists( $permission, 'roles', 'Permission->roles()' );

            $this->addResult( 'ReverseRelationships', 'Role->permissions() and Permission->roles()', true, 'Relacionamentos reversos existem' );

        } catch ( Exception $e ) {
            $this->addResult( 'ReverseRelationships', 'Role->permissions() and Permission->roles()', false, $e->getMessage() );
        }

        echo "\n";
    }

    /**
     * Testa relacionamentos do Activity
     */
    private function testActivityRelationships(): void
    {
        echo "🔗 Testando relacionamentos Activity...\n";

        try {
            $activity = new Activity();

            // Verifica relacionamentos belongsTo
            $this->assertMethodExists( $activity, 'tenant', 'Activity->tenant()' );
            $this->assertMethodExists( $activity, 'user', 'Activity->user()' );

            $this->addResult( 'Activity', 'belongsTo relationships', true, 'Relacionamentos existem' );

        } catch ( Exception $e ) {
            $this->addResult( 'Activity', 'belongsTo relationships', false, $e->getMessage() );
        }

        echo "\n";
    }

    /**
     * Testa relacionamentos do Contact
     */
    private function testContactRelationships(): void
    {
        echo "🔗 Testando relacionamentos Contact...\n";

        try {
            $contact = new Contact();

            // Verifica relacionamentos
            $this->assertMethodExists( $contact, 'tenant', 'Contact->tenant()' );
            $this->assertMethodExists( $contact, 'customer', 'Contact->customer()' );
            $this->assertMethodExists( $contact, 'providers', 'Contact->providers()' );

            $this->addResult( 'Contact', 'belongsTo, hasOne, hasMany', true, 'Todos os relacionamentos existem' );

        } catch ( Exception $e ) {
            $this->addResult( 'Contact', 'belongsTo, hasOne, hasMany', false, $e->getMessage() );
        }

        echo "\n";
    }

    /**
     * Testa scopes personalizados
     */
    private function testCustomScopes(): void
    {
        echo "🔍 Testando scopes personalizados...\n";

        try {
            // Testa scope activeStatus do BudgetStatus
            $query = BudgetStatus::activeStatus();
            $this->addResult( 'CustomScopes', 'BudgetStatus::activeStatus()', true, 'Scope executou sem erro' );

        } catch ( Exception $e ) {
            $this->addResult( 'CustomScopes', 'BudgetStatus::activeStatus()', false, $e->getMessage() );
        }

        echo "\n";
    }

    /**
     * Testa constantes do Customer
     */
    private function testCustomerConstants(): void
    {
        echo "📋 Testando constantes do Customer...\n";

        try {
            // Verifica se as constantes existem e têm os valores esperados
            $this->assertConstantExists( Customer::class, 'STATUS_ACTIVE', 'Customer::STATUS_ACTIVE' );
            $this->assertConstantExists( Customer::class, 'STATUS_INACTIVE', 'Customer::STATUS_INACTIVE' );
            $this->assertConstantExists( Customer::class, 'STATUS_DELETED', 'Customer::STATUS_DELETED' );
            $this->assertConstantExists( Customer::class, 'STATUSES', 'Customer::STATUSES' );

            // Verifica se os valores das constantes estão corretos
            $this->assertEquals( 'active', Customer::STATUS_ACTIVE, 'Customer::STATUS_ACTIVE valor' );
            $this->assertEquals( 'inactive', Customer::STATUS_INACTIVE, 'Customer::STATUS_INACTIVE valor' );
            $this->assertEquals( 'deleted', Customer::STATUS_DELETED, 'Customer::STATUS_DELETED valor' );

            $expectedStatuses = [ 'active', 'inactive', 'deleted' ];
            $this->assertEquals( $expectedStatuses, Customer::STATUSES, 'Customer::STATUSES array' );

            $this->addResult( 'CustomerConstants', 'STATUS constants', true, 'Constantes existem e têm valores corretos' );

        } catch ( Exception $e ) {
            $this->addResult( 'CustomerConstants', 'STATUS constants', false, $e->getMessage() );
        }

        echo "\n";
    }

    /**
     * Verifica se um método existe em um objeto
     */
    private function assertMethodExists( $object, string $method, string $testName ): void
    {
        if ( !method_exists( $object, $method ) ) {
            throw new Exception( "Método $method não encontrado em $testName" );
        }
    }

    /**
     * Verifica se uma constante existe em uma classe
     */
    private function assertConstantExists( string $class, string $constant, string $testName ): void
    {
        if ( !defined( "$class::$constant" ) ) {
            throw new Exception( "Constante $constant não encontrada em $testName" );
        }
    }

    /**
     * Verifica se dois valores são iguais
     */
    private function assertEquals( $expected, $actual, string $testName ): void
    {
        if ( $expected !== $actual ) {
            throw new Exception( "Teste $testName falhou: esperado " . var_export( $expected, true ) . ", recebido " . var_export( $actual, true ) );
        }
    }

    /**
     * Adiciona um resultado de teste
     */
    private function addResult( string $category, string $test, bool $passed, string $message = '' ): void
    {
        $this->results[] = [
            'category' => $category,
            'test'     => $test,
            'passed'   => $passed,
            'message'  => $message
        ];

        $icon = $passed ? '✅' : '❌';
        echo "  $icon $category -> $test";
        if ( $message ) {
            echo " ($message)";
        }
        echo "\n";
    }

    /**
     * Gera relatório final dos testes
     */
    private function generateReport(): array
    {
        echo "\n" . str_repeat( "=", 80 ) . "\n";
        echo "📊 RELATÓRIO FINAL DOS TESTES\n";
        echo str_repeat( "=", 80 ) . "\n";

        echo "Total de testes executados: {$this->testsRun}\n";
        echo "✅ Testes aprovados: {$this->testsPassed}\n";
        echo "❌ Testes reprovados: {$this->testsFailed}\n";

        $successRate = $this->testsRun > 0 ? round( ( $this->testsPassed / $this->testsRun ) * 100, 2 ) : 0;
        echo "🎯 Taxa de sucesso: {$successRate}%\n\n";

        // Agrupa resultados por categoria
        $categories = [];
        foreach ( $this->results as $result ) {
            $category = $result[ 'category' ];
            if ( !isset( $categories[ $category ] ) ) {
                $categories[ $category ] = [ 'passed' => 0, 'failed' => 0, 'tests' => [] ];
            }
            $categories[ $category ][ 'tests' ][] = $result;
            if ( $result[ 'passed' ] ) {
                $categories[ $category ][ 'passed' ]++;
            } else {
                $categories[ $category ][ 'failed' ]++;
            }
        }

        // Exibe resultados por categoria
        foreach ( $categories as $category => $data ) {
            $total = $data[ 'passed' ] + $data[ 'failed' ];
            $rate  = $total > 0 ? round( ( $data[ 'passed' ] / $total ) * 100, 2 ) : 0;

            echo "📂 $category: {$data[ 'passed' ]}/{$total} ✅ ({$rate}%)\n";

            // Mostra detalhes dos testes que falharam
            foreach ( $data[ 'tests' ] as $test ) {
                if ( !$test[ 'passed' ] ) {
                    echo "   ❌ {$test[ 'test' ]}: {$test[ 'message' ]}\n";
                }
            }
        }

        echo "\n" . str_repeat( "=", 80 ) . "\n";

        // Verifica se todos os testes críticos passaram
        $criticalTests  = [ 'ModelInstantiation', 'BusinessRules' ];
        $criticalPassed = true;

        foreach ( $criticalTests as $critical ) {
            if ( isset( $categories[ $critical ] ) && $categories[ $critical ][ 'failed' ] > 0 ) {
                $criticalPassed = false;
                break;
            }
        }

        if ( $criticalPassed && $this->testsFailed === 0 ) {
            echo "🎉 TODOS OS TESTES PASSARAM! Relacionamentos validados com sucesso.\n";
        } else {
            echo "⚠️  Alguns testes falharam. Verifique os detalhes acima.\n";
        }

        echo str_repeat( "=", 80 ) . "\n";

        return [
            'total_tests'  => $this->testsRun,
            'passed'       => $this->testsPassed,
            'failed'       => $this->testsFailed,
            'success_rate' => $successRate,
            'categories'   => $categories,
            'all_passed'   => $this->testsFailed === 0
        ];
    }

}

// Executa os testes
$validator = new RelationshipValidator();
$validator->runAllTests();
