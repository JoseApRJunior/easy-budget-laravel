<?php

namespace App\Services\Application;

use App\Exports\ReportExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Serviço avançado de exportação multi-formato
 * Suporta PDF, Excel, CSV, JSON com configurações avançadas
 */
class ExportService
{
    private array $defaultOptions = [
        'orientation'      => 'portrait',
        'page_size'        => 'a4',
        'margin_top'       => 20,
        'margin_right'     => 15,
        'margin_bottom'    => 20,
        'margin_left'      => 15,
        'encoding'         => 'UTF-8',
        'font_size'        => 10,
        'header_font_size' => 12,
        'title_font_size'  => 16
    ];

    /**
     * Exporta dados para PDF
     */
    public function exportToPdf( Collection $data, array $config = [] ): array
    {
        try {
            $config = array_merge( $this->defaultOptions, $config );

            // Preparar dados para a view
            $viewData = [
                'data'         => $data,
                'config'       => $config,
                'generated_at' => now(),
                'company'      => $this->getCompanyInfo(),
                'summary'      => $this->calculateSummary( $data, $config ),
                'columns'      => $this->extractColumns( $data )
            ];

            // Gerar HTML
            $html = view( 'reports.pdf.layout', $viewData )->render();

            // Configurar opções do PDF
            $pdfOptions = [
                'orientation'      => $config[ 'orientation' ],
                'page-size'        => $config[ 'page_size' ],
                'margin-top'       => $config[ 'margin_top' ],
                'margin-right'     => $config[ 'margin_right' ],
                'margin-bottom'    => $config[ 'margin_bottom' ],
                'margin-left'      => $config[ 'margin_left' ],
                'encoding'         => $config[ 'encoding' ],
                'footer-right'     => 'Página [page] de [topage]',
                'footer-font-size' => 8,
                'header-html'      => $this->generateHeaderHtml( $config ),
                'footer-html'      => $this->generateFooterHtml( $config )
            ];

            // Gerar nome do arquivo
            $filename = $this->generateFilename( $config, 'pdf' );
            $path     = "reports/exports/pdf/{$filename}";

            // Criar PDF
            $pdf = Pdf::loadHTML( $html )
                ->setOptions( $pdfOptions )
                ->setPaper( $config[ 'page_size' ], $config[ 'orientation' ] );

            // Adicionar paginação customizada se necessário
            if ( isset( $config[ 'watermark' ] ) ) {
                $pdf->setOption( 'watermark', $config[ 'watermark' ] );
            }

            // Salvar arquivo
            $fullPath = storage_path( "app/public/{$path}" );
            $pdf->save( $fullPath );

            // Obter tamanho do arquivo
            $fileSize = filesize( $fullPath );

            return [
                'success'        => true,
                'format'         => 'pdf',
                'filename'       => $filename,
                'path'           => $path,
                'full_path'      => $fullPath,
                'size'           => $fileSize,
                'size_formatted' => $this->formatFileSize( $fileSize ),
                'url'            => asset( "storage/{$path}" ),
                'download_url'   => route( 'reports.download', [ 'hash' => $this->generateFileHash( $fullPath ) ] )
            ];

        } catch ( Exception $e ) {
            return [
                'success' => false,
                'error'   => $e->getMessage(),
                'format'  => 'pdf'
            ];
        }
    }

    /**
     * Exporta dados para Excel
     */
    public function exportToExcel( Collection $data, array $config = [] ): array
    {
        try {
            $config = array_merge( $this->defaultOptions, $config );

            // Preparar dados para exportação
            $exportData = [
                'data'    => $data,
                'config'  => $config,
                'summary' => $this->calculateSummary( $data, $config ),
                'columns' => $this->extractColumns( $data )
            ];

            // Gerar nome do arquivo
            $filename = $this->generateFilename( $config, 'xlsx' );
            $path     = "reports/exports/excel/{$filename}";

            // Criar e salvar arquivo Excel
            Excel::store(
                new ReportExport( $exportData ),
                $path,
                'public',
                \Maatwebsite\Excel\Excel::XLSX,
            );

            // Obter informações do arquivo
            $fullPath = storage_path( "app/public/{$path}" );
            $fileSize = filesize( $fullPath );

            return [
                'success'        => true,
                'format'         => 'excel',
                'filename'       => $filename,
                'path'           => $path,
                'full_path'      => $fullPath,
                'size'           => $fileSize,
                'size_formatted' => $this->formatFileSize( $fileSize ),
                'url'            => asset( "storage/{$path}" ),
                'download_url'   => route( 'reports.download', [ 'hash' => $this->generateFileHash( $fullPath ) ] )
            ];

        } catch ( Exception $e ) {
            return [
                'success' => false,
                'error'   => $e->getMessage(),
                'format'  => 'excel'
            ];
        }
    }

