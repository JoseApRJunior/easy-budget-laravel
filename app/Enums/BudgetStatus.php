<?php

declare(strict_types=1);

namespace App\Enums;

enum BudgetStatus: string implements \App\Contracts\Interfaces\StatusEnumInterface
{
    case DRAFT     = 'draft';
    case PENDING   = 'pending';
    case APPROVED  = 'approved';
    case REJECTED  = 'rejected';
    case CANCELLED = 'cancelled';
    case COMPLETED = 'completed';

    /**
     * Retorna uma descrição para cada status
     *
     * @return string
     */
    public function getDescription(): string
    {
        return match ( $this ) {
            self::DRAFT     => 'Orçamento em elaboração',
            self::PENDING   => 'Aguardando aprovação do cliente',
            self::APPROVED  => 'Orçamento aprovado',
            self::REJECTED  => 'Orçamento rejeitado',
            self::CANCELLED => 'Orçamento cancelado',
            self::COMPLETED => 'Orçamento concluído'
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
            self::DRAFT     => '#6c757d', // Cinza
            self::PENDING   => '#ffc107', // Amarelo
            self::APPROVED  => '#28a745', // Verde
            self::REJECTED  => '#dc3545', // Vermelho
            self::CANCELLED => '#6c757d', // Cinza
            self::COMPLETED => '#007bff', // Azul
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
            self::DRAFT     => 'bi-pencil-square',
            self::PENDING   => 'bi-hourglass-split',
            self::APPROVED  => 'bi-check-circle-fill',
            self::REJECTED  => 'bi-x-circle-fill',
            self::CANCELLED => 'bi-x-circle',
            self::COMPLETED => 'bi-check-circle',
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
            self::DRAFT, self::PENDING                                       => true,
            self::APPROVED, self::REJECTED, self::CANCELLED, self::COMPLETED => false,
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
            self::APPROVED, self::REJECTED, self::CANCELLED, self::COMPLETED => true,
            self::DRAFT, self::PENDING                                       => false,
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
            'value'       => $this->value,
            'description' => $this->getDescription(),
            'color'       => $this->getColor(),
            'icon'        => $this->getIcon(),
            'is_active'   => $this->isActive(),
            'is_finished' => $this->isFinished(),
            'can_edit'    => $this->canEdit(),
            'can_delete'  => $this->canDelete(),
        ];
    }

    /**
     * Cria instância do enum a partir de string
     *
     * @param string $value Valor do status
     * @return BudgetStatus|null Instância do enum ou null se inválido
     */
    public static function fromString( string $value ): ?self
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
            // Ordem: DRAFT, PENDING, APPROVED, REJECTED, CANCELLED, COMPLETED
            $order = [
                self::DRAFT->value     => 1,
                self::PENDING->value   => 2,
                self::APPROVED->value  => 3,
                self::REJECTED->value  => 4,
                self::CANCELLED->value => 5,
                self::COMPLETED->value => 6,
            ];
            return ( $order[ $a->value ] ?? 99 ) <=> ( $order[ $b->value ] ?? 99 );
        } );

        if ( !$includeFinished ) {
            $statuses = array_filter( $statuses, function ( BudgetStatus $status ) {
                return !$status->isFinished();
            } );
        }

        return array_values( $statuses );
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

        foreach ( $statuses as $status ) {
            if ( $status->isActive() ) {
                $active++;
            } elseif ( $status->isFinished() ) {
                $finished++;
            }
        }

        return [
            'total'               => $total,
            'active'              => $active,
            'finished'            => $finished,
            'active_percentage'   => $total > 0 ? round( ( $active / $total ) * 100, 1 ) : 0,
            'finished_percentage' => $total > 0 ? round( ( $finished / $total ) * 100, 1 ) : 0,
        ];
    }

    public function canEdit(): bool
    {
        return match ( $this ) {
            self::DRAFT, self::PENDING => true,
            default                    => false
        };
    }

    public function canDelete(): bool
    {
        return match ( $this ) {
            self::DRAFT, self::CANCELLED => true,
            default                      => false
        };
    }

    public function canTransitionTo( self $newStatus ): bool
    {
        return match ( $this ) {
            self::DRAFT    => in_array( $newStatus, [ self::PENDING, self::CANCELLED ] ),
            self::PENDING  => in_array( $newStatus, [ self::APPROVED, self::REJECTED, self::CANCELLED ] ),
            self::APPROVED => in_array( $newStatus, [ self::COMPLETED, self::CANCELLED ] ),
            self::REJECTED => in_array( $newStatus, [ self::CANCELLED ] ),
            default        => false
        };
    }

    public static function values(): array
    {
        return array_column( self::cases(), 'value' );
    }

    public function label(): string
    {
        return match ( $this ) {
            self::DRAFT     => 'Rascunho',
            self::PENDING   => 'Pendente',
            self::APPROVED  => 'Aprovado',
            self::REJECTED  => 'Rejeitado',
            self::CANCELLED => 'Cancelado',
            self::COMPLETED => 'Concluído'
        };
    }

}
