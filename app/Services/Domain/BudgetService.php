<?php

declare(strict_types=1);

namespace App\Services\Domain;

use App\Enums\BudgetStatus;
use App\Models\Budget;
use App\Models\User;
use App\Repositories\BudgetRepository;
use App\Services\Core\Abstracts\AbstractBaseService;
use App\Support\ServiceResult;
use Illuminate\Pagination\LengthAwarePaginator;
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

            // Atualiza status e comentário
            $updatedBudget = $this->repository->update( $budgetId, [
                'status'             => $status,
                'status_comment'     => $comment,
                'status_updated_at'  => now(),
                'status_updated_by'  => $this->authUser()?->id
            ] );

            if ( !$updatedBudget ) {
                return ServiceResult::error( 'Falha ao alterar status do orçamento.' );
            }

            return ServiceResult::success( $updatedBudget, 'Status do orçamento alterado com sucesso.' );

        } catch ( \Exception $e ) {
            return ServiceResult::error( 'Erro ao alterar status: ' . $e->getMessage() );
        }
    }

    /**
     * Manipula mudança de status do orçamento com validações e lógica de negócio.
     *
     * @param int $budgetId ID do orçamento
     * @param string $currentStatusSlug Status atual do orçamento
     * @param string $action Ação a ser executada (approve, reject, revise, etc.)
     * @param int $tenantId ID do tenant
     * @param string|null $comment Comentário da alteração
     * @param bool $emailNotification Se deve enviar notificação por e-mail
     * @return ServiceResult
     */
    public function handleStatusChange(
        int $budgetId,
        string $currentStatusSlug,
        string $action,
        int $tenantId,
        ?string $comment = null,
        bool $emailNotification = false,
    ): ServiceResult {
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

            // Verifica se o orçamento tem serviços associados (exceto para ações específicas)
            $services                 = $budget->services;
            $actionsRequiringServices = [ 'approve', 'reject', 'revise' ];

            if ( in_array( $action, $actionsRequiringServices ) && $services->isEmpty() ) {
                return ServiceResult::error(
                    'Não é possível alterar o status do orçamento sem serviços associados.',
                );
            }

            // Determina o novo status baseado na ação
            $newStatusSlug = $this->determineNewStatusFromAction( $currentStatusSlug, $action );

            if ( !$newStatusSlug ) {
                return ServiceResult::error( 'Ação inválida para o status atual do orçamento.' );
            }

            // Valida se a transição é permitida
            if ( !$this->isValidStatusTransition( $currentStatusSlug, $newStatusSlug ) ) {
                return ServiceResult::error(
                    "Transição de status não permitida: {$currentStatusSlug} -> {$newStatusSlug}",
                );
            }

            // Executa a mudança de status
            $result = $this->changeStatus( $budgetId, $newStatusSlug, $comment, $tenantId );

            if ( !$result->isSuccess() ) {
                return $result;
            }

            // TODO: Implementar envio de notificações por e-mail se necessário
            if ( $emailNotification ) {
                // $this->sendStatusChangeNotification( $budget, $newStatusSlug, $comment );
            }

            return ServiceResult::success(
                $result->getData(),
                'Status do orçamento alterado com sucesso.',
            );

        } catch ( \Exception $e ) {
            return ServiceResult::error( 'Erro ao processar mudança de status: ' . $e->getMessage() );
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
        $requiredFields = [ 'customer_id', 'description', 'total' ];

        foreach ( $requiredFields as $field ) {
            if ( !isset( $data[ $field ] ) || empty( $data[ $field ] ) ) {
                throw new InvalidArgumentException( "Campo obrigatório ausente: {$field}" );
            }
        }

        // Validações específicas
        if ( $data[ 'total' ] <= 0 ) {
            throw new InvalidArgumentException( 'Total deve ser maior que zero' );
        }

        if ( strlen( $data[ 'description' ] ) < 3 ) {
            throw new InvalidArgumentException( 'Descrição deve ter pelo menos 3 caracteres' );
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
        do {
            $code = 'BUD-' . date( 'Y' ) . '-' . strtoupper( Str::random( 6 ) );
        } while ( $this->repository->findByCode( $code, $tenantId ) !== null );

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

}