    /**
     * Exporta dados para CSV
     */
    public function exportToCsv( Collection $data, array $config = [] ): array
    {
        try {
            $config = array_merge( $this->defaultOptions, $config );

            // Gerar nome do arquivo
            $filename = $this->generateFilename( $config, 'csv' );
            $path     = "reports/exports/csv/{$filename}";
            $fullPath = storage_path( "app/public/{$path}" );

            // Abrir arquivo para escrita
            $handle = fopen( $fullPath, 'w' );

            if ( !$handle ) {
                throw new Exception( 'Não foi possível criar o arquivo CSV' );
            }

            // Configurar encoding
            fwrite( $handle, "\xEF\xBB\xBF" ); // BOM para UTF-8

            // Escrever cabeçalhos
            $columns = $this->extractColumns( $data );
            fputcsv( $handle, $columns, $config[ 'delimiter' ] ?? ',', $config[ 'enclosure' ] ?? '"' );

            // Escrever dados
            foreach ( $data as $row ) {
                $csvRow = [];

                foreach ( $columns as $column ) {
                    $value = data_get( $row, $column );

                    // Formatar valor conforme tipo
                    if ( isset( $config[ 'formatters' ][ $column ] ) ) {
                        $value = $this->formatValue( $value, $config[ 'formatters' ][ $column ] );
                    }

                    $csvRow[] = $value;
                }

                fputcsv( $handle, $csvRow, $config[ 'delimiter' ] ?? ',', $config[ 'enclosure' ] ?? '"' );
            }

            fclose( $handle );

            // Obter informações do arquivo
            $fileSize = filesize( $fullPath );

            return [
                'success'        => true,
                'format'         => 'csv',
                'filename'       => $filename,
                'path'           => $path,
                'full_path'      => $fullPath,
                'size'           => $fileSize,
                'size_formatted' => $this->formatFileSize( $fileSize ),
                'url'            => asset( "storage/{$path}" ),
                'download_url'   => route( 'reports.download', [ 'hash' => $this->generateFileHash( $fullPath ) ] )
            ];

        } catch ( Exception $e ) {
            return [
                'success' => false,
                'error'   => $e->getMessage(),
                'format'  => 'csv'
            ];
        }
    }

