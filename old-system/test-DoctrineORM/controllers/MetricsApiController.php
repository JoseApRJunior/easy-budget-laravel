<?php

declare(strict_types=1);

namespace app\controllers;

use core\library\Response;
use core\services\AlertService;
use DateTime;
use PDO;

/**
 * API Controller para métricas de monitoramento
 */
class MetricsApiController
{
    private PDO $pdo;
    private AlertService $alertService;

    public function __construct()
    {
        $this->pdo = new PDO('mysql:host=localhost;dbname=easybudget;charset=utf8mb4', 'root', '');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->alertService = new AlertService();
    }

    /**
     * GET /api/metrics/summary
     */
    public function summary(): Response
    {
        $data = [
            'middlewares' => $this->getMiddlewareSummary(),
            'alerts' => $this->getActiveAlertsCount(),
            'system_health' => $this->getSystemHealth(),
            'timestamp' => (new DateTime())->format('c')
        ];

        return new Response(
            json_encode($data),
            200,
            ['Content-Type' => 'application/json']
        );
    }

    /**
     * GET /api/metrics/realtime
     */
    public function realtime(): Response
    {
        $data = [
            'current_metrics' => $this->getCurrentMetrics(),
            'active_alerts' => $this->alertService->getActiveAlerts(),
            'timestamp' => (new DateTime())->format('c')
        ];

        return new Response(
            json_encode($data),
            200,
            ['Content-Type' => 'application/json']
        );
    }

    /**
     * Obtém resumo dos middlewares
     */
    private function getMiddlewareSummary(): array
    {
        $sql = "SELECT 
                    middleware_name,
                    COUNT(*) as total_requests,
                    SUM(CASE WHEN status_code >= 200 AND status_code < 300 THEN 1 ELSE 0 END) as successful_requests,
                    AVG(response_time) as avg_response_time
                FROM middleware_metrics_history 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                GROUP BY middleware_name";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        
        $middlewares = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $successRate = $row['total_requests'] > 0 
                ? ($row['successful_requests'] / $row['total_requests']) * 100 
                : 100;
            
            $middlewares[] = [
                'name' => $row['middleware_name'],
                'total_requests' => (int)$row['total_requests'],
                'success_rate' => round($successRate, 2),
                'avg_response_time' => round((float)$row['avg_response_time'], 2),
                'status' => $this->getMiddlewareStatus($successRate, (float)$row['avg_response_time'])
            ];
        }
        
        return $middlewares;
    }

    /**
     * Obtém contagem de alertas ativos
     */
    private function getActiveAlertsCount(): array
    {
        $sql = "SELECT severity, COUNT(*) as count 
                FROM monitoring_alerts_history 
                WHERE status = 'ACTIVE' 
                GROUP BY severity";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        
        $alerts = ['CRITICAL' => 0, 'WARNING' => 0, 'INFO' => 0];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $alerts[$row['severity']] = (int)$row['count'];
        }
        
        return $alerts;
    }

    /**
     * Obtém saúde geral do sistema
     */
    private function getSystemHealth(): string
    {
        $activeAlerts = $this->getActiveAlertsCount();
        
        if ($activeAlerts['CRITICAL'] > 0) {
            return 'CRITICAL';
        } elseif ($activeAlerts['WARNING'] > 2) {
            return 'WARNING';
        } else {
            return 'HEALTHY';
        }
    }

    /**
     * Obtém métricas atuais (últimos 5 minutos)
     */
    private function getCurrentMetrics(): array
    {
        $sql = "SELECT 
                    middleware_name,
                    COUNT(*) as requests_last_5min,
                    AVG(response_time) as avg_response_time,
                    AVG(memory_usage) as avg_memory_usage
                FROM middleware_metrics_history 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
                GROUP BY middleware_name";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Determina status do middleware
     */
    private function getMiddlewareStatus(float $successRate, float $avgResponseTime): string
    {
        if ($successRate < 90 || $avgResponseTime > 150) {
            return 'CRITICAL';
        } elseif ($successRate < 95 || $avgResponseTime > 100) {
            return 'WARNING';
        } else {
            return 'HEALTHY';
        }
    }
}