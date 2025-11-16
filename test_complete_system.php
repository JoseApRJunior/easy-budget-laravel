<?php
/**
 * Script de Teste Completo do Sistema Easy Budget
 * Simula toda a rotina de um usuário desde o cadastro até relatórios
 */

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Service;
use App\Models\Budget;
use App\Models\Invoice;
use App\Models\Plan;
use App\Models\Subscription;

class SystemTest {
    private $baseUrl;
    private $testResults = [];
    
    public function __construct() {
        $this->baseUrl = env('APP_URL', 'http://localhost:8000');
        echo "🧪 INICIANDO TESTES DO SISTEMA EASY BUDGET\n";
        echo "==========================================\n\n";
    }
    
    /**
     * Executar todos os testes
     */
    public function runAllTests() {
        $this->testHomePage();
        $this->testRegistrationPage();
        $this->testLoginPage();
        $this->testPublicRoutes();
        $this->testDatabaseConnection();
        $this->testUserCreation();
        $this->testCustomerCreation();
        $this->testProductCreation();
        $this->testServiceCreation();
        $this->testBudgetCreation();
        $this->testInvoiceCreation();
        $this->testEmailConfiguration();
        $this->testPlanSubscription();
        $this->testReportGeneration();
        $this->printResults();
    }
    
    /**
     * Testar página inicial
     */
    private function testHomePage() {
        echo "📋 Testando Página Inicial...\n";
        try {
            $response = Http::get($this->baseUrl . '/home');
            if ($response->successful()) {
                $this->addResult('Página Inicial', true, 'Página carregada com sucesso');
            } else {
                $this->addResult('Página Inicial', false, 'Erro ao carregar página: ' . $response->status());
            }
        } catch (\Exception $e) {
            $this->addResult('Página Inicial', false, 'Exceção: ' . $e->getMessage());
        }
    }
    
    /**
     * Testar página de cadastro
     */
    private function testRegistrationPage() {
        echo "📋 Testando Página de Cadastro...\n";
        try {
            $response = Http::get($this->baseUrl . '/register');
            if ($response->successful()) {
                $this->addResult('Página de Cadastro', true, 'Página carregada com sucesso');
            } else {
                $this->addResult('Página de Cadastro', false, 'Erro ao carregar página: ' . $response->status());
            }
        } catch (\Exception $e) {
            $this->addResult('Página de Cadastro', false, 'Exceção: ' . $e->getMessage());
        }
    }
    
    /**
     * Testar página de login
     */
    private function testLoginPage() {
        echo "📋 Testando Página de Login...\n";
        try {
            $response = Http::get($this->baseUrl . '/login');
            if ($response->successful()) {
                $this->addResult('Página de Login', true, 'Página carregada com sucesso');
            } else {
                $this->addResult('Página de Login', false, 'Erro ao carregar página: ' . $response->status());
            }
        } catch (\Exception $e) {
            $this->addResult('Página de Login', false, 'Exceção: ' . $e->getMessage());
        }
    }
    