    /**
     * Exporta dados para JSON
     */
    public function exportToJson( Collection $data, array $config = [] ): array
    {
        try {
            $config = array_merge( $this->defaultOptions, $config );

            // Preparar estrutura JSON
            $jsonData = [
                'metadata' => [
                    'exported_at'   => now()->toISOString(),
                    'exported_by'   => auth()->user()->name ?? 'Sistema',
                    'tenant_id'     => auth()->user()->tenant_id ?? null,
                    'total_records' => $data->count(),
                    'config'        => $config
                ],
                'summary'  => $this->calculateSummary( $data, $config ),
                'data'     => $data->toArray()
            ];

            // Adicionar informações da empresa se disponível
            if ( $company = $this->getCompanyInfo() ) {
                $jsonData[ 'metadata' ][ 'company' ] = $company;
            }

            // Gerar nome do arquivo
            $filename = $this->generateFilename( $config, 'json' );
            $path     = "reports/exports/json/{$filename}";
            $fullPath = storage_path( "app/public/{$path}" );

            // Salvar arquivo JSON
            $jsonString = json_encode( $jsonData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
            file_put_contents( $fullPath, $jsonString );

            // Obter informações do arquivo
            $fileSize = filesize( $fullPath );

            return [
                'success'        => true,
                'format'         => 'json',
                'filename'       => $filename,
                'path'           => $path,
                'full_path'      => $fullPath,
                'size'           => $fileSize,
                'size_formatted' => $this->formatFileSize( $fileSize ),
                'url'            => asset( "storage/{$path}" ),
                'download_url'   => route( 'reports.download', [ 'hash' => $this->generateFileHash( $fullPath ) ] )
            ];

        } catch ( Exception $e ) {
            return [
                'success' => false,
                'error'   => $e->getMessage(),
                'format'  => 'json'
            ];
        }
    }

    /**
     * Exporta relatório completo com múltiplos formatos
     */
    public function exportMultipleFormats( Collection $data, array $formats, array $config = [] ): array
    {
        $results = [];
        $errors  = [];

        foreach ( $formats as $format ) {
            try {
                $result = $this->export( $data, $format, $config );

                if ( $result[ 'success' ] ) {
                    $results[ $format ] = $result;
                } else {
                    $errors[ $format ] = $result[ 'error' ];
                }
            } catch ( Exception $e ) {
                $errors[ $format ] = $e->getMessage();
            }
        }

        return [
            'success'       => count( $results ) > 0,
            'results'       => $results,
            'errors'        => $errors,
            'total_success' => count( $results ),
            'total_errors'  => count( $errors )
        ];
    }

    /**
     * Método genérico de exportação
     */
    public function export( Collection $data, string $format, array $config = [] ): array
    {
        return match ( strtolower( $format ) ) {
            'pdf'           => $this->exportToPdf( $data, $config ),
            'excel', 'xlsx' => $this->exportToExcel( $data, $config ),
            'csv'           => $this->exportToCsv( $data, $config ),
            'json'          => $this->exportToJson( $data, $config ),
            default         => [
                'success'         => false,
                'error'           => "Formato '{$format}' não suportado",
                'format'          => $format
            ]
        };
    }

    /**
     * Gera nome do arquivo baseado na configuração
     */
    private function generateFilename( array $config, string $extension ): string
    {
        $timestamp  = now()->format( 'Y-m-d_H-i-s' );
        $reportName = $config[ 'title' ] ?? 'report';
        $safeName   = preg_replace( '/[^a-zA-Z0-9_-]/', '_', $reportName );

        return "{$safeName}_{$timestamp}.{$extension}";
    }

    /**
     * Extrai colunas dos dados
     */
    private function extractColumns( Collection $data ): array
    {
        if ( $data->isEmpty() ) {
            return [];
        }

        $firstRow = $data->first();
        return is_array( $firstRow ) ? array_keys( $firstRow ) : array_keys( (array) $firstRow );
    }

    /**
     * Calcula resumo dos dados
     */
    private function calculateSummary( Collection $data, array $config ): array
    {
        $summary = [
            'total_records' => $data->count(),
            'generated_at'  => now()->toISOString()
        ];

        // Calcular totais se houver campos numéricos
        $numericColumns = $config[ 'numeric_columns' ] ?? [];

        foreach ( $numericColumns as $column ) {
            $values = $data->pluck( $column )->filter()->map( function ( $value ) {
                return is_numeric( $value ) ? (float) $value : 0;
            } );

            if ( $values->isNotEmpty() ) {
                $summary[ "total_{$column}" ]   = $values->sum();
                $summary[ "average_{$column}" ] = $values->avg();
                $summary[ "min_{$column}" ]     = $values->min();
                $summary[ "max_{$column}" ]     = $values->max();
            }
        }

        return $summary;
    }

    /**
     * Obtém informações da empresa
     */
    private function getCompanyInfo(): ?array
    {
        try {
            // Tentar obter informações da empresa do sistema
            $systemSettings = \App\Models\SystemSettings::where( 'tenant_id', auth()->user()->tenant_id ?? null )->first();

            if ( $systemSettings && isset( $systemSettings->settings[ 'company' ] ) ) {
                return $systemSettings->settings[ 'company' ];
            }

            // Retornar informações padrão
            return [
                'name'        => config( 'app.name', 'Easy Budget Laravel' ),
                'version'     => '1.0.0',
                'exported_at' => now()->toISOString()
            ];
        } catch ( Exception $e ) {
            return null;
        }
    }

    /**
     * Gera HTML do cabeçalho para PDF
     */
    private function generateHeaderHtml( array $config ): string
    {
        $title = $config[ 'title' ] ?? 'Relatório';

        return view( 'reports.pdf.partials.header', [
            'title'  => $title,
            'config' => $config
        ] )->render();
    }

    /**
     * Gera HTML do rodapé para PDF
     */
    private function generateFooterHtml( array $config ): string
    {
        return view( 'reports.pdf.partials.footer', [
            'config'       => $config,
            'generated_at' => now()
        ] )->render();
    }

    /**
     * Formata tamanho do arquivo
     */
    private function formatFileSize( int $bytes ): string
    {
        $units     = [ 'B', 'KB', 'MB', 'GB' ];
        $unitIndex = 0;

        while ( $bytes >= 1024 && $unitIndex < count( $units ) - 1 ) {
            $bytes /= 1024;
            $unitIndex++;
        }

        return round( $bytes, 2 ) . ' ' . $units[ $unitIndex ];
    }

    /**
     * Gera hash do arquivo para download seguro
     */
    private function generateFileHash( string $filePath ): string
    {
        return hash( 'sha256', file_get_contents( $filePath ) . time() );
    }

    /**
     * Formata valor conforme tipo especificado
     */
    private function formatValue( $value, array $formatter ): string
    {
        $type = $formatter[ 'type' ] ?? 'string';

        return match ( $type ) {
            'currency'   => 'R$ ' . number_format( (float) $value, 2, ',', '.' ),
            'number'     => number_format( (float) $value, $formatter[ 'decimals' ] ?? 2, ',', '.' ),
            'percentage' => number_format( (float) $value, 2 ) . '%',
            'date'       => Carbon::parse( $value )->format( $formatter[ 'format' ] ?? 'd/m/Y' ),
            'datetime'   => Carbon::parse( $value )->format( $formatter[ 'format' ] ?? 'd/m/Y H:i:s' ),
            default      => (string) $value
        };
    }

    /**
     * Obtém formatos suportados
     */
    public function getSupportedFormats(): array
    {
        return [
            'pdf'   => [
                'name'        => 'PDF',
                'mime_type'   => 'application/pdf',
                'extension'   => 'pdf',
                'description' => 'Documento PDF formatado'
            ],
            'excel' => [
                'name'        => 'Excel',
                'mime_type'   => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'extension'   => 'xlsx',
                'description' => 'Planilha Excel'
            ],
            'csv'   => [
                'name'        => 'CSV',
                'mime_type'   => 'text/csv',
                'extension'   => 'csv',
                'description' => 'Arquivo CSV para importação'
            ],
            'json'  => [
                'name'        => 'JSON',
                'mime_type'   => 'application/json',
                'extension'   => 'json',
                'description' => 'Dados estruturados JSON'
            ]
        ];
    }

    /**
     * Valida configuração de exportação
     */
    public function validateExportConfig( array $config, string $format ): array
    {
        $errors           = [];
        $supportedFormats = array_keys( $this->getSupportedFormats() );

        if ( !in_array( $format, $supportedFormats ) ) {
            $errors[] = "Formato '{$format}' não é suportado. Formatos disponíveis: " . implode( ', ', $supportedFormats );
        }

        // Validações específicas por formato
        switch ( $format ) {
            case 'pdf':
                if ( isset( $config[ 'orientation' ] ) && !in_array( $config[ 'orientation' ], [ 'portrait', 'landscape' ] ) ) {
                    $errors[] = 'Orientação deve ser "portrait" ou "landscape"';
                }
                if ( isset( $config[ 'page_size' ] ) && !in_array( $config[ 'page_size' ], [ 'a4', 'a3', 'letter', 'legal' ] ) ) {
                    $errors[] = 'Tamanho da página deve ser "a4", "a3", "letter" ou "legal"';
                }
                break;

            case 'csv':
                if ( isset( $config[ 'delimiter' ] ) && strlen( $config[ 'delimiter' ] ) !== 1 ) {
                    $errors[] = 'Delimitador deve ser um único caractere';
                }
                break;
        }

        return $errors;
    }

    /**
     * Cria arquivo temporário para download
     */
    public function createTemporaryFile( string $content, string $filename, string $format ): string
    {
        $tempPath = sys_get_temp_dir() . '/' . $filename;
        file_put_contents( $tempPath, $content );

        return $tempPath;
    }

    /**
     * Remove arquivo após download
     */
    public function cleanupAfterDownload( string $filePath ): bool
    {
        if ( file_exists( $filePath ) ) {
            return unlink( $filePath );
        }

        return false;
    }

}
