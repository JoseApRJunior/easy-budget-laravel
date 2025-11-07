<?php

declare(strict_types=1);

namespace App\Services\Application;

use App\Enums\BudgetStatus;
use App\Enums\OperationStatus;
use App\Support\ServiceResult;

/**
 * Serviço para gerenciamento de status de orçamentos usando enums.
 *
 * Esta classe gerencia operações relacionadas a BudgetStatus,
 * substituindo o modelo BudgetStatus por enums para melhor type safety
 * e performance. Como os status agora são enums, este serviço foca em
 * fornecer métodos utilitários para trabalhar com os status de forma type-safe.
 *
 * @package App\Services\Application
 */
class BudgetStatusService
{

    /**
     * Busca status por slug
     */
    public function getStatusBySlug( string $slug ): ServiceResult
    {
        $status = BudgetStatus::tryFrom( $slug );

        if ( !$status ) {
            return $this->error( 'Status não encontrado', [ 'slug' => $slug ] );
        }

        return $this->success( $status, 'Status encontrado com sucesso' );
    }

    /**
     * Busca todos os status ativos
     */
    public function getActiveStatuses(): ServiceResult
    {
        $statuses = array_filter( BudgetStatus::cases(), fn( $status ) => $status->isActive() );

        return $this->success( array_values( $statuses ), 'Status ativos recuperados com sucesso' );
    }

    /**
     * Busca status ordenados por campo
     */
    public function getOrderedStatuses( string $field = 'order_index', string $direction = 'asc' ): ServiceResult
    {
        $statuses = BudgetStatus::cases();

        usort( $statuses, function ( $a, $b ) use ( $field, $direction ) {
            $valueA = $this->getFieldValue( $a, $field );
            $valueB = $this->getFieldValue( $b, $field );

            return ( $direction === 'desc' ) ? $valueB <=> $valueA : $valueA <=> $valueB;
        } );

        return $this->success( $statuses, 'Status ordenados recuperados com sucesso' );
    }

    /**
     * Busca status por nome
     */
    public function getStatusByName( string $name ): ServiceResult
    {
        $status = array_filter( BudgetStatus::cases(), fn( $status ) => $status->getDescription() === $name );

        if ( empty( $status ) ) {
            return $this->error( 'Status não encontrado', [ 'name' => $name ] );
        }

        return $this->success( array_shift( $status ), 'Status encontrado com sucesso' );
    }

    /**
     * Verifica se status existe por slug
     */
    public function statusExists( string $slug ): ServiceResult
    {
        $exists = BudgetStatus::tryFrom( $slug ) !== null;

        return $this->success( $exists, $exists ? 'Status existe' : 'Status não existe' );
    }

    /**
     * Conta status ativos
     */
    public function countActiveStatuses(): ServiceResult
    {
        $count = count( array_filter( BudgetStatus::cases(), fn( $status ) => $status->isActive() ) );

        return $this->success( $count, 'Contagem de status ativos realizada' );
    }

    /**
     * Busca status por cor
     */
    public function getStatusesByColor( string $color ): ServiceResult
    {
        $statuses = array_filter( BudgetStatus::cases(), fn( $status ) => $status->getColor() === $color );

        return $this->success( array_values( $statuses ), 'Status por cor recuperados com sucesso' );
    }

    /**
     * Busca status por range de order_index
     */
    public function getStatusesByOrderIndexRange( int $min, int $max ): ServiceResult
    {
        $statuses = array_filter( BudgetStatus::cases(), function ( $status ) use ( $min, $max ) {
            return $status->getPriorityOrder() >= $min && $status->getPriorityOrder() <= $max;
        } );

        return $this->success( array_values( $statuses ), 'Status por range recuperados com sucesso' );
    }

    /**
     * Busca status por ID (compatibilidade - usa o valor do enum)
     */
    public function getStatusById( int $id ): ServiceResult
    {
        $status = array_filter( BudgetStatus::cases(), fn( $status ) => $status->getPriorityOrder() === $id );

        if ( empty( $status ) ) {
            return $this->error( 'Status não encontrado', [ 'id' => $id ] );
        }

        return $this->success( array_shift( $status ), 'Status encontrado com sucesso' );
    }

    /**
     * Retorna todos os status disponíveis
     */
    public function getAllStatuses(): ServiceResult
    {
        $statuses = BudgetStatus::cases();

        return $this->success( $statuses, 'Todos os status recuperados com sucesso' );
    }

    /**
     * Busca status por múltiplos critérios
     */
    public function getStatusesByCriteria( array $criteria ): ServiceResult
    {
        $statuses = array_filter( BudgetStatus::cases(), function ( $status ) use ( $criteria ) {
            foreach ( $criteria as $field => $value ) {
                if ( $this->getFieldValue( $status, $field ) !== $value ) {
                    return false;
                }
            }
            return true;
        } );

        return $this->success( array_values( $statuses ), 'Status por critérios recuperados com sucesso' );
    }

    /**
     * Busca um status por critérios
     */
    public function getOneStatusByCriteria( array $criteria ): ServiceResult
    {
        $statuses = array_filter( BudgetStatus::cases(), function ( $status ) use ( $criteria ) {
            foreach ( $criteria as $field => $value ) {
                if ( $this->getFieldValue( $status, $field ) !== $value ) {
                    return false;
                }
            }
            return true;
        } );

        if ( empty( $statuses ) ) {
            return $this->error( 'Status não encontrado', $criteria );
        }

        return $this->success( array_shift( $statuses ), 'Status encontrado com sucesso' );
    }

    /**
     * Conta status por critérios
     */
    public function countStatusesByCriteria( array $criteria ): ServiceResult
    {
        $count = count( array_filter( BudgetStatus::cases(), function ( $status ) use ( $criteria ) {
            foreach ( $criteria as $field => $value ) {
                if ( $this->getFieldValue( $status, $field ) !== $value ) {
                    return false;
                }
            }
            return true;
        } ) );

        return $this->success( $count, 'Contagem de status realizada' );
    }

    /**
     * Obtém o valor de um campo específico do status
     */
    private function getFieldValue( BudgetStatus $status, string $field ): mixed
    {
        return match ( $field ) {
            'name'           => $status->getDescription(),
            'description'    => $status->getDescription(),
            'color'          => $status->getColor(),
            'icon'           => $status->getIcon(),
            'order_index'    => $status->getPriorityOrder(),
            'priority_order' => $status->getPriorityOrder(),
            'is_active'      => $status->isActive(),
            'value'          => $status->value,
            default          => null,
        };
    }

    /**
     * Valida se um status é válido
     */
    public function validateStatus( string $status ): ServiceResult
    {
        $enumStatus = BudgetStatus::tryFrom( $status );

        if ( !$enumStatus ) {
            return $this->error( 'Status inválido', [ 'status' => $status ] );
        }

        return $this->success( $enumStatus, 'Status válido' );
    }

    /**
     * Retorna informações completas de um status
     */
    public function getStatusInfo( string $slug ): ServiceResult
    {
        $status = BudgetStatus::tryFrom( $slug );

        if ( !$status ) {
            return $this->error( 'Status não encontrado', [ 'slug' => $slug ] );
        }

        $info = [
            'slug'           => $status->value,
            'name'           => $status->getDescription(),
            'description'    => $status->getDescription(),
            'color'          => $status->getColor(),
            'icon'           => $status->getIcon(),
            'order_index'    => $status->getPriorityOrder(),
            'priority_order' => $status->getPriorityOrder(),
            'is_active'      => $status->isActive(),
        ];

        return $this->success( $info, 'Informações do status recuperadas' );
    }

    /**
     * Retorna transições permitidas para um status
     */
    public function getAllowedTransitions( string $currentStatus ): ServiceResult
    {
        $status = BudgetStatus::tryFrom( $currentStatus );

        if ( !$status ) {
            return $this->error( 'Status atual inválido', [ 'status' => $currentStatus ] );
        }

        $transitions = BudgetStatus::getAllowedTransitions( $status->value );

        return $this->success( $transitions, 'Transições permitidas recuperadas' );
    }

    /**
     * Verifica se uma transição é permitida
     */
    public function canTransitionTo( string $currentStatus, string $targetStatus ): ServiceResult
    {
        $current = BudgetStatus::tryFrom( $currentStatus );
        $target  = BudgetStatus::tryFrom( $targetStatus );

        if ( !$current || !$target ) {
            return $this->error( 'Status inválido', [
                'current' => $currentStatus,
                'target'  => $targetStatus
            ] );
        }

        $transitions   = BudgetStatus::getAllowedTransitions( $current->value );
        $canTransition = in_array( $target->value, $transitions );

        return $this->success( $canTransition, $canTransition ? 'Transição permitida' : 'Transição não permitida' );
    }

    /**
     * Retorna um ServiceResult de sucesso.
     *
     * @param mixed $data
     * @param string $message
     * @return ServiceResult
     */
    private function success( $data, string $message = 'Operação realizada com sucesso' ): ServiceResult
    {
        return new ServiceResult( OperationStatus::SUCCESS, $message, $data );
    }

    /**
     * Retorna um ServiceResult de erro.
     *
     * @param string $message
     * @param array $context
     * @return ServiceResult
     */
    private function error( string $message, array $context = [] ): ServiceResult
    {
        return new ServiceResult( OperationStatus::ERROR, $message, $context );
    }

    /**
     * Altera o status de um orçamento com validação de transições.
     *
     * @param \App\Models\Budget $budget Orçamentó para alterar status
     * @param string $newStatus Novo status (string ou enum)
     * @param string|null $comments Comentários da alteração
     * @param int|null $userId ID do usuário que está alterando
     * @return ServiceResult
     */
    public function changeStatus( \App\Models\Budget $budget, string $newStatus, ?string $comments = null, ?int $userId = null ): ServiceResult
    {
        try {
            // 1. Validar se o novo status é válido
            $validationResult = $this->validateStatus( $newStatus );
            if ( !$validationResult->isSuccess() ) {
                return $this->error( 'Status inválido', [ 'newStatus' => $newStatus ] );
            }

            $targetStatus = $validationResult->getData();

            // 2. Verificar se o status atual é válido
            $currentStatusString = $budget->status instanceof BudgetStatus ? $budget->status->value : $budget->status;
            $currentStatus       = BudgetStatus::tryFrom( $currentStatusString );

            if ( !$currentStatus ) {
                return $this->error( 'Status atual inválido', [ 'currentStatus' => $currentStatusString ] );
            }

            // 3. Validar se a transição é permitida
            $transitionResult = $this->canTransitionTo( $currentStatusString, $newStatus );
            if ( !$transitionResult->isSuccess() || !$transitionResult->getData() ) {
                return $this->error( 'Transição de status não permitida', [
                    'from' => $currentStatusString,
                    'to'   => $newStatus
                ] );
            }

            // 4. Iniciar transação de banco
            \Illuminate\Support\Facades\DB::beginTransaction();

            try {
                // 5. Atualizar o status do orçamento
                $budget->status = $targetStatus;

                // Adicionar comentários se fornecidos
                if ( $comments ) {
                    $budget->history = $this->addHistoryEntry( $budget->history, [
                        'action'     => 'status_change',
                        'old_status' => $currentStatusString,
                        'new_status' => $newStatus,
                        'comments'   => $comments,
                        'user_id'    => $userId,
                        'timestamp'  => now()->toISOString(),
                    ] );
                }

                // Salvar o orçamento
                if ( !$budget->save() ) {
                    throw new \Exception( 'Falha ao salvar orçamento' );
                }

                // 6. Confirmar transação
                \Illuminate\Support\Facades\DB::commit();

                return $this->success( $budget, sprintf(
                    'Status alterado de %s para %s com sucesso',
                    $currentStatus->getDescription(),
                    $targetStatus->getDescription(),
                ) );

            } catch ( \Exception $e ) {
                // 7. Rollback em caso de erro
                \Illuminate\Support\Facades\DB::rollBack();
                throw $e;
            }

        } catch ( \Exception $e ) {
            return $this->error( 'Erro ao alterar status do orçamento', [
                'message'   => $e->getMessage(),
                'budget_id' => $budget->id,
                'newStatus' => $newStatus
            ] );
        }
    }

    /**
     * Adiciona entrada ao histórico do orçamento.
     *
     * @param string|null $currentHistory
     * @param array $entry
     * @return string
     */
    private function addHistoryEntry( ?string $currentHistory, array $entry ): string
    {
        $history   = $currentHistory ? json_decode( $currentHistory, true ) : [];
        $history[] = $entry;
        return json_encode( $history, JSON_UNESCAPED_UNICODE );
    }

}
