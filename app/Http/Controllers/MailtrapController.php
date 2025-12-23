<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Abstracts\Controller;
use App\Services\Infrastructure\EmailProviderService;
use App\Services\Infrastructure\EmailTestService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Controller para gerenciamento da interface web do Mailtrap e ferramentas de teste de e-mail.
 *
 * Funcionalidades principais:
 * - Interface web para configuração de Mailtrap
 * - Testes manuais através da interface
 * - Monitoramento de e-mails enviados
 * - Logs detalhados de teste
 * - Dashboard de status dos provedores
 *
 * Este controller integra com EmailProviderService e EmailTestService
 * para fornecer uma interface completa de gerenciamento de e-mail.
 */
class MailtrapController extends Controller
{
    /**
     * Serviço de provedores de e-mail.
     */
    private EmailProviderService $providerService;

    /**
     * Serviço de testes de e-mail.
     */
    private EmailTestService $testService;

    /**
     * Construtor: inicializa serviços necessários.
     */
    public function __construct(
        EmailProviderService $providerService,
        EmailTestService $testService,
    ) {
        $this->providerService = $providerService;
        $this->testService = $testService;
    }

    /**
     * Exibe dashboard principal do Mailtrap.
     */
    public function index(Request $request): View
    {
        try {
            $data = [
                'title' => 'Mailtrap - Ferramentas de E-mail',
                'current_provider' => $this->providerService->getCurrentProvider(),
                'available_providers' => $this->providerService->getAvailableProviders(),
                'provider_stats' => $this->providerService->getProviderStats(),
                'test_types' => $this->testService->getAvailableTestTypes(),
                'recent_tests' => $this->getRecentTestResults(),
            ];

            Log::info('Dashboard Mailtrap acessado', [
                'user_id' => auth()->id(),
                'current_provider' => $data['current_provider']['provider'] ?? 'unknown',
            ]);

            return view('mailtrap.index', $data);

        } catch (Exception $e) {
            Log::error('Erro ao carregar dashboard Mailtrap', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return view('mailtrap.index', [
                'title' => 'Mailtrap - Ferramentas de E-mail',
                'error' => 'Erro ao carregar dashboard: '.$e->getMessage(),
            ]);
        }
    }

    /**
     * Exibe página de configuração de provedores.
     */
    public function providers(Request $request): View
    {
        try {
            $data = [
                'title' => 'Configuração de Provedores de E-mail',
                'providers' => $this->providerService->getAvailableProviders(),
                'current_provider' => $this->providerService->getCurrentProvider(),
                'provider_configs' => $this->getProviderConfigurations(),
            ];

            return view('mailtrap.providers', $data);

        } catch (Exception $e) {
            Log::error('Erro ao carregar configuração de provedores', [
                'error' => $e->getMessage(),
            ]);

            return view('mailtrap.providers', [
                'title' => 'Configuração de Provedores de E-mail',
                'error' => 'Erro ao carregar provedores: '.$e->getMessage(),
            ]);
        }
    }

    /**
     * Testa conectividade com um provedor específico.
     */
    public function testProvider(Request $request)
    {
        try {
            $provider = $request->input('provider');

            if (! $provider) {
                return response()->json([
                    'success' => false,
                    'error' => 'Provedor não especificado',
                ], 400);
            }

            $result = $this->providerService->testProvider($provider);

            return response()->json([
                'success' => $result->isSuccess(),
                'message' => $result->getMessage(),
                'data' => $result->getData(),
                'timestamp' => now()->toDateTimeString(),
            ]);

        } catch (Exception $e) {
            Log::error('Erro ao testar provedor', [
                'provider' => $request->input('provider'),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erro interno: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Exibe página de testes de e-mail.
     */
    public function tests(Request $request): View
    {
        try {
            $data = [
                'title' => 'Testes de E-mail',
                'test_types' => $this->testService->getAvailableTestTypes(),
                'recent_results' => $this->getRecentTestResults(),
                'test_history' => $this->getTestHistory(),
            ];

            return view('mailtrap.tests', $data);

        } catch (Exception $e) {
            Log::error('Erro ao carregar página de testes', [
                'error' => $e->getMessage(),
            ]);

            return view('mailtrap.tests', [
                'title' => 'Testes de E-mail',
                'error' => 'Erro ao carregar testes: '.$e->getMessage(),
            ]);
        }
    }

    /**
     * Executa teste específico de e-mail.
     */
    public function runTest(Request $request)
    {
        try {
            $testType = $request->input('test_type');

            if (! $testType) {
                return response()->json([
                    'success' => false,
                    'error' => 'Tipo de teste não especificado',
                ], 400);
            }

            $options = [
                'recipient_email' => $request->input('recipient_email', 'test@example.com'),
                'tenant_id' => $request->input('tenant_id', auth()->user()->tenant_id ?? 1),
                'verification_url' => $request->input('verification_url'),
            ];

            $result = $this->testService->runTest($testType, $options);

            return response()->json([
                'success' => $result->isSuccess(),
                'message' => $result->getMessage(),
                'data' => $result->getData(),
                'timestamp' => now()->toDateTimeString(),
            ]);

        } catch (Exception $e) {
            Log::error('Erro ao executar teste de e-mail', [
                'test_type' => $request->input('test_type'),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erro interno: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Exibe logs detalhados de e-mail.
     */
    public function logs(Request $request): View
    {
        try {
            $data = [
                'title' => 'Logs de E-mail',
                'log_entries' => $this->getEmailLogs(),
                'log_summary' => $this->getLogSummary(),
            ];

            return view('mailtrap.logs', $data);

        } catch (Exception $e) {
            Log::error('Erro ao carregar logs de e-mail', [
                'error' => $e->getMessage(),
            ]);

            return view('mailtrap.logs', [
                'title' => 'Logs de E-mail',
                'error' => 'Erro ao carregar logs: '.$e->getMessage(),
            ]);
        }
    }

    /**
     * Gera relatório completo de testes.
     */
    public function generateReport(Request $request)
    {
        try {
            $result = $this->testService->generateTestReport();

            if ($result->isSuccess()) {
                $reportData = $result->getData();

                // Se for requisição AJAX, retorna JSON
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => true,
                        'report' => $reportData,
                        'generated_at' => now()->toDateTimeString(),
                    ]);
                }

                // Caso contrário, exibe página com relatório
                return view('mailtrap.report', [
                    'title' => 'Relatório de Testes de E-mail',
                    'report' => $reportData,
                ]);
            } else {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'error' => $result->getMessage(),
                    ], 500);
                }

                return back()->with('error', $result->getMessage());
            }

        } catch (Exception $e) {
            Log::error('Erro ao gerar relatório de testes', [
                'error' => $e->getMessage(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Erro interno: '.$e->getMessage(),
                ], 500);
            }

            return back()->with('error', 'Erro ao gerar relatório: '.$e->getMessage());
        }
    }

    /**
     * Limpa cache de testes e configurações.
     */
    public function clearCache(Request $request)
    {
        try {
            $results = [];

            // Limpar cache de provedores
            $providerResult = $this->providerService->clearProviderCache();
            $results[] = [
                'service' => 'provider_cache',
                'success' => $providerResult->isSuccess(),
                'message' => $providerResult->getMessage(),
            ];

            // Limpar cache de testes
            $testResult = $this->testService->clearTestCache();
            $results[] = [
                'service' => 'test_cache',
                'success' => $testResult->isSuccess(),
                'message' => $testResult->getMessage(),
            ];

            $allSuccessful = collect($results)->every('success');

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => $allSuccessful,
                    'results' => $results,
                    'message' => $allSuccessful ? 'Cache limpo com sucesso' : 'Alguns caches não foram limpos',
                    'timestamp' => now()->toDateTimeString(),
                ]);
            }

            $message = $allSuccessful
                ? 'Cache limpo com sucesso'
                : 'Alguns caches não foram limpos. Verifique os detalhes.';

            return back()->with($allSuccessful ? 'success' : 'warning', $message);

        } catch (Exception $e) {
            Log::error('Erro ao limpar cache', [
                'error' => $e->getMessage(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Erro interno: '.$e->getMessage(),
                ], 500);
            }

            return back()->with('error', 'Erro ao limpar cache: '.$e->getMessage());
        }
    }

