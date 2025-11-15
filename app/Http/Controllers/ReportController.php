<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Abstracts\Controller;
use App\Http\Requests\ReportGenerateRequest;
use App\Models\Customer;
use App\Services\Domain\ReportService;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Controlador principal para gerenciamento de relatórios
 * Gerencia interface web e operações básicas
 */
class ReportController extends Controller
{
    /**
     * Índice de relatórios
     */
    public function index( Request $request ): View
    {
        try {
            $filters = $request->only( [ 'search', 'type', 'status', 'format', 'start_date', 'end_date' ] );
            $result  = app( ReportService::class)->getFilteredReports( $filters, [ 'user' ] );
            if ( !$result->isSuccess() ) abort( 500, 'Erro ao carregar relatórios' );

            $stats         = app( ReportService::class)->getReportStats();
            $recentReports = app( ReportService::class)->getRecentReports( 10 );

            return view( 'pages.report.index', [
                'reports'        => $result->getData(),
                'recent_reports' => $recentReports->getData(),
                'filters'        => $filters,
                'stats'          => $stats->getData(),
            ] );
        } catch ( Exception $e ) {
            Log::error( 'Erro ao carregar página de relatórios', [ 'error' => $e->getMessage() ] );
            abort( 500, 'Erro interno do servidor' );
        }
    }

    /**
     * Formulário de geração de relatório
     */
    public function create(): View
    {
        return view( 'reports.create', [
            'types'   => [ 'budget' => 'Orçamentos', 'customer' => 'Clientes', 'product' => 'Produtos', 'service' => 'Serviços' ],
            'formats' => [ 'pdf' => 'PDF', 'excel' => 'Excel', 'csv' => 'CSV' ],
        ] );
    }

    /**
     * Solicitar geração de relatório
     */
    public function store( ReportGenerateRequest $request )
    {
        try {
            $result = app( ReportService::class)->generateReport( $request->validated() );
            if ( $result->isSuccess() ) {
                return redirect()->route( 'reports.index' )->with( 'success', $result->getMessage() );
            } else {
                return redirect()->back()->with( 'error', $result->getMessage() )->withInput();
            }
        } catch ( Exception $e ) {
            Log::error( 'Erro ao solicitar relatório', [ 'error' => $e->getMessage(), 'data' => $request->all() ] );
            return redirect()->back()->with( 'error', 'Erro interno do servidor' )->withInput();
        }
    }

    /**
     * Fazer download do relatório
     */
    public function download( string $hash ): BinaryFileResponse
    {
        try {
            $result = app( ReportService::class)->downloadReport( $hash );
            if ( !$result->isSuccess() ) abort( 404, $result->getMessage() );

            $data     = $result->getData();
            $filePath = Storage::disk( 'reports' )->path( $data[ 'file_path' ] );

            return response()->download( $filePath, $data[ 'file_name' ], [
                'Content-Type' => $data[ 'mime_type' ]
            ] );
        } catch ( Exception $e ) {
            Log::error( 'Erro ao fazer download do relatório', [ 'hash' => $hash, 'error' => $e->getMessage() ] );
            abort( 500, 'Erro interno do servidor' );
        }
    }

    /**
     * Relatório financeiro
     */
    public function financial(): View
    {
        return view( 'pages.report.financial.financial' );
    }

    /**
     * Relatório de clientes
     */
    public function customers(): View
    {
        return view( 'pages.report.customer.customer' );
    }

    /**
     * Relatório de produtos
     */
    public function products(): View
    {
        return view( 'pages.report.product.product' );
    }

    /**
     * Relatório de orçamentos
     */
    public function budgets(): View
    {
        return view( 'pages.report.budget.budget' );
    }

    /**
     * Gerar PDF de orçamentos (LEGACY)
     */
    public function budgetsPdf( Request $request ): Response
    {
        try {
            $filters = $request->only( [ 'code', 'start_date', 'end_date', 'customer_name', 'total', 'status' ] );

            $budgetService = app( \App\Services\Domain\BudgetService::class);
            $result        = $budgetService->getFilteredBudgets( $filters );
            if ( !$result->isSuccess() ) abort( 500, 'Erro ao buscar dados' );

            $budgets = $result->getData();
            $totals  = $this->calculateTotals( $budgets );

            $html     = view( 'reports.budgets_pdf', compact( 'budgets', 'totals', 'filters' ) )->render();
            $filename = $this->generateFileName( 'orcamentos', 'pdf', count( $budgets ) );

            $pdfService = app( \App\Services\Infrastructure\ReportPdfService::class);
            $pdfResult  = $pdfService->generateFromHtml( $html, $filename );

            if ( !$pdfResult->isSuccess() ) abort( 500, 'Erro ao gerar PDF' );

            $this->service->create( [
                'type'         => 'budget',
                'format'       => 'pdf',
                'status'       => 'completed',
                'file_name'    => $filename,
                'size'         => $pdfResult->getData()[ 'size' ],
                'filters'      => $filters,
                'generated_at' => now(),
            ] );

            return response( $pdfResult->getData()[ 'content' ], 200, [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $filename . '"'
            ] );
        } catch ( Exception $e ) {
            Log::error( 'Erro ao gerar PDF de orçamentos', [ 'error' => $e->getMessage() ] );
            abort( 500, 'Erro interno' );
        }
    }

    private function calculateTotals( Collection $budgets ): array
    {
        return [
            'count' => $budgets->count(),
            'sum'   => $budgets->sum( 'total' ),
            'avg'   => $budgets->avg( 'total' ) ?? 0,
        ];
    }

    private function generateFileName( string $type, string $format, int $count ): string
    {
        $timestamp      = now()->format( 'Ymd_His' );
        $countFormatted = str_pad( $count, 3, '0', STR_PAD_LEFT );
        return "relatorio_{$type}_{$timestamp}_{$countFormatted}_registros.{$format}";
    }

    /**
     * Exportação PDF de orçamentos
     */
    public function budgets_pdf()
    {
        // Implementar lógica baseada no sistema legado
        return response()->json( [ 'message' => 'PDF será implementado' ] );
    }

    /**
     * Exportação Excel de orçamentos
     */
    public function budgets_excel()
    {
        // Implementar lógica baseada no sistema legado
        return response()->json( [ 'message' => 'Excel será implementado' ] );
    }

    /**
     * Relatório de serviços
     */
    public function services(): View
    {
        return view( 'pages.report.service.service' );
    }

    /**
     * Busca dados para relatório de clientes
     */
    public function customersSearch( Request $request ): JsonResponse
    {
        try {
            $name      = $request->input( 'name', '' );
            $document  = $request->input( 'document', '' );
            $startDate = $request->input( 'start_date' );
            $endDate   = $request->input( 'end_date' );

            Log::info( 'Filtros recebidos:', [
                'name'       => $name,
                'document'   => $document,
                'start_date' => $startDate,
                'end_date'   => $endDate
            ] );

            $query = Customer::with( [ 'commonData', 'contact' ] )
                ->where( 'tenant_id', auth()->user()->tenant_id );

            // Aplicar filtros separadamente com AND
            if ( !empty( $name ) ) {
                $query->whereHas( 'commonData', function ( $subQ ) use ( $name ) {
                    $subQ->where( 'first_name', 'like', "%{$name}%" )
                        ->orWhere( 'last_name', 'like', "%{$name}%" )
                        ->orWhere( 'company_name', 'like', "%{$name}%" );
                } );
            }

            if ( !empty( $document ) ) {
                $cleanDocument = clean_document_partial( $document, 1 );
                Log::info( 'Documento limpo:', [ 'original' => $document, 'clean' => $cleanDocument ] );

                if ( !empty( $cleanDocument ) ) {
                    $query->whereHas( 'commonData', function ( $subQ ) use ( $cleanDocument ) {
                        $subQ->where( 'cpf', 'like', "%{$cleanDocument}%" )
                            ->orWhere( 'cnpj', 'like', "%{$cleanDocument}%" );
                    } );
                }
            }

            // Filtro por data de cadastro
            if ( !empty( $startDate ) ) {
                $query->where( 'created_at', '>=', $startDate . ' 00:00:00' );
            }

            if ( !empty( $endDate ) ) {
                $query->where( 'created_at', '<=', $endDate . ' 23:59:59' );
            }

            $customers = $query->get();

            Log::info( 'Resultados encontrados:', [ 'count' => $customers->count() ] );

            $result = $customers->map( function ( $customer ) {
                $commonData = $customer->commonData;
                $contact = $customer->contact;

                return [
                    'id'             => $customer->id,
                    'customer_name'  => $commonData ?
                        ( $commonData->company_name ?: ( $commonData->first_name . ' ' . $commonData->last_name ) ) :
                        'Nome não informado',
                    'cpf'            => $commonData?->cpf ?? '',
                    'cnpj'           => $commonData?->cnpj ?? '',
                    'email'          => $contact?->email_personal ?? '',
                    'email_business' => $contact?->email_business ?? '',
                    'phone'          => $contact?->phone_personal ?? '',
                    'phone_business' => $contact?->phone_business ?? '',
                    'created_at'     => $customer->created_at->toISOString(),
                ];
            } );

            return response()->json( $result );

        } catch ( Exception $e ) {
            return response()->json( [
                'error' => 'Erro interno: ' . $e->getMessage()
            ], 500 );
        }
    }

    /**
     * Exportação PDF de clientes
     */
    public function customersPdf( Request $request )
    {
        try {
            $name      = $request->input( 'name', '' );
            $document  = $request->input( 'document', '' );
            $startDate = $request->input( 'start_date' );
            $endDate   = $request->input( 'end_date' );

            $query = Customer::with( [ 'commonData', 'contact' ] )
                ->where( 'tenant_id', auth()->user()->tenant_id );

            // Aplicar mesmos filtros da busca
            if ( !empty( $name ) ) {
                $query->whereHas( 'commonData', function ( $subQ ) use ( $name ) {
                    $subQ->where( 'first_name', 'like', "%{$name}%" )
                        ->orWhere( 'last_name', 'like', "%{$name}%" )
                        ->orWhere( 'company_name', 'like', "%{$name}%" );
                } );
            }

            if ( !empty( $document ) ) {
                $cleanDocument = clean_document_partial( $document, 1 );
                if ( !empty( $cleanDocument ) ) {
                    $query->whereHas( 'commonData', function ( $subQ ) use ( $cleanDocument ) {
                        $subQ->where( 'cpf', 'like', "%{$cleanDocument}%" )
                            ->orWhere( 'cnpj', 'like', "%{$cleanDocument}%" );
                    } );
                }
            }

            if ( !empty( $startDate ) ) {
                $query->where( 'created_at', '>=', $startDate . ' 00:00:00' );
            }

            if ( !empty( $endDate ) ) {
                $query->where( 'created_at', '<=', $endDate . ' 23:59:59' );
            }

            $customers = $query->get();

            $filters = [
                'name'       => $name,
                'document'   => $document,
                'start_date' => $startDate,
                'end_date'   => $endDate
            ];

            return view( 'pages.report.customer.pdf_customer', [
                'customers' => $customers,
                'filters'   => $filters
            ] );

        } catch ( Exception $e ) {
            return response()->json( [
                'error' => 'Erro ao gerar PDF: ' . $e->getMessage()
            ], 500 );
        }
    }

}
