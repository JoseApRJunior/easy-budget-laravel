<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\OperationStatus;
use App\Interfaces\ServiceInterface;
use App\Repositories\CommonDataRepository;
use App\Services\Abstracts\BaseTenantService;
use App\Support\ServiceResult;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CommonDataService extends BaseTenantService
{
    private CommonDataRepository $commonDataRepository;

    public function __construct( CommonDataRepository $commonDataRepository )
    {
        $this->commonDataRepository = $commonDataRepository;
    }

    protected function findEntityByIdAndTenantId( int $id, int $tenantId ): ?EloquentModel
    {
        return $this->commonDataRepository->findByIdAndTenantId( $id, $tenantId );
    }

    protected function listEntitiesByTenantId( int $tenantId, array $criteria = [], ?array $orderBy = null, ?int $limit = null, ?int $offset = null ): array
    {
        return $this->commonDataRepository->findAllByTenantId( $tenantId, $criteria, $orderBy, $limit, $offset );
    }

    protected function createEntity( array $data, int $tenantId ): EloquentModel
    {
        $data[ 'tenant_id' ] = $tenantId;
        $commonData          = new \App\Models\CommonData();
        $commonData->fill( $data );
        return $commonData;
    }

    protected function updateEntity( EloquentModel $entity, array $data, int $tenantId ): void
    {
        $entity->fill( $data );
    }

    protected function deleteEntity( EloquentModel $entity ): bool
    {
        return $entity->delete();
    }

    protected function canDeleteEntity( EloquentModel $entity ): bool
    {
        // Checar se common_data é usado em customer ativo
        $customer = \App\Models\Customer::where( 'common_data_id', $entity->id )->first();
        if ( $customer && $customer->status === 'active' ) {
            return false;
        }
        return true;
    }

    protected function saveEntity( EloquentModel $entity ): bool
    {
        return $entity->save();
    }

    protected function belongsToTenant( EloquentModel $entity, int $tenantId ): bool
    {
        return $entity->tenant_id === $tenantId;
    }

    public function validateForTenant( array $data, int $tenantId, bool $isUpdate = false ): ServiceResult
    {
        $id        = $data[ 'id' ] ?? null;
        $rules     = [ 
            'company_name' => 'required|string|max:255',
            'cnpj'         => [ 
                'required',
                'string',
                $isUpdate ? 'unique:common_data,cnpj,' . $id . ',id,tenant_id,' . $tenantId : 'unique:common_data,cnpj,NULL,id,tenant_id,' . $tenantId
            ],
            'fantasy_name' => 'nullable|string|max:255',
            'customer_id'  => [ 
                'required',
                Rule::exists( 'customers', 'id' )->where( fn( $q ) => $q->where( 'tenant_id', $tenantId ) )
            ],
        ];
        $validator = Validator::make( $data, $rules );
        if ( $validator->fails() ) {
            $messages = $validator->errors()->all();
            return $this->error( OperationStatus::INVALID_DATA, implode( ', ', $messages ) );
        }
        return $this->success();
    }

    public function getByIdAndTenantId( int $id, int $tenantId ): ServiceResult
    {
        $entity = $this->findEntityByIdAndTenantId( $id, $tenantId );
        if ( !$entity ) {
            return $this->error( OperationStatus::NOT_FOUND, 'Dados comuns não encontrados.' );
        }
        return $this->success( $entity );
    }

    public function listByTenantId( int $tenantId, array $criteria = [], ?array $orderBy = null, ?int $limit = null, ?int $offset = null ): ServiceResult
    {
        $data = $this->listEntitiesByTenantId( $tenantId, $criteria, $orderBy, $limit, $offset );
        return $this->success( $data );
    }

    public function createByTenantId( array $data, int $tenantId ): ServiceResult
    {
        return parent::createByTenantId( $data, $tenantId );
    }

    public function updateByIdAndTenantId( int $id, array $data, int $tenantId ): ServiceResult
    {
        return parent::updateByIdAndTenantId( $id, $data, $tenantId );
    }

    public function deleteByIdAndTenantId( int $id, int $tenantId ): ServiceResult
    {
        $entity = $this->findEntityByIdAndTenantId( $id, $tenantId );
        if ( !$entity ) {
            return $this->error( OperationStatus::NOT_FOUND, 'Dados comuns não encontrados.' );
        }
        if ( !$this->canDeleteEntity( $entity ) ) {
            return $this->error( OperationStatus::CONFLICT, 'Não é possível deletar dados comuns usados.' );
        }
        $this->deleteEntity( $entity );
        return $this->success( true, 'Dados comuns deletados com sucesso.' );
    }

    public function deleteManyByTenantId( array $id, int $tenantId ): ServiceResult
    {
        $deleted = $this->commonDataRepository->deleteManyByIdsAndTenantId( $id, $tenantId );
        return $this->success( $deleted, 'Dados comuns deletados.' );
    }

    public function paginateByTenantId( int $tenantId, int $page = 1, int $perPage = 15, array $criteria = [], ?array $orderBy = null ): ServiceResult
    {
        $data = $this->commonDataRepository->paginateByTenantId( $tenantId, $page, $perPage, $criteria, $orderBy );
        return $this->success( $data );
    }

    public function countByTenantId( int $tenantId, array $criteria = [] ): ServiceResult
    {
        $count = $this->commonDataRepository->countByTenantId( $tenantId, $criteria );
        return $this->success( $count );
    }

    public function existsByTenantId( array $criteria, int $tenantId ): ServiceResult
    {
        $count  = $this->commonDataRepository->countByTenantId( $tenantId, $criteria );
        $exists = $count > 0;
        return $this->success( $exists );
    }

    public function updateManyByTenantId( array $id, array $data, int $tenantId ): ServiceResult
    {
        $updated = $this->commonDataRepository->updateManyByTenantId( $id, $data, $tenantId );
        return $this->success( $updated );
    }

    public function findByAndTenantId( array $criteria, int $tenantId, ?array $orderBy = null, ?int $limit = null ): ServiceResult
    {
        $data = $this->commonDataRepository->findAllByTenantId( $tenantId, $criteria, $orderBy, $limit );
        return $this->success( $data );
    }

    public function listByCustomerIdAndTenantId( int $customerId, int $tenantId ): ServiceResult
    {
        $criteria = [ 'customer_id' => $customerId ];
        $data     = $this->listEntitiesByTenantId( $tenantId, $criteria );
        return $this->success( $data, 'Dados comuns do cliente listados.' );
    }

    public function createForCustomer( array $commonData, int $tenantId, int $customerId ): ServiceResult
    {
        return DB::transaction( function () use ($commonData, $tenantId, $customerId) {
            $commonData[ 'tenant_id' ]   = $tenantId;
            $commonData[ 'customer_id' ] = $customerId;
            $validation                  = $this->validateForTenant( $commonData, $tenantId, false );
            if ( !$validation->isSuccess() ) {
                return $validation;
            }
            $entity = $this->createEntity( $commonData, $tenantId );
            return $this->success( $entity, 'Dados comuns criados para cliente.' );
        } );
    }

    public function getBySlugAndTenantId( string $slug, int $tenantId ): ServiceResult
    {
        return $this->error( OperationStatus::NOT_SUPPORTED, 'Método não suportado para CommonDataService (sem slug).' );
    }

    public function listActiveByTenantId( int $tenantId, ?array $orderBy = null, ?int $limit = null ): ServiceResult
    {
        $criteria = []; // Assume all common data are active, or add 'status' => 'active' if model has status
        $data     = $this->listEntitiesByTenantId( $tenantId, $criteria, $orderBy, $limit );
        return $this->success( $data, 'Dados comuns listados.' );
    }

    public function validate( array $data, bool $isUpdate = false ): ServiceResult
    {
        return $this->validateForTenant( $data, $this->tenantId(), $isUpdate );
    }

}
