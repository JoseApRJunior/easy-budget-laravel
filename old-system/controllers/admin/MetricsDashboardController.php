<?php

declare(strict_types=1);

namespace app\controllers\admin;

use app\controllers\AbstractController;
use core\library\Response;
use core\library\Twig;
use PDO;

/**
 * Controller para dashboard de métricas operacionais
 */
class MetricsDashboardController extends AbstractController
{
    private PDO $pdo;

    public function __construct(protected Twig $twig)
    {
        $this->pdo = new PDO('mysql:host=localhost;dbname=easybudget;charset=utf8mb4', 'root', '');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function index(): Response
    {
        $data = [
            'title' => 'Dashboard de Métricas Operacionais',
            'metrics' => $this->getOperationalMetrics(),
            'middlewares' => $this->getMiddlewareList()
        ];

        return new Response(
            $this->twig->env->render('pages/admin/metrics-dashboard.twig', $data)
        );
    }

    public function apiPerformanceMetrics(): Response
    {
        $metrics = $this->getPerformanceMetrics();
        return new Response(
            json_encode($metrics),
            200,
            ['Content-Type' => 'application/json']
        );
    }

    public function apiBottlenecks(): Response
    {
        $bottlenecks = $this->getBottlenecks();
        return new Response(
            json_encode($bottlenecks),
            200,
            ['Content-Type' => 'application/json']
        );
    }

    public function apiSummary(): Response
    {
        $summary = $this->getMetricsSummary();
        return new Response(
            json_encode($summary),
            200,
            ['Content-Type' => 'application/json']
        );
    }

    private function getOperationalMetrics(): array
    {
        $sql = "SELECT 
                    middleware_name,
                    COUNT(*) as total_executions,
                    AVG(response_time) as avg_response_time,
                    MIN(response_time) as min_response_time,
                    MAX(response_time) as max_response_time,
                    SUM(CASE WHEN status_code >= 200 AND status_code < 300 THEN 1 ELSE 0 END) as success_count
                FROM middleware_metrics_history 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                GROUP BY middleware_name";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getPerformanceMetrics(): array
    {
        $sql = "SELECT 
                    DATE_FORMAT(created_at, '%H:%i') as time_slot,
                    AVG(response_time) as avg_time,
                    COUNT(*) as request_count
                FROM middleware_metrics_history 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 HOUR)
                GROUP BY time_slot
                ORDER BY time_slot";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getBottlenecks(): array
    {
        $sql = "SELECT 
                    middleware_name,
                    AVG(response_time) as avg_time,
                    COUNT(*) as slow_requests
                FROM middleware_metrics_history 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                  AND response_time > 200
                GROUP BY middleware_name
                HAVING avg_time > 300
                ORDER BY avg_time DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getMetricsSummary(): array
    {
        $sql = "SELECT 
                    COUNT(*) as total_requests,
                    AVG(response_time) as avg_response_time,
                    SUM(CASE WHEN status_code >= 200 AND status_code < 300 THEN 1 ELSE 0 END) as successful_requests
                FROM middleware_metrics_history 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $successRate = $row['total_requests'] > 0 
            ? ($row['successful_requests'] / $row['total_requests']) * 100 
            : 100;

        return [
            'total_requests' => (int)$row['total_requests'],
            'avg_response_time' => round((float)$row['avg_response_time'], 2),
            'success_rate' => round($successRate, 2),
            'status' => $this->getSystemStatus($successRate, (float)$row['avg_response_time'])
        ];
    }

    private function getMiddlewareList(): array
    {
        return ['AdminMiddleware', 'AuthMiddleware', 'GuestMiddleware', 'ProviderMiddleware', 'UserMiddleware'];
    }

    private function getSystemStatus(float $successRate, float $avgTime): string
    {
        if ($successRate < 90 || $avgTime > 500) {
            return 'CRITICAL';
        } elseif ($successRate < 95 || $avgTime > 300) {
            return 'WARNING';
        }
        return 'HEALTHY';
    }
}