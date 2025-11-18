<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Abstracts\Controller;
use App\Services\Application\AIAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Maatwebsite\Excel\Facades\Excel;

class AIMetricsController extends Controller
{
    public function __construct(
        private AIAnalyticsService $aiAnalyticsService
    ) {}

    /**
     * Display AI metrics dashboard
     */
    public function index(Request $request): View
    {
        $metrics = $this->aiAnalyticsService->getMetrics();
        
        return view('admin.ai.metrics', compact('metrics'));
    }

    /**
     * Display AI analytics
     */
    public function analytics(Request $request): View
    {
        $dateRange = $request->get('date_range', 'last_30_days');
        $analytics = $this->aiAnalyticsService->getAnalytics($dateRange);
        
        return view('admin.ai.analytics', compact('analytics', 'dateRange'));
    }

    /**
     * Display AI predictions
     */
    public function predictions(Request $request): View
    {
        $predictions = $this->aiAnalyticsService->getPredictions();
        
        return view('admin.ai.predictions', compact('predictions'));
    }

    /**
     * Display AI anomalies
     */
    public function anomalies(Request $request): View
    {
        $anomalies = $this->aiAnalyticsService->getAnomalies();
        
        return view('admin.ai.anomalies', compact('anomalies'));
    }

    /**
     * Display AI insights
     */
    public function insights(Request $request): View
    {
        $insights = $this->aiAnalyticsService->getInsights();
        
        return view('admin.ai.insights', compact('insights'));
    }

    /**
     * Retrain AI models
     */
    public function retrain(Request $request): RedirectResponse
    {
        try {
            $result = $this->aiAnalyticsService->retrainModels();
            
            if ($result->isSuccess()) {
                return back()->with('success', 'Modelos AI re-treinados com sucesso!');
            }
            
            return back()->with('error', 'Erro ao re-treinar modelos: ' . $result->getMessage());
        } catch (\Exception $e) {
            return back()->with('error', 'Erro ao re-treinar modelos: ' . $e->getMessage());
        }
    }

    /**
     * Export AI metrics
     */
    public function export(string $type): mixed
    {
        try {
            $exportData = $this->aiAnalyticsService->prepareExportData($type);
            
            if ($type === 'excel') {
                return Excel::download($exportData, "ai_metrics.xlsx");
            } elseif ($type === 'csv') {
                return Excel::download($exportData, "ai_metrics.csv");
            } elseif ($type === 'pdf') {
                return $this->aiAnalyticsService->generatePdfExport($exportData);
            }
            
            return back()->with('error', 'Tipo de exportação não suportado.');
        } catch (\Exception $e) {
            return back()->with('error', 'Erro ao exportar métricas: ' . $e->getMessage());
        }
    }
}