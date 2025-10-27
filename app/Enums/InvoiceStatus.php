<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Enum que representa os possíveis status de uma fatura
 *
 * Este enum define todos os status disponíveis para as faturas
 * conforme especificado na estrutura da tabela invoices e invoice_statuses.
 *
 * Funcionalidades disponíveis:
 * - Descrições detalhadas de cada status
 * - Cores e ícones para interface
 * - Controle de fluxo e transições válidas
 * - Verificação de status ativo/finalizado
 * - Metadados completos para cada status
 *
 * @package App\Enums
 *
 * @example Uso básico:
 * ```php
 * $status = InvoiceStatus::PENDING;
 * echo $status->getDescription(); // "Fatura pendente de pagamento"
 * echo $status->getColor(); // "#ffc107"
 * ```
 *
 * @example Controle de fluxo:
 * ```php
 * $currentStatus = InvoiceStatus::PENDING;
 * $nextStatus = $currentStatus->getNextStatus(); // InvoiceStatus::PAID
 *
 * if ($currentStatus->canTransitionTo(InvoiceStatus::PAID)) {
 *     // Realizar transição
 * }
 * ```
 *
 * @example Uso em collections/queries:
 * ```php
 * $pendingInvoices = InvoiceStatus::getPending();
 * $paidInvoices = InvoiceStatus::getPaid();
 *
 * $invoices = Invoice::whereIn('status', $pendingInvoices)->get();
 * ```
 */
enum InvoiceStatus: string
{
    /** Fatura pendente de pagamento */
    case PENDING = 'PENDING';

    /** Fatura paga */
    case PAID = 'PAID';

    /** Fatura cancelada */
    case CANCELLED = 'CANCELLED';

    /** Fatura vencida */
    case OVERDUE = 'OVERDUE';

    /**
     * Retorna uma descrição para cada status
     *
     * @return string
     */
    public function getDescription(): string
    {
        return match ( $this ) {
            self::PENDING   => 'Fatura pendente de pagamento',
            self::PAID      => 'Fatura paga',
            self::CANCELLED => 'Fatura cancelada',
            self::OVERDUE   => 'Fatura vencida',
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
            self::PENDING   => '#ffc107', // Amarelo
            self::PAID      => '#198754', // Verde escuro
            self::CANCELLED => '#dc3545', // Vermelho
            self::OVERDUE   => '#6f42c1', // Roxo
        };
    }

    /**
     * Retorna o ícone associado a cada status
     *
     * @return string Nome do ícone para interface (Bootstrap Icons)
     */
    public function getIcon(): string
    {
        return match ( $this ) {
            self::PENDING   => 'bi-hourglass-split',
            self::PAID      => 'bi-check-circle-fill',
            self::CANCELLED => 'bi-x-circle-fill',
            self::OVERDUE   => 'bi-calendar-x-fill',
        };
    }

    /**
     * Verifica se o status indica que a fatura está pendente
     *
     * @return bool True se a fatura estiver pendente
     */
    public function isPending(): bool
    {
        return match ( $this ) {
            self::PENDING, self::OVERDUE => true,
            self::PAID, self::CANCELLED  => false,
        };
    }

    /**
     * Verifica se o status indica que a fatura foi finalizada
     *
     * @return bool True se a fatura estiver finalizada
     */
    public function isFinished(): bool
    {
        return match ( $this ) {
            self::PAID, self::CANCELLED  => true,
            self::PENDING, self::OVERDUE => false,
        };
    }

    /**
     * Verifica se o status indica que a fatura pode ser cobrada
     *
     * @return bool True se a fatura estiver em estado cobrável
     */
    public function isChargeable(): bool
    {
        return match ( $this ) {
            self::PENDING                              => true,
            self::PAID, self::CANCELLED, self::OVERDUE => false,
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
            self::PENDING,
            self::PAID,
            self::CANCELLED,
            self::OVERDUE,
        ];
    }

    /**
     * Retorna apenas os status pendentes
     *
     * @return array<string> Lista de status pendentes
     */
    public static function getPending(): array
    {
        return [
            self::PENDING,
            self::OVERDUE,
        ];
    }

    /**
     * Retorna apenas os status pagos
     *
     * @return array<string> Lista de status pagos
     */
    public static function getPaid(): array
    {
        return [
            self::PAID,
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
            self::PAID,
            self::CANCELLED,
        ];
    }

    /**
     * Retorna apenas os status cobráveis
     *
     * @return array<string> Lista de status cobráveis
     */
    public static function getChargeable(): array
    {
        return [
            self::PENDING,
        ];
    }

    /**
     * Retorna o próximo status lógico na sequência de cobrança
     *
     * @return InvoiceStatus|null Próximo status ou null se for final
     */
    public function getNextStatus(): ?InvoiceStatus
    {
        return match ( $this ) {
            self::PENDING => self::PAID,
            default       => null, // Status finais não têm próximo
        };
    }

