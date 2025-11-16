<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\BudgetStatus;
use App\Http\Controllers\Abstracts\Controller;
use App\Http\Requests\BudgetStoreRequest;
use App\Http\Requests\BudgetUpdateRequest;
use App\Models\Budget;
use App\Models\BudgetItemCategory;
use App\Models\BudgetTemplate;
use App\Models\UserConfirmationToken;
use App\Services\BudgetTemplateService;
use App\Services\Domain\BudgetService;
use App\Services\Domain\BudgetTokenService;
use App\Services\Infrastructure\BudgetPdfService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BudgetController extends Controller
{
    public function __construct(
        private BudgetService $budgetService,
        private BudgetPdfService $budgetPdfService,
        private BudgetTokenService $budgetTokenService,
    ) {}

    public function index( Request $request ): View
    {
        $user    = Auth::user();
        $budgets = $this->budgetService->getBudgetsForProvider( $user->id, $request->all() );
        return view( 'pages.budget.index', [
            'budgets' => $budgets
        ] );
    }

    public function create( Request $request ): View
    {
        // Buscar clientes ativos para o autocomplete
        $tenantId  = Auth::user()->tenant_id;
        $customers = app( \App\Services\Domain\CustomerService::class)->listCustomers( $tenantId, [ 'status' => 'active' ] );

        // Cliente pré-selecionado via URL (?customer_id=123)
        $selectedCustomer = null;
        if ( $request->has( 'customer_id' ) ) {
            $customerService = app( \App\Services\Domain\CustomerService::class);
            $result          = $customerService->findById( (int) $request->customer_id );
            if ( $result->isSuccess() ) {
                $selectedCustomer = $result->getData();
            }
        }

        return view( 'pages.budget.create', [
            'customers'        => $customers->isSuccess() ? $customers->getData() : collect(),
            'selectedCustomer' => $selectedCustomer
        ] );
    }

    public function store( BudgetStoreRequest $request ): RedirectResponse
    {
        try {
            $result = $this->budgetService->create( $request->validated() );

            if ( !$result->isSuccess() ) {
                return redirect()->back()
                    ->withInput()
                    ->with( 'error', $result->getMessage() );
            }

            return redirect()->route( 'provider.budgets.show', $result->getData()->code )
                ->with( 'success', 'Orçamento criado com sucesso!' );

        } catch ( Exception $e ) {
            return redirect()->back()
                ->withInput()
                ->with( 'error', 'Erro ao criar orçamento: ' . $e->getMessage() );
        }
    }

    public function update( string $code, BudgetUpdateRequest $request ): RedirectResponse
    {
        try {
            $result = $this->budgetService->updateByCode( $code, $request->validated() );

            if ( !$result->isSuccess() ) {
                return redirect()->back()
                    ->withInput()
                    ->with( 'error', $result->getMessage() );
            }

            return redirect()->route( 'provider.budgets.show', $code )
                ->with( 'success', 'Orçamento atualizado com sucesso!' );

        } catch ( Exception $e ) {
            return redirect()->back()
                ->withInput()
                ->with( 'error', 'Erro ao atualizar orçamento: ' . $e->getMessage() );
        }
    }

    public function show( string $code ): View
    {
        try {
            $result = $this->budgetService->findByCode( $code, [
                'customer.commonData',
                'customer.contact'
            ] );

            if ( !$result->isSuccess() ) {
                abort( 404, 'Orçamento não encontrado' );
            }

            return view( 'pages.budget.show', [
                'budget' => $result->getData()
            ] );

        } catch ( Exception $e ) {
            abort( 500, 'Erro ao carregar orçamento' );
        }
    }

    public function edit( string $code ): View
    {
        try {
            $result = $this->budgetService->findByCode( $code, [
                'customer:id,name',
                'items:id,budget_id,description,quantity,unit_price,total_price'
            ] );

            if ( !$result->isSuccess() ) {
                abort( 404, 'Orçamento não encontrado' );
            }

            $budget = $result->getData();

            // Verificar se pode editar
            if ( !$budget->status->canEdit() ) {
                abort( 403, 'Orçamento não pode ser editado no status atual' );
            }

            $tenantId  = Auth::user()->tenant_id;
            $customers = app( \App\Services\Domain\CustomerService::class)->listCustomers( $tenantId, [ 'status' => 'active' ] );

            return view( 'budgets.edit', [
                'budget'    => $budget,
                'customers' => $customers->getData()
            ] );

        } catch ( Exception $e ) {
            abort( 500, 'Erro ao carregar formulário de edição' );
        }
    }

    public function update_store( string $code, BudgetUpdateRequest $request ): RedirectResponse
    {
        try {
            $result = $this->budgetService->updateByCode( $code, $request->validated() );

            if ( !$result->isSuccess() ) {
                return redirect()->back()
                    ->withInput()
                    ->with( 'error', $result->getMessage() );
            }

            return redirect()->route( 'budgets.show', $code )
                ->with( 'success', 'Orçamento atualizado com sucesso!' );

        } catch ( Exception $e ) {
            return redirect()->back()
                ->withInput()
                ->with( 'error', 'Erro ao atualizar orçamento: ' . $e->getMessage() );
        }
    }

    /**
     * Display the budget status selection page for public access.
     */
    public function chooseBudgetStatus( string $code, string $token ): View|RedirectResponse
    {
        try {
            $validation = $this->budgetTokenService->validatePublicToken( $token, $code );

            if ( !$validation->isSuccess() ) {
                $errorMessage = $validation->getMessage();

                // Check if token is expired
                if ( str_contains( $errorMessage, 'expirado' ) || str_contains( $errorMessage, 'expired' ) ) {
                    // Find the budget to regenerate token
                    $budget = Budget::where( 'code', $code )->first();

                    if ( $budget ) {
                        // Regenerate token automatically
                        $newTokenResult = $this->budgetTokenService->regenerateToken( $budget );

                        if ( $newTokenResult->isSuccess() ) {
                            $newToken = $newTokenResult->getData()[ 'token' ];

                            // TODO: Send new token via email
                            // $this->emailService->sendBudgetToken($budget, $newToken);

                            return redirect()->back()
                                ->with( 'info', 'Token expirado. Um novo token foi enviado por email.' );
                        }
                    }
                }

                return redirect()->back()
                    ->with( 'error', 'Token inválido ou expirado.' );
            }

            $budget = $validation->getData()[ 'budget' ];

            // Get available status options (only approved and rejected for public)
            $availableStatuses = collect( [ BudgetStatus::APPROVED, BudgetStatus::REJECTED ] )
                ->sortBy( fn( $status ) => $status->label() )
                ->values();

            return view( 'budgets.public.choose-status', [
                'budget'            => $budget,
                'availableStatuses' => $availableStatuses,
                'token'             => $token
            ] );

        } catch ( Exception $e ) {
            return redirect()->back()
                ->with( 'error', 'Erro ao validar token.' );
        }
    }

    /**
     * Process the budget status selection for public access.
     */
    public function chooseBudgetStatusStore( Request $request ): RedirectResponse
    {
        try {
            $request->validate( [
                'budget_code'      => 'required|string',
                'token'            => 'required|string|size:43', // base64url format: 32 bytes = 43 caracteres
                'budget_status_id' => [ 'required', 'string', 'in:' . implode( ',', array_map( fn( $status ) => $status->value, BudgetStatus::cases() ) ) ]
            ] );

            // Find the budget by code and token
            $budget = Budget::where( 'code', $request->budget_code )
                ->whereHas( 'userConfirmationToken', function ( $query ) use ( $request ) {
                    $query->where( 'token', $request->token )
                        ->where( 'expires_at', '>', now() );
                } )
                ->with( [ 'customer', 'budgetStatus', 'userConfirmationToken' ] )
                ->first();

            if ( !$budget ) {
                Log::warning( 'Budget not found or token expired in store', [
                    'code'  => $request->budget_code,
                    'token' => $request->token,
                    'ip'    => request()->ip()
                ] );
                return redirect()->route( 'error.not-found' );
            }

            // Validate that the selected status is allowed (only approved and rejected for public)
            $allowedStatusValues = [ BudgetStatus::APPROVED->value, BudgetStatus::REJECTED->value ];
            if ( !in_array( $request->budget_status_id, $allowedStatusValues ) ) {
                Log::warning( 'Invalid status selected', [
                    'budget_code' => $request->budget_code,
                    'status_id'   => $request->budget_status_id,
                    'ip'          => request()->ip()
                ] );
                return redirect()->back()->with( 'error', 'Status inválido selecionado.' );
            }

            // Update budget status
            $newStatusEnum = BudgetStatus::from( $request->budget_status_id );
            $budget->update( [
                'status'  => $request->budget_status_id,
                'history' => $budget->history . "\n\n" . now()->format( 'd/m/Y H:i:s' ) . ' - Status alterado para: ' . $newStatusEnum->label() . ' (via link público)'
            ] );

            // Log the action
            $oldStatusEnum = $budget->status; // Use the enum directly from the model
            Log::info( 'Budget status updated via public link', [
                'budget_id'   => $budget->id,
                'budget_code' => $budget->code,
                'old_status'  => $oldStatusEnum->label(),
                'new_status'  => $newStatusEnum->label(),
                'ip'          => request()->ip()
            ] );

            return redirect()->route( 'budgets.public.choose-status', [
                'code'  => $budget->code,
                'token' => $request->token
            ] )->with( 'success', 'Status do orçamento atualizado com sucesso!' );

        } catch ( \Exception $e ) {
            Log::error( 'Error in chooseBudgetStatusStore', [
                'error'   => $e->getMessage(),
                'request' => $request->all(),
                'ip'      => request()->ip()
            ] );
            return redirect()->route( 'error.internal' );
        }
    }

    public function print( string $code ): Response
    {
        try {
            $result = $this->budgetService->findByCode( $code, [
                'customer:id,name,email,phone',
                'items:id,budget_id,description,quantity,unit_price,total_price'
            ] );

            if ( !$result->isSuccess() ) {
                abort( 404, 'Orçamento não encontrado' );
            }

            $budget = $result->getData();

            $pdfPath = $this->budgetPdfService->generatePdf( $budget );
            $hash    = $this->budgetPdfService->generateHash( $pdfPath );
            $budget->update( [ 'pdf_verification_hash' => $hash ] );

            $verificationUrl = route( 'documents.verify', [ 'hash' => $hash ] );
            $qrService       = app( \App\Services\Infrastructure\QrCodeService::class);
            $qrDataUri       = $qrService->generateDataUri( $verificationUrl, 180 );

            $pdfPath    = $this->budgetPdfService->generatePdf( $budget->fresh(), [
                'verificationUrl' => $verificationUrl,
                'qrDataUri'       => $qrDataUri,
            ] );
            $pdfContent = Storage::get( $pdfPath );

            return response( $pdfContent, 200, [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => "inline; filename=\"orcamento_{$budget->code}.pdf\"",
                'Cache-Control'       => 'public, max-age=86400'
            ] );

        } catch ( Exception $e ) {
            abort( 500, 'Erro ao gerar PDF' );
        }
    }

    public function change_status( string $code, Request $request ): RedirectResponse
    {
        $request->validate( [
            'status' => [ 'required', 'string', Rule::in( array_map( fn( $status ) => $status->value, BudgetStatus::cases() ) ) ]
        ] );

        try {
            $result = $this->budgetService->changeStatusByCode( $code, $request->status );

            if ( !$result->isSuccess() ) {
                return redirect()->back()
                    ->with( 'error', $result->getMessage() );
            }

            return redirect()->route( 'budgets.show', $code )
                ->with( 'success', 'Status alterado com sucesso!' );

        } catch ( Exception $e ) {
            return redirect()->back()
                ->with( 'error', 'Erro ao alterar status: ' . $e->getMessage() );
        }
    }

    public function delete_store( string $code ): RedirectResponse
    {
        try {
            $result = $this->budgetService->deleteByCode( $code );

            if ( !$result->isSuccess() ) {
                return redirect()->back()
                    ->with( 'error', $result->getMessage() );
            }

            return redirect()->route( 'budgets.index' )
                ->with( 'success', 'Orçamento excluído com sucesso!' );

        } catch ( Exception $e ) {
            return redirect()->back()
                ->with( 'error', 'Erro ao excluir orçamento: ' . $e->getMessage() );
        }
    }

    /**
     * Dashboard de orçamentos com estatísticas e dados recentes.
     */
    public function dashboard(): View
    {
        try {
            $user = Auth::user();

            if ( !$user || !$user->tenant_id ) {
                abort( 403, 'Acesso negado.' );
            }

            // Buscar dados consolidados do dashboard via BudgetService
            $stats = $this->budgetService->getDashboardData( $user->tenant_id );

            return view( 'pages.budget.dashboard', [
                'stats' => $stats
            ] );

        } catch ( Exception $e ) {
            Log::error( 'Erro ao carregar dashboard de orçamentos', [
                'error'   => $e->getMessage(),
                'user_id' => Auth::id()
            ] );
            abort( 500, 'Erro ao carregar dashboard' );
        }
    }

    /**
     * AJAX endpoint para filtrar orçamentos.
     */
    public function ajaxFilter( Request $request ): JsonResponse
    {
        try {
            $filters = $request->only([
                'filter_code',
                'filter_start_date',
                'filter_end_date',
                'filter_customer',
                'filter_min_value',
                'filter_status',
                'filter_order_by',
                'per_page'
            ]);

            $result = $this->budgetService->getBudgetsForProvider(auth()->id(), $filters);

            return response()->json([
                'success' => true,
                'data' => $result
            ]);

        } catch ( Exception $e ) {
            Log::error( 'Erro no AJAX de filtro de orçamentos', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ] );
            
            return response()->json([
                'success' => false,
                'message' => 'Erro ao filtrar orçamentos'
            ], 500);
        }
    }

}
