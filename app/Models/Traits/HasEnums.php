<?php

declare(strict_types=1);

namespace App\Models\Traits;

use App\Enums\BudgetStatus;
use App\Enums\InvoiceStatus;
use App\Enums\ServiceStatusEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

/**
 * Trait HasEnums
 *
 * Fornece suporte para enums customizados em modelos Eloquent.
 * Agora usa os enums reais (BudgetStatus, ServiceStatusEnum, InvoiceStatus)
 * em vez de arrays estáticos, mantendo compatibilidade com código legado.
 */
trait HasEnums
{
    /**
     * Status de Orçamento (Budget) - Agora usa BudgetStatus real.
     * Mantido para compatibilidade com código que ainda usa as constantes.
     *
     * @deprecated Use BudgetStatus diretamente
     */
    protected const BUDGET_STATUS_ENUM = [
        self::BUDGET_DRAFT     => [
            'value'       => 1,
            'slug'        => 'draft',
            'name'        => 'Rascunho',
            'description' => 'Orçamento em elaboração',
            'color'       => '#9CA3AF',
            'icon'        => 'mdi-file-document-edit',
            'order_index' => 1,
            'is_active'   => true,
        ],
        self::BUDGET_SENT      => [
            'value'       => 2,
            'slug'        => 'sent',
            'name'        => 'Enviado',
            'description' => 'Aguardando aprovação do cliente',
            'color'       => '#3B82F6',
            'icon'        => 'mdi-send',
            'order_index' => 2,
            'is_active'   => true,
        ],
        self::BUDGET_APPROVED  => [
            'value'       => 3,
            'slug'        => 'approved',
            'name'        => 'Aprovado',
            'description' => 'Orçamento aprovado pelo cliente',
            'color'       => '#10B981',
            'icon'        => 'mdi-check-circle',
            'order_index' => 3,
            'is_active'   => true,
        ],
        self::BUDGET_COMPLETED => [
            'value'       => 4,
            'slug'        => 'completed',
            'name'        => 'Concluído',
            'description' => 'Orçamento aprovado e executado',
            'color'       => '#059669',
            'icon'        => 'mdi-check-circle-outline',
            'order_index' => 4,
            'is_active'   => true,
        ],
        self::BUDGET_REJECTED  => [
            'value'       => 5,
            'slug'        => 'rejected',
            'name'        => 'Rejeitado',
            'description' => 'Orçamento rejeitado pelo cliente',
            'color'       => '#EF4444',
            'icon'        => 'mdi-close-circle',
            'order_index' => 5,
            'is_active'   => true,
        ],
        self::BUDGET_EXPIRED   => [
            'value'       => 6,
            'slug'        => 'expired',
            'name'        => 'Expirado',
            'description' => 'Orçamento expirou sem aprovação',
            'color'       => '#F59E0B',
            'icon'        => 'mdi-timer-off',
            'order_index' => 6,
            'is_active'   => true,
        ],
        self::BUDGET_REVISED   => [
            'value'       => 8,
            'slug'        => 'revised',
            'name'        => 'Revisado',
            'description' => 'Orçamento revisado',
            'color'       => '#8B5CF6',
            'icon'        => 'mdi-file-compare',
            'order_index' => 8,
            'is_active'   => true,
        ],
        self::BUDGET_CANCELLED => [
            'value'       => 7,
            'slug'        => 'cancelled',
            'name'        => 'Cancelado',
            'description' => 'Orçamento cancelado pelo provedor',
            'color'       => '#6B7280',
            'icon'        => 'mdi-cancel',
            'order_index' => 9,
            'is_active'   => true,
        ],
    ];

