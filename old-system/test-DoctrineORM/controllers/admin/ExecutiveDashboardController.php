<?php

declare(strict_types=1);

namespace app\controllers\admin;

use core\library\Response;
use core\library\Twig;
use PDO;
use DateTime;

/**
 * Controller unificado para dashboards administrativos
 * Combina funcionalidades executivas, métricas e monitoramento
 */
class ExecutiveDashboardController
{
    private PDO $pdo;

    public function __construct(protected Twig $twig)
    {
        $this->pdo = new PDO('mysql:host=localhost;dbname=easybudget;charset=utf8mb4', 'root', '');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    /**
     * Dashboard executivo principal
     */
    public function index(): Response
    {
        $data = [
            'kpis' => $this->getKPIs(),
            'trends' => $this->getTrends(),
            'alerts_summary' => $this->getAlertsSummary()
        ];

        return new Response(
            $this->twig->env->render('pages/admin/executive-dashboard.twig', $data)
        );
    }

    /**
     * API para dados dos gráficos
     */
    public function chartData(): Response
    {
        $data = [
            'performance_trend' => $this->getPerformanceTrend(),
            'middleware_distribution' => $this->getMiddlewareDistribution(),
            'alerts_timeline' => $this->getAlertsTimeline()
        ];

        return new Response(
            json_encode($data),
            200,
            ['Content-Type' => 'application/json']
        );
    }

    /**
     * Exportar relatório PDF
     */
    public function exportPDF(): Response
    {
        $data = [
            'kpis' => $this->getKPIs(),
            'trends' => $this->getTrends(),
            'generated_at' => (new DateTime())->format('d/m/Y H:i:s')
        ];

        // Simular geração PDF
        $pdfContent = $this->generatePDFContent($data);
        
        return new Response(
            $pdfContent,
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="relatorio-executivo-' . date('Y-m-d') . '.pdf"'
            ]
        );
    }

    /**
     * KPIs principais
     */
    private function getKPIs(): array
    {
        $sql = "SELECT 
                    COUNT(*) as total_requests,
                    SUM(CASE WHEN status_code >= 200 AND status_code < 300 THEN 1 ELSE 0 END) as successful_requests,
                    AVG(response_time) as avg_response_time,
                    MAX(response_time) as max_response_time
                FROM middleware_metrics_history 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $successRate = $row['total_requests'] > 0 
            ? ($row['successful_requests'] / $row['total_requests']) * 100 
            : 0;

        return [
            'total_requests' => (int)$row['total_requests'],
            'success_rate' => round($successRate, 2),
            'avg_response_time' => round((float)$row['avg_response_time'], 2),
            'max_response_time' => round((float)$row['max_response_time'], 2),
            'system_health' => $this->getSystemHealth($successRate, (float)$row['avg_response_time'])
        ];
    }

    /**
     * Tendências de performance
     */
    private function getTrends(): array
    {
        $sql = "SELECT 
                    DATE_FORMAT(created_at, '%H:00') as hour,
                    COUNT(*) as requests,
                    AVG(response_time) as avg_time,
                    SUM(CASE WHEN status_code >= 200 AND status_code < 300 THEN 1 ELSE 0 END) as successes
                FROM middleware_metrics_history 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                GROUP BY hour
                ORDER BY hour";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Resumo de alertas
     */
    private function getAlertsSummary(): array
    {
        $sql = "SELECT 
                    severity,
                    COUNT(*) as count
                FROM monitoring_alerts_history 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
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
     * Dados para gráfico de performance
     */
    private function getPerformanceTrend(): array
    {
        $sql = "SELECT 
                    DATE_FORMAT(created_at, '%H:%i') as time,
                    AVG(response_time) as avg_time
                FROM middleware_metrics_history 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                GROUP BY time
                ORDER BY time";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Distribuição por middleware
     */
    private function getMiddlewareDistribution(): array
    {
        $sql = "SELECT 
                    middleware_name,
                    COUNT(*) as requests
                FROM middleware_metrics_history 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                GROUP BY middleware_name";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Timeline de alertas
     */
    private function getAlertsTimeline(): array
    {
        $sql = "SELECT 
                    DATE_FORMAT(created_at, '%H:%i') as time,
                    severity,
                    COUNT(*) as count
                FROM monitoring_alerts_history 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                GROUP BY time, severity
                ORDER BY time";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Determina saúde do sistema
     */
    private function getSystemHealth(float $successRate, float $avgTime): string
    {
        if ($successRate < 90 || $avgTime > 150) {
            return 'CRITICAL';
        } elseif ($successRate < 95 || $avgTime > 100) {
            return 'WARNING';
        } else {
            return 'HEALTHY';
        }
    }

    /**
     * Dashboard operacional (compatibilidade com MonitoringController)
     */
    public function monitoring(): Response
    {
        return $this->index();
    }

    /**
     * Métricas em tempo real
     */
    public function realTimeMetrics(): Response
    {
        return $this->chartData();
    }

    /**
     * Dashboard de métricas (compatibilidade com MetricsDashboardController)
     */
    public function metricsDashboard(): Response
    {
        $data = [
            'title' => 'Dashboard Unificado de Métricas',
            'kpis' => $this->getKPIs(),
            'middlewares' => ['AdminMiddleware', 'AuthMiddleware', 'GuestMiddleware', 'ProviderMiddleware', 'UserMiddleware']
        ];

        return new Response(
            $this->twig->env->render('pages/admin/executive-dashboard.twig', $data)
        );
    }

    /**
     * API unificada para métricas
     */
    public function apiMetrics(): Response
    {
        return $this->chartData();
    }

    /**
     * API para relatórios
     */
    public function apiReports(): Response
    {
        $data = [
            'bottlenecks' => $this->identifyBottlenecks(),
            'recommendations' => $this->getRecommendations()
        ];

        return new Response(
            json_encode($data),
            200,
            ['Content-Type' => 'application/json']
        );
    }

    /**
     * Identifica gargalos do sistema
     */
    private function identifyBottlenecks(): array
    {
        $sql = "SELECT 
                    middleware_name,
                    AVG(response_time) as avg_time,
                    COUNT(*) as requests
                FROM middleware_metrics_history 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                  AND response_time > 100
                GROUP BY middleware_name
                HAVING avg_time > 150
                ORDER BY avg_time DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Gera recomendações baseadas nas métricas
     */
    private function getRecommendations(): array
    {
        $kpis = $this->getKPIs();
        $recommendations = [];

        if ($kpis['success_rate'] < 95) {
            $recommendations[] = 'Taxa de sucesso baixa - verificar logs de erro';
        }
        
        if ($kpis['avg_response_time'] > 100) {
            $recommendations[] = 'Tempo de resposta alto - considerar otimizações';
        }
        
        if (empty($recommendations)) {
            $recommendations[] = 'Performance geral excelente';
            $recommendations[] = 'Considere implementar cache';
            $recommendations[] = 'Monitore crescimento de uso';
        }

        return $recommendations;
    }

    /**
     * Gera conteúdo PDF (simulado)
     */
    private function generatePDFContent(array $data): string
    {
        return "PDF Content - Relatório Executivo\n" . 
               "Gerado em: " . $data['generated_at'] . "\n" .
               "Total Requisições: " . $data['kpis']['total_requests'] . "\n" .
               "Taxa de Sucesso: " . $data['kpis']['success_rate'] . "%\n";
    }
}