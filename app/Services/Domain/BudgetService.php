<?php

declare(strict_types=1);

namespace App\Services\Domain;

use App\Enums\BudgetStatus;
use App\Enums\OperationStatus;
use App\Models\Budget;
use App\Models\User;
use App\Repositories\BudgetRepository;
use App\Services\Core\Abstracts\AbstractBaseService;
use App\Support\ServiceResult;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class BudgetService extends AbstractBaseService
{
    public function __construct( BudgetRepository $budgetRepository )
    {
        parent::__construct( $budgetRepository );
    }

    /**
     * Retorna lista paginada de orçamentos para um provider específico.
     *
     * @param int $userId ID do usuário provider
     * @param array $filters Filtros a aplicar
     * @return LengthAwarePaginator
     */
    public function getBudgetsForProvider( int $userId, array $filters = [] ): LengthAwarePaginator
    {
        // Busca o usuário para obter o tenant_id
        $user = User::find( $userId );

        if ( !$user || !$user->tenant_id ) {
            throw new InvalidArgumentException( 'Usuário ou tenant não encontrado.' );
        }

        // Note: Budgets are related to customers within the tenant, not directly to users
        // The filtering is done by tenant_id in the repository
        // Remove the incorrect user_id filter as budgets table doesn't have this column

        // Configura paginação padrão
        $perPage = $filters[ 'per_page' ] ?? 10;
        unset( $filters[ 'per_page' ] );

        // Usa o repositório para buscar com filtros
        return $this->repository->getPaginatedBudgets(
            tenantId: $user->tenant_id,
            filters: $filters,
            perPage: $perPage,
        );
    }

    /**
     * Cria um novo orçamento.
     *
     * @param array $data Dados do orçamento
     * @param int $tenantId ID do tenant
     * @return ServiceResult
     */
    /**
     * Cria um novo orçamento com tenant do usuário autenticado.
     *
     * @param array $data Dados do orçamento
     * @return ServiceResult
     */
    public function create( array $data ): ServiceResult
    {
        $tenantId = $this->tenantId();
        if ( !$tenantId ) {
            return ServiceResult::error( 'Usuário não autenticado ou tenant não encontrado.' );
        }

        return $this->createBudget( $data, $tenantId );
    }

    public function createBudget( array $data, int $tenantId ): ServiceResult
    {
        try {
            // Validações básicas
            $this->validateBudgetData( $data );

            // Gera código único para o orçamento
            $data[ 'code' ]      = $this->generateUniqueBudgetCode( $tenantId );
            $data[ 'tenant_id' ] = $tenantId;

            // Define status padrão se não fornecido
            if ( !isset( $data[ 'status' ] ) ) {
                $data[ 'status' ] = BudgetStatus::DRAFT->value;
            }

            // Valida status
            if ( !$this->isValidBudgetStatus( $data[ 'status' ] ) ) {
                return ServiceResult::error(
                    'Status de orçamento inválido: ' . $data[ 'status' ]
                );
            }

            // Define valores padrão para campos obrigatórios
            $data['discount'] = $data['discount'] ?? 0.00;
            $data['total'] = $data['total'] ?? 0.00;
            
            // Cria o orçamento
            $budget = $this->repository->create( $data );

            return ServiceResult::success( $budget, 'Orçamento criado com sucesso.' );

        } catch ( InvalidArgumentException $e ) {
            return ServiceResult::invalidData( $e->getMessage() );
        } catch ( \Exception $e ) {
            return ServiceResult::error( 'Erro ao criar orçamento: ' . $e->getMessage() );
        }
    }

    /**
     * Atualiza um orçamento existente.
     *
     * @param int $budgetId ID do orçamento
     * @param array $data Dados para atualização
     * @param int $tenantId ID do tenant
     * @return ServiceResult
     */
    public function updateBudget( int $budgetId, array $data, int $tenantId ): ServiceResult
    {
        try {
            // Validações básicas
            $this->validateBudgetData( $data, false );

            // Busca o orçamento primeiro
            $budget = $this->repository->find( $budgetId );

            if ( !$budget ) {
                return ServiceResult::notFound( 'Orçamento' );
            }

            // Verifica se pertence ao tenant correto
            if ( $budget->tenant_id !== $tenantId ) {
                return ServiceResult::forbidden( 'Acesso negado a este orçamento.' );
            }

            // Valida status se fornecido
            if ( isset( $data[ 'status' ] ) && !$this->isValidBudgetStatus( $data[ 'status' ] ) ) {
                return ServiceResult::error(
                    'Status de orçamento inválido: ' . $data[ 'status' ]
                );
            }

            // Atualiza o orçamento
            $updatedBudget = $this->repository->update( $budgetId, $data );

            if ( !$updatedBudget ) {
                return ServiceResult::error( 'Falha ao atualizar orçamento.' );
            }

            return ServiceResult::success( $updatedBudget, 'Orçamento atualizado com sucesso.' );

        } catch ( InvalidArgumentException $e ) {
            return ServiceResult::invalidData( $e->getMessage() );
        } catch ( \Exception $e ) {
            return ServiceResult::error( 'Erro ao atualizar orçamento: ' . $e->getMessage() );
        }
    }

    /**
     * Altera o status de um orçamento por código.
     *
     * @param string $code Código do orçamento
     * @param string $status Novo status
     * @return ServiceResult
     */
    public function changeStatusByCode( string $code, string $status ): ServiceResult
    {
        try {
            $tenantId = $this->tenantId();
            if ( !$tenantId ) {
                return ServiceResult::error( 'Usuário não autenticado ou tenant não encontrado.' );
            }

            // Busca o orçamento por código
            $budget = $this->repository->findByCode( $code, $tenantId );

            if ( !$budget ) {
                return ServiceResult::notFound( 'Orçamento' );
            }

            // Usa o método changeStatus existente com comentário vazio
            return $this->changeStatus( $budget->id, $status, '', $tenantId );

        } catch ( \Exception $e ) {
            return ServiceResult::error( 'Erro ao alterar status: ' . $e->getMessage() );
        }
    }

    /**
     * Altera o status de um orçamento.
     *
     * @param int $budgetId ID do orçamento
     * @param string $status Novo status
     * @param string $comment Comentário da alteração
     * @param int $tenantId ID do tenant
     * @return ServiceResult
     */
    public function changeStatus( int $budgetId, string $status, string $comment, int $tenantId ): ServiceResult
    {
        try {
            // Valida status
            if ( !$this->isValidBudgetStatus( $status ) ) {
                return ServiceResult::error( 'Status de orçamento inválido: ' . $status );
            }

            // Busca o orçamento
            $budget = $this->repository->find( $budgetId );

            if ( !$budget ) {
                return ServiceResult::notFound( 'Orçamento' );
            }

            // Verifica se pertence ao tenant correto
            if ( $budget->tenant_id !== $tenantId ) {
                return ServiceResult::forbidden( 'Acesso negado a este orçamento.' );
            }

            // Usa transação para atomicidade
            $updatedBudget = DB::transaction( function () use ($budgetId, $status, $comment) {
                // Atualiza status e comentário
                $updated = $this->repository->update( $budgetId, [
                    'status'            => $status,
                    'status_comment'    => $comment,
                    'status_updated_at' => now(),
                    'status_updated_by' => $this->authUser()?->id
                ] );

                if ( !$updated ) {
                    throw new \Exception( 'Falha ao alterar status do orçamento.' );
                }

                // TODO: Atualizar serviços relacionados se necessário
                // Por exemplo, se aprovado, atualizar status dos serviços

                // Recarrega o orçamento atualizado
                return $this->repository->find( $budgetId );
            } );

            return ServiceResult::success( $updatedBudget, 'Status do orçamento alterado com sucesso.' );

        } catch ( \Exception $e ) {
            return ServiceResult::error( 'Erro ao alterar status: ' . $e->getMessage() );
        }
    }

    /**
     * Manipula mudança de status do orçamento com validações via enum e cascata automática.
     *
     * @param Budget $budget Instância do orçamento
     * @param string $newStatus Novo status desejado
     * @return ServiceResult
     */
    public function handleStatusChange( Budget $budget, string $newStatus ): ServiceResult
    {
        try {
            return DB::transaction( function () use ($budget, $newStatus) {
                $oldStatus = $budget->status;

                // Validar transição
                if ( !$oldStatus->canTransitionTo( BudgetStatus::fromString( $newStatus ) ) ) {
                    return $this->error(
                        OperationStatus::INVALID_DATA,
                        "Transição de {$oldStatus->value} para {$newStatus} não permitida",
                    );
                }

                // Atualizar orçamento
                $budget->update( [ 'status' => $newStatus ] );

                // Atualizar serviços em cascata
                $this->updateRelatedServices( $budget, $newStatus );

                return $this->success( $budget, 'Status alterado com sucesso' );
            } );

        } catch ( \Exception $e ) {
            return $this->error(
                OperationStatus::ERROR,
                'Erro ao alterar status',
                null,
                $e,
            );
        }
    }

    /**
     * Atualiza serviços relacionados baseado no novo status do orçamento.
     *
     * @param Budget $budget Orçamento que teve o status alterado
     * @param string $newStatus Novo status do orçamento
     * @return void
     */
    private function updateRelatedServices( Budget $budget, string $newStatus ): void
    {
        $serviceStatus = match ( $newStatus ) {
            'APPROVED'              => 'in_progress',
            'REJECTED', 'CANCELLED' => 'cancelled',
            default                 => null
        };

        if ( $serviceStatus ) {
            $budget->services()->update( [ 'status' => $serviceStatus ] );
        }
    }

    /**
     * Gera PDF do orçamento com verificação de hash.
     *
     * @param string $code Código do orçamento
     * @param int $tenantId ID do tenant
     * @param string|null $verificationHash Hash de verificação (opcional)
     * @return ServiceResult
     */
    public function printPDF( string $code, int $tenantId, ?string $verificationHash = null ): ServiceResult
    {
        try {
            // Busca o orçamento completo com dados relacionados
            $budget = $this->repository->getBudgetFullByCode( $code, $tenantId );

            if ( !$budget ) {
                return ServiceResult::notFound( 'Orçamento' );
            }

            // Verifica hash de verificação se fornecido
            if ( $verificationHash && $budget->pdf_verification_hash !== $verificationHash ) {
                return ServiceResult::forbidden( 'Hash de verificação inválido.' );
            }

            // Verifica se o orçamento pode ser visualizado (status apropriado)
            $viewableStatuses = [ 'sent', 'approved', 'completed' ];
            if ( !in_array( $budget->status->value, $viewableStatuses ) ) {
                return ServiceResult::error(
                    'Orçamento não pode ser visualizado no status atual.',
                );
            }

            // TODO: Implementar geração do PDF usando PdfService
            // $pdfContent = $this->pdfService->generateBudgetPDF( $budget );

            // Por enquanto, retorna dados para geração do PDF
            $pdfData = [
                'budget'            => $budget,
                'customer'          => $budget->customer,
                'services'          => $budget->services,
                'total'             => $budget->total,
                'code'              => $budget->code,
                'created_at'        => $budget->created_at,
                'verification_hash' => $budget->pdf_verification_hash
            ];

            return ServiceResult::success(
                $pdfData,
                'Dados do orçamento preparados para geração de PDF.',
            );

        } catch ( \Exception $e ) {
            return ServiceResult::error( 'Erro ao gerar PDF: ' . $e->getMessage() );
        }
    }

    /**
     * Determina o novo status baseado na ação do usuário.
     */
    private function determineNewStatusFromAction( string $currentStatus, string $action ): ?string
    {
        return match ( $action ) {
            'approve'  => match ( $currentStatus ) {
                    'sent'  => 'approved',
                    default => null,
                },
            'reject'   => match ( $currentStatus ) {
                    'sent'  => 'rejected',
                    default => null,
                },
            'revise'   => match ( $currentStatus ) {
                    'sent'  => 'revised',
                    default => null,
                },
            'cancel'   => match ( $currentStatus ) {
                    'draft', 'sent', 'approved' => 'cancelled',
                    default                     => null,
                },
            'complete' => match ( $currentStatus ) {
                    'approved' => 'completed',
                    default    => null,
                },
            'expire'   => match ( $currentStatus ) {
                    'sent', 'approved' => 'expired',
                    default            => null,
                },
            'reset'    => match ( $currentStatus ) {
                    'cancelled', 'rejected', 'expired' => 'draft',
                    default                            => null,
                },
            default    => null,
        };
    }

    /**
     * Valida se a transição de status é permitida.
     */
    private function isValidStatusTransition( string $currentStatus, string $newStatus ): bool
    {
        $allowedTransitions = BudgetStatus::getAllowedTransitions( $currentStatus );

        return in_array( $newStatus, $allowedTransitions );
    }

    /**
     * Duplica um orçamento existente.
     *
     * @param int $budgetId ID do orçamento original
     * @param int $tenantId ID do tenant
     * @return ServiceResult
     */
    public function duplicateBudget( int $budgetId, int $tenantId ): ServiceResult
    {
        try {
            // Busca o orçamento original
            $originalBudget = $this->repository->find( $budgetId );

            if ( !$originalBudget ) {
                return ServiceResult::notFound( 'Orçamento' );
            }

            // Verifica se pertence ao tenant correto
            if ( $originalBudget->tenant_id !== $tenantId ) {
                return ServiceResult::forbidden( 'Acesso negado a este orçamento.' );
            }

            // Prepara dados para duplicação
            $duplicateData = $originalBudget->toArray();

            // Remove campos que não devem ser duplicados
            unset(
                $duplicateData[ 'id' ],
                $duplicateData[ 'code' ],
                $duplicateData[ 'created_at' ],
                $duplicateData[ 'updated_at' ],
                $duplicateData[ 'status' ] // Reseta para draft
            );

            // Define novo título e status
            $duplicateData[ 'title' ]     = 'Cópia de: ' . $originalBudget->title;
            $duplicateData[ 'status' ]    = BudgetStatus::DRAFT->value;
            $duplicateData[ 'tenant_id' ] = $tenantId;

            // Gera novo código único
            $duplicateData[ 'code' ] = $this->generateUniqueBudgetCode( $tenantId );

            // Cria a duplicata
            $duplicatedBudget = $this->repository->create( $duplicateData );

            return ServiceResult::success( $duplicatedBudget, 'Orçamento duplicado com sucesso.' );

        } catch ( \Exception $e ) {
            return ServiceResult::error( 'Erro ao duplicar orçamento: ' . $e->getMessage() );
        }
    }

    /**
     * Atualiza um orçamento por código.
     *
     * @param string $code Código do orçamento
     * @param array $data Dados para atualização
     * @return ServiceResult
     */
    public function updateByCode( string $code, array $data ): ServiceResult
    {
        try {
            return DB::transaction( function () use ($code, $data) {
                $budget = Budget::where( 'code', $code )->first();

                if ( !$budget ) {
                    return $this->error(
                        OperationStatus::NOT_FOUND,
                        "Orçamento {$code} não encontrado",
                    );
                }

                // Verificar se pode editar
                if ( !$budget->status->canBeEdited() ) {
                    return $this->error(
                        OperationStatus::INVALID_DATA,
                        "Orçamento não pode ser editado no status {$budget->status->value}",
                    );
                }

                // Atualizar orçamento
                $budget->update( $data );

                // Atualizar itens se fornecidos
                if ( isset( $data[ 'items' ] ) ) {
                    $this->updateBudgetItems( $budget, $data[ 'items' ] );
                }

                return $this->success( $budget->fresh(), 'Orçamento atualizado' );
            } );

        } catch ( \Exception $e ) {
            return $this->error(
                OperationStatus::ERROR,
                'Erro ao atualizar orçamento',
                null,
                $e,
            );
        }
    }

    private function updateBudgetItems( Budget $budget, array $items ): void
    {
        // Deletar itens existentes
        $budget->items()->delete();

        // Criar novos itens
        foreach ( $items as $item ) {
            $budget->items()->create( $item );
        }
    }

    /**
     * Exclui um orçamento por código (soft delete).
     *
     * @param string $code Código do orçamento
     * @return ServiceResult
     */
    public function deleteByCode( string $code ): ServiceResult
    {
        try {
            return DB::transaction( function () use ($code) {
                $budget = Budget::where( 'code', $code )->first();

                if ( !$budget ) {
                    return $this->error(
                        OperationStatus::NOT_FOUND,
                        "Orçamento {$code} não encontrado",
                    );
                }

                // Verificar se pode deletar
                if ( !$budget->status->canDelete() ) {
                    return $this->error(
                        OperationStatus::INVALID_DATA,
                        "Orçamento não pode ser excluído no status {$budget->status->value}",
                    );
                }

                // Verificar relacionamentos
                if ( $budget->services()->exists() ) {
                    return $this->error(
                        OperationStatus::INVALID_DATA,
                        "Orçamento possui serviços associados e não pode ser excluído",
                    );
                }

                // Soft delete
                $budget->delete();

                return $this->success( null, 'Orçamento excluído' );
            } );

        } catch ( \Exception $e ) {
            return $this->error(
                OperationStatus::ERROR,
                'Erro ao excluir orçamento',
                null,
                $e,
            );
        }
    }

    /**
     * Busca orçamento por código com relacionamentos carregados.
     *
     * @param string $code Código do orçamento
     * @param array $relations Relacionamentos a carregar
     * @return ServiceResult
     */
    public function findByCode( string $code, array $with = [] ): ServiceResult
    {
        try {
            $query = Budget::where( 'code', $code );

            if ( !empty( $with ) ) {
                $query->with( $with );
            }

            $budget = $query->first();

            if ( !$budget ) {
                return $this->error(
                    OperationStatus::NOT_FOUND,
                    "Orçamento com código {$code} não encontrado",
                );
            }

            return $this->success( $budget, 'Orçamento encontrado' );

        } catch ( \Exception $e ) {
            return $this->error(
                OperationStatus::ERROR,
                'Erro ao buscar orçamento',
                null,
                $e,
            );
        }
    }

    /**
     * Remove um orçamento (soft delete).
     *
     * @param int $budgetId ID do orçamento
     * @param int $tenantId ID do tenant
     * @return ServiceResult
     */
    public function deleteBudget( int $budgetId, int $tenantId ): ServiceResult
    {
        try {
            // Busca o orçamento
            $budget = $this->repository->find( $budgetId );

            if ( !$budget ) {
                return ServiceResult::notFound( 'Orçamento' );
            }

            // Verifica se pertence ao tenant correto
            if ( $budget->tenant_id !== $tenantId ) {
                return ServiceResult::forbidden( 'Acesso negado a este orçamento.' );
            }

            // Remove o orçamento
            $deleted = $this->repository->delete( $budgetId );

            if ( !$deleted ) {
                return ServiceResult::error( 'Falha ao remover orçamento.' );
            }

            return ServiceResult::success( null, 'Orçamento removido com sucesso.' );

        } catch ( \Exception $e ) {
            return ServiceResult::error( 'Erro ao remover orçamento: ' . $e->getMessage() );
        }
    }

    /**
     * Atualiza status de múltiplos orçamentos em lote.
     *
     * @param array $budgetIds IDs dos orçamentos
     * @param string $status Novo status
     * @param string $comment Comentário da alteração
     * @param bool $stopOnFirstError Parar no primeiro erro
     * @param int $tenantId ID do tenant
     * @return ServiceResult
     */
    public function bulkUpdateStatus(
        array $budgetIds,
        string $status,
        string $comment,
        bool $stopOnFirstError,
        int $tenantId,
    ): ServiceResult {
        try {
            // Valida status
            if ( !$this->isValidBudgetStatus( $status ) ) {
                return ServiceResult::error( 'Status de orçamento inválido: ' . $status );
            }

            // Usa o método do repositório para atualização em lote
            $updatedCount = $this->repository->bulkUpdateStatus( $budgetIds, $status, $tenantId, $this->authUser()?->id ?? 0 );

            $result = [
                'updated_count' => $updatedCount,
                'failed_count'  => count( $budgetIds ) - $updatedCount,
                'total_count'   => count( $budgetIds )
            ];

            return ServiceResult::success( $result, 'Atualização em lote concluída.' );

        } catch ( \Exception $e ) {
            return ServiceResult::error( 'Erro na atualização em lote: ' . $e->getMessage() );
        }
    }

    /**
     * Retorna estatísticas de orçamentos.
     *
     * @param int $tenantId ID do tenant
     * @return ServiceResult
     */
    public function getBudgetStats( int $tenantId ): ServiceResult
    {
        try {
            // Busca estatísticas básicas
            $stats = $this->repository->getConversionStats( $tenantId );

            // Busca breakdown por status
            $statusBreakdown = $this->getStatusBreakdown( $tenantId );

            $stats[ 'status_breakdown' ] = $statusBreakdown;

            return ServiceResult::success( $stats, 'Estatísticas obtidas com sucesso.' );

        } catch ( \Exception $e ) {
            return ServiceResult::error( 'Erro ao obter estatísticas: ' . $e->getMessage() );
        }
    }

    /**
     * Valida dados do orçamento.
     *
     * @param array $data Dados a validar
     * @param bool $isCreate Se é criação (true) ou atualização (false)
     * @throws InvalidArgumentException
     */
    private function validateBudgetData( array $data, bool $isCreate = true ): void
    {
        if ( $isCreate && empty( $data[ 'customer_id' ] ) ) {
            throw new InvalidArgumentException( 'Cliente é obrigatório.' );
        }
    }

    /**
     * Valida se o status é válido para orçamentos.
     *
     * @param string $status Status a validar
     * @return bool
     */
    private function isValidBudgetStatus( string $status ): bool
    {
        return BudgetStatus::tryFrom( $status ) !== null;
    }

    /**
     * Gera código único para orçamento.
     *
     * @param int $tenantId ID do tenant
     * @return string Código único
     */
    private function generateUniqueBudgetCode( int $tenantId ): string
    {
        $date   = date( 'Ymd' ); // YYYYMMDD
        $prefix = 'ORC-' . $date;

        // Busca o último código do dia para determinar o sequencial
        $lastCode = $this->repository->getLastBudgetCodeByPrefix( $prefix, $tenantId );

        if ( $lastCode ) {
            // Extrai o sequencial do último código (últimos 4 dígitos)
            $lastSequential = (int) substr( $lastCode, -4 );
            $newSequential  = $lastSequential + 1;
        } else {
            $newSequential = 1;
        }

        // Garante que o sequencial tenha 4 dígitos
        $code = $prefix . str_pad( (string) $newSequential, 4, '0', STR_PAD_LEFT );

        return $code;
    }

    /**
     * Retorna breakdown de orçamentos por status.
     *
     * @param int $tenantId ID do tenant
     * @return array
     */
    private function getStatusBreakdown( int $tenantId ): array
    {
        $breakdown = [];

        foreach ( BudgetStatus::cases() as $status ) {
            $count                       = $this->repository->countByStatus( $status->value );
            $breakdown[ $status->value ] = $count;
        }

        return $breakdown;
    }

    /**
     * Define filtros suportados pelo serviço.
     *
     * @return array
     */
    protected function getSupportedFilters(): array
    {
        return [
            'id', 'code', 'title', 'description', 'total', 'customer_id',
            'status', 'created_at', 'updated_at', 'date_from', 'date_to'
        ];
    }

    /**
     * Gera código único para orçamento no padrão ORC-YYYYMMDDXXXX.
     *
     * @return string
     */
    private function generateUniqueCode(): string
    {
        return DB::transaction( function () {
            $today  = date( 'Ymd' );
            $prefix = "ORC-{$today}";

            // Buscar último código do dia com lock
            $lastBudget = Budget::where( 'code', 'LIKE', "{$prefix}%" )
                ->lockForUpdate()
                ->orderBy( 'code', 'desc' )
                ->first();

            if ( !$lastBudget ) {
                return "{$prefix}0001";
            }

            // Extrair sequencial e incrementar
            $lastSequential = (int) substr( $lastBudget->code, -4 );
            $newSequential  = str_pad( $lastSequential + 1, 4, '0', STR_PAD_LEFT );

            return "{$prefix}{$newSequential}";
        } );
    }

}
