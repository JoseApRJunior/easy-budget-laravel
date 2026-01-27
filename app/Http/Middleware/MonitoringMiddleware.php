<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\MiddlewareMetricsHistory;
use App\Services\AlertService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class MonitoringMiddleware
{
    private float $startTime;

    private int $startMemory;

    private int $startQueries;

    private array $startCacheStats;

    public function handle(Request $request, Closure $next): Response
    {
        // Registrar métricas iniciais
        $this->startTime = microtime(true);
        $this->startMemory = memory_get_usage(true);
        $this->startQueries = DB::getQueryLog() ? count(DB::getQueryLog()) : 0;
        $this->startCacheStats = [
            'hits' => Cache::get('cache_hits', 0),
            'misses' => Cache::get('cache_misses', 0),
        ];

        // Processar a requisição
        $response = $next($request);

        // Registrar métricas finais após a resposta
        $this->recordMetrics($request, $response);

        return $response;
    }

    private function recordMetrics(Request $request, Response $response): void
    {
        try {
            $endTime = microtime(true);
            $endMemory = memory_get_usage(true);
            $endQueries = DB::getQueryLog() ? count(DB::getQueryLog()) : 0;

            $responseTime = ($endTime - $this->startTime) * 1000; // ms
            $memoryUsage = max(0, $endMemory - $this->startMemory);
            $databaseQueries = max(0, $endQueries - $this->startQueries);

            // Calcular cache hits/misses
            $endCacheHits = Cache::get('cache_hits', 0);
            $endCacheMisses = Cache::get('cache_misses', 0);
            $cacheHits = max(0, $endCacheHits - $this->startCacheStats['hits']);
            $cacheMisses = max(0, $endCacheMisses - $this->startCacheStats['misses']);

            // Obter tenant_id do usuário autenticado ou do request
            $tenantId = $this->getTenantId($request);
            $userId = $this->getUserId($request);

            // Registrar no banco de dados (somente se o modelo existir)
            if (class_exists(\App\Models\MiddlewareMetricsHistory::class)) {
                MiddlewareMetricsHistory::create([
                    'tenant_id' => $tenantId,
                    'user_id' => $userId,
                    'middleware_name' => 'monitoring',
                    'endpoint' => $request->path(),
                    'method' => $request->method(),
                    'response_time' => $responseTime,
                    'memory_usage' => $memoryUsage,
                    'cpu_usage' => $this->getCpuUsage(),
                    'status_code' => $response->getStatusCode(),
                    'database_queries' => $databaseQueries,
                    'cache_hits' => $cacheHits,
                    'cache_misses' => $cacheMisses,
                    'request_size' => strlen($request->getContent()),
                    'response_size' => strlen($response->getContent()),
                    'ip_address' => $request->ip(),
                    'user_agent' => substr($request->userAgent() ?? '', 0, 255),
                    'created_at' => now(),
                ]);
            } else {
                Log::debug('MiddlewareMetricsHistory não encontrado, métricas registradas apenas em log', [
                    'endpoint' => $request->path(),
                    'method' => $request->method(),
                    'response_time' => $responseTime,
                    'memory_usage' => $memoryUsage,
                    'status_code' => $response->getStatusCode(),
                ]);
            }

            // Verificar se deve gerar alertas baseado nas métricas
            $this->checkForAlerts($tenantId, $responseTime, $memoryUsage, $response->getStatusCode(), $databaseQueries);

        } catch (\Exception $e) {
            Log::error('Erro ao registrar métricas do middleware', [
                'error' => $e->getMessage(),
                'request_path' => $request->path(),
                'request_method' => $request->method(),
            ]);
        }
    }

    private function getTenantId(Request $request): ?int
    {
        // Tentar obter do usuário autenticado
        if ($request->user() && method_exists($request->user(), 'tenant_id')) {
            return $request->user()->tenant_id;
        }

        // Tentar do header ou parâmetro
        $tenantId = $request->header('X-Tenant-ID') ?? $request->input('tenant_id');

        if ($tenantId) {
            return (int) $tenantId;
        }

        return null;
    }

    private function getUserId(Request $request): ?int
    {
        return $request->user()?->id;
    }

    private function getCpuUsage(): ?float
    {
        try {
            if (function_exists('sys_getloadavg')) {
                $load = sys_getloadavg();

                return $load[0] ?? null;
            }
        } catch (\Exception $e) {
            // Silencioso - CPU usage não é crítico
        }

        return null;
    }

    private function checkForAlerts(
        ?int $tenantId,
        float $responseTime,
        int $memoryUsage,
        int $statusCode,
        int $databaseQueries
    ): void {
        if (! $tenantId) {
            return;
        }

        try {
            // Verificar tempo de resposta alto
            if ($responseTime > 5000) { // 5 segundos
                app(AlertService::class)->evaluateMetric(
                    $tenantId,
                    \App\Enums\AlertTypeEnum::PERFORMANCE,
                    'response_time',
                    $responseTime,
                    [
                        'endpoint' => request()->path(),
                        'method' => request()->method(),
                        'threshold' => 5000,
                        'unit' => 'ms',
                    ]
                );
            }

            // Verificar uso de memória alto (mais de 128MB)
            if ($memoryUsage > 134217728) { // 128MB em bytes
                app(AlertService::class)->evaluateMetric(
                    $tenantId,
                    \App\Enums\AlertTypeEnum::RESOURCE,
                    'memory_usage',
                    $memoryUsage / 1048576, // Converter para MB
                    [
                        'threshold' => 128,
                        'unit' => 'MB',
                    ]
                );
            }

            // Verificar erros HTTP
            if ($statusCode >= 500) {
                app(AlertService::class)->evaluateMetric(
                    $tenantId,
                    \App\Enums\AlertTypeEnum::AVAILABILITY,
                    'error_5xx',
                    $statusCode,
                    [
                        'endpoint' => request()->path(),
                        'method' => request()->method(),
                        'status_code' => $statusCode,
                    ]
                );
            }

            // Verificar muitas queries de banco
            if ($databaseQueries > 50) {
                app(AlertService::class)->evaluateMetric(
                    $tenantId,
                    \App\Enums\AlertTypeEnum::PERFORMANCE,
                    'database_queries',
                    $databaseQueries,
                    [
                        'endpoint' => request()->path(),
                        'method' => request()->method(),
                        'threshold' => 50,
                    ]
                );
            }

        } catch (\Exception $e) {
            Log::error('Erro ao verificar alertas', [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