    /**
     * Status de Serviço (Service) - 12 valores identificados na análise.
     *
     * @var array
     */
    protected const SERVICE_STATUS_ENUM = [
        self::SERVICE_DRAFT         => [
            'value'       => 1,
            'slug'        => 'draft',
            'name'        => 'Rascunho',
            'description' => 'Serviço em elaboração',
            'color'       => '#6c757d',
            'icon'        => 'file-outline',
            'order_index' => 1,
            'is_active'   => true,
        ],
        self::SERVICE_PENDING       => [
            'value'       => 2,
            'slug'        => 'pending',
            'name'        => 'Pendente',
            'description' => 'Aguardando agendamento',
            'color'       => '#ffc107',
            'icon'        => 'clock-outline',
            'order_index' => 2,
            'is_active'   => true,
        ],
        self::SERVICE_SCHEDULING    => [
            'value'       => 3,
            'slug'        => 'scheduling',
            'name'        => 'Agendamento',
            'description' => 'Em processo de agendamento',
            'color'       => '#17a2b8',
            'icon'        => 'calendar-outline',
            'order_index' => 3,
            'is_active'   => true,
        ],
        self::SERVICE_PREPARING     => [
            'value'       => 4,
            'slug'        => 'preparing',
            'name'        => 'Preparando',
            'description' => 'Preparação do serviço',
            'color'       => '#20c997',
            'icon'        => 'construct-outline',
            'order_index' => 4,
            'is_active'   => true,
        ],
        self::SERVICE_IN_PROGRESS   => [
            'value'       => 5,
            'slug'        => 'in_progress',
            'name'        => 'Em Andamento',
            'description' => 'Serviço sendo executado',
            'color'       => '#007bff',
            'icon'        => 'play-outline',
            'order_index' => 5,
            'is_active'   => true,
        ],
        self::SERVICE_ON_HOLD       => [
            'value'       => 6,
            'slug'        => 'on_hold',
            'name'        => 'Em Espera',
            'description' => 'Serviço pausado temporariamente',
            'color'       => '#fd7e14',
            'icon'        => 'pause-outline',
            'order_index' => 6,
            'is_active'   => true,
        ],
        self::SERVICE_SCHEDULED     => [
            'value'       => 7,
            'slug'        => 'scheduled',
            'name'        => 'Agendado',
            'description' => 'Serviço agendado para execução',
            'color'       => '#28a745',
            'icon'        => 'calendar-check-outline',
            'order_index' => 7,
            'is_active'   => true,
        ],
        self::SERVICE_COMPLETED     => [
            'value'       => 8,
            'slug'        => 'completed',
            'name'        => 'Concluído',
            'description' => 'Serviço finalizado com sucesso',
            'color'       => '#28a745',
            'icon'        => 'checkmark-circle-outline',
            'order_index' => 8,
            'is_active'   => true,
        ],
        self::SERVICE_PARTIAL       => [
            'value'       => 9,
            'slug'        => 'partial',
            'name'        => 'Parcial',
            'description' => 'Serviço parcialmente concluído',
            'color'       => '#ffc107',
            'icon'        => 'remove-circle-outline',
            'order_index' => 9,
            'is_active'   => true,
        ],
        self::SERVICE_CANCELLED     => [
            'value'       => 10,
            'slug'        => 'cancelled',
            'name'        => 'Cancelado',
            'description' => 'Serviço cancelado',
            'color'       => '#dc3545',
            'icon'        => 'close-circle-outline',
            'order_index' => 10,
            'is_active'   => true,
        ],
        self::SERVICE_NOT_PERFORMED => [
            'value'       => 11,
            'slug'        => 'not_performed',
            'name'        => 'Não Realizado',
            'description' => 'Serviço não realizado',
            'color'       => '#6c757d',
            'icon'        => 'ban-outline',
            'order_index' => 11,
            'is_active'   => true,
        ],
        self::SERVICE_EXPIRED       => [
            'value'       => 12,
            'slug'        => 'expired',
            'name'        => 'Expirado',
            'description' => 'Prazo do serviço expirou',
            'color'       => '#fd7e14',
            'icon'        => 'time-outline',
            'order_index' => 12,
            'is_active'   => true,
        ],
    ];

    /**
     * Status de Fatura (Invoice) - 4 valores identificados na análise.
     *
     * @var array
     */
    protected const INVOICE_STATUS_ENUM = [
        self::INVOICE_PENDING   => [
            'value'       => 1,
            'slug'        => 'pending',
            'name'        => 'Pendente',
            'description' => 'Fatura aguardando pagamento',
            'color'       => '#ffc107',
            'icon'        => 'clock-outline',
            'order_index' => 1,
            'is_active'   => true,
        ],
        self::INVOICE_PAID      => [
            'value'       => 2,
            'slug'        => 'paid',
            'name'        => 'Pago',
            'description' => 'Fatura paga',
            'color'       => '#28a745',
            'icon'        => 'checkmark-circle-outline',
            'order_index' => 2,
            'is_active'   => true,
        ],
        self::INVOICE_CANCELLED => [
            'value'       => 3,
            'slug'        => 'cancelled',
            'name'        => 'Cancelado',
            'description' => 'Fatura cancelada',
            'color'       => '#dc3545',
            'icon'        => 'close-circle-outline',
            'order_index' => 3,
            'is_active'   => true,
        ],
        self::INVOICE_OVERDUE   => [
            'value'       => 4,
            'slug'        => 'overdue',
            'name'        => 'Atrasado',
            'description' => 'Fatura vencida',
            'color'       => '#dc3545',
            'icon'        => 'alert-circle-outline',
            'order_index' => 4,
            'is_active'   => true,
        ],
    ];

