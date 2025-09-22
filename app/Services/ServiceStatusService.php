<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\OperationStatus;
use App\Interfaces\ServiceNoTenantInterface;
use App\Repositories\ServiceStatusRepository;
use App\Services\Abstracts\BaseNoTenantService;
use App\Support\ServiceResult;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ServiceStatusService extends BaseNoTenantService
{
    private ServiceStatusRepository $serviceStatusRepository;

    public function __construct( ServiceStatusRepository $serviceStatusRepository )
    {
        parent::__construct();
        $this->serviceStatusRepository = $serviceStatusRepository;
    }

    /**
     * Retorna a classe do modelo ServiceStatus.
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    protected function getModelClass(): \Illuminate\Database\Eloquent\Model
    {
        return new \App\Models\ServiceStatus();
    }

    protected function findEntityById( int $id ): ?Model
    {
        return $this->serviceStatusRepository->findById( $id );
    }

    protected function listEntities( ?array $orderBy = null, ?int $limit = null ): array
    {
        $filters = [];
        if ( $orderBy !== null ) {
            $filters[ 'order' ] = $orderBy;
        }
        if ( $limit !== null ) {
            $filters[ 'limit' ] = $limit;
        }
        return $this->serviceStatusRepository->findAll( $filters );
    }

    protected function createEntity( array $data ): Model
    {
        $serviceStatus = new \App\Models\ServiceStatus();
        $serviceStatus->fill( $data );
        return $serviceStatus;
    }

    protected function updateEntity( int $id, array $data ): Model
    {
        $entity = $this->findEntityById( $id );
        if ( !$entity ) {
            throw new \Exception( 'Service status não encontrado para atualização.' );
        }
        $entity->fill( $data );
        return $entity;
    }

    protected function deleteEntity( int $id ): bool
    {
        $entity = $this->findEntityById( $id );
        if ( !$entity ) {
            return false;
        }
        return $entity->delete();
    }

    protected function canDeleteEntity( Model $entity ): bool
    {
        // For lookup, perhaps no delete, or check if used in services
        return true;
    }

    protected function validateForGlobal( array $data, bool $isUpdate = false ): ServiceResult
    {
        $id        = $data[ 'id' ] ?? null;
        $rules     = [ 
            'name'        => [ 
                'required',
                'string',
                'max:255',
                $isUpdate ? Rule::unique( 'service_statuses', 'name' )->ignore( $id ) : Rule::unique( 'service_statuses', 'name' )
            ],
            'description' => 'nullable|string|max:500',
            'status'      => 'required|in:active,inactive',
        ];
        $validator = Validator::make( $data, $rules );
        if ( $validator->fails() ) {
            $messages = $validator->errors()->all();
            return $this->error( OperationStatus::INVALID_DATA, implode( ', ', $messages ) );
        }
        return $this->success();
    }

    /**
     * Validação para tenant (não aplicável para serviços NoTenant).
     *
     * Este método é obrigatório por herança mas não realiza validação específica
     * de tenant, pois esta é uma classe NoTenant.
     *
     * @param array $data Dados a validar
     * @param int $tenant_id ID do tenant
     * @param bool $is_update Se é uma operação de atualização
     * @return ServiceResult Resultado da validação
     */
    protected function validateForTenant( array $data, int $tenant_id, bool $is_update = false ): ServiceResult
    {
        // Para serviços NoTenant, não há validação específica de tenant
        // Retorna sucesso pois a validação é feita pelo método validateForGlobal
        return $this->success();
    }

}
