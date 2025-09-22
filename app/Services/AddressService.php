<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\OperationStatus;
use App\Interfaces\ServiceInterface;
use App\Repositories\AddressRepository;
use App\Services\Abstracts\BaseTenantService;
use App\Support\ServiceResult;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AddressService extends BaseTenantService
{
    private AddressRepository $addressRepository;

    public function __construct( AddressRepository $addressRepository )
    {
        $this->addressRepository = $addressRepository;
    }

    protected function findEntityByIdAndTenantId( int $id, int $tenantId ): ?EloquentModel
    {
        return $this->addressRepository->findByIdAndTenantId( $id, $tenantId );
    }

    protected function listEntitiesByTenantId( int $tenantId, array $filters = [], ?array $orderBy = null, ?int $limit = null, ?int $offset = null ): array
    {
        return $this->addressRepository->findAllByTenantId( $tenantId, $filters, $orderBy, $limit, $offset );
    }

    protected function createEntity( array $data, int $tenantId ): EloquentModel
    {
        $data[ 'tenant_id' ] = $tenantId;
        $address             = new \App\Models\Address();
        $address->fill( $data );
        return $address;
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
        // Checar se address é usado em customer ativo ou invoices
        $customer = \App\Models\Customer::where( 'address_id', $entity->id )->first();
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
        return (int) $entity->tenant_id === (int) $tenantId;
    }

    public function validateForTenant( array $data, int $tenantId, bool $isUpdate = false ): ServiceResult
    {
        $rules     = [ 
            'street'      => 'required|string|max:255',
            'number'      => 'required|string|max:10',
            'zip'         => 'required|string|size:8',
            'city'        => 'required|string|max:100',
            'state'       => 'required|string|size:2',
            'type'        => 'required|in:billing,shipping',
            'customer_id' => [ 
                'required',
                Rule::exists( 'customers', 'id' )->where( fn( $q ) => $q->where( 'tenant_id', $tenantId ) ),
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
            return $this->error( OperationStatus::NOT_FOUND, 'Endereço não encontrado.' );
        }
        return $this->success( $entity );
    }

    public function listByTenantId( int $tenantId, array $filters = [], ?array $orderBy = null, ?int $limit = null, ?int $offset = null ): ServiceResult
    {
        $data = $this->listEntitiesByTenantId( $tenantId, $filters, $orderBy, $limit, $offset );
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
            return $this->error( OperationStatus::NOT_FOUND, 'Endereço não encontrado.' );
        }
        if ( !$this->canDeleteEntity( $entity ) ) {
            return $this->error( OperationStatus::CONFLICT, 'Não é possível deletar endereço usado.' );
        }
        $this->deleteEntity( $entity );
        return $this->success( true, 'Endereço deletado com sucesso.' );
    }

    public function deleteManyByTenantId( array $id, int $tenantId ): ServiceResult
    {
        $deleted = $this->addressRepository->deleteManyByIdsAndTenantId( $id, $tenantId );
        return $this->success( $deleted, 'Endereços deletados.' );
    }

    public function paginateByTenantId( int $tenantId, int $page = 1, int $perPage = 15, array $criteria = [], ?array $orderBy = null ): ServiceResult
    {
        $data = $this->addressRepository->paginateByTenantId( $tenantId, $page, $perPage, $criteria, $orderBy );
        return $this->success( $data );
    }

    public function countByTenantId( int $tenantId, array $criteria = [] ): ServiceResult
    {
        $count = $this->addressRepository->countByTenantId( $tenantId, $criteria );
        return $this->success( $count );
    }

    public function existsByTenantId( array $criteria, int $tenantId ): ServiceResult
    {
        $count  = $this->addressRepository->countByTenantId( $tenantId, $criteria );
        $exists = $count > 0;
        return $this->success( $exists );
    }

    public function updateManyByTenantId( array $id, array $data, int $tenantId ): ServiceResult
    {
        $updated = $this->addressRepository->updateManyByTenantId( $id, $data, $tenantId );
        return $this->success( $updated );
    }

    public function findByAndTenantId( array $criteria, int $tenantId, ?array $orderBy = null, ?int $limit = null ): ServiceResult
    {
        $data = $this->addressRepository->findAllByTenantId( $tenantId, $criteria, $orderBy, $limit );
        return $this->success( $data );
    }

    public function listByCustomerIdAndTenantId( int $customerId, int $tenantId, ?array $orderBy = null ): ServiceResult
    {
        $criteria = [ 'customer_id' => $customerId ];
        $data     = $this->listEntitiesByTenantId( $tenantId, $criteria, $orderBy );
        return $this->success( $data, 'Endereços do cliente listados.' );
    }

    public function createForCustomer( array $addresses, int $tenantId, int $customerId ): ServiceResult
    {
        return DB::transaction( function () use ($addresses, $tenantId, $customerId) {
            $created = [];
            foreach ( $addresses as $addressData ) {
                $addressData[ 'tenant_id' ]   = $tenantId;
                $addressData[ 'customer_id' ] = $customerId;
                $validation                   = $this->validateForTenant( $addressData, $tenantId, false );
                if ( !$validation->isSuccess() ) {
                    return $validation;
                }
                $entity    = $this->createEntity( $addressData, $tenantId );
                $created[] = $entity;
            }
            return $this->success( $created, 'Endereços criados para cliente.' );
        } );
    }

    public function getBySlugAndTenantId( string $slug, int $tenantId ): ServiceResult
    {
        return $this->error( OperationStatus::NOT_SUPPORTED, 'Método não suportado para AddressService (sem slug).' );
    }

    public function listActiveByTenantId( int $tenantId, ?array $orderBy = null, ?int $limit = null ): ServiceResult
    {
        // Assume all addresses are active, or add criteria if model has status
        $criteria = []; // or ['status' => 'active'] if applicable
        $data     = $this->listEntitiesByTenantId( $tenantId, $criteria, $orderBy, $limit );
        return $this->success( $data, 'Endereços listados.' );
    }

    public function validate( array $data, bool $isUpdate = false ): ServiceResult
    {
        return $this->validateForTenant( $data, $this->tenantId(), $isUpdate );
    }

}
