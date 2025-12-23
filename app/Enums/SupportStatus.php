<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Enum que representa os possíveis status de um chamado de suporte
 *
 * Este enum define todos os status disponíveis para os chamados
 * conforme especificado na estrutura da tabela supports.
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
 *
 * @implements \App\Contracts\Interfaces\StatusEnumInterface
 *
 * @example Uso básico:
 * ```php
 * $status = SupportStatus::ABERTO;
 * echo $status->getDescription(); // "Chamado aberto, aguardando atendimento"
 * echo $status->getColor(); // "#FFA500"
 * ```
 * @example Controle de fluxo:
 * ```php
 * $currentStatus = SupportStatus::EM_ANDAMENTO;
 * $nextStatus = $currentStatus->getNextStatus(); // SupportStatus::AGUARDANDO_RESPOSTA
 *
 * if ($currentStatus->canTransitionTo(SupportStatus::RESOLVIDO)) {
 *     // Realizar transição
 * }
 * ```
 * @example Uso em collections/queries:
 * ```php
 * $activeSupports = SupportStatus::getActive();
 * $finishedSupports = SupportStatus::getFinished();
 *
 * $supports = Support::whereIn('status', $activeSupports)->get();
 * ```
 */
enum SupportStatus: string implements \App\Contracts\Interfaces\StatusEnumInterface
{
    /** Chamado aberto, aguardando atendimento */
    case ABERTO = 'ABERTO';

    /** Chamado respondido pela equipe */
    case RESPONDIDO = 'RESPONDIDO';

    /** Chamado resolvido */
    case RESOLVIDO = 'RESOLVIDO';

    /** Chamado fechado */
    case FECHADO = 'FECHADO';

    /** Chamado em andamento */
    case EM_ANDAMENTO = 'EM_ANDAMENTO';

    /** Aguardando resposta do cliente */
    case AGUARDANDO_RESPOSTA = 'AGUARDANDO_RESPOSTA';

    /** Chamado cancelado */
    case CANCELADO = 'CANCELADO';

    /**
     * Retorna uma descrição para cada status
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::ABERTO => 'Chamado aberto, aguardando atendimento',
            self::RESPONDIDO => 'Chamado respondido pela equipe',
            self::RESOLVIDO => 'Chamado resolvido',
            self::FECHADO => 'Chamado fechado',
            self::EM_ANDAMENTO => 'Chamado em andamento',
            self::AGUARDANDO_RESPOSTA => 'Aguardando resposta do cliente',
            self::CANCELADO => 'Chamado cancelado',
        };
    }

    /**
     * Retorna a cor associada a cada status para interface
     *
     * @return string Cor em formato hexadecimal
     */
    public function getColor(): string
    {
        return match ($this) {
            self::ABERTO => '#FFA500',              // Laranja
            self::RESPONDIDO => '#007BFF',          // Azul
            self::RESOLVIDO => '#28A745',           // Verde
            self::FECHADO => '#6C757D',             // Cinza
            self::EM_ANDAMENTO => '#17A2B8',        // Azul claro
            self::AGUARDANDO_RESPOSTA => '#FFC107', // Amarelo
            self::CANCELADO => '#DC3545',           // Vermelho
        };
    }

    /**
     * Retorna o ícone associado a cada status
     *
     * @return string Nome do ícone para interface
     */
    public function getIcon(): string
    {
        return match ($this) {
            self::ABERTO => 'circle',
            self::RESPONDIDO => 'reply',
            self::RESOLVIDO => 'check-circle',
            self::FECHADO => 'times-circle',
            self::EM_ANDAMENTO => 'cog',
            self::AGUARDANDO_RESPOSTA => 'clock',
            self::CANCELADO => 'ban',
        };
    }