    /**
     * Retorna o status anterior lógico na sequência
     *
     * @return InvoiceStatus|null Status anterior ou null se for inicial
     */
    public function getPreviousStatus(): ?InvoiceStatus
    {
        return match ( $this ) {
            self::PAID => self::PENDING,
            default    => null, // Status iniciais não têm anterior
        };
    }

    /**
     * Verifica se é possível transitar para um determinado status
     *
     * @param InvoiceStatus $targetStatus Status alvo
     * @return bool True se a transição for válida
     */
    public function canTransitionTo( InvoiceStatus $targetStatus ): bool
    {
        // Define transições válidas usando strings como chaves
        $validTransitions = [
            self::PENDING->value   => [ self::PAID->value, self::CANCELLED->value, self::OVERDUE->value ],
            self::PAID->value      => [], // Status final
            self::CANCELLED->value => [], // Status final
            self::OVERDUE->value   => [ self::PAID->value, self::CANCELLED->value ],
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
            self::OVERDUE   => 1, // Vencidas têm maior prioridade
            self::PENDING   => 2,
            self::PAID      => 3,
            self::CANCELLED => 4,
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
            'is_pending'     => $this->isPending(),
            'is_finished'    => $this->isFinished(),
            'is_chargeable'  => $this->isChargeable(),
            'priority_order' => $this->getPriorityOrder(),
        ];
    }

    /**
     * Cria instância do enum a partir de string
     *
     * @param string $value Valor do status
     * @return InvoiceStatus|null Instância do enum ou null se inválido
     */
    public static function fromString( string $value ): ?InvoiceStatus
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
     * @param bool $includeOverdue Incluir status vencidos
     * @return array<string, string> Array associativo [valor => descrição]
     */
    public static function getOptions( bool $includeFinished = true, bool $includeOverdue = true ): array
    {
        $options = [];

        foreach ( self::cases() as $status ) {
            if ( !$includeFinished && $status->isFinished() ) {
                continue;
            }
            if ( !$includeOverdue && $status === self::OVERDUE ) {
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
     * @return array<InvoiceStatus> Status ordenados por prioridade
     */
    public static function getOrdered( bool $includeFinished = true ): array
    {
        $statuses = self::cases();

        usort( $statuses, function ( InvoiceStatus $a, InvoiceStatus $b ) {
            return $a->getPriorityOrder() <=> $b->getPriorityOrder();
        } );

        if ( !$includeFinished ) {
            $statuses = array_filter( $statuses, function ( InvoiceStatus $status ) {
                return !$status->isFinished();
            } );
        }

        return array_values( $statuses );
    }

    /**
     * Valida se uma transição de status é permitida
     *
     * @param InvoiceStatus $fromStatus Status atual
     * @param InvoiceStatus $toStatus Status alvo
     * @return bool True se transição for válida
     */
    public static function isValidTransition( InvoiceStatus $fromStatus, InvoiceStatus $toStatus ): bool
    {
        // Define transições válidas usando strings como chaves
        $validTransitions = [
            self::PENDING->value   => [ self::PAID->value, self::CANCELLED->value, self::OVERDUE->value ],
            self::PAID->value      => [], // Status final
            self::CANCELLED->value => [], // Status final
            self::OVERDUE->value   => [ self::PAID->value, self::CANCELLED->value ],
        ];

        return in_array( $toStatus->value, $validTransitions[ $fromStatus->value ] ?? [] );
    }

    /**
     * Calcula métricas de status para dashboards
     *
     * @param array<InvoiceStatus> $statuses Lista de status para análise
     * @return array<string, int> Métricas [pendente, pago, cancelado, vencido, total]
     */
    public static function calculateMetrics( array $statuses ): array
    {
        $total     = count( $statuses );
        $pending   = 0;
        $paid      = 0;
        $cancelled = 0;
        $overdue   = 0;

        foreach ( $statuses as $status ) {
            switch ( $status ) {
                case self::PENDING:
                    $pending++;
                    break;
                case self::PAID:
                    $paid++;
                    break;
                case self::CANCELLED:
                    $cancelled++;
                    break;
                case self::OVERDUE:
                    $overdue++;
                    break;
            }
        }

        return [
            'total'                => $total,
            'pending'              => $pending,
            'paid'                 => $paid,
            'cancelled'            => $cancelled,
            'overdue'              => $overdue,
            'pending_percentage'   => $total > 0 ? round( ( $pending / $total ) * 100, 1 ) : 0,
            'paid_percentage'      => $total > 0 ? round( ( $paid / $total ) * 100, 1 ) : 0,
            'cancelled_percentage' => $total > 0 ? round( ( $cancelled / $total ) * 100, 1 ) : 0,
            'overdue_percentage'   => $total > 0 ? round( ( $overdue / $total ) * 100, 1 ) : 0,
        ];
    }

}
