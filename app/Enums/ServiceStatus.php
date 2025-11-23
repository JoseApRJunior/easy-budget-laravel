<?php

declare(strict_types=1);

namespace App\Enums;

enum ServiceStatus: string implements \App\Contracts\Interfaces\StatusEnumInterface
{
    /** Serviço em elaboração, permite modificações */
    case DRAFT = 'DRAFT';

    /** Serviço pendente de agendamento */
    case PENDING = 'PENDING';

    /** Serviço em processo de agendamento */
    case SCHEDULING = 'SCHEDULING';

    /** Serviço em preparação */
    case PREPARING = 'PREPARING';

    /** Serviço em andamento */
    case IN_PROGRESS = 'IN_PROGRESS';

    /** Serviço em espera/pausado */
    case ON_HOLD = 'ON_HOLD';

    /** Serviço agendado */
    case SCHEDULED = 'SCHEDULED';

    /** Serviço concluído */
    case COMPLETED = 'COMPLETED';

    /** Serviço concluído parcialmente */
    case PARTIAL = 'PARTIAL';

    /** Serviço cancelado */
    case CANCELLED = 'CANCELLED';

    /** Serviço não realizado */
    case NOT_PERFORMED = 'NOT_PERFORMED';

    /** Serviço expirado */
    case EXPIRED = 'EXPIRED';

    /** Status de aprovação para clientes */
    case APPROVED = 'approved';
    case REJECTED = 'rejected';

    /**
     * Retorna uma descrição para cada status
     *
     * @return string
     */
    public function getDescription(): string
    {
        return match ( $this ) {
            self::DRAFT         => 'Serviço em elaboração, permite modificações',
            self::PENDING       => 'Serviço pendente de agendamento',
            self::SCHEDULING    => 'Serviço em processo de agendamento',
            self::PREPARING     => 'Serviço em preparação',
            self::IN_PROGRESS   => 'Serviço em andamento',
            self::ON_HOLD       => 'Serviço em espera/pausado',
            self::SCHEDULED     => 'Serviço agendado',
            self::COMPLETED     => 'Serviço concluído',
            self::PARTIAL       => 'Serviço concluído parcialmente',
            self::CANCELLED     => 'Serviço cancelado',
            self::NOT_PERFORMED => 'Serviço não realizado',
            self::EXPIRED       => 'Serviço expirado',
            self::APPROVED      => 'Aprovado',
            self::REJECTED      => 'Rejeitado',
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
            self::DRAFT         => '#6c757d', // Cinza
            self::PENDING       => '#ffc107', // Amarelo
            self::SCHEDULING    => '#007bff', // Azul
            self::PREPARING     => '#ffc107', // Amarelo
            self::IN_PROGRESS   => '#007bff', // Azul
            self::ON_HOLD       => '#6c757d', // Cinza
            self::SCHEDULED     => '#007bff', // Azul
            self::COMPLETED     => '#28a745', // Verde
            self::PARTIAL       => '#28a745', // Verde
            self::CANCELLED     => '#dc3545', // Vermelho
            self::NOT_PERFORMED => '#dc3545', // Vermelho
            self::EXPIRED       => '#dc3545', // Vermelho
            self::APPROVED      => '#10B981', // Verde
            self::REJECTED      => '#DC2626', // Vermelho
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
            self::DRAFT         => 'bi-pencil-square',
            self::PENDING       => 'bi-clock',
            self::SCHEDULING    => 'bi-calendar-check',
            self::PREPARING     => 'bi-tools',
            self::IN_PROGRESS   => 'bi-gear',
            self::ON_HOLD       => 'bi-pause-circle',
            self::SCHEDULED     => 'bi-calendar-plus',
            self::COMPLETED     => 'bi-check-circle',
            self::PARTIAL       => 'bi-check-circle-fill',
            self::CANCELLED     => 'bi-x-circle',
            self::NOT_PERFORMED => 'bi-slash-circle',
            self::EXPIRED       => 'bi-calendar-x',
            self::APPROVED      => 'bi-check-circle',
            self::REJECTED      => 'bi-x-circle',
        };
    }

    /**
     * Verifica se o status indica que o serviço está ativo
     *
     * @return bool True se o serviço estiver ativo
     */
    public function isActive(): bool
    {
        return match ( $this ) {
            self::DRAFT, self::PENDING, self::SCHEDULING, self::PREPARING,
            self::IN_PROGRESS, self::ON_HOLD, self::SCHEDULED                  => true,
            self::COMPLETED, self::PARTIAL, self::CANCELLED,
            self::NOT_PERFORMED, self::EXPIRED, self::APPROVED, self::REJECTED => false,
        };
    }

    /**
     * Verifica se o status indica que o serviço foi finalizado
     *
     * @return bool True se o serviço estiver finalizado
     */
    public function isFinished(): bool
    {
        return match ( $this ) {
            self::COMPLETED, self::PARTIAL, self::CANCELLED,
            self::NOT_PERFORMED, self::EXPIRED, self::APPROVED, self::REJECTED => true,
            self::DRAFT, self::PENDING, self::SCHEDULING, self::PREPARING,
            self::IN_PROGRESS, self::ON_HOLD, self::SCHEDULED                  => false,
        };
    }

    /**
     * Verifica se o status indica que o serviço pode ser executado
     *
     * @return bool True se o serviço estiver em estado executável
     */
    public function isExecutable(): bool
    {
        return match ( $this ) {
            self::SCHEDULED, self::IN_PROGRESS                                 => true,
            self::DRAFT, self::PENDING, self::SCHEDULING, self::PREPARING,
            self::ON_HOLD, self::COMPLETED, self::PARTIAL, self::CANCELLED,
            self::NOT_PERFORMED, self::EXPIRED, self::APPROVED, self::REJECTED => false,
        };
    }

