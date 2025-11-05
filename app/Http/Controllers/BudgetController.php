<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\BudgetStatus;
use App\Http\Controllers\Abstracts\Controller;
use App\Models\Budget;
use App\Models\BudgetItemCategory;
use App\Models\BudgetTemplate;
use App\Models\UserConfirmationToken;
use App\Services\BudgetPdfService;
use App\Services\BudgetTemplateService;
use App\Services\Domain\BudgetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class BudgetController extends Controller
{
    public function index( Request $request ): View
    {
        $user    = Auth::user();
        $budgets = app( BudgetService::class)->getBudgetsForProvider( $user->id, $request->all() );
        return view( 'pages.budget.index', [
            'budgets' => $budgets
        ] );
    }

    public function create(): View
    {
        return view( 'pages.budget.create' );
    }

    /**
     * Display the budget status selection page for public access.
     */
    public function chooseBudgetStatus( string $code, string $token ): View|RedirectResponse
    {
        try {
            // Find the budget by code and token
            $budget = Budget::where( 'code', $code )
                ->whereHas( 'userConfirmationToken', function ( $query ) use ( $token ) {
                    $query->where( 'token', $token )
                        ->where( 'expires_at', '>', now() );
                } )
                ->with( [ 'customer', 'budgetStatus', 'userConfirmationToken' ] )
                ->first();

            if ( !$budget ) {
                Log::warning( 'Budget not found or token expired', [
                    'code'  => $code,
                    'token' => $token,
                    'ip'    => request()->ip()
                ] );
                return redirect()->route( 'error.not-found' );
            }

            // Get available status options (only approved and rejected for public)
            $availableStatuses = collect( [ BudgetStatus::APPROVED, BudgetStatus::REJECTED ] )
                ->sortBy( fn( $status ) => $status->getOrderIndex() )
                ->values();

            return view( 'budgets.public.choose-status', [
                'budget'            => $budget,
                'availableStatuses' => $availableStatuses,
                'token'             => $token
            ] );

        } catch ( \Exception $e ) {
            Log::error( 'Error in chooseBudgetStatus', [
                'code'  => $code,
                'token' => $token,
                'error' => $e->getMessage(),
                'ip'    => request()->ip()
            ] );
            return redirect()->route( 'error.internal' );
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
                'history' => $budget->history . "\n\n" . now()->format( 'd/m/Y H:i:s' ) . ' - Status alterado para: ' . $newStatusEnum->getName() . ' (via link público)'
            ] );

            // Log the action
            $oldStatusEnum = $budget->status; // Use the enum directly from the model
            Log::info( 'Budget status updated via public link', [
                'budget_id'   => $budget->id,
                'budget_code' => $budget->code,
                'old_status'  => $oldStatusEnum->getName(),
                'new_status'  => $newStatusEnum->getName(),
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

    /**
     * Print budget for public access.
     */
    public function print( string $code, string $token ): View|RedirectResponse
    {
        try {
            // Find the budget by code and token
            $budget = Budget::where( 'code', $code )
                ->whereHas( 'userConfirmationToken', function ( $query ) use ( $token ) {
                    $query->where( 'token', $token )
                        ->where( 'expires_at', '>', now() );
                } )
                ->with( [
                    'customer',
                    'budgetStatus',
                    'items.product',
                    'userConfirmationToken',
                    'tenant'
                ] )
                ->first();

            if ( !$budget ) {
                Log::warning( 'Budget not found or token expired for print', [
                    'code'  => $code,
                    'token' => $token,
                    'ip'    => request()->ip()
                ] );
                return redirect()->route( 'error.not-found' );
            }

            return view( 'budgets.public.print', [
                'budget' => $budget
            ] );

        } catch ( \Exception $e ) {
            Log::error( 'Error in budget print', [
                'code'  => $code,
                'token' => $token,
                'error' => $e->getMessage(),
                'ip'    => request()->ip()
            ] );
            return redirect()->route( 'error.internal' );
        }
    }

}