    /**
     * Verifica se o status indica que o chamado está ativo
     *
     * @return bool True se o chamado estiver ativo
     */
    public function isActive(): bool
    {
        return match ($this) {
            self::ABERTO, self::RESPONDIDO, self::EM_ANDAMENTO, self::AGUARDANDO_RESPOSTA => true,
            self::RESOLVIDO, self::FECHADO, self::CANCELADO => false,
        };
    }

    /**
     * Verifica se o status indica que o chamado foi finalizado
     *
     * @return bool True se o chamado estiver finalizado
     */
    public function isFinished(): bool
    {
        return match ($this) {
            self::RESOLVIDO, self::FECHADO, self::CANCELADO => true,
            self::ABERTO, self::RESPONDIDO, self::EM_ANDAMENTO, self::AGUARDANDO_RESPOSTA => false,
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
            self::ABERTO,
            self::RESPONDIDO,
            self::RESOLVIDO,
            self::FECHADO,
            self::EM_ANDAMENTO,
            self::AGUARDANDO_RESPOSTA,
            self::CANCELADO,
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
            self::ABERTO,
            self::RESPONDIDO,
            self::EM_ANDAMENTO,
            self::AGUARDANDO_RESPOSTA,
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
            self::RESOLVIDO,
            self::FECHADO,
            self::CANCELADO,
        ];
    }

    /**
     * Retorna o próximo status lógico na sequência de atendimento
     *
     * @return SupportStatus|null Próximo status ou null se for final
     */
    public function getNextStatus(): ?SupportStatus
    {
        return match ($this) {
            self::ABERTO => self::EM_ANDAMENTO,
            self::EM_ANDAMENTO => self::AGUARDANDO_RESPOSTA,
            self::AGUARDANDO_RESPOSTA => self::EM_ANDAMENTO,
            self::RESPONDIDO => self::RESOLVIDO,
            self::RESOLVIDO => self::FECHADO,
            default => null, // Status finais não têm próximo
        };
    }

    /**
     * Retorna o status anterior lógico na sequência
     *
     * @return SupportStatus|null Status anterior ou null se for inicial
     */
    public function getPreviousStatus(): ?SupportStatus
    {
        return match ($this) {
            self::EM_ANDAMENTO => self::ABERTO,
            self::AGUARDANDO_RESPOSTA => self::EM_ANDAMENTO,
            self::RESOLVIDO => self::RESPONDIDO,
            self::FECHADO => self::RESOLVIDO,
            default => null, // Status iniciais não têm anterior
        };
    }

    /**
     * Verifica se é possível transitar para um determinado status
     *
     * @param  SupportStatus  $targetStatus  Status alvo
     * @return bool True se a transição for válida
     */
    public function canTransitionTo(SupportStatus $targetStatus): bool
    {
        // Define transições válidas usando strings como chaves
        $validTransitions = [
            self::ABERTO->value => [self::EM_ANDAMENTO->value, self::CANCELADO->value],
            self::EM_ANDAMENTO->value => [self::AGUARDANDO_RESPOSTA->value, self::RESOLVIDO->value, self::CANCELADO->value],
            self::AGUARDANDO_RESPOSTA->value => [self::EM_ANDAMENTO->value, self::RESOLVIDO->value],
            self::RESPONDIDO->value => [self::EM_ANDAMENTO->value, self::RESOLVIDO->value, self::CANCELADO->value],
            self::RESOLVIDO->value => [self::FECHADO->value],
            self::FECHADO->value => [], // Status final
            self::CANCELADO->value => [], // Status final
        ];

        return in_array($targetStatus->value, $validTransitions[$this->value] ?? []);
    }

    /**
     * Retorna a ordem de prioridade para exibição
     *
     * @return int Ordem (menor número = maior prioridade)
     */
    public function getPriorityOrder(): int
    {
        return match ($this) {
            self::ABERTO => 1,
            self::EM_ANDAMENTO => 2,
            self::AGUARDANDO_RESPOSTA => 3,
            self::RESPONDIDO => 4,
            self::RESOLVIDO => 5,
            self::FECHADO => 6,
            self::CANCELADO => 7,
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
            'value' => $this->value,
            'description' => $this->getDescription(),
            'color' => $this->getColor(),
            'icon' => $this->getIcon(),
            'is_active' => $this->isActive(),
            'is_finished' => $this->isFinished(),
            'priority_order' => $this->getPriorityOrder(),
        ];
    }

    /**
     * Cria instância do enum a partir de string
     *
     * @param  string  $value  Valor do status
     * @return SupportStatus|null Instância do enum ou null se inválido
     */
    public static function fromString(string $value): ?SupportStatus
    {
        foreach (self::cases() as $case) {
            if ($case->value === $value) {
                return $case;
            }
        }

        return null;
    }

    /**
     * Retorna opções formatadas para uso em formulários/selects
     *
     * @param  bool  $includeFinished  Incluir status finalizados
     * @return array<string, string> Array associativo [valor => descrição]
     */
    public static function getOptions(bool $includeFinished = true): array
    {
        $options = [];

        foreach (self::cases() as $status) {
            if (! $includeFinished && $status->isFinished()) {
                continue;
            }
            $options[$status->value] = $status->getDescription();
        }

        return $options;
    }

    /**
     * Ordena status por prioridade para exibição
     *
     * @param  bool  $includeFinished  Incluir status finalizados na ordenação
     * @return array<SupportStatus> Status ordenados por prioridade
     */
    public static function getOrdered(bool $includeFinished = true): array
    {
        $statuses = self::cases();

        usort($statuses, function (SupportStatus $a, SupportStatus $b) {
            return $a->getPriorityOrder() <=> $b->getPriorityOrder();
        });

        if (! $includeFinished) {
            $statuses = array_filter($statuses, function (SupportStatus $status) {
                return ! $status->isFinished();
            });
        }

        return array_values($statuses);
    }

    /**
     * Valida se uma transição de status é permitida
     *
     * @param  SupportStatus  $fromStatus  Status atual
     * @param  SupportStatus  $toStatus  Status alvo
     * @return bool True se transição for válida
     */
    public static function isValidTransition(SupportStatus $fromStatus, SupportStatus $toStatus): bool
    {
        // Define transições válidas usando strings como chaves
        $validTransitions = [
            self::ABERTO->value => [self::EM_ANDAMENTO->value, self::CANCELADO->value],
            self::EM_ANDAMENTO->value => [self::AGUARDANDO_RESPOSTA->value, self::RESOLVIDO->value, self::CANCELADO->value],
            self::AGUARDANDO_RESPOSTA->value => [self::EM_ANDAMENTO->value, self::RESOLVIDO->value],
            self::RESPONDIDO->value => [self::EM_ANDAMENTO->value, self::RESOLVIDO->value, self::CANCELADO->value],
            self::RESOLVIDO->value => [self::FECHADO->value],
            self::FECHADO->value => [], // Status final
            self::CANCELADO->value => [], // Status final
        ];

        return in_array($toStatus->value, $validTransitions[$fromStatus->value] ?? []);
    }

    /**
     * Calcula métricas de status para dashboards
     *
     * @param  array<SupportStatus>  $statuses  Lista de status para análise
     * @return array<string, int> Métricas [ativo, finalizado, total]
     */
    public static function calculateMetrics(array $statuses): array
    {
        $total = count($statuses);
        $active = 0;
        $finished = 0;

        foreach ($statuses as $status) {
            if ($status->isActive()) {
                $active++;
            } elseif ($status->isFinished()) {
                $finished++;
            }
        }

        return [
            'total' => $total,
            'active' => $active,
            'finished' => $finished,
            'active_percentage' => $total > 0 ? round(($active / $total) * 100, 1) : 0,
            'finished_percentage' => $total > 0 ? round(($finished / $total) * 100, 1) : 0,
        ];
    }
}
