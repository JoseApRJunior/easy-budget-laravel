<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Abstracts\Controller;
use App\Models\Customer;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Controlador principal para gerenciamento de relatórios
 * Gerencia interface web e operações básicas
 */
class ReportController extends Controller
{
    /**
     * Índice de relatórios
     */
    public function index(): View
    {
        $recent_reports = [];
        return view( 'pages.report.index', compact( 'recent_reports' ) );
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
    public function customersPdf(Request $request)
    {
        try {
            $name = $request->input('name', '');
            $document = $request->input('document', '');
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            
            $query = Customer::with(['commonData', 'contact'])
                ->where('tenant_id', auth()->user()->tenant_id);

            // Aplicar mesmos filtros da busca
            if (!empty($name)) {
                $query->whereHas('commonData', function ($subQ) use ($name) {
                    $subQ->where('first_name', 'like', "%{$name}%")
                         ->orWhere('last_name', 'like', "%{$name}%")
                         ->orWhere('company_name', 'like', "%{$name}%");
                });
            }

            if (!empty($document)) {
                $cleanDocument = clean_document_partial($document, 1);
                if (!empty($cleanDocument)) {
                    $query->whereHas('commonData', function ($subQ) use ($cleanDocument) {
                        $subQ->where('cpf', 'like', "%{$cleanDocument}%")
                             ->orWhere('cnpj', 'like', "%{$cleanDocument}%");
                    });
                }
            }

            if (!empty($startDate)) {
                $query->where('created_at', '>=', $startDate . ' 00:00:00');
            }
            
            if (!empty($endDate)) {
                $query->where('created_at', '<=', $endDate . ' 23:59:59');
            }

            $customers = $query->get();
            
            $filters = [
                'name' => $name,
                'document' => $document,
                'start_date' => $startDate,
                'end_date' => $endDate
            ];

            return view('pages.report.customer.pdf_customer', [
                'customers' => $customers,
                'filters' => $filters
            ]);
            
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Erro ao gerar PDF: ' . $e->getMessage()
            ], 500);
        }
    }

}