    /**
     * Verifica se pode ser editado
     *
     * @return bool
     */
    public function canEdit(): bool
    {
        return match ( $this ) {
            self::DRAFT, self::SCHEDULED, self::PREPARING, self::ON_HOLD,
            self::IN_PROGRESS, self::PARTIAL                     => true,
            self::APPROVED, self::REJECTED, self::COMPLETED,
            self::CANCELLED, self::PENDING,
            self::SCHEDULING, self::NOT_PERFORMED, self::EXPIRED => false,
        };
    }

    /**
     * Retorna o índice de ordem para classificação
     *
     * @return int
     */
    public function getOrderIndex(): int
    {
        return match ( $this ) {
            self::DRAFT         => 1,
            self::PENDING       => 2,
            self::SCHEDULING    => 3,
            self::SCHEDULED     => 4,
            self::PREPARING     => 5,
            self::IN_PROGRESS   => 6,
            self::ON_HOLD       => 7,
            self::PARTIAL       => 8,
            self::COMPLETED     => 9,
            self::APPROVED      => 10,
            self::REJECTED      => 11,
            self::CANCELLED     => 12,
            self::NOT_PERFORMED => 13,
            self::EXPIRED       => 14,
        };
    }

    /**
     * Retorna a prioridade do status para ordenação
     *
     * @return int
     */
    public function getPriorityOrder(): int
    {
        return $this->getOrderIndex();
    }

    /**
     * Cria instância do enum a partir de string
     *
     * @param string $value Valor do status
     * @return self|null Instância do enum ou null se inválido
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
     * Retorna as transições permitidas para um status
     *
     * @param string $currentStatus Status atual
     * @return array Array de status permitidos
     */
    public static function getAllowedTransitions( string $currentStatus ): array
    {
        return match ( $currentStatus ) {
            self::DRAFT->value   => [ self::PENDING->value, self::CANCELLED->value ],
            self::PENDING->value   => [ self::SCHEDULING->value, self::CANCELLED->value, self::EXPIRED->value ],
            self::SCHEDULING->value   => [ self::SCHEDULED->value, self::CANCELLED->value, self::PENDING->value ],
            self::SCHEDULED->value   => [ self::PREPARING->value, self::CANCELLED->value, self::ON_HOLD->value ],
            self::PREPARING->value   => [ self::IN_PROGRESS->value, self::CANCELLED->value, self::ON_HOLD->value ],
            self::IN_PROGRESS->value   => [ self::COMPLETED->value, self::PARTIAL->value, self::ON_HOLD->value, self::CANCELLED->value ],
            self::ON_HOLD->value   => [ self::SCHEDULED->value, self::PREPARING->value, self::IN_PROGRESS->value, self::CANCELLED->value ],
            default => [], // Status finais não têm transições
        };
    }

    /**
     * Retorna todos os status finais
     *
     * @return array Array de status finais
     */
    public static function getFinalStatuses(): array
    {
        return [
            self::COMPLETED->value,
            self::PARTIAL->value,
            self::CANCELLED->value,
            self::NOT_PERFORMED->value,
            self::EXPIRED->value,
            self::APPROVED->value,
            self::REJECTED->value,
        ];
    }

    /**
     * Retorna metadados completos do status
     *
     * @return array<string, mixed> Array com descrição, cor, ícone e flags
     */
    public function getMetadata(): array
    {
        return [
            'description' => $this->getDescription(),
            'color'       => $this->getColor(),
            'icon'        => $this->getIcon(),
            'isActive'    => $this->isActive(),
            'isFinished'  => $this->isFinished(),
        ];
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

        foreach ( self::cases() as $case ) {
            if ( !$includeFinished && $case->isFinished() ) {
                continue;
            }
            $options[ $case->value ] = $case->getDescription();
        }

        return $options;
    }

    /**
     * Ordena status por prioridade para exibição
     *
     * @param bool $includeFinished Incluir status finalizados na ordenação
     * @return array<self> Status ordenados por prioridade
     */
    public static function getOrdered( bool $includeFinished = true ): array
    {
        $statuses = collect( self::cases() )
            ->sortBy( function ( self $case ) {
                return $case->getPriorityOrder();
            } )
            ->values()
            ->toArray();

        if ( !$includeFinished ) {
            $statuses = array_filter( $statuses, function ( self $case ) {
                return !$case->isFinished();
            } );
        }

        return array_values( $statuses );
    }

    /**
     * Calcula métricas de status para dashboards
     *
     * @param array<self> $statuses Lista de status para análise
     * @return array<string, mixed> Métricas calculadas
     */
    public static function calculateMetrics( array $statuses ): array
    {
        $total        = count( $statuses );
        $active       = 0;
        $finished     = 0;
        $statusCounts = [];

        foreach ( $statuses as $status ) {
            if ( $status->isActive() ) {
                $active++;
            }
            if ( $status->isFinished() ) {
                $finished++;
            }

            $statusCounts[ $status->value ] = ( $statusCounts[ $status->value ] ?? 0 ) + 1;
        }

        return [
            'total'               => $total,
            'active'              => $active,
            'finished'            => $finished,
            'status_distribution' => $statusCounts,
            'completion_rate'     => $total > 0 ? round( ( $finished / $total ) * 100, 2 ) : 0,
        ];
    }

}
