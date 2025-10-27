<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Enum que representa os possíveis status de um serviço
 *
 * Este enum define todos os status disponíveis para os serviços
 * conforme especificado na estrutura da tabela services e service_statuses.
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
 * $status = ServiceStatus::DRAFT;
 * echo $status->getDescription(); // "Serviço em elaboração, permite modificações"
 * echo $status->getColor(); // "#6c757d"
 * ```
 *
 * @example Controle de fluxo:
 * ```php
 * $currentStatus = ServiceStatus::SCHEDULING;
 * $nextStatus = $currentStatus->getNextStatus(); // ServiceStatus::SCHEDULED
 *
 * if ($currentStatus->canTransitionTo(ServiceStatus::IN_PROGRESS)) {
 *     // Realizar transição
 * }
 * ```
 *
 * @example Uso em collections/queries:
 * ```php
 * $activeServices = ServiceStatus::getActive();
 * $finishedServices = ServiceStatus::getFinished();
 *
 * $services = Service::whereIn('status', $activeServices)->get();
 * ```
 */
enum ServiceStatus: string
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
            self::IN_PROGRESS, self::ON_HOLD, self::SCHEDULED => true,
            self::COMPLETED, self::PARTIAL, self::CANCELLED,
            self::NOT_PERFORMED, self::EXPIRED                => false,
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
            self::NOT_PERFORMED, self::EXPIRED                => true,
            self::DRAFT, self::PENDING, self::SCHEDULING, self::PREPARING,
            self::IN_PROGRESS, self::ON_HOLD, self::SCHEDULED => false,
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
            self::SCHEDULED, self::IN_PROGRESS => true,
            self::DRAFT, self::PENDING, self::SCHEDULING, self::PREPARING,
            self::ON_HOLD, self::COMPLETED, self::PARTIAL, self::CANCELLED,
            self::NOT_PERFORMED, self::EXPIRED => false,
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
            self::SCHEDULING,
            self::PREPARING,
            self::IN_PROGRESS,
            self::ON_HOLD,
            self::SCHEDULED,
            self::COMPLETED,
            self::PARTIAL,
            self::CANCELLED,
            self::NOT_PERFORMED,
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
            self::SCHEDULING,
            self::PREPARING,
            self::IN_PROGRESS,
            self::ON_HOLD,
            self::SCHEDULED,
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
            self::COMPLETED,
            self::PARTIAL,
            self::CANCELLED,
            self::NOT_PERFORMED,
            self::EXPIRED,
        ];
    }

    /**
     * Retorna apenas os status executáveis
     *
     * @return array<string> Lista de status executáveis
     */
    public static function getExecutable(): array
    {
        return [
            self::SCHEDULED,
            self::IN_PROGRESS,
        ];
    }

    /**
     * Retorna o próximo status lógico na sequência de execução
     *
     * @return ServiceStatus|null Próximo status ou null se for final
     */
    public function getNextStatus(): ?ServiceStatus
    {
        return match ( $this ) {
            self::DRAFT       => self::PENDING,
            self::PENDING     => self::SCHEDULING,
            self::SCHEDULING  => self::SCHEDULED,
            self::SCHEDULED   => self::PREPARING,
            self::PREPARING   => self::IN_PROGRESS,
            self::IN_PROGRESS => self::COMPLETED,
            default           => null, // Status finais não têm próximo
        };
    }

    /**
     * Retorna o status anterior lógico na sequência
     *
     * @return ServiceStatus|null Status anterior ou null se for inicial
     */
    public function getPreviousStatus(): ?ServiceStatus
    {
        return match ( $this ) {
            self::PENDING     => self::DRAFT,
            self::SCHEDULING  => self::PENDING,
            self::SCHEDULED   => self::SCHEDULING,
            self::PREPARING   => self::SCHEDULED,
            self::IN_PROGRESS => self::PREPARING,
            self::COMPLETED   => self::IN_PROGRESS,
            default           => null, // Status iniciais não têm anterior
        };
    }

    /**
     * Verifica se é possível transitar para um determinado status
     *
     * @param ServiceStatus $targetStatus Status alvo
     * @return bool True se a transição for válida
     */
    public function canTransitionTo( ServiceStatus $targetStatus ): bool
    {
        // Define transições válidas usando strings como chaves
        $validTransitions = [
            self::DRAFT->value         => [ self::PENDING->value, self::CANCELLED->value ],
            self::PENDING->value       => [ self::SCHEDULING->value, self::CANCELLED->value, self::EXPIRED->value ],
            self::SCHEDULING->value    => [ self::SCHEDULED->value, self::CANCELLED->value, self::PENDING->value ],
            self::SCHEDULED->value     => [ self::PREPARING->value, self::CANCELLED->value, self::ON_HOLD->value ],
            self::PREPARING->value     => [ self::IN_PROGRESS->value, self::CANCELLED->value, self::ON_HOLD->value ],
            self::IN_PROGRESS->value   => [ self::COMPLETED->value, self::PARTIAL->value, self::ON_HOLD->value, self::CANCELLED->value ],
            self::ON_HOLD->value       => [ self::SCHEDULED->value, self::PREPARING->value, self::IN_PROGRESS->value, self::CANCELLED->value ],
            self::COMPLETED->value     => [], // Status final
            self::PARTIAL->value       => [], // Status final
            self::CANCELLED->value     => [], // Status final
            self::NOT_PERFORMED->value => [], // Status final
            self::EXPIRED->value       => [], // Status final
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
            self::DRAFT         => 1,
            self::PENDING       => 2,
            self::SCHEDULING    => 3,
            self::SCHEDULED     => 4,
            self::PREPARING     => 5,
            self::IN_PROGRESS   => 6,
            self::ON_HOLD       => 7,
            self::COMPLETED     => 8,
            self::PARTIAL       => 9,
            self::CANCELLED     => 10,
            self::NOT_PERFORMED => 11,
            self::EXPIRED       => 12,
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
            'is_executable'  => $this->isExecutable(),
            'priority_order' => $this->getPriorityOrder(),
        ];
    }

    /**
     * Cria instância do enum a partir de string
     *
     * @param string $value Valor do status
     * @return ServiceStatus|null Instância do enum ou null se inválido
     */
    public static function fromString( string $value ): ?ServiceStatus
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
     * @param bool $includeExecutable Incluir apenas status executáveis
     * @return array<string, string> Array associativo [valor => descrição]
     */
    public static function getOptions( bool $includeFinished = true, bool $includeExecutable = false ): array
    {
        $options = [];

        foreach ( self::cases() as $status ) {
            if ( !$includeFinished && $status->isFinished() ) {
                continue;
            }
            if ( $includeExecutable && !$status->isExecutable() ) {
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
     * @return array<ServiceStatus> Status ordenados por prioridade
     */
    public static function getOrdered( bool $includeFinished = true ): array
    {
        $statuses = self::cases();

        usort( $statuses, function ( ServiceStatus $a, ServiceStatus $b ) {
            return $a->getPriorityOrder() <=> $b->getPriorityOrder();
        } );

        if ( !$includeFinished ) {
            $statuses = array_filter( $statuses, function ( ServiceStatus $status ) {
                return !$status->isFinished();
            } );
        }

        return array_values( $statuses );
    }

    /**
     * Valida se uma transição de status é permitida
     *
     * @param ServiceStatus $fromStatus Status atual
     * @param ServiceStatus $toStatus Status alvo
     * @return bool True se transição for válida
     */
    public static function isValidTransition( ServiceStatus $fromStatus, ServiceStatus $toStatus ): bool
    {
        // Define transições válidas usando strings como chaves
        $validTransitions = [
            self::DRAFT->value         => [ self::PENDING->value, self::CANCELLED->value ],
            self::PENDING->value       => [ self::SCHEDULING->value, self::CANCELLED->value, self::EXPIRED->value ],
            self::SCHEDULING->value    => [ self::SCHEDULED->value, self::CANCELLED->value, self::PENDING->value ],
            self::SCHEDULED->value     => [ self::PREPARING->value, self::CANCELLED->value, self::ON_HOLD->value ],
            self::PREPARING->value     => [ self::IN_PROGRESS->value, self::CANCELLED->value, self::ON_HOLD->value ],
            self::IN_PROGRESS->value   => [ self::COMPLETED->value, self::PARTIAL->value, self::ON_HOLD->value, self::CANCELLED->value ],
            self::ON_HOLD->value       => [ self::SCHEDULED->value, self::PREPARING->value, self::IN_PROGRESS->value, self::CANCELLED->value ],
            self::COMPLETED->value     => [], // Status final
            self::PARTIAL->value       => [], // Status final
            self::CANCELLED->value     => [], // Status final
            self::NOT_PERFORMED->value => [], // Status final
            self::EXPIRED->value       => [], // Status final
        ];

        return in_array( $toStatus->value, $validTransitions[ $fromStatus->value ] ?? [] );
    }

    /**
     * Calcula métricas de status para dashboards
     *
     * @param array<ServiceStatus> $statuses Lista de status para análise
     * @return array<string, int> Métricas [ativo, finalizado, executável, total]
     */
    public static function calculateMetrics( array $statuses ): array
    {
        $total      = count( $statuses );
        $active     = 0;
        $finished   = 0;
        $executable = 0;

        foreach ( $statuses as $status ) {
            if ( $status->isActive() ) {
                $active++;
            }
            if ( $status->isFinished() ) {
                $finished++;
            }
            if ( $status->isExecutable() ) {
                $executable++;
            }
        }

        return [
            'total'                 => $total,
            'active'                => $active,
            'finished'              => $finished,
            'executable'            => $executable,
            'active_percentage'     => $total > 0 ? round( ( $active / $total ) * 100, 1 ) : 0,
            'finished_percentage'   => $total > 0 ? round( ( $finished / $total ) * 100, 1 ) : 0,
            'executable_percentage' => $total > 0 ? round( ( $executable / $total ) * 100, 1 ) : 0,
        ];
    }

}
