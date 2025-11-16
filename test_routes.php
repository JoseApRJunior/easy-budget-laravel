<?php
/**
 * Script de Teste de Rotas do Sistema Easy Budget
 * Verifica todas as rotas principais do sistema
 */

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\Route;

class RouteTester {
    private $routes = [];
    private $results = [];
    
    public function __construct() {
        echo "🛣️  TESTANDO ROTAS DO SISTEMA\n";
        echo "============================\n\n";
        $this->loadRoutes();
    }
    
    /**
     * Carregar todas as rotas do sistema
     */
    private function loadRoutes() {
        // Rotas de Autenticação
        $this->routes['auth'] = [
            'login' => '/login',
            'register' => '/register',
            'password.request' => '/forgot-password',
            'password.reset' => '/reset-password/{token}',
            'verification.notice' => '/email/verify',
            'verification.verify' => '/email/verify/{id}/{hash}',
            'verification.resend' => '/email/resend',
        ];
        
        // Rotas Públicas
        $this->routes['public'] = [
            'home.index' => '/home',
            'home.features' => '/features',
            'home.pricing' => '/pricing',
            'home.about' => '/about',
            'home.contact' => '/contact',
        ];
        
        // Rotas do Provider
        $this->routes['provider'] = [
            'provider.dashboard' => '/provider/dashboard',
            'provider.profile' => '/provider/profile',
            'provider.settings' => '/provider/settings',
            'provider.customers.index' => '/provider/customers',
            'provider.customers.create' => '/provider/customers/create',
            'provider.products.index' => '/provider/products',
            'provider.products.create' => '/provider/products/create',
            'provider.services.index' => '/provider/services',
            'provider.services.create' => '/provider/services/create',
            'provider.budgets.index' => '/provider/budgets',
            'provider.budgets.create' => '/provider/budgets/create',
            'provider.invoices.index' => '/provider/invoices',
            'provider.invoices.create' => '/provider/invoices/create',
            'provider.schedules.index' => '/provider/schedules',
            'provider.schedules.create' => '/provider/schedules/create',
            'provider.qrcode.index' => '/provider/qrcode',
            'provider.reports.index' => '/provider/reports',
        ];
        
        // Rotas de API
        $this->routes['api'] = [
            'api.user' => '/api/user',
            'api.budgets.index' => '/api/budgets',
            'api.customers.index' => '/api/customers',
            'api.products.index' => '/api/products',
            'api.services.index' => '/api/services',
        ];
    }
    
    /**
     * Executar testes de rotas
     */
    public function runTests() {
        $this->testRouteNames();
        $this->testRouteParameters();
        $this->testRouteMiddleware();
        $this->printResults();
    }
    
    /**
     * Testar nomes de rotas
     */
    private function testRouteNames() {
        echo "📋 Testando Nomes de Rotas...\n";
        
        foreach ($this->routes as $group => $routes) {
            foreach ($routes as $name => $path) {
                try {
                    $route = Route::getRoutes()->getByName($name);
                    if ($route) {
                        $this->addResult("Rota $name", true, "Rota encontrada: $path");
                    } else {
                        $this->addResult("Rota $name", false, "Rota não encontrada: $path");
                    }
                } catch (\Exception $e) {
                    $this->addResult("Rota $name", false, "Erro: " . $e->getMessage());
                }
            }
        }
    }
    
    /**
     * Testar parâmetros de rotas
     */
    private function testRouteParameters() {
        echo "📋 Testando Parâmetros de Rotas...\n";
        
        // Rotas com parâmetros
        $parameterRoutes = [
            'provider.customers.show' => '/provider/customers/{customer}',
            'provider.products.show' => '/provider/products/{product}',
            'provider.services.show' => '/provider/services/{service}',
            'provider.budgets.show' => '/provider/budgets/{budget}',
            'provider.invoices.show' => '/provider/invoices/{invoice}',
            'provider.schedules.show' => '/provider/schedules/{schedule}',
        ];
        
        foreach ($parameterRoutes as $name => $path) {
            try {
                $route = Route::getRoutes()->getByName($name);
                if ($route) {
                    $parameters = $route->parameterNames();
                    $this->addResult("Parâmetros $name", true, "Parâmetros: " . implode(', ', $parameters));
                } else {
                    $this->addResult("Parâmetros $name", false, "Rota não encontrada");
                }
            } catch (\Exception $e) {
                $this->addResult("Parâmetros $name", false, "Erro: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Testar middleware de rotas
     */
    private function testRouteMiddleware() {
        echo "📋 Testando Middleware de Rotas...\n";
        
        $middlewareGroups = [
            'web' => ['web'],
            'auth' => ['auth', 'verified'],
            'provider' => ['auth', 'verified', 'provider'],
            'api' => ['api', 'auth:sanctum'],
        ];
        
        foreach ($middlewareGroups as $group => $expectedMiddleware) {
            try {
                // Testar uma rota representativa do grupo
                $testRoute = $this->getTestRouteForGroup($group);
                if ($testRoute) {
                    $route = Route::getRoutes()->getByName($testRoute);
                    if ($route) {
                        $middleware = $route->gatherMiddleware();
                        $hasExpected = !empty(array_intersect($expectedMiddleware, $middleware));
                        $this->addResult("Middleware $group", $hasExpected, "Middleware: " . implode(', ', $middleware));
                    } else {
                        $this->addResult("Middleware $group", false, "Rota de teste não encontrada");
                    }
                } else {
                    $this->addResult("Middleware $group", false, "Rota de teste não definida");
                }
            } catch (\Exception $e) {
                $this->addResult("Middleware $group", false, "Erro: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Obter rota de teste para grupo
     */
    private function getTestRouteForGroup($group) {
        $testRoutes = [
            'web' => 'home.index',
            'auth' => 'dashboard',
            'provider' => 'provider.dashboard',
            'api' => 'api.user',
        ];
        
        return $testRoutes[$group] ?? null;
    }
    
    /**
     * Adicionar resultado do teste
     */
    private function addResult($test, $success, $message) {
        $this->results[] = [
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
        echo "\n📊 RESUMO DOS TESTES DE ROTAS\n";
        echo "=============================\n\n";
        
        $total = count($this->results);
        $passed = count(array_filter($this->results, function($r) { return $r['success']; }));
        $failed = $total - $passed;
        
        echo "Total de testes: $total\n";
        echo "✅ Passou: $passed\n";
        echo "❌ Falhou: $failed\n";
        echo "📈 Taxa de sucesso: " . round(($passed / $total) * 100, 2) . "%\n\n";
        
        if ($failed > 0) {
            echo "❌ ROTAS COM PROBLEMAS:\n";
            foreach ($this->results as $result) {
                if (!$result['success']) {
                    echo "  - {$result['test']}: {$result['message']}\n";
                }
            }
        }
        
        echo "\n🎯 CONCLUSÃO:\n";
        if ($failed == 0) {
            echo "✅ Todas as rotas estão funcionando corretamente!\n";
        } elseif ($failed <= 3) {
            echo "⚠️  Algumas rotas têm problemas, mas o sistema está funcional.\n";
        } else {
            echo "❌ Várias rotas com problemas. Revisar antes da produção.\n";
        }
    }
}

// Executar testes
$tester = new RouteTester();
$tester->runTests();