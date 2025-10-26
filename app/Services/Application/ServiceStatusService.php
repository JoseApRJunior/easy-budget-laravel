<?php

declare(strict_types=1);

namespace App\Services\Application;

use App\Enums\OperationStatus;
use App\Enums\ServiceStatusEnum;
use App\Support\ServiceResult;

/**
 * Serviço para gerenciamento de status de serviços usando enums.
 *
 * Este serviço fornece funcionalidades para trabalhar com status de serviços
 * através de enums, substituindo o modelo ServiceStatus por ServiceStatusEnum.
 * Como os status agora são enums, este serviço foca em fornecer métodos utilitários
 * para trabalhar com os status de forma type-safe.
 *
 * Funcionalidades principais:
 * - Obter todos os status disponíveis
 * - Buscar status por valor
 * - Validar se um status é válido
 * - Obter metadados dos status (nome, cor, ícone, etc.)
 * - Gerenciar transições de status
 *
 * @package App\Services
 */
class ServiceStatusService
{
    /**
     * Obtém todos os status de serviço disponíveis.
     *
     * @return array
     */
    public function getAllStatuses(): array
    {
        return ServiceStatusEnum::cases();
    }

    /**
     * Obtém um status específico pelo seu valor.
     *
     * @param string $value
     * @return ServiceStatusEnum|null
     */
    public function getStatusByValue( string $value ): ?ServiceStatusEnum
    {
        return ServiceStatusEnum::tryFrom( $value );
    }

    /**
     * Verifica se um valor de status é válido.
     *
     * @param string $value
     * @return bool
     */
    public function isValidStatus( string $value ): bool
    {
        return ServiceStatusEnum::tryFrom( $value ) !== null;
    }

    /**
     * Obtém os metadados de um status específico.
     *
     * @param string $value
     * @return ServiceResult
     */
    public function getStatusMetadata( string $value ): ServiceResult
    {
        $status = $this->getStatusByValue( $value );

        if ( $status === null ) {
            return $this->error( "Status inválido: {$value}" );
        }

        return $this->success( [
            'value'       => $status->value,
            'name'        => $status->getName(),
            'color'       => $status->getColor(),
            'icon'        => $status->getIcon(),
            'order_index' => $status->getOrderIndex(),
            'is_active'   => $status->isActive(),
        ], 'Metadados do status obtidos com sucesso' );
    }

    /**
     * Obtém todas as opções de status formatadas para uso em selects/forms.
     *
     * @return array
     */
    public function getStatusOptions(): array
    {
        $options = [];
        foreach ( ServiceStatusEnum::cases() as $status ) {
            $options[ $status->value ] = [
                'value' => $status->value,
                'label' => $status->getName(),
                'color' => $status->getColor(),
                'icon'  => $status->getIcon(),
            ];
        }
        return $options;
    }

    /**
     * Retorna transições permitidas para um status
     */
    public function getAllowedTransitions( string $currentStatus ): ServiceResult
    {
        $status = ServiceStatusEnum::tryFrom( $currentStatus );

        if ( !$status ) {
            return $this->error( 'Status atual inválido', [ 'status' => $currentStatus ] );
        }

        $transitions = ServiceStatusEnum::getAllowedTransitions( $status->value );

        return $this->success( $transitions, 'Transições permitidas recuperadas' );
    }

    /**
     * Verifica se uma transição é permitida
     */
    public function canTransitionTo( string $currentStatus, string $targetStatus ): ServiceResult
    {
        $current = ServiceStatusEnum::tryFrom( $currentStatus );
        $target  = ServiceStatusEnum::tryFrom( $targetStatus );

        if ( !$current || !$target ) {
            return $this->error( 'Status inválido', [
                'current' => $currentStatus,
                'target'  => $targetStatus
            ] );
        }

        $transitions   = ServiceStatusEnum::getAllowedTransitions( $current->value );
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

}
