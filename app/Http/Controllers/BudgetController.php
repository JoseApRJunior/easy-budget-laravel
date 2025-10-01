<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\BudgetItemCategory;
use App\Models\BudgetTemplate;
use App\Services\BudgetCalculationService;
use App\Services\BudgetPdfService;
use App\Services\BudgetTemplateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BudgetController extends Controller
{
    private BudgetCalculationService $calculationService;
    private BudgetPdfService         $pdfService;
    private BudgetTemplateService    $templateService;

    public function __construct(
        BudgetCalculationService $calculationService,
        BudgetPdfService $pdfService,
        BudgetTemplateService $templateService,
    ) {
        $this->calculationService = $calculationService;
        $this->pdfService         = $pdfService;
        $this->templateService    = $templateService;
    }

    /**
     * Lista todos os orçamentos.
     */
    public function index( Request $request )
    {
        $user    = Auth::user();
        $filters = $request->only( [
            'search', 'status', 'customer', 'period', 'sort_by'
        ] );

        // Buscar orçamentos do tenant
        $budgets = Budget::where( 'tenant_id', $user->tenant_id )
            ->with( [ 'customer', 'budgetStatus', 'items' ] )
            ->when( $filters[ 'search' ] ?? null, function ( $query, $search ) {
                $query->where( function ( $q ) use ( $search ) {
                    $q->where( 'code', 'like', "%{$search}%" )
                        ->orWhere( 'description', 'like', "%{$search}%" )
                        ->orWhereHas( 'customer', function ( $customerQuery ) use ( $search ) {
                            $customerQuery->where( 'name', 'like', "%{$search}%" );
                        } );
                } );
            } )
            ->when( $filters[ 'status' ] ?? null, function ( $query, $status ) {
                $query->byStatus( $status );
            } )
            ->when( $filters[ 'customer' ] ?? null, function ( $query, $customerId ) {
                $query->where( 'customer_id', $customerId );
            } )
            ->when( $filters[ 'period' ] ?? null, function ( $query, $period ) {
                switch ( $period ) {
                    case 'today':
                        $query->whereDate( 'created_at', today() );
                        break;
                    case 'week':
                        $query->whereBetween( 'created_at', [
                            now()->startOfWeek(),
                            now()->endOfWeek()
                        ] );
                        break;
                    case 'month':
                        $query->whereMonth( 'created_at', now()->month );
                        break;
                }
            } )
            ->when( $filters[ 'sort_by' ] ?? null, function ( $query, $sort ) {
                switch ( $sort ) {
                    case 'budget_number':
                        $query->orderBy( 'code' );
                        break;
                    case 'grand_total':
                        $query->orderBy( 'total', 'desc' );
                        break;
                    case 'created_at':
                    default:
                        $query->orderBy( 'created_at', 'desc' );
                        break;
                }
            }, function ( $query ) {
                $query->orderBy( 'created_at', 'desc' );
            } )
            ->paginate( 15 );

        // Estatísticas rápidas
        $stats = [
            'total'    => Budget::where( 'tenant_id', $user->tenant_id )->count(),
            'draft'    => Budget::where( 'tenant_id', $user->tenant_id )->draft()->count(),
            'sent'     => Budget::where( 'tenant_id', $user->tenant_id )->sent()->count(),
            'approved' => Budget::where( 'tenant_id', $user->tenant_id )->approved()->count(),
        ];

        return view( 'budgets.index', compact( 'budgets', 'stats', 'filters' ) );
    }

    /**
     * Mostra formulário de criação de orçamento.
     */
    public function create( Request $request )
    {
        $user = Auth::user();

        // Carregar dados necessários
        $customers = \App\Models\Customer::where( 'tenant_id', $user->tenant_id )
            ->where( 'status', 'ativo' )
            ->orderBy( 'name' )
            ->get();

        $categories = BudgetItemCategory::where( 'tenant_id', $user->tenant_id )
            ->active()
            ->ordered()
            ->get();

        $templates = $this->templateService->listTemplates( $user->tenant_id )->getData();

        // Se houver template selecionado, carregar dados
        $selectedTemplate = null;
        if ( $request->has( 'template' ) ) {
            $selectedTemplate = BudgetTemplate::where( 'tenant_id', $user->tenant_id )
                ->where( 'slug', $request->template )
                ->first();
        }

        return view( 'budgets.create', compact(
            'customers',
            'categories',
            'templates',
            'selectedTemplate',
        ) );
    }

    /**
     * Salva novo orçamento.
     */
    public function store( Request $request )
    {
        $user = Auth::user();

        // Validar dados
        $validated = $request->validate( [
            'customer_id'                     => 'required|integer|exists:customers,id',
            'description'                     => 'nullable|string|max:1000',
            'valid_until'                     => 'nullable|date|after:today',
            'global_discount_percentage'      => 'nullable|numeric|min:0|max:100',
            'items'                           => 'required|array|min:1',
            'items.*.title'                   => 'required|string|max:255',
            'items.*.description'             => 'nullable|string|max:1000',
            'items.*.quantity'                => 'required|numeric|min:0.01',
            'items.*.unit'                    => 'required|string|max:20',
            'items.*.unit_price'              => 'required|numeric|min:0',
            'items.*.discount_percentage'     => 'nullable|numeric|min:0|max:100',
            'items.*.tax_percentage'          => 'nullable|numeric|min:0|max:100',
            'items.*.budget_item_category_id' => 'nullable|integer|exists:budget_item_categories,id',
        ] );

        try {
            // Criar orçamento
            $budget = Budget::create( [
                'tenant_id'                  => $user->tenant_id,
                'customer_id'                => $validated[ 'customer_id' ],
                'budget_statuses_id'         => \App\Models\BudgetStatus::where( 'slug', 'rascunho' )->first()->id,
                'user_id'                    => $user->id,
                'code'                       => $this->generateBudgetCode(),
                'description'                => $validated[ 'description' ] ?? null,
                'valid_until'                => $validated[ 'valid_until' ] ?? null,
                'global_discount_percentage' => $validated[ 'global_discount_percentage' ] ?? 0,
            ] );

            // Adicionar itens
            foreach ( $validated[ 'items' ] as $itemData ) {
                $budget->addItem( $itemData );
            }

            // Calcular totais
            $this->calculationService->recalculateBudgetItems( $budget );

            // Criar versão inicial
            $budget->createVersion( 'Orçamento criado', $user->id );

            // Log da ação
            \App\Models\BudgetActionHistory::logAction(
                $budget->id,
                $user->id,
                'created',
                null,
                'rascunho',
                'Orçamento criado via interface web',
            );

            return redirect()->route( 'budgets.show', $budget )
                ->with( 'success', 'Orçamento criado com sucesso!' );

        } catch ( \Exception $e ) {
            return back()->withInput()
                ->with( 'error', 'Erro ao criar orçamento: ' . $e->getMessage() );
        }
    }

    /**
     * Mostra detalhes de um orçamento.
     */
    public function show( Budget $budget )
    {
        // Verificar permissão
        if ( $budget->tenant_id !== Auth::user()->tenant_id ) {
            abort( 403 );
        }

        $budget->load( [
            'customer',
            'budgetStatus',
            'items.category',
            'versions.user',
            'attachments',
            'actionHistory.user'
        ] );

        // Calcular totais atualizados
        $totals = $this->calculationService->calculateTotals( $budget );

        // Verificar se precisa de nova versão
        if ( $this->pdfService->needsRegeneration( $budget ) ) {
            $this->pdfService->generatePdf( $budget );
        }

        return view( 'budgets.show', compact( 'budget', 'totals' ) );
    }

    /**
     * Mostra formulário de edição.
     */
    public function edit( Budget $budget )
    {
        // Verificar permissão e se pode editar
        if ( $budget->tenant_id !== Auth::user()->tenant_id || !$budget->canBeEdited() ) {
            abort( 403 );
        }

        $budget->load( [ 'customer', 'items.category' ] );

        $customers = \App\Models\Customer::where( 'tenant_id', Auth::user()->tenant_id )
            ->where( 'status', 'ativo' )
            ->orderBy( 'name' )
            ->get();

        $categories = BudgetItemCategory::where( 'tenant_id', Auth::user()->tenant_id )
            ->active()
            ->ordered()
            ->get();

        return view( 'budgets.edit', compact( 'budget', 'customers', 'categories' ) );
    }

    /**
     * Atualiza orçamento.
     */
    public function update( Request $request, Budget $budget )
    {
        // Verificar permissão
        if ( $budget->tenant_id !== Auth::user()->tenant_id || !$budget->canBeEdited() ) {
            abort( 403 );
        }

        $validated = $request->validate( [
            'customer_id'                     => 'required|integer|exists:customers,id',
            'description'                     => 'nullable|string|max:1000',
            'valid_until'                     => 'nullable|date|after:today',
            'global_discount_percentage'      => 'nullable|numeric|min:0|max:100',
            'items'                           => 'required|array|min:1',
            'items.*.id'                      => 'nullable|integer|exists:budget_items,id',
            'items.*.title'                   => 'required|string|max:255',
            'items.*.description'             => 'nullable|string|max:1000',
            'items.*.quantity'                => 'required|numeric|min:0.01',
            'items.*.unit'                    => 'required|string|max:20',
            'items.*.unit_price'              => 'required|numeric|min:0',
            'items.*.discount_percentage'     => 'nullable|numeric|min:0|max:100',
            'items.*.tax_percentage'          => 'nullable|numeric|min:0|max:100',
            'items.*.budget_item_category_id' => 'nullable|integer|exists:budget_item_categories,id',
        ] );

        try {
            // Atualizar dados do orçamento
            $budget->update( [
                'customer_id'                => $validated[ 'customer_id' ],
                'description'                => $validated[ 'description' ] ?? null,
                'valid_until'                => $validated[ 'valid_until' ] ?? null,
                'global_discount_percentage' => $validated[ 'global_discount_percentage' ] ?? 0,
            ] );

            // Atualizar itens
            $existingItemIds = [];
            foreach ( $validated[ 'items' ] as $itemData ) {
                if ( isset( $itemData[ 'id' ] ) ) {
                    // Atualizar item existente
                    $item = $budget->items()->find( $itemData[ 'id' ] );
                    if ( $item ) {
                        $item->update( $itemData );
                        $existingItemIds[] = $item->id;
                    }
                } else {
                    // Criar novo item
                    $newItem           = $budget->addItem( $itemData );
                    $existingItemIds[] = $newItem->id;
                }
            }

            // Remover itens que não estão na lista
            $budget->items()->whereNotIn( 'id', $existingItemIds )->delete();

            // Recalcular totais
            $this->calculationService->recalculateBudgetItems( $budget );

            // Criar nova versão
            $budget->createVersion( 'Orçamento atualizado via interface web', Auth::id() );

            // Log da ação
            \App\Models\BudgetActionHistory::logAction(
                $budget->id,
                Auth::id(),
                'updated',
                null,
                null,
                'Orçamento atualizado via interface web',
            );

            return redirect()->route( 'budgets.show', $budget )
                ->with( 'success', 'Orçamento atualizado com sucesso!' );

        } catch ( \Exception $e ) {
            return back()->withInput()
                ->with( 'error', 'Erro ao atualizar orçamento: ' . $e->getMessage() );
        }
    }

    /**
     * Envia orçamento para o cliente.
     */
    public function send( Budget $budget )
    {
        // Verificar permissão
        if ( $budget->tenant_id !== Auth::user()->tenant_id || !$budget->canBeSent() ) {
            abort( 403 );
        }

        try {
            // Alterar status para enviado
            $budget->budget_statuses_id = \App\Models\BudgetStatus::where( 'slug', 'enviado' )->first()->id;
            $budget->save();

            // Criar nova versão
            $budget->createVersion( 'Orçamento enviado para cliente', Auth::id() );

            // Log da ação
            \App\Models\BudgetActionHistory::logAction(
                $budget->id,
                Auth::id(),
                'sent',
                'rascunho',
                'enviado',
                'Orçamento enviado para aprovação do cliente',
            );

            return redirect()->back()
                ->with( 'success', 'Orçamento enviado com sucesso!' );

        } catch ( \Exception $e ) {
            return back()->with( 'error', 'Erro ao enviar orçamento: ' . $e->getMessage() );
        }
    }

    /**
     * Gera PDF do orçamento.
     */
    public function generatePdf( Budget $budget )
    {
        // Verificar permissão
        if ( $budget->tenant_id !== Auth::user()->tenant_id ) {
            abort( 403 );
        }

        try {
            $pdfPath = $this->pdfService->generatePdf( $budget );

            return response()->download( storage_path( "app/public/{$pdfPath}" ) );

        } catch ( \Exception $e ) {
            return back()->with( 'error', 'Erro ao gerar PDF: ' . $e->getMessage() );
        }
    }

    /**
     * Duplica orçamento.
     */
    public function duplicate( Budget $budget )
    {
        // Verificar permissão
        if ( $budget->tenant_id !== Auth::user()->tenant_id ) {
            abort( 403 );
        }

        try {
            $newBudget = $budget->duplicate( Auth::id() );

            return redirect()->route( 'budgets.edit', $newBudget )
                ->with( 'success', 'Orçamento duplicado com sucesso!' );

        } catch ( \Exception $e ) {
            return back()->with( 'error', 'Erro ao duplicar orçamento: ' . $e->getMessage() );
        }
    }

    /**
     * Mostra versões do orçamento.
     */
    public function versions( Budget $budget )
    {
        // Verificar permissão
        if ( $budget->tenant_id !== Auth::user()->tenant_id ) {
            abort( 403 );
        }

        $budget->load( [ 'versions.user' ] );

        return view( 'budgets.versions', compact( 'budget' ) );
    }

    /**
     * Restaura uma versão específica.
     */
    public function restoreVersion( Budget $budget, $versionId )
    {
        // Verificar permissão
        if ( $budget->tenant_id !== Auth::user()->tenant_id || !$budget->canBeEdited() ) {
            abort( 403 );
        }

        try {
            $version = $budget->versions()->findOrFail( $versionId );

            $budget->restoreVersion( $version, Auth::id() );

            return redirect()->back()
                ->with( 'success', 'Versão restaurada com sucesso!' );

        } catch ( \Exception $e ) {
            return back()->with( 'error', 'Erro ao restaurar versão: ' . $e->getMessage() );
        }
    }

    /**
     * Gera código único para orçamento.
     */
    private function generateBudgetCode(): string
    {
        $prefix = 'ORC';
        $year   = date( 'Y' );
        $month  = date( 'm' );

        // Buscar último número sequencial do mês
        $lastBudget = Budget::where( 'tenant_id', Auth::user()->tenant_id )
            ->where( 'code', 'like', "{$prefix}{$year}{$month}%" )
            ->orderBy( 'code', 'desc' )
            ->first();

        $sequence = $lastBudget ? (int) substr( $lastBudget->code, -4 ) + 1 : 1;

        return sprintf( '%s%s%s%04d', $prefix, $year, $month, $sequence );
    }

}
