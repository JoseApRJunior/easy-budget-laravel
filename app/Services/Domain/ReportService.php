<?php

declare(strict_types=1);

namespace App\Services\Domain;

use App\Enums\OperationStatus;
use App\Repositories\ReportRepository;
use App\Services\Core\Abstracts\AbstractBaseService;
use App\Support\ServiceResult;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ReportService extends AbstractBaseService
{
    public function __construct( ReportRepository $repository )
    {
        parent::__construct( $repository );
    }

    protected function getReportRepository(): ReportRepository
    {
        /** @var ReportRepository $repository */
        $repository = $this->repository;
        return $repository;
    }

    public function generateReport( array $data ): ServiceResult
    {
        try {
            return DB::transaction( function () use ($data) {
                $report = $this->repository->create( [
                    'type'        => $data[ 'type' ],
                    'format'      => $data[ 'format' ],
                    'status'      => 'processing',
                    'filters'     => $data[ 'filters' ],
                    'description' => $this->generateDescription( $data[ 'filters' ] ),
                    'file_name'   => $this->generateFileName( $data[ 'type' ], $data[ 'format' ] ),
                ] );

                // TODO: Implement GenerateReportJob
                // GenerateReportJob::dispatch( $report );

                return $this->success( $report, 'Relatório solicitado com sucesso. Você será notificado quando estiver pronto.' );
            } );
        } catch ( Exception $e ) {
            return $this->error( OperationStatus::ERROR, 'Erro ao solicitar relatório', null, $e );
        }
    }

    public function getFilteredReports( array $filters = [], array $with = [] ): ServiceResult
    {
        try {
            $reports = $this->getReportRepository()->getPaginated( $filters, 15 );
            return $this->success( $reports, 'Relatórios filtrados' );
        } catch ( Exception $e ) {
            return $this->error( OperationStatus::ERROR, 'Erro ao filtrar relatórios', null, $e );
        }
    }

    public function downloadReport( string $hash ): ServiceResult
    {
        try {
            $report = $this->getReportRepository()->findByHash( $hash, [ 'user' ] );
            if ( !$report ) return $this->error( OperationStatus::NOT_FOUND, 'Relatório não encontrado' );

            if ( $report->status !== 'completed' ) {
                return $this->error( OperationStatus::VALIDATION_ERROR, 'Relatório ainda não está pronto' );
            }

            if ( !$report->file_path || !Storage::disk( 'reports' )->exists( $report->file_path ) ) {
                return $this->error( OperationStatus::ERROR, 'Arquivo do relatório não encontrado' );
            }

            return $this->success( [
                'file_path' => $report->file_path,
                'file_name' => $report->file_name,
                'mime_type' => $this->getMimeType( $report->format )
            ], 'Relatório pronto para download' );
        } catch ( Exception $e ) {
            return $this->error( OperationStatus::ERROR, 'Erro ao preparar download', null, $e );
        }
    }

    public function getReportStats(): ServiceResult
    {
        try {
            $model = $this->getReportRepository()->getModel();
            $stats = [
                'total_reports'   => $model->count(),
                'completed_today' => $model->where( 'status', 'completed' )->whereDate( 'generated_at', today() )->count(),
                'by_type'         => $model->selectRaw( 'type, count(*) as count' )->groupBy( 'type' )->pluck( 'count', 'type' )->toArray(),
                'by_status'       => $model->selectRaw( 'status, count(*) as count' )->groupBy( 'status' )->pluck( 'count', 'status' )->toArray(),
            ];
            return $this->success( $stats, 'Estatísticas de relatórios' );
        } catch ( Exception $e ) {
            return $this->error( OperationStatus::ERROR, 'Erro ao calcular estatísticas', null, $e );
        }
    }

    public function getRecentReports( int $limit = 10 ): ServiceResult
    {
        try {
            $reports = $this->getReportRepository()->getRecentReports( $limit );

            // Formatar dados para a view
            $formattedReports = $reports->map( function ( $report ) {
                return (object) [
                    'id'           => $report->id,
                    'type'         => $report->getTypeLabel(),
                    'description'  => $report->description ?: 'Sem descrição',
                    'date'         => $report->generated_at ?: $report->created_at,
                    'status'       => $report->getStatusLabel(),
                    'status_color' => $this->getStatusColor( $report->status ),
                    'size'         => $report->getFileSizeFormatted(),
                    'view_url'     => $report->getDownloadUrl(),
                    'download_url' => $report->getDownloadUrl(),
                ];
            } );

            return $this->success( $formattedReports, 'Relatórios recentes obtidos com sucesso' );
        } catch ( Exception $e ) {
            return $this->error( OperationStatus::ERROR, 'Erro ao obter relatórios recentes', null, $e );
        }
    }

    private function getStatusColor( string $status ): string
    {
        return match ( $status ) {
            'completed'  => 'success',
            'processing' => 'warning',
            'pending'    => 'secondary',
            'failed'     => 'danger',
            'expired'    => 'dark',
            default      => 'secondary'
        };
    }

    private function generateDescription( array $filters ): string
    {
        $parts = [];
        if ( !empty( $filters[ 'start_date' ] ) ) $parts[] = 'De: ' . $filters[ 'start_date' ];
        if ( !empty( $filters[ 'end_date' ] ) ) $parts[] = 'Até: ' . $filters[ 'end_date' ];
        if ( !empty( $filters[ 'customer_name' ] ) ) $parts[] = 'Cliente: ' . $filters[ 'customer_name' ];
        return implode( ' | ', $parts ) ?: 'Relatório geral';
    }

    private function generateFileName( string $type, string $format ): string
    {
        $timestamp = now()->format( 'Ymd_His' );
        return "relatorio_{$type}_{$timestamp}.{$format}";
    }

    private function getMimeType( string $format ): string
    {
        return match ( $format ) {
            'pdf'   => 'application/pdf',
            'excel' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'csv'   => 'text/csv',
            default => 'application/octet-stream'
        };
    }

}