    /**
     * Status de Suporte (Support/Ticket) - Status básicos para tickets de suporte.
     *
     * @var array
     */
    protected const SUPPORT_STATUS_ENUM = [
        self::SUPPORT_OPEN        => [
            'value'       => 1,
            'slug'        => 'open',
            'name'        => 'Aberto',
            'description' => 'Ticket aberto e aguardando atendimento',
            'color'       => '#007bff',
            'icon'        => 'help-circle-outline',
            'order_index' => 1,
            'is_active'   => true,
        ],
        self::SUPPORT_IN_PROGRESS => [
            'value'       => 2,
            'slug'        => 'in_progress',
            'name'        => 'Em Atendimento',
            'description' => 'Ticket sendo atendido',
            'color'       => '#17a2b8',
            'icon'        => 'construct-outline',
            'order_index' => 2,
            'is_active'   => true,
        ],
        self::SUPPORT_CLOSED      => [
            'value'       => 3,
            'slug'        => 'closed',
            'name'        => 'Fechado',
            'description' => 'Ticket resolvido e fechado',
            'color'       => '#28a745',
            'icon'        => 'checkmark-circle-outline',
            'order_index' => 3,
            'is_active'   => true,
        ],
        self::SUPPORT_CANCELLED   => [
            'value'       => 4,
            'slug'        => 'cancelled',
            'name'        => 'Cancelado',
            'description' => 'Ticket cancelado',
            'color'       => '#6c757d',
            'icon'        => 'close-circle-outline',
            'order_index' => 4,
            'is_active'   => true,
        ],
    ];

    // Constantes de conveniência para os valores dos enums
    public const BUDGET_DRAFT     = 1;
    public const BUDGET_SENT      = 2;
    public const BUDGET_PENDING   = 2;
    public const BUDGET_APPROVED  = 3;
    public const BUDGET_COMPLETED = 4;
    public const BUDGET_REJECTED  = 4;
    public const BUDGET_EXPIRED   = 5;
    public const BUDGET_REVISED   = 8;
    public const BUDGET_CANCELLED = 7;

    public const SERVICE_DRAFT         = 1;
    public const SERVICE_PENDING       = 2;
    public const SERVICE_SCHEDULING    = 3;
    public const SERVICE_PREPARING     = 4;
    public const SERVICE_IN_PROGRESS   = 5;
    public const SERVICE_ON_HOLD       = 6;
    public const SERVICE_SCHEDULED     = 7;
    public const SERVICE_COMPLETED     = 8;
    public const SERVICE_PARTIAL       = 9;
    public const SERVICE_CANCELLED     = 10;
    public const SERVICE_NOT_PERFORMED = 11;
    public const SERVICE_EXPIRED       = 12;

    public const INVOICE_PENDING   = 1;
    public const INVOICE_PAID      = 2;
    public const INVOICE_CANCELLED = 3;
    public const INVOICE_OVERDUE   = 4;

    public const SUPPORT_OPEN        = 1;
    public const SUPPORT_IN_PROGRESS = 2;
    public const SUPPORT_CLOSED      = 3;
    public const SUPPORT_CANCELLED   = 4;

    /**
     * Obtém as opções de enum para um tipo específico.
     * Agora usa os enums reais quando disponíveis, mantendo compatibilidade.
     *
     * @param string $type Tipo de enum: 'budget', 'service', 'invoice', 'support'
     * @return array
     */
    public function getEnumOptions( string $type ): array
    {
        return match ( strtolower( $type ) ) {
            'budget'  => $this->getBudgetStatusOptions(),
            'service' => $this->getServiceStatusOptions(),
            'invoice' => $this->getInvoiceStatusOptions(),
            'support' => self::SUPPORT_STATUS_ENUM,
            default   => [],
        };
    }

    /**
     * Obtém opções de status de orçamento usando BudgetStatus real.
     *
     * @return array
     */
    private function getBudgetStatusOptions(): array
    {
        $options = [];
        foreach ( BudgetStatus::cases() as $status ) {
            $options[ $status->value ] = [
                'value'       => $status->value,
                'slug'        => $status->name,
                'name'        => $status->getDescription(),
                'description' => $status->getDescription(), // Usar nome como descrição por enquanto
                'color'       => $status->getColor(),
                'icon'        => $status->getIcon(),
                'order_index' => $status->getOrderIndex(),
                'is_active'   => $status->isActive(),
            ];
        }
        return $options;
    }

