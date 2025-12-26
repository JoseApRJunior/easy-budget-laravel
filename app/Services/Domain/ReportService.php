<?php

declare(strict_types=1);

namespace App\Services\Domain;

use App\DTOs\Report\ReportDTO;
use App\Repositories\ReportRepository;
use App\Services\Core\Abstracts\AbstractBaseService;
use App\Support\ServiceResult;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ReportService extends AbstractBaseService
{
    /**
     * @param ReportRepository $repository
     */
    public function __construct(ReportRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Gera um novo relatório.
     *
     * @param ReportDTO $dto
     * @return ServiceResult
     */
    public function generateReport(ReportDTO $dto): ServiceResult
    {
        return $this->safeExecute(function () use ($dto) {
            return DB::transaction(function () use ($dto) {
                // Prepara os dados finais mesclando o DTO com valores padrão e contextuais
                $finalData = array_merge($dto->toArray(), [
                    'status'      => $dto->status ?? 'processing',
                    'description' => $dto->description ?? $this->generateDescription($dto->filters ?? []),
                    'file_name'   => $dto->file_name ?? $this->generateFileName($dto->type, $dto->format),
                    'tenant_id'   => $dto->tenant_id ?? $this->getEffectiveTenantId(),
                    'user_id'     => $dto->user_id ?? (int) auth()->id(),
                ]);

                // Cria um novo DTO com os dados completos
                $finalDto = ReportDTO::fromRequest($finalData);

                // Salva o relatório usando o repositório
                $report = $this->repository->createFromDTO($finalDto);

                // Dispara evento para processamento assíncrono
                event(new \App\Events\ReportGenerated($report));

                return $this->success(
                    $report,
                    'Relatório solicitado com sucesso. Você será notificado quando estiver pronto.'
                );
            });
        }, 'Erro ao solicitar relatório.');
    }

    /**
     * Obtém relatórios filtrados com paginação.
     *
     * @param array $filters
     * @param array $with
     * @return ServiceResult
     */
    public function getFilteredReports(array $filters = [], array $with = []): ServiceResult
    {
        return $this->safeExecute(function () use ($filters, $with) {
            $reports = $this->repository->getPaginated($filters, 15, $with);
            return $this->success($reports, 'Relatórios filtrados com sucesso.');
        }, 'Erro ao filtrar relatórios.');
    }

    /**
     * Prepara o download de um relatório.
     *
     * @param string $hash
     * @return ServiceResult
     */
    public function downloadReport(string $hash): ServiceResult
    {
        return $this->safeExecute(function () use ($hash) {
            $report = $this->repository->findByHash($hash, ['user']);

            if (!$report) {
                return $this->error('Relatório não encontrado.');
            }

            if ($report->status !== 'completed') {
                return $this->error('Relatório ainda não está pronto.');
            }

            if (!$report->file_path || !Storage::disk('reports')->exists($report->file_path)) {
                return $this->error('Arquivo do relatório não encontrado.');
            }

            return $this->success([
                'file_path' => $report->file_path,
                'file_name' => $report->file_name,
                'mime_type' => $this->getMimeType($report->format)
            ], 'Relatório pronto para download.');
        }, 'Erro ao preparar download.');
    }

    /**
     * Obtém estatísticas de relatórios.
     *
     * @return ServiceResult
     */
    public function getReportStats(): ServiceResult
    {
        return $this->safeExecute(function () {
            $stats = $this->repository->getStats();
            return $this->success($stats, 'Estatísticas de relatórios obtidas com sucesso.');
        }, 'Erro ao calcular estatísticas.');
    }

    /**
     * Obtém relatórios recentes formatados para a view.
     *
     * @param int $limit
     * @return ServiceResult
     */
    public function getRecentReports(int $limit = 10): ServiceResult
    {
        return $this->safeExecute(function () use ($limit) {
            $reports = $this->repository->getRecentReports($limit);

            $formattedReports = $reports->map(function ($report) {
                return (object) [
                    'id'           => $report->id,
                    'type'         => $report->getTypeLabel(),
                    'description'  => $report->description ?: 'Sem descrição',
                    'date'         => $report->generated_at ?: $report->created_at,
                    'status'       => $report->getStatusLabel(),
                    'status_color' => $this->getStatusColor($report->status),
                    'size'         => $report->getFileSizeFormatted(),
                    'view_url'     => $report->getDownloadUrl(),
                    'download_url' => $report->getDownloadUrl(),
                ];
            });

            return $this->success($formattedReports, 'Relatórios recentes obtidos com sucesso.');
        }, 'Erro ao obter relatórios recentes.');
    }

    /**
     * Retorna a cor correspondente ao status do relatório.
     */
    private function getStatusColor(string $status): string
    {
        return match ($status) {
            'completed'  => 'success',
            'processing' => 'warning',
            'pending'    => 'secondary',
            'failed'     => 'danger',
            'expired'    => 'dark',
            default      => 'secondary'
        };
    }

    /**
     * Gera uma descrição amigável baseada nos filtros.
     */
    private function generateDescription(array $filters): string
    {
        $parts = [];
        if (!empty($filters['start_date'])) $parts[] = 'De: ' . $filters['start_date'];
        if (!empty($filters['end_date'])) $parts[] = 'Até: ' . $filters['end_date'];
        if (!empty($filters['customer_name'])) $parts[] = 'Cliente: ' . $filters['customer_name'];
        return implode(' | ', $parts) ?: 'Relatório geral';
    }

    /**
     * Gera um nome de arquivo único para o relatório.
     */
    private function generateFileName(string $type, string $format): string
    {
        $timestamp = now()->format('Ymd_His');
        return "relatorio_{$type}_{$timestamp}.{$format}";
    }

    /**
     * Retorna o MIME type baseado no formato do relatório.
     */
    private function getMimeType(string $format): string
    {
        return match ($format) {
            'pdf'   => 'application/pdf',
            'excel' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'csv'   => 'text/csv',
            default => 'application/octet-stream'
        };
    }
}
