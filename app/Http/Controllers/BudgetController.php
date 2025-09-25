<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\BudgetChangeStatusFormRequest;
use App\Http\Requests\BudgetChooseStatusFormRequest;
use App\Http\Requests\BudgetFormRequest;
use App\Services\BudgetService;
use App\Services\NotificationService;
use App\Services\PdfService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class BudgetController extends Controller
{
    public function __construct(
        private readonly BudgetService $budgetService,
        private readonly PdfService $pdfService,
        private readonly NotificationService $notificationService,
    ) {}

    public function index( Request $request ): View
    {
        $user       = Auth::user();
        $tenantId   = $user->tenant_id ?? 1;
        $providerId = $user->provider_id ?? $user->id;

        $filters = $request->only( [ 'status', 'customer_name', 'date_from', 'date_to' ] );
        $budgets = $this->budgetService->getBudgetsForProvider( $providerId, $filters );

        return view( 'pages.budget.index', compact( 'budgets', 'filters' ) );
    }

    public function create(): View
    {
        $user       = Auth::user();
        $tenantId   = $user->tenant_id ?? 1;
        $providerId = $user->provider_id ?? $user->id;

        $customers = $this->budgetService->getAllCustomersForBudget( $tenantId, $providerId );

        return view( 'pages.budget.create', compact( 'customers' ) );
    }

    public function store( BudgetFormRequest $request ): RedirectResponse
    {
        $user       = Auth::user();
        $tenantId   = $user->tenant_id ?? 1;
        $providerId = $user->provider_id ?? $user->id;

        $validated = $request->validated();

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            $budget = new \App\Models\Budget();
            $budget->customer_name = $validated['customer_name'];
            $budget->project_description = $validated['project_description'];
            $budget->due_date = $validated['due_date'];
            $budget->total_value = $validated['total_value'];
            $budget->tenant_id = $tenantId;
            $budget->provider_id = $providerId;
            $budget->user_id = $user->id;
            $budget->status = 'draft'; // assuming initial status
            $budget->save();

            // Generate code after save
            $budget->code = 'ORC-' . $tenantId . '-' . str_pad($budget->id, 6, '0', STR_PAD_LEFT);
            $budget->save();

            if ($request->hasFile('budget_file')) {
                $filePath = $request->file('budget_file')->store('budgets', 'public');
                $budget->budget_file = $filePath;
                $budget->save();
            }

            // Processar itens
            foreach ($validated['items'] as $itemData) {
                \App\Models\ItemBudget::create([
                    'budget_id' => $budget->id,
                    'description' => $itemData['description'],
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'total' => $itemData['total'],
                ]);
            }

            \Illuminate\Support\Facades\DB::commit();
            return redirect()->route( 'budgets.show', $budget->code )->with( 'success', 'Orçamento criado com sucesso!' );
        } catch ( \Exception $e ) {
            \Illuminate\Support\Facades\DB::rollback();
            return back()->with( 'error', 'Erro ao criar orçamento: ' . $e->getMessage() )->withInput();
        }
    }

    public function show( string $code ): View
    {
        $user     = Auth::user();
        $tenantId = $user->tenant_id ?? 1;

        $budget = $this->budgetService->getByCodeWithServices( $code, $tenantId );

        if ( !$budget ) {
            abort( 404 );
        }

        return view( 'pages.budget.show', compact( 'budget' ) );
    }

    public function edit( string $code ): View
    {
        $user     = Auth::user();
        $tenantId = $user->tenant_id ?? 1;

        $budget = $this->budgetService->getBudgetUpdateData( $code, $tenantId );

        if ( !$budget ) {
            abort( 404 );
        }

        $services = $budget[ 'services' ] ?? [];

        return view( 'pages.budget.edit', compact( 'budget', 'services' ) );
    }

    public function update( BudgetFormRequest $request, string $code ): RedirectResponse
    {
        $user     = Auth::user();
        $tenantId = $user->tenant_id ?? 1;

        try {
            $budget = $this->budgetService->updateByCode( $code, $request, $tenantId );

            return redirect()->route( 'budgets.show', $code )->with( 'success', 'Orçamento atualizado com sucesso!' );
        } catch ( \Exception $e ) {
            return back()->with( 'error', 'Erro ao atualizar orçamento: ' . $e->getMessage() )->withInput();
        }
    }

    public function changeStatus( BudgetChangeStatusFormRequest $request, string $code ): RedirectResponse
    {
        $user     = Auth::user();
        $tenantId = $user->tenant_id ?? 1;

        try {
            $budget = $this->budgetService->changeStatusByCode( $code, $request->status, $tenantId, $request->validated() );

            $message = 'Status do orçamento alterado com sucesso!';

            if ( $request->status === 'approved' ) {
                $pdfPath = 'budgets/' . $budget->id . '.pdf';
                $this->pdfService->generateBudgetPdf( $budget, $pdfPath );
                $message = 'Orçamento aprovado e PDF gerado!';
            }

            return redirect()->route( 'budgets.show', $code )->with( 'success', $message );
        } catch ( \Exception $e ) {
            return back()->with( 'error', 'Erro ao alterar status: ' . $e->getMessage() );
        }
    }

    public function chooseBudgetStatus( string $code, string $token ): View
    {
        $budget = $this->budgetService->getBudgetForStatusChange( $code, $token );

        if ( !$budget ) {
            abort( 404 );
        }

        $services             = $this->budgetService->getServicesForBudgetStatus( $budget->id, $budget->tenant_id );
        $allServicesCompleted = $this->budgetService->areAllServicesCompleted( $budget->id, $budget->tenant_id );

        return view( 'pages.budget.choose-status', compact( 'budget', 'services', 'allServicesCompleted', 'token' ) );
    }

    public function chooseBudgetStatusStore( BudgetChooseStatusFormRequest $request ): RedirectResponse
    {
        $validated = $request->validated();

        try {
            $success = $this->budgetService->handleCustomerStatusChange( $validated[ 'budget_id' ], $validated[ 'status' ], $validated[ 'token' ], $validated[ 'comments' ] ?? null );

            if ( $success ) {
                return redirect()->route( 'home' )->with( 'success', 'Status do orçamento atualizado com sucesso!' );
            }

            return back()->with( 'error', 'Erro ao atualizar status.' );
        } catch ( \Exception $e ) {
            return back()->with( 'error', 'Erro ao atualizar status: ' . $e->getMessage() );
        }
    }

    public function print( string $code ): \Illuminate\Http\Response
    {
        $user     = Auth::user();
        $tenantId = $user->tenant_id ?? 1;

        $budget = $this->budgetService->getByCode( $code, $tenantId );

        if ( !$budget ) {
            abort( 404 );
        }

        $pdfPath    = 'budgets/' . $budget->id . '.pdf';
        $pdfContent = $this->pdfService->generateBudgetPdf( $budget, $pdfPath );

        $filename = "orcamento_{$code}.pdf";

        return response( $pdfContent )
            ->header( 'Content-Type', 'application/pdf' )
            ->header( 'Content-Disposition', "inline; filename=\"{$filename}\"" );
    }

    public function destroy( string $code ): RedirectResponse
    {
        $user     = Auth::user();
        $tenantId = $user->tenant_id ?? 1;

        try {
            $success = $this->budgetService->deleteByCode( $code, $tenantId );

            if ( $success ) {
                return redirect()->route( 'budgets.index' )->with( 'success', 'Orçamento deletado com sucesso!' );
            }

            return back()->with( 'error', 'Erro ao deletar orçamento.' );
        } catch ( \Exception $e ) {
            return back()->with( 'error', 'Erro ao deletar orçamento: ' . $e->getMessage() );
        }
    }

}