    /**
     * Obtém opções de status de serviço usando ServiceStatusEnum real.
     *
     * @return array
     */
    private function getServiceStatusOptions(): array
    {
        $options = [];
        foreach ( ServiceStatusEnum::cases() as $status ) {
            $options[ $status->value ] = [
                'value'       => $status->value,
                'slug'        => $status->name,
                'name'        => $status->getDescription(),
                'description' => $status->getDescription(), // Usar nome como descrição por enquanto
                'color'       => $status->getColor(),
                'icon'        => $status->getIcon(),
                'order_index' => $status->getOrderIndex(),
                'is_active'   => $status->isActive(),
            ];
        }
        return $options;
    }

    /**
     * Obtém opções de status de fatura usando InvoiceStatus real.
     *
     * @return array
     */
    private function getInvoiceStatusOptions(): array
    {
        $options = [];
        foreach ( InvoiceStatus::cases() as $status ) {
            $options[ $status->value ] = [
                'value'       => $status->value,
                'slug'        => $status->name,
                'name'        => $status->getDescription(),
                'description' => $status->getDescription(), // Usar nome como descrição por enquanto
                'color'       => $status->getColor(),
                'icon'        => $status->getIcon(),
                'order_index' => $status->getOrderIndex(),
                'is_active'   => $status->isActive(),
            ];
        }
        return $options;
    }

    /**
     * Verifica se um valor é válido para um tipo de enum.
     *
     * @param string $type Tipo de enum
     * @param mixed $value Valor a validar
     * @return bool
     */
    public function isValidEnumValue( string $type, mixed $value ): bool
    {
        $options = $this->getEnumOptions( $type );
        return Arr::has( $options, $value );
    }

    /**
     * Obtém o label (nome) de um status.
     *
     * @param string $type Tipo de enum
     * @param int $value Valor do status
     * @return string|null
     */
    public function getEnumLabel( string $type, int $value ): ?string
    {
        $options = $this->getEnumOptions( $type );
        return $options[ $value ][ 'name' ] ?? null;
    }

    /**
     * Obtém a cor de um status.
     *
     * @param string $type Tipo de enum
     * @param int $value Valor do status
     * @return string|null
     */
    public function getEnumColor( string $type, int $value ): ?string
    {
        $options = $this->getEnumOptions( $type );
        return $options[ $value ][ 'color' ] ?? null;
    }

    /**
     * Obtém o ícone de um status.
     *
     * @param string $type Tipo de enum
     * @param int $value Valor do status
     * @return string|null
     */
    public function getEnumIcon( string $type, int $value ): ?string
    {
        $options = $this->getEnumOptions( $type );
        return $options[ $value ][ 'icon' ] ?? null;
    }

    /**
     * Obtém todos os status ativos para um tipo.
     *
     * @param string $type Tipo de enum
     * @return array
     */
    public function getActiveEnums( string $type ): array
    {
        $options = $this->getEnumOptions( $type );
        return array_filter( $options, fn( $option ) => $option[ 'is_active' ] );
    }

    /**
     * Converte um valor de enum para array com metadados.
     *
     * @param string $type Tipo de enum
     * @param int $value Valor do status
     * @return array|null
     */
    public function getEnumMetadata( string $type, int $value ): ?array
    {
        $options = $this->getEnumOptions( $type );
        return $options[ $value ] ?? null;
    }

    /**
     * ATENÇÃO: Este trait NÃO fornece scopes de query genéricos como scopeByStatus ou scopeActiveStatus,
     * pois eles assumem a existência de uma coluna 'status' na tabela do model, o que não é universal.
     *
     * Este trait é projetado PRIMARIAMENTE para models que possuem uma coluna 'status' (ex: Budget, Service, Invoice),
     * onde o valor da coluna corresponde aos enums definidos (ex: BUDGET_DRAFT = 1).
     *
     * Para models de status em si (ex: BudgetStatus, ServiceStatus, InvoiceStatus), que tipicamente usam colunas como
     * 'slug', 'is_active' e 'order_index' em vez de 'status', NÃO use este trait. Em vez disso:
     * - Para status ativos: $model->where('is_active', true)->orderBy('order_index')
     * - Para status por slug: $model->where('slug', $slug)
     *
     * Se necessário, crie traits específicos como HasStatusScopes para models com coluna 'status',
     * ou implemente scopes customizados no model individual.
     *
     * Exemplo de uso correto em Budget (que tem budget_statuses_id referenciando BudgetStatus):
     * Budget::whereHas('status', fn($q) => $q->where('is_active', true))
     *        ->orWhere('budget_statuses_id', BudgetStatus::APPROVED->value);
     *
     * Esta limitação evita erros de runtime por coluna inexistente e promove Clean Architecture
     * com responsabilidades bem definidas.
     */

}
