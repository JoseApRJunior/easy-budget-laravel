<?php

declare(strict_types=1);

namespace App\Services\Application;

use App\Enums\OperationStatus;
use App\Enums\ServiceStatus;
use App\Support\ServiceResult;

/**
 * Serviço para gerenciamento de status de serviços usando enums.
 *
 * Este serviço fornece funcionalidades para trabalhar com status de serviços
 * através de enums, substituindo o modelo ServiceStatus por ServiceStatus.
 * Como os status agora são enums, este serviço foca em fornecer métodos utilitários
 * para trabalhar com os status de forma type-safe.
 *
 * Funcionalidades principais:
 * - Obter todos os status disponíveis
 * - Buscar status por valor
 * - Validar se um status é válido
 * - Obter metadados dos status (nome, cor, ícone, etc.)
 * - Gerenciar transições de status
 */
class ServiceStatusService
{
    /**
     * Obtém todos os status de serviço disponíveis.
     */
    public function getAllStatuses(): array
    {
        return ServiceStatus::cases();
    }

    /**
     * Obtém um status específico pelo seu valor.
     */
    public function getStatusByValue(string $value): ?ServiceStatus
    {
        return ServiceStatus::tryFrom($value);
    }

    /**
     * Verifica se um valor de status é válido.
     */
    public function isValidStatus(string $value): bool
    {
        return ServiceStatus::tryFrom($value) !== null;
    }

    /**
     * Obtém os metadados de um status específico.
     */
    public function getStatusMetadata(string $value): ServiceResult
    {
        $status = $this->getStatusByValue($value);

        if ($status === null) {
            return $this->error("Status inválido: {$value}");
        }

        return $this->success([
            'value' => $status->value,
            'name' => $status->getDescription(),
            'color' => $status->getColor(),
            'icon' => $status->getIcon(),
            'order_index' => 0,
            'is_active' => $status->isActive(),
        ], 'Metadados do status obtidos com sucesso');
    }

    /**
     * Obtém todas as opções de status formatadas para uso em selects/forms.
     */
    public function getStatusOptions(): array
    {
        $options = [];
        foreach (ServiceStatus::cases() as $status) {
            $options[$status->value] = [
                'value' => $status->value,
                'label' => $status->getDescription(),
                'color' => $status->getColor(),
                'icon' => $status->getIcon(),
            ];
        }

        return $options;
    }

    /**
     * Retorna transições permitidas para um status
     */
    public function getAllowedTransitions(string $currentStatus): ServiceResult
    {
        $status = ServiceStatus::tryFrom($currentStatus);

        if (! $status) {
            return $this->error('Status atual inválido', ['status' => $currentStatus]);
        }

        $transitions = ServiceStatus::getAllowedTransitions($status->value);

        return $this->success($transitions, 'Transições permitidas recuperadas');
    }

    /**
     * Verifica se uma transição é permitida
     */
    public function canTransitionTo(string $currentStatus, string $targetStatus): ServiceResult
    {
        $current = ServiceStatus::tryFrom($currentStatus);
        $target = ServiceStatus::tryFrom($targetStatus);

        if (! $current || ! $target) {
            return $this->error('Status inválido', [
                'current' => $currentStatus,
                'target' => $targetStatus,
            ]);
        }

        $transitions = ServiceStatus::getAllowedTransitions($current->value);
        $canTransition = in_array($target->value, $transitions);

        return $this->success($canTransition, $canTransition ? 'Transição permitida' : 'Transição não permitida');
    }

    /**
     * Retorna um ServiceResult de sucesso.
     *
     * @param  mixed  $data
     */
    private function success($data, string $message = 'Operação realizada com sucesso'): ServiceResult
    {
        return ServiceResult::success($data, $message);
    }

    /**
     * Retorna um ServiceResult de erro.
     */
    private function error(string $message, array $context = []): ServiceResult
    {
        return ServiceResult::error(OperationStatus::ERROR, $message, $context);
    }
}