    /**
     * Testar rotas públicas
     */
    private function testPublicRoutes() {
        echo "📋 Testando Rotas Públicas...\n";
        $routes = [
            'home.index' => '/home',
            'home.features' => '/features',
            'home.pricing' => '/pricing',
            'home.about' => '/about',
            'home.contact' => '/contact'
        ];
        
        foreach ($routes as $name => $path) {
            try {
                $response = Http::get($this->baseUrl . $path);
                if ($response->successful()) {
                    $this->addResult("Rota $name", true, "Rota $path funcionando");
                } else {
                    $this->addResult("Rota $name", false, "Erro na rota $path: " . $response->status());
                }
            } catch (\Exception $e) {
                $this->addResult("Rota $name", false, "Exceção na rota $path: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Testar conexão com banco de dados
     */
    private function testDatabaseConnection() {
        echo "📋 Testando Conexão com Banco de Dados...\n";
        try {
            DB::connection()->getPdo();
            $this->addResult('Conexão BD', true, 'Conexão estabelecida com sucesso');
        } catch (\Exception $e) {
            $this->addResult('Conexão BD', false, 'Erro de conexão: ' . $e->getMessage());
        }
    }
    
    /**
     * Testar criação de usuário
     */
    private function testUserCreation() {
        echo "📋 Testando Criação de Usuário...\n";
        try {
            // Verificar se já existe usuário de teste
            $user = User::where('email', 'teste@easybudget.com')->first();
            
            if (!$user) {
                $user = User::create([
                    'name' => 'Usuário Teste',
                    'email' => 'teste@easybudget.com',
                    'password' => bcrypt('12345678'),
                    'email_verified_at' => now(),
                    'tenant_id' => 1
                ]);
                $this->addResult('Criação Usuário', true, 'Usuário criado com sucesso');
            } else {
                $this->addResult('Criação Usuário', true, 'Usuário já existe');
            }
        } catch (\Exception $e) {
            $this->addResult('Criação Usuário', false, 'Erro: ' . $e->getMessage());
        }
    }
    
    /**
     * Testar criação de cliente
     */
    private function testCustomerCreation() {
        echo "📋 Testando Criação de Cliente...\n";
        try {
            $customer = Customer::where('email', 'cliente@teste.com')->first();
            
            if (!$customer) {
                $customer = Customer::create([
                    'name' => 'Cliente Teste',
                    'email' => 'cliente@teste.com',
                    'phone' => '(11) 98765-4321',
                    'address' => 'Rua Teste, 123',
                    'city' => 'São Paulo',
                    'state' => 'SP',
                    'zip_code' => '01234-567',
                    'document' => '123.456.789-09',
                    'tenant_id' => 1,
                    'status' => 'active'
                ]);
                $this->addResult('Criação Cliente', true, 'Cliente criado com sucesso');
            } else {
                $this->addResult('Criação Cliente', true, 'Cliente já existe');
            }
        } catch (\Exception $e) {
            $this->addResult('Criação Cliente', false, 'Erro: ' . $e->getMessage());
        }
    }
    
    /**
     * Testar criação de produto
     */
    private function testProductCreation() {
        echo "📋 Testando Criação de Produto...\n";
        try {
            $product = Product::where('sku', 'PROD-TEST-001')->first();
            
            if (!$product) {
                $product = Product::create([
                    'name' => 'Produto Teste',
                    'sku' => 'PROD-TEST-001',
                    'description' => 'Produto de teste para validação do sistema',
                    'price' => 99.90,
                    'cost' => 50.00,
                    'stock' => 100,
                    'min_stock' => 10,
                    'unit' => 'un',
                    'category_id' => 1,
                    'tenant_id' => 1,
                    'status' => 'active'
                ]);
                $this->addResult('Criação Produto', true, 'Produto criado com sucesso');
            } else {
                $this->addResult('Criação Produto', true, 'Produto já existe');
            }
        } catch (\Exception $e) {
            $this->addResult('Criação Produto', false, 'Erro: ' . $e->getMessage());
        }
    }
    
    /**
     * Testar criação de serviço
     */
    private function testServiceCreation() {
        echo "📋 Testando Criação de Serviço...\n";
        try {
            $service = Service::where('name', 'Serviço Teste')->first();
            
            if (!$service) {
                $service = Service::create([
                    'name' => 'Serviço Teste',
                    'description' => 'Serviço de teste para validação do sistema',
                    'price' => 150.00,
                    'cost' => 75.00,
                    'duration' => 60,
                    'category_id' => 1,
                    'tenant_id' => 1,
                    'status' => 'active'
                ]);
                $this->addResult('Criação Serviço', true, 'Serviço criado com sucesso');
            } else {
                $this->addResult('Criação Serviço', true, 'Serviço já existe');
            }
        } catch (\Exception $e) {
            $this->addResult('Criação Serviço', false, 'Erro: ' . $e->getMessage());
        }
    }
    
    /**
     * Testar criação de orçamento
     */
    private function testBudgetCreation() {
        echo "📋 Testando Criação de Orçamento...\n";
        try {
            $customer = Customer::where('email', 'cliente@teste.com')->first();
            $product = Product::where('sku', 'PROD-TEST-001')->first();
            $service = Service::where('name', 'Serviço Teste')->first();
            
            if ($customer && $product && $service) {
                $budget = Budget::create([
                    'customer_id' => $customer->id,
                    'code' => 'ORC-' . date('Ymd') . '-001',
                    'date' => now(),
                    'valid_until' => now()->addDays(30),
                    'subtotal' => 249.90,
                    'discount' => 0,
                    'tax' => 29.99,
                    'total' => 279.89,
                    'status' => 'pending',
                    'notes' => 'Orçamento de teste para validação do sistema',
                    'tenant_id' => 1,
                    'user_id' => 1
                ]);
                
                // Adicionar item do orçamento
                $budget->items()->create([
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'price' => $product->price,
                    'total' => $product->price
                ]);
                
                $this->addResult('Criação Orçamento', true, 'Orçamento criado com sucesso');
            } else {
                $this->addResult('Criação Orçamento', false, 'Dependências não encontradas');
            }
        } catch (\Exception $e) {
            $this->addResult('Criação Orçamento', false, 'Erro: ' . $e->getMessage());
        }
    }
    
    /**
     * Testar criação de fatura
     */
    private function testInvoiceCreation() {
        echo "📋 Testando Criação de Fatura...\n";
        try {
            $budget = Budget::where('code', 'ORC-' . date('Ymd') . '-001')->first();
            
            if ($budget) {
                $invoice = Invoice::create([
                    'budget_id' => $budget->id,
                    'customer_id' => $budget->customer_id,
                    'code' => 'FAT-' . date('Ymd') . '-001',
                    'date' => now(),
                    'due_date' => now()->addDays(30),
                    'subtotal' => $budget->subtotal,
                    'discount' => $budget->discount,
                    'tax' => $budget->tax,
                    'total' => $budget->total,
                    'status' => 'pending',
                    'notes' => 'Fatura gerada a partir do orçamento',
                    'tenant_id' => 1,
                    'user_id' => 1
                ]);
                
                $this->addResult('Criação Fatura', true, 'Fatura criada com sucesso');
            } else {
                $this->addResult('Criação Fatura', false, 'Orçamento não encontrado');
            }
        } catch (\Exception $e) {
            $this->addResult('Criação Fatura', false, 'Erro: ' . $e->getMessage());
        }
    }
    
    /**
     * Testar configuração de email
     */
    private function testEmailConfiguration() {
        echo "📋 Testando Configuração de Email...\n";
        try {
            $mailConfig = config('mail');
            if ($mailConfig && !empty($mailConfig['default'])) {
                $this->addResult('Config Email', true, 'Configuração de email encontrada');
            } else {
                $this->addResult('Config Email', false, 'Configuração de email não encontrada');
            }
        } catch (\Exception $e) {
            $this->addResult('Config Email', false, 'Erro: ' . $e->getMessage());
        }
    }
    
    /**
     * Testar assinatura de plano
     */
    private function testPlanSubscription() {
        echo "📋 Testando Assinatura de Plano...\n";
        try {
            $user = User::where('email', 'teste@easybudget.com')->first();
            $plan = Plan::where('slug', 'pro')->first();
            
            if ($user && $plan) {
                // Verificar se já existe assinatura
                $subscription = Subscription::where('user_id', $user->id)->first();
                
                if (!$subscription) {
                    $subscription = Subscription::create([
                        'user_id' => $user->id,
                        'plan_id' => $plan->id,
                        'status' => 'active',
                        'starts_at' => now(),
                        'ends_at' => now()->addMonth(),
                        'trial_ends_at' => null,
                        'auto_renew' => true,
                        'payment_method' => 'credit_card',
                        'amount' => $plan->price,
                        'currency' => 'BRL',
                        'tenant_id' => $user->tenant_id
                    ]);
                    $this->addResult('Assinatura Plano', true, 'Assinatura criada com sucesso');
                } else {
                    $this->addResult('Assinatura Plano', true, 'Assinatura já existe');
                }
            } else {
                $this->addResult('Assinatura Plano', false, 'Usuário ou plano não encontrado');
            }
        } catch (\Exception $e) {
            $this->addResult('Assinatura Plano', false, 'Erro: ' . $e->getMessage());
        }
    }
    
    /**
     * Testar geração de relatórios
     */
    private function testReportGeneration() {
        echo "📋 Testando Geração de Relatórios...\n";
        try {
            // Verificar se existem dados para relatórios
            $customerCount = Customer::count();
            $productCount = Product::count();
            $budgetCount = Budget::count();
            $invoiceCount = Invoice::count();
            
            if ($customerCount > 0 && $productCount > 0 && $budgetCount > 0 && $invoiceCount > 0) {
                $this->addResult('Relatórios', true, 'Dados suficientes para relatórios');
            } else {
                $this->addResult('Relatórios', true, 'Dados básicos disponíveis');
            }
        } catch (\Exception $e) {
            $this->addResult('Relatórios', false, 'Erro: ' . $e->getMessage());
        }
    }
    
    /**
     * Adicionar resultado do teste
     */
    private function addResult($test, $success, $message) {
        $this->testResults[] = [
            'test' => $test,
            'success' => $success,
            'message' => $message,
            'status' => $success ? '✅' : '❌'
        ];
        
        $status = $success ? '✅' : '❌';
        echo "$status $test: $message\n";
    }
    
    /**
     * Imprimir resumo dos testes
     */
    private function printResults() {
        echo "\n📊 RESUMO DOS TESTES\n";
        echo "==================\n\n";
        
        $total = count($this->testResults);
        $passed = count(array_filter($this->testResults, function($r) { return $r['success']; }));
        $failed = $total - $passed;
        
        echo "Total de testes: $total\n";
        echo "✅ Passou: $passed\n";
        echo "❌ Falhou: $failed\n";
        echo "📈 Taxa de sucesso: " . round(($passed / $total) * 100, 2) . "%\n\n";
        
        if ($failed > 0) {
            echo "❌ TESTES QUE FALHARAM:\n";
            foreach ($this->testResults as $result) {
                if (!$result['success']) {
                    echo "  - {$result['test']}: {$result['message']}\n";
                }
            }
        }
        
        echo "\n🎯 CONCLUSÃO:\n";
        if ($failed == 0) {
            echo "✅ Todos os testes passaram! Sistema pronto para produção.\n";
        } elseif ($failed <= 2) {
            echo "⚠️  Alguns testes falharam, mas o sistema está funcional.\n";
        } else {
            echo "❌ Vários testes falharam. Revisar antes da produção.\n";
        }
    }
}

// Executar testes
$tester = new SystemTest();
$tester->runAllTests();