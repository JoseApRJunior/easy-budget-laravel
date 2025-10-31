<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Enum que representa os possíveis status de um orçamento
 *
 * Este enum define todos os status disponíveis para os orçamentos
 * conforme especificado na análise do sistema antigo.
 *
 * Implementa StatusEnumInterface para garantir consistência
 * com outros enums de status do sistema.
 *
 * Funcionalidades disponíveis:
 * - Descrições detalhadas de cada status
 * - Cores e ícones para interface
 * - Controle de fluxo e transições válidas
 * - Verificação de status ativo/finalizado
 * - Metadados completos para cada status
 * - Controle de edição/exclusão baseado no status
 *
 * @package App\Enums
 * @implements \App\Contracts\Interfaces\StatusEnumInterface
 *
 * @example Uso básico:
 * ```php
 * $status = BudgetStatus::DRAFT;
 * echo $status->getDescription(); // "Orçamento em rascunho"
 * echo $status->getColor(); // "#6C757D"
 * ```
 *
 * @example Controle de fluxo:
 * ```php
 * $currentStatus = BudgetStatus::PENDING;
 * $nextStatus = $currentStatus->getNextStatus(); // BudgetStatus::APPROVED
 *
 * if ($currentStatus->canTransitionTo(BudgetStatus::APPROVED)) {
 *     // Realizar transição
 * }
 * ```
 *
 * @example Uso em collections/queries:
 * ```php
 * $activeBudgets = BudgetStatus::getActive();
 * $finishedBudgets = BudgetStatus::getFinished();
 *
 * $budgets = Budget::whereIn('status', $activeBudgets)->get();
 * ```
 */
enum BudgetStatus: string implements \App\Contracts\Interfaces\StatusEnumInterface
{
    /** Orçamento em rascunho, ainda não enviado */
    case DRAFT = 'DRAFT';

    /** Orçamento enviado, aguardando aprovação */
    case PENDING = 'PENDING';

    /** Orçamento aprovado pelo cliente */
    case APPROVED = 'APPROVED';

    /** Orçamento rejeitado pelo cliente */
    case REJECTED = 'REJECTED';

    /** Orçamento cancelado */
    case CANCELLED = 'CANCELLED';

    /** Orçamento concluído (todos serviços finalizados) */
    case COMPLETED = 'COMPLETED';

    /** Orçamento expirado */
    case EXPIRED = 'EXPIRED';

    /**
     * Retorna uma descrição para cada status
     *
     * @return string
     */
    public function getDescription(): string
    {
        return match ( $this ) {
            self::DRAFT     => 'Orçamento em rascunho',
            self::PENDING   => 'Aguardando aprovação do cliente',
            self::APPROVED  => 'Orçamento aprovado',
            self::REJECTED  => 'Orçamento rejeitado',
            self::CANCELLED => 'Orçamento cancelado',
            self::COMPLETED => 'Orçamento concluído',
            self::EXPIRED   => 'Orçamento expirado',
        };
    }

    /**
     * Retorna a cor associada a cada status para interface
     *
     * @return string Cor em formato hexadecimal
     */
    public function getColor(): string
    {
        return match ( $this ) {
            self::DRAFT     => '#6C757D', // Cinza
            self::PENDING   => '#FFC107', // Amarelo
            self::APPROVED  => '#28A745', // Verde
            self::REJECTED  => '#DC3545', // Vermelho
            self::CANCELLED => '#6C757D', // Cinza escuro
            self::COMPLETED => '#007BFF', // Azul
            self::EXPIRED   => '#FFA500', // Laranja
        };
    }

    /**
     * Retorna o ícone associado a cada status
     *
     * @return string Nome do ícone para interface
     */
    public function getIcon(): string
    {
        return match ( $this ) {
            self::DRAFT     => 'edit',
            self::PENDING   => 'clock',
            self::APPROVED  => 'check-circle',
            self::REJECTED  => 'times-circle',
            self::CANCELLED => 'ban',
            self::COMPLETED => 'check-double',
            self::EXPIRED   => 'calendar-times',
        };
    }

    /**
     * Verifica se o status indica que o orçamento está ativo
     *
     * @return bool True se o orçamento estiver ativo
     */
    public function isActive(): bool
    {
        return match ( $this ) {
            self::DRAFT, self::PENDING                                                      => true,
            self::APPROVED, self::REJECTED, self::CANCELLED, self::COMPLETED, self::EXPIRED => false,
        };
    }

    /**
     * Verifica se o status indica que o orçamento foi finalizado
     *
     * @return bool True se o orçamento estiver finalizado
     */
    public function isFinished(): bool
    {
        return match ( $this ) {
            self::APPROVED, self::REJECTED, self::CANCELLED, self::COMPLETED, self::EXPIRED => true,
            self::DRAFT, self::PENDING                                                      => false,
        };
    }

    /**
     * Verifica se o orçamento pode ser editado
     *
     * @return bool True se pode ser editado
     */
    public function canBeEdited(): bool
    {
        return match ( $this ) {
            self::DRAFT                                                                                    => true,
            self::PENDING, self::APPROVED, self::REJECTED, self::CANCELLED, self::COMPLETED, self::EXPIRED => false,
        };
    }

    /**
     * Verifica se o orçamento pode ser deletado
     *
     * @return bool True se pode ser deletado
     */
    public function canBeDeleted(): bool
    {
        return match ( $this ) {
            self::DRAFT                                                                                    => true,
            self::PENDING, self::APPROVED, self::REJECTED, self::CANCELLED, self::COMPLETED, self::EXPIRED => false,
        };
    }

    /**
     * Retorna todos os status disponíveis como array
     *
     * @return array<string> Lista de todos os status
     */
    public static function getAll(): array
    {
        return [
            self::DRAFT,
            self::PENDING,
            self::APPROVED,
            self::REJECTED,
            self::CANCELLED,
            self::COMPLETED,
            self::EXPIRED,
        ];
    }

    /**
     * Retorna apenas os status ativos
     *
     * @return array<string> Lista de status ativos
     */
    public static function getActive(): array
    {
        return [
            self::DRAFT,
            self::PENDING,
        ];
    }

    /**
     * Retorna apenas os status finalizados
     *
     * @return array<string> Lista de status finalizados
     */
    public static function getFinished(): array
    {
        return [
            self::APPROVED,
            self::REJECTED,
            self::CANCELLED,
            self::COMPLETED,
            self::EXPIRED,
        ];
    }

    /**
     * Retorna o próximo status lógico na sequência
     *
     * @return BudgetStatus|null Próximo status ou null se for final
     */
    public function getNextStatus(): ?BudgetStatus
    {
        return match ( $this ) {
            self::DRAFT    => self::PENDING,
            self::PENDING  => self::APPROVED,
            self::APPROVED => self::COMPLETED,
            default        => null, // Status finais não têm próximo
        };
    }

    /**
     * Retorna o status anterior lógico na sequência
     *
     * @return BudgetStatus|null Status anterior ou null se for inicial
     */
    public function getPreviousStatus(): ?BudgetStatus
    {
        return match ( $this ) {
            self::PENDING   => self::DRAFT,
            self::APPROVED  => self::PENDING,
            self::COMPLETED => self::APPROVED,
            default         => null, // Status iniciais não têm anterior
        };
    }

    /**
     * Verifica se é possível transitar para um determinado status
     *
     * @param BudgetStatus $targetStatus Status alvo
     * @return bool True se a transição for válida
     */
    public function canTransitionTo( BudgetStatus $targetStatus ): bool
    {
        // Define transições válidas usando strings como chaves
        $validTransitions = [
            self::DRAFT->value     => [ self::PENDING->value, self::CANCELLED->value ],
            self::PENDING->value   => [ self::APPROVED->value, self::REJECTED->value, self::CANCELLED->value, self::EXPIRED->value ],
            self::APPROVED->value  => [ self::COMPLETED->value, self::CANCELLED->value ],
            self::REJECTED->value  => [ self::DRAFT->value ], // Pode reabrir
            self::CANCELLED->value => [ self::DRAFT->value ], // Pode reabrir
            self::EXPIRED->value   => [ self::DRAFT->value ], // Pode reabrir
            self::COMPLETED->value => [], // Status final
        ];

        return in_array( $targetStatus->value, $validTransitions[ $this->value ] ?? [] );
    }

    /**
     * Retorna a ordem de prioridade para exibição
     *
     * @return int Ordem (menor número = maior prioridade)
     */
    public function getPriorityOrder(): int
    {
        return match ( $this ) {
            self::PENDING   => 1, // Maior prioridade - aguardando ação
            self::DRAFT     => 2,
            self::APPROVED  => 3,
            self::COMPLETED => 4,
            self::REJECTED  => 5,
            self::CANCELLED => 6,
            self::EXPIRED   => 7,
        };
    }

    /**
     * Retorna metadados completos do status
     *
     * @return array<string, mixed> Array com descrição, cor, ícone e flags
     */
    public function getMetadata(): array
    {
        return [
            'value'          => $this->value,
            'description'    => $this->getDescription(),
            'color'          => $this->getColor(),
            'icon'           => $this->getIcon(),
            'is_active'      => $this->isActive(),
            'is_finished'    => $this->isFinished(),
            'can_be_edited'  => $this->canBeEdited(),
            'can_be_deleted' => $this->canBeDeleted(),
            'priority_order' => $this->getPriorityOrder(),
        ];
    }

    /**
     * Cria instância do enum a partir de string
     *
     * @param string $value Valor do status
     * @return BudgetStatus|null Instância do enum ou null se inválido
     */
    public static function fromString( string $value ): ?BudgetStatus
    {
        foreach ( self::cases() as $case ) {
            if ( $case->value === $value ) {
                return $case;
            }
        }
        return null;
    }

    /**
     * Retorna opções formatadas para uso em formulários/selects
     *
     * @param bool $includeFinished Incluir status finalizados
     * @return array<string, string> Array associativo [valor => descrição]
     */
    public static function getOptions( bool $includeFinished = true ): array
    {
        $options = [];

        foreach ( self::cases() as $status ) {
            if ( !$includeFinished && $status->isFinished() ) {
                continue;
            }
            $options[ $status->value ] = $status->getDescription();
        }

        return $options;
    }

    /**
     * Ordena status por prioridade para exibição
     *
     * @param bool $includeFinished Incluir status finalizados na ordenação
     * @return array<BudgetStatus> Status ordenados por prioridade
     */
    public static function getOrdered( bool $includeFinished = true ): array
    {
        $statuses = self::cases();

        usort( $statuses, function ( BudgetStatus $a, BudgetStatus $b ) {
            return $a->getPriorityOrder() <=> $b->getPriorityOrder();
        } );

        if ( !$includeFinished ) {
            $statuses = array_filter( $statuses, function ( BudgetStatus $status ) {
                return !$status->isFinished();
            } );
        }

        return array_values( $statuses );
    }

    /**
     * Valida se uma transição de status é permitida
     *
     * @param BudgetStatus $fromStatus Status atual
     * @param BudgetStatus $toStatus Status alvo
     * @return bool True se transição for válida
     */
    public static function isValidTransition( BudgetStatus $fromStatus, BudgetStatus $toStatus ): bool
    {
        // Define transições válidas usando strings como chaves
        $validTransitions = [
            self::DRAFT->value     => [ self::PENDING->value, self::CANCELLED->value ],
            self::PENDING->value   => [ self::APPROVED->value, self::REJECTED->value, self::CANCELLED->value, self::EXPIRED->value ],
            self::APPROVED->value  => [ self::COMPLETED->value, self::CANCELLED->value ],
            self::REJECTED->value  => [ self::DRAFT->value ], // Pode reabrir
            self::CANCELLED->value => [ self::DRAFT->value ], // Pode reabrir
            self::EXPIRED->value   => [ self::DRAFT->value ], // Pode reabrir
            self::COMPLETED->value => [], // Status final
        ];

        return in_array( $toStatus->value, $validTransitions[ $fromStatus->value ] ?? [] );
    }

    /**
     * Calcula métricas de status para dashboards
     *
     * @param array<BudgetStatus> $statuses Lista de status para análise
     * @return array<string, int> Métricas [ativo, finalizado, total]
     */
    public static function calculateMetrics( array $statuses ): array
    {
        $total    = count( $statuses );
        $active   = 0;
        $finished = 0;
        $approved = 0;
        $rejected = 0;
        $pending  = 0;

        foreach ( $statuses as $status ) {
            if ( $status->isActive() ) {
                $active++;
            } elseif ( $status->isFinished() ) {
                $finished++;
            }

            match ( $status ) {
                self::APPROVED => $approved++,
                self::REJECTED => $rejected++,
                self::PENDING  => $pending++,
                default        => null,
            };
        }

        return [
            'total'               => $total,
            'active'              => $active,
            'finished'            => $finished,
            'approved'            => $approved,
            'rejected'            => $rejected,
            'pending'             => $pending,
            'active_percentage'   => $total > 0 ? round( ( $active / $total ) * 100, 1 ) : 0,
            'finished_percentage' => $total > 0 ? round( ( $finished / $total ) * 100, 1 ) : 0,
            'approved_percentage' => $total > 0 ? round( ( $approved / $total ) * 100, 1 ) : 0,
            'rejected_percentage' => $total > 0 ? round( ( $rejected / $total ) * 100, 1 ) : 0,
            'conversion_rate'     => $total > 0 ? round( ( $approved / $total ) * 100, 1 ) : 0,
        ];
    }

}
