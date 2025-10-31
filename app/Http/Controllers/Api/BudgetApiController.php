<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\BudgetStatus;
use App\Http\Controllers\Abstracts\Controller;
use App\Models\Budget;
use App\Models\BudgetActionHistory;
use App\Models\BudgetItem;
use App\Models\BudgetTemplate;
use App\Models\BudgetVersion;
use App\Services\Application\BudgetCalculationService;
use App\Services\Application\BudgetTemplateService;
use App\Services\Infrastructure\BudgetPdfService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BudgetApiController extends Controller
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
     * Lista orçamentos com paginação e filtros.
     */
    public function index( Request $request ): JsonResponse
    {
        $user    = Auth::user();
        $filters = $request->only( [
            'search', 'status', 'customer_id', 'date_from', 'date_to', 'sort_by', 'sort_order'
        ] );

        $perPage = $request->get( 'per_page', 15 );
        $page    = $request->get( 'page', 1 );

        try {
            $query = Budget::where( 'tenant_id', $user->tenant_id )
                ->with( [ 'customer', 'budgetStatus', 'items' ] );

            // Aplicar filtros
            if ( !empty( $filters[ 'search' ] ) ) {
                $query->where( function ( $q ) use ( $filters ) {
                    $q->where( 'code', 'like', "%{$filters[ 'search' ]}%" )
                        ->orWhere( 'description', 'like', "%{$filters[ 'search' ]}%" )
                        ->orWhereHas( 'customer', function ( $customerQuery ) use ( $filters ) {
                            $customerQuery->where( 'name', 'like', "%{$filters[ 'search' ]}%" );
                        } );
                } );
            }

            if ( !empty( $filters[ 'status' ] ) ) {
                $query->byStatus( $filters[ 'status' ] );
            }

            if ( !empty( $filters[ 'customer_id' ] ) ) {
                $query->where( 'customer_id', $filters[ 'customer_id' ] );
            }

            if ( !empty( $filters[ 'date_from' ] ) ) {
                $query->whereDate( 'created_at', '>=', $filters[ 'date_from' ] );
            }

            if ( !empty( $filters[ 'date_to' ] ) ) {
                $query->whereDate( 'created_at', '<=', $filters[ 'date_to' ] );
            }

            // Ordenação
            $sortBy    = $filters[ 'sort_by' ] ?? 'created_at';
            $sortOrder = $filters[ 'sort_order' ] ?? 'desc';

            $query->orderBy( $sortBy, $sortOrder );

            $budgets = $query->paginate( $perPage, [ '*' ], 'page', $page );

            // Adicionar totais calculados
            $budgets->getCollection()->transform( function ( $budget ) {
                $totals                    = $this->calculationService->calculateTotals( $budget );
                $budget->calculated_totals = $totals;
                return $budget;
            } );

            return response()->json( [
                'success' => true,
                'data'    => $budgets,
                'message' => 'Orçamentos listados com sucesso.',
            ] );

        } catch ( \Exception $e ) {
            return response()->json( [
                'success' => false,
                'message' => 'Erro ao listar orçamentos: ' . $e->getMessage(),
            ], 500 );
        }
    }

    /**
     * Cria novo orçamento.
     */
    public function store( Request $request ): JsonResponse
    {
        $user = Auth::user();

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
            DB::beginTransaction();

            // Criar orçamento
            $budget = Budget::create( [
                'tenant_id'                  => $user->tenant_id,
                'customer_id'                => $validated[ 'customer_id' ],
                'budget_statuses_id'         => BudgetStatus::DRAFT->value,
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
            $budget->createVersion( 'Orçamento criado via API', $user->id );

            // Log da ação
            \App\Models\BudgetActionHistory::logAction(
                $budget->id,
                $user->id,
                'created',
                null,
                'rascunho',
                'Orçamento criado via API',
            );

            DB::commit();

            return response()->json( [
                'success' => true,
                'data'    => $budget->load( [ 'customer', 'budgetStatus', 'items' ] ),
                'message' => 'Orçamento criado com sucesso.',
            ], 201 );

        } catch ( \Exception $e ) {
            DB::rollBack();

            return response()->json( [
                'success' => false,
                'message' => 'Erro ao criar orçamento: ' . $e->getMessage(),
            ], 500 );
        }
    }

    /**
     * Mostra detalhes de um orçamento.
     */
    public function show( Budget $budget ): JsonResponse
    {
        if ( $budget->tenant_id !== Auth::user()->tenant_id ) {
            return response()->json( [
                'success' => false,
                'message' => 'Orçamento não encontrado.',
            ], 404 );
        }

        $budget->load( [
            'customer',
            'budgetStatus',
            'items.category',
            'versions.user',
            'attachments'
        ] );

        $totals = $this->calculationService->calculateTotals( $budget );

        return response()->json( [
            'success' => true,
            'data'    => [
                'budget' => $budget,
                'totals' => $totals,
            ],
            'message' => 'Orçamento encontrado.',
        ] );
    }

    /**
     * Atualiza orçamento.
     */
    public function update( Request $request, Budget $budget ): JsonResponse
    {
        if ( $budget->tenant_id !== Auth::user()->tenant_id || !$budget->canBeEdited() ) {
            return response()->json( [
                'success' => false,
                'message' => 'Orçamento não pode ser editado.',
            ], 403 );
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
            DB::beginTransaction();

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
                    $item = $budget->items()->find( $itemData[ 'id' ] );
                    if ( $item ) {
                        $item->update( $itemData );
                        $existingItemIds[] = $item->id;
                    }
                } else {
                    $newItem           = $budget->addItem( $itemData );
                    $existingItemIds[] = $newItem->id;
                }
            }

            // Remover itens não incluídos
            $budget->items()->whereNotIn( 'id', $existingItemIds )->delete();

            // Recalcular totais
            $this->calculationService->recalculateBudgetItems( $budget );

            // Criar nova versão
            $budget->createVersion( 'Orçamento atualizado via API', Auth::id() );

            DB::commit();

            return response()->json( [
                'success' => true,
                'data'    => $budget->load( [ 'customer', 'budgetStatus', 'items' ] ),
                'message' => 'Orçamento atualizado com sucesso.',
            ] );

        } catch ( \Exception $e ) {
            DB::rollBack();

            return response()->json( [
                'success' => false,
                'message' => 'Erro ao atualizar orçamento: ' . $e->getMessage(),
            ], 500 );
        }
    }

    /**
     * Remove orçamento.
     */
    public function destroy( Budget $budget ): JsonResponse
    {
        if ( $budget->tenant_id !== Auth::user()->tenant_id ) {
            return response()->json( [
                'success' => false,
                'message' => 'Orçamento não encontrado.',
            ], 404 );
        }

        try {
            DB::beginTransaction();

            // Log antes de excluir
            \App\Models\BudgetActionHistory::logAction(
                $budget->id,
                Auth::id(),
                'deleted',
                $budget->budgetStatus->slug,
                null,
                'Orçamento excluído via API',
            );

            $budget->delete();

            DB::commit();

            return response()->json( [
                'success' => true,
                'message' => 'Orçamento excluído com sucesso.',
            ] );

        } catch ( \Exception $e ) {
            DB::rollBack();

            return response()->json( [
                'success' => false,
                'message' => 'Erro ao excluir orçamento: ' . $e->getMessage(),
            ], 500 );
        }
    }

    /**
     * Adiciona item ao orçamento.
     */
    public function addItem( Request $request, Budget $budget ): JsonResponse
    {
        if ( $budget->tenant_id !== Auth::user()->tenant_id || !$budget->canBeEdited() ) {
            return response()->json( [
                'success' => false,
                'message' => 'Não é possível adicionar itens.',
            ], 403 );
        }

        $validated = $request->validate( [
            'title'                   => 'required|string|max:255',
            'description'             => 'nullable|string|max:1000',
            'quantity'                => 'required|numeric|min:0.01',
            'unit'                    => 'required|string|max:20',
            'unit_price'              => 'required|numeric|min:0',
            'discount_percentage'     => 'nullable|numeric|min:0|max:100',
            'tax_percentage'          => 'nullable|numeric|min:0|max:100',
            'budget_item_category_id' => 'nullable|integer|exists:budget_item_categories,id',
        ] );

        try {
            $item = $budget->addItem( $validated );

            // Recalcular totais
            $this->calculationService->recalculateBudgetItems( $budget );

            // Criar nova versão
            $budget->createVersion( 'Item adicionado via API', Auth::id() );

            return response()->json( [
                'success' => true,
                'data'    => $item,
                'message' => 'Item adicionado com sucesso.',
            ], 201 );

        } catch ( \Exception $e ) {
            return response()->json( [
                'success' => false,
                'message' => 'Erro ao adicionar item: ' . $e->getMessage(),
            ], 500 );
        }
    }

    /**
     * Atualiza item do orçamento.
     */
    public function updateItem( Request $request, Budget $budget, BudgetItem $item ): JsonResponse
    {
        if ( $budget->tenant_id !== Auth::user()->tenant_id || !$budget->canBeEdited() ) {
            return response()->json( [
                'success' => false,
                'message' => 'Não é possível editar itens.',
            ], 403 );
        }

        if ( $item->budget_id !== $budget->id ) {
            return response()->json( [
                'success' => false,
                'message' => 'Item não pertence ao orçamento.',
            ], 400 );
        }

        $validated = $request->validate( [
            'title'                   => 'required|string|max:255',
            'description'             => 'nullable|string|max:1000',
            'quantity'                => 'required|numeric|min:0.01',
            'unit'                    => 'required|string|max:20',
            'unit_price'              => 'required|numeric|min:0',
            'discount_percentage'     => 'nullable|numeric|min:0|max:100',
            'tax_percentage'          => 'nullable|numeric|min:0|max:100',
            'budget_item_category_id' => 'nullable|integer|exists:budget_item_categories,id',
        ] );

        try {
            $item->update( $validated );

            // Recalcular totais
            $this->calculationService->recalculateBudgetItems( $budget );

            // Criar nova versão
            $budget->createVersion( 'Item atualizado via API', Auth::id() );

            return response()->json( [
                'success' => true,
                'data'    => $item,
                'message' => 'Item atualizado com sucesso.',
            ] );

        } catch ( \Exception $e ) {
            return response()->json( [
                'success' => false,
                'message' => 'Erro ao atualizar item: ' . $e->getMessage(),
            ], 500 );
        }
    }

    /**
     * Remove item do orçamento.
     */
    public function removeItem( Budget $budget, BudgetItem $item ): JsonResponse
    {
        if ( $budget->tenant_id !== Auth::user()->tenant_id || !$budget->canBeEdited() ) {
            return response()->json( [
                'success' => false,
                'message' => 'Não é possível remover itens.',
            ], 403 );
        }

        if ( $item->budget_id !== $budget->id ) {
            return response()->json( [
                'success' => false,
                'message' => 'Item não pertence ao orçamento.',
            ], 400 );
        }

        try {
            $budget->removeItem( $item );

            // Recalcular totais
            $this->calculationService->recalculateBudgetItems( $budget );

            // Criar nova versão
            $budget->createVersion( 'Item removido via API', Auth::id() );

            return response()->json( [
                'success' => true,
                'message' => 'Item removido com sucesso.',
            ] );

        } catch ( \Exception $e ) {
            return response()->json( [
                'success' => false,
                'message' => 'Erro ao remover item: ' . $e->getMessage(),
            ], 500 );
        }
    }

    /**
     * Envia orçamento para cliente.
     */
    public function sendToCustomer( Budget $budget ): JsonResponse
    {
        if ( $budget->tenant_id !== Auth::user()->tenant_id || !$budget->canBeSent() ) {
            return response()->json( [
                'success' => false,
                'message' => 'Orçamento não pode ser enviado.',
            ], 403 );
        }

        try {
            // Alterar status
            $budget->budget_statuses_id = BudgetStatus::PENDING->value;
            $budget->save();

            // Criar nova versão
            $budget->createVersion( 'Orçamento enviado via API', Auth::id() );

            // Log da ação
            \App\Models\BudgetActionHistory::logAction(
                $budget->id,
                Auth::id(),
                'sent',
                'rascunho',
                'enviado',
                'Orçamento enviado para cliente via API',
            );

            return response()->json( [
                'success' => true,
                'data'    => $budget,
                'message' => 'Orçamento enviado com sucesso.',
            ] );

        } catch ( \Exception $e ) {
            return response()->json( [
                'success' => false,
                'message' => 'Erro ao enviar orçamento: ' . $e->getMessage(),
            ], 500 );
        }
    }

    /**
     * Aprova orçamento.
     */
    public function approve( Budget $budget ): JsonResponse
    {
        if ( $budget->tenant_id !== Auth::user()->tenant_id || !$budget->canBeApproved() ) {
            return response()->json( [
                'success' => false,
                'message' => 'Orçamento não pode ser aprovado.',
            ], 403 );
        }

        try {
            $budget->budget_statuses_id = BudgetStatus::APPROVED->value;
            $budget->save();

            // Criar nova versão
            $budget->createVersion( 'Orçamento aprovado via API', Auth::id() );

            // Log da ação
            \App\Models\BudgetActionHistory::logAction(
                $budget->id,
                Auth::id(),
                'approved',
                'enviado',
                'aprovado',
                'Orçamento aprovado via API',
            );

            return response()->json( [
                'success' => true,
                'data'    => $budget,
                'message' => 'Orçamento aprovado com sucesso.',
            ] );

        } catch ( \Exception $e ) {
            return response()->json( [
                'success' => false,
                'message' => 'Erro ao aprovar orçamento: ' . $e->getMessage(),
            ], 500 );
        }
    }

    /**
     * Rejeita orçamento.
     */
    public function reject( Request $request, Budget $budget ): JsonResponse
    {
        if ( $budget->tenant_id !== Auth::user()->tenant_id || !$budget->canBeRejected() ) {
            return response()->json( [
                'success' => false,
                'message' => 'Orçamento não pode ser rejeitado.',
            ], 403 );
        }

        $validated = $request->validate( [
            'reason' => 'nullable|string|max:1000',
        ] );

        try {
            $budget->budget_statuses_id = BudgetStatus::REJECTED->value;
            $budget->save();

            // Criar nova versão
            $budget->createVersion( 'Orçamento rejeitado via API', Auth::id() );

            // Log da ação
            \App\Models\BudgetActionHistory::logAction(
                $budget->id,
                Auth::id(),
                'rejected',
                'enviado',
                'rejeitado',
                'Orçamento rejeitado via API: ' . ( $validated[ 'reason' ] ?? 'Sem motivo' )
            );

            return response()->json( [
                'success' => true,
                'data'    => $budget,
                'message' => 'Orçamento rejeitado.',
            ] );

        } catch ( \Exception $e ) {
            return response()->json( [
                'success' => false,
                'message' => 'Erro ao rejeitar orçamento: ' . $e->getMessage(),
            ], 500 );
        }
    }

    /**
     * Obtém versões do orçamento.
     */
    public function getVersions( Budget $budget ): JsonResponse
    {
        if ( $budget->tenant_id !== Auth::user()->tenant_id ) {
            return response()->json( [
                'success' => false,
                'message' => 'Orçamento não encontrado.',
            ], 404 );
        }

        $versions = $budget->versions()->with( 'user' )->latestFirst()->get();

        return response()->json( [
            'success' => true,
            'data'    => $versions,
            'message' => 'Versões obtidas com sucesso.',
        ] );
    }

    /**
     * Cria nova versão do orçamento.
     */
    public function createVersion( Request $request, Budget $budget ): JsonResponse
    {
        if ( $budget->tenant_id !== Auth::user()->tenant_id || !$budget->canBeEdited() ) {
            return response()->json( [
                'success' => false,
                'message' => 'Não é possível criar versão.',
            ], 403 );
        }

        $validated = $request->validate( [
            'changes_description' => 'nullable|string|max:1000',
        ] );

        try {
            $version = $budget->createVersion(
                $validated[ 'changes_description' ] ?? 'Nova versão criada via API',
                Auth::id(),
            );

            return response()->json( [
                'success' => true,
                'data'    => $version,
                'message' => 'Versão criada com sucesso.',
            ], 201 );

        } catch ( \Exception $e ) {
            return response()->json( [
                'success' => false,
                'message' => 'Erro ao criar versão: ' . $e->getMessage(),
            ], 500 );
        }
    }

    /**
     * Restaura versão específica.
     */
    public function restoreVersion( Budget $budget, BudgetVersion $version ): JsonResponse
    {
        if ( $budget->tenant_id !== Auth::user()->tenant_id || !$budget->canBeEdited() ) {
            return response()->json( [
                'success' => false,
                'message' => 'Não é possível restaurar versão.',
            ], 403 );
        }

        if ( $version->budget_id !== $budget->id ) {
            return response()->json( [
                'success' => false,
                'message' => 'Versão não pertence ao orçamento.',
            ], 400 );
        }

        try {
            $budget->restoreVersion( $version, Auth::id() );

            return response()->json( [
                'success' => true,
                'data'    => $budget->load( [ 'items' ] ),
                'message' => 'Versão restaurada com sucesso.',
            ] );

        } catch ( \Exception $e ) {
            return response()->json( [
                'success' => false,
                'message' => 'Erro ao restaurar versão: ' . $e->getMessage(),
            ], 500 );
        }
    }

    /**
     * Gera PDF do orçamento.
     */
    public function generatePdf( Budget $budget ): JsonResponse
    {
        if ( $budget->tenant_id !== Auth::user()->tenant_id ) {
            return response()->json( [
                'success' => false,
                'message' => 'Orçamento não encontrado.',
            ], 404 );
        }

        try {
            $pdfPath = $this->pdfService->generatePdf( $budget );

            return response()->json( [
                'success' => true,
                'data'    => [
                    'pdf_path' => $pdfPath,
                    'pdf_url'  => asset( 'storage/' . $pdfPath ),
                ],
                'message' => 'PDF gerado com sucesso.',
            ] );

        } catch ( \Exception $e ) {
            return response()->json( [
                'success' => false,
                'message' => 'Erro ao gerar PDF: ' . $e->getMessage(),
            ], 500 );
        }
    }

    /**
     * Envia orçamento por email.
     */
    public function emailBudget( Request $request, Budget $budget ): JsonResponse
    {
        if ( $budget->tenant_id !== Auth::user()->tenant_id ) {
            return response()->json( [
                'success' => false,
                'message' => 'Orçamento não encontrado.',
            ], 404 );
        }

        $validated = $request->validate( [
            'recipients'         => 'required|array',
            'recipients.*.email' => 'required|email',
            'recipients.*.name'  => 'nullable|string|max:255',
            'message'            => 'nullable|string|max:2000',
        ] );

        try {
            $this->pdfService->emailPdf(
                $budget,
                $validated[ 'recipients' ],
                $validated[ 'message' ] ?? ''
            );

            return response()->json( [
                'success' => true,
                'message' => 'Orçamento enviado por email com sucesso.',
            ] );

        } catch ( \Exception $e ) {
            return response()->json( [
                'success' => false,
                'message' => 'Erro ao enviar orçamento: ' . $e->getMessage(),
            ], 500 );
        }
    }

    /**
     * Lista templates disponíveis.
     */
    public function getTemplates( Request $request ): JsonResponse
    {
        $user    = Auth::user();
        $filters = $request->only( [ 'category', 'is_public', 'search' ] );

        try {
            $result = $this->templateService->listTemplates( $user->tenant_id, $filters );

            if ( !$result->isSuccess() ) {
                return response()->json( [
                    'success' => false,
                    'message' => $result->getMessage(),
                ], 400 );
            }

            return response()->json( [
                'success' => true,
                'data'    => $result->getData(),
                'message' => 'Templates listados com sucesso.',
            ] );

        } catch ( \Exception $e ) {
            return response()->json( [
                'success' => false,
                'message' => 'Erro ao listar templates: ' . $e->getMessage(),
            ], 500 );
        }
    }

    /**
     * Cria novo template.
     */
    public function createTemplate( Request $request ): JsonResponse
    {
        $user = Auth::user();

        $validated = $request->validate( [
            'name'            => 'required|string|max:255',
            'slug'            => 'required|string|max:100|alpha_dash|unique:budget_templates,slug',
            'description'     => 'nullable|string|max:1000',
            'category'        => 'required|string|max:50',
            'template_data'   => 'required|array',
            'default_items'   => 'required|array',
            'variables'       => 'nullable|array',
            'estimated_hours' => 'nullable|numeric|min:0',
            'is_public'       => 'required|boolean',
        ] );

        try {
            $result = $this->templateService->createTemplate( $validated, $user->tenant_id, $user->id );

            if ( !$result->isSuccess() ) {
                return response()->json( [
                    'success' => false,
                    'message' => $result->getMessage(),
                ], 400 );
            }

            return response()->json( [
                'success' => true,
                'data'    => $result->getData(),
                'message' => 'Template criado com sucesso.',
            ], 201 );

        } catch ( \Exception $e ) {
            return response()->json( [
                'success' => false,
                'message' => 'Erro ao criar template: ' . $e->getMessage(),
            ], 500 );
        }
    }

    /**
     * Calcula totais do orçamento.
     */
    public function calculateTotals( Request $request ): JsonResponse
    {
        $validated = $request->validate( [
            'budget_id'                  => 'nullable|integer|exists:budgets,id',
            'items'                      => 'nullable|array',
            'global_discount_percentage' => 'nullable|numeric|min:0|max:100',
        ] );

        try {
            if ( isset( $validated[ 'budget_id' ] ) ) {
                $budget = Budget::where( 'tenant_id', Auth::user()->tenant_id )
                    ->findOrFail( $validated[ 'budget_id' ] );

                $totals = $this->calculationService->calculateTotals( $budget );
            } else {
                // Calcular baseado nos itens fornecidos
                $totals = $this->calculationService->calculateTotalsFromItems(
                    $validated[ 'items' ] ?? [],
                    $validated[ 'global_discount_percentage' ] ?? 0
                );
            }

            return response()->json( [
                'success' => true,
                'data'    => $totals,
                'message' => 'Cálculos realizados com sucesso.',
            ] );

        } catch ( \Exception $e ) {
            return response()->json( [
                'success' => false,
                'message' => 'Erro ao calcular totais: ' . $e->getMessage(),
            ], 500 );
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
