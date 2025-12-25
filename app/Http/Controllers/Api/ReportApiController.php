<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\DTOs\Report\ReportDTO;
use App\Http\Controllers\Abstracts\Controller;
use App\Services\Domain\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;

/**
 * API RESTful para gerenciamento avançado de relatórios
 */
class ReportApiController extends Controller
{
    /**
     * @param ReportService $reportService
     */
    public function __construct(
        protected ReportService $reportService
    ) {}

    /**
     * Lista relatórios com filtros.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $result = $this->reportService->getFilteredReports($request->all());
        return $this->response($result);
    }

    /**
     * Solicita a geração de um relatório.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $dto = ReportDTO::fromRequest($request->all());
            $result = $this->reportService->generateReport($dto);
            return $this->response($result);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao processar solicitação: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Obtém estatísticas dos relatórios.
     *
     * @return JsonResponse
     */
    public function stats(): JsonResponse
    {
        $result = $this->reportService->getReportStats();
        return $this->response($result);
    }

    /**
     * Prepara o download de um relatório.
     *
     * @param string $hash
     * @return JsonResponse
     */
    public function download(string $hash): JsonResponse
    {
        $result = $this->reportService->downloadReport($hash);
        return $this->response($result);
    }

    /**
     * Obtém relatórios recentes.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function recent(Request $request): JsonResponse
    {
        $limit = (int) $request->get('limit', 10);
        $result = $this->reportService->getRecentReports($limit);
        return $this->response($result);
    }
}