    /**
     * Exibe configurações detalhadas de um provedor.
     */
    public function providerConfig(Request $request, string $provider)
    {
        try {
            $config = $this->providerService->getProviderConfig($provider);

            if (empty($config)) {
                return response()->json([
                    'success' => false,
                    'error' => "Provedor '{$provider}' não encontrado",
                ], 404);
            }

            $testResult = $this->providerService->testProvider($provider);

            return response()->json([
                'success' => true,
                'provider' => $provider,
                'config' => $config,
                'test_result' => [
                    'is_success' => $testResult->isSuccess(),
                    'message' => $testResult->getMessage(),
                ],
                'timestamp' => now()->toDateTimeString(),
            ]);

        } catch (Exception $e) {
            Log::error('Erro ao obter configuração de provedor', [
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erro interno: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtém resultados recentes de teste.
     */
    private function getRecentTestResults(): array
    {
        $results = [];

        foreach (['connectivity', 'verification', 'budget_notification', 'invoice_notification'] as $testType) {
            $cachedResult = $this->testService->getCachedTestResult($testType);

            if ($cachedResult) {
                $results[] = [
                    'test_type' => $testType,
                    'name' => $this->testService->getAvailableTestTypes()[$testType]['name'] ?? $testType,
                    'is_success' => $cachedResult['is_success'],
                    'message' => $cachedResult['message'],
                    'cached_at' => $cachedResult['cached_at'],
                ];
            }
        }

        return $results;
    }

    /**
     * Obtém histórico de testes.
     */
    private function getTestHistory(): array
    {
        // Em produção, seria implementado com banco de dados
        // Por ora, retorna dados dos últimos testes em cache
        return $this->getRecentTestResults();
    }

    /**
     * Obtém configurações de provedores para exibição.
     */
    private function getProviderConfigurations(): array
    {
        $configs = [];

        foreach (['mailtrap', 'smtp', 'ses', 'log'] as $provider) {
            $configs[$provider] = $this->providerService->getProviderConfig($provider);
        }

        return $configs;
    }

    /**
     * Obtém logs de e-mail (últimas entradas).
     */
    private function getEmailLogs(): array
    {
        try {
            // Em produção, seria implementado com leitura de arquivos de log
            // Por ora, retorna informações básicas
            return [
                [
                    'timestamp' => now()->toDateTimeString(),
                    'level' => 'info',
                    'message' => 'Sistema de logs de e-mail inicializado',
                    'context' => ['user_id' => auth()->id()],
                ],
            ];

        } catch (Exception $e) {
            Log::error('Erro ao obter logs de e-mail', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Obtém resumo dos logs.
     */
    private function getLogSummary(): array
    {
        return [
            'total_entries' => 1,
            'last_updated' => now()->toDateTimeString(),
            'log_level' => 'info',
        ];
    }
}
