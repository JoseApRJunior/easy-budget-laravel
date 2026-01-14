<?php

declare(strict_types=1);

namespace App\Models\Traits;

use App\Enums\BudgetStatus;
use App\Enums\InvoiceStatus;
use App\Enums\ServiceStatus;
use App\Enums\SupportStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

/**
 * Trait HasEnums
 *
 * Fornece suporte para enums customizados em modelos Eloquent.
 * Usa exclusivamente enums modernos (BudgetStatus, ServiceStatus, InvoiceStatus, SupportStatus)
 * com métodos auxiliares seguros para garantir compatibilidade.
 *
 * IMPORTANTE: Não mantém mais arrays estáticos antigos - é 100% baseado em enums PHP modernos.
 *
 * @version 2.0 - Refatorado para usar apenas enums modernos
 *
 * @since 2025-11-09 - Migração completa do sistema legado
 */
trait HasEnums
{
    // Arrays estáticos removidos - agora usa apenas enums modernos

    /**
     * Obtém as opções de enum para um tipo específico.
     * Agora usa os enums reais quando disponíveis, mantendo compatibilidade.
     *
     * @param  string  $type  Tipo de enum: 'budget', 'service', 'invoice', 'support'
     */
    public function getEnumOptions(string $type): array
    {
        return match (strtolower($type)) {
            'budget' => $this->getBudgetStatusOptions(),
            'service' => $this->getServiceStatusOptions(),
            'invoice' => $this->getInvoiceStatusOptions(),
            'support' => $this->getSupportStatusOptions(),
            default => [],
        };
    }

    /**
     * Obtém opções de status de suporte usando SupportStatus real.
     */
    private function getSupportStatusOptions(): array
    {
        $options = [];
        foreach (SupportStatus::cases() as $status) {
            $options[$status->value] = [
                'value' => $status->value,
                'slug' => $status->name,
                'name' => $this->getStatusDisplayName($status),
                'description' => $this->getStatusDisplayName($status),
                'color' => $this->getStatusColor($status),
                'icon' => $this->getStatusIcon($status),
                'order_index' => $this->getStatusOrderIndex($status),
                'is_active' => $this->getStatusIsActive($status),
            ];
        }

        return $options;
    }

    /**
     * Obtém opções de status de orçamento usando BudgetStatus real.
     */
    private function getBudgetStatusOptions(): array
    {
        $options = [];
        foreach (BudgetStatus::cases() as $status) {
            $options[$status->value] = [
                'value' => $status->value,
                'slug' => $status->name,
                'name' => $this->getStatusDisplayName($status),
                'description' => $this->getStatusDisplayName($status),
                'color' => $this->getStatusColor($status),
                'icon' => $this->getStatusIcon($status),
                'order_index' => $this->getStatusOrderIndex($status),
                'is_active' => $this->getStatusIsActive($status),
            ];
        }

        return $options;
    }

    /**
     * Obtém opções de status de serviço usando ServiceStatus real.
     */
    private function getServiceStatusOptions(): array
    {
        $options = [];
        foreach (ServiceStatus::cases() as $status) {
            $options[$status->value] = [
                'value' => $status->value,
                'slug' => $status->name,
                'name' => $this->getStatusDisplayName($status),
                'description' => $this->getStatusDisplayName($status),
                'color' => $this->getStatusColor($status),
                'icon' => $this->getStatusIcon($status),
                'order_index' => $this->getStatusOrderIndex($status),
                'is_active' => $this->getStatusIsActive($status),
            ];
        }

        return $options;
    }

    /**
     * Obtém opções de status de fatura usando InvoiceStatus real.
     */
    private function getInvoiceStatusOptions(): array
    {
        $options = [];
        foreach (InvoiceStatus::cases() as $status) {
            $options[$status->value] = [
                'value' => $status->value,
                'slug' => $status->name,
                'name' => $this->getStatusDisplayName($status),
                'description' => $this->getStatusDisplayName($status),
                'color' => $this->getStatusColor($status),
                'icon' => $this->getStatusIcon($status),
                'order_index' => $this->getStatusOrderIndex($status),
                'is_active' => $this->getStatusIsActive($status),
            ];
        }

        return $options;
    }

    /**
     * Verifica se um valor é válido para um tipo de enum.
     *
     * @param  string  $type  Tipo de enum
    /**
     * Obtém nome de display de um status de forma segura
     */
    private function getStatusDisplayName(mixed $status): string
    {
        return method_exists($status, 'getDescription')
            ? $status->getDescription()
            : $status->name;
    }

    /**
     * Obtém cor de um status de forma segura
     */
    private function getStatusColor(mixed $status): string
    {
        return method_exists($status, 'getColor')
            ? $status->getColor()
            : '#6c757d';
    }

    /**
     * Obtém ícone de um status de forma segura
     */
    private function getStatusIcon(mixed $status): string
    {
        return method_exists($status, 'getIcon')
            ? $status->getIcon()
            : 'bi-circle';
    }

    /**
     * Obtém índice de ordem de um status de forma segura
     */
    private function getStatusOrderIndex(mixed $status): int
    {
        return method_exists($status, 'getOrderIndex')
            ? $status->getOrderIndex()
            : 0;
    }

    /**
     * Obtém status ativo de um status de forma segura
     */
    private function getStatusIsActive(mixed $status): bool
    {
        return method_exists($status, 'isActive')
            ? $status->isActive()
            : true;
    }

    /**
     * Verifica se um valor é válido para um tipo de enum.
     *
     * @param  string  $type  Tipo de enum
     * @param  mixed  $value  Valor a validar
     */
    public function isValidEnumValue(string $type, mixed $value): bool
    {
        $options = $this->getEnumOptions($type);

        return Arr::has($options, $value);
    }

    /**
     * Obtém o label (nome) de um status.
     *
     * @param  string  $type  Tipo de enum
     * @param  int  $value  Valor do status
     */
    public function getEnumLabel(string $type, int $value): ?string
    {
        $options = $this->getEnumOptions($type);

        return $options[$value]['name'] ?? null;
    }

    /**
     * Obtém a cor de um status.
     *
     * @param  string  $type  Tipo de enum
     * @param  int  $value  Valor do status
     */
    public function getEnumColor(string $type, int $value): ?string
    {
        $options = $this->getEnumOptions($type);

        return $options[$value]['color'] ?? null;
    }

    /**
     * Obtém o ícone de um status.
     *
     * @param  string  $type  Tipo de enum
     * @param  int  $value  Valor do status
     */
    public function getEnumIcon(string $type, int $value): ?string
    {
        $options = $this->getEnumOptions($type);

        return $options[$value]['icon'] ?? null;
    }

    /**
     * Obtém todos os status ativos para um tipo.
     *
     * @param  string  $type  Tipo de enum
     */
    public function getActiveEnums(string $type): array
    {
        $options = $this->getEnumOptions($type);

        return array_filter($options, fn ($option) => $option['is_active']);
    }

    /**
     * Converte um valor de enum para array com metadados.
     *
     * @param  string  $type  Tipo de enum
     * @param  int  $value  Valor do status
     */
    public function getEnumMetadata(string $type, int $value): ?array
    {
        $options = $this->getEnumOptions($type);

        return $options[$value] ?? null;
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
