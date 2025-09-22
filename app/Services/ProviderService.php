<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\OperationStatus;
use App\Interfaces\ServiceInterface;
use App\Repositories\AddressRepository;
use App\Repositories\CommonDataRepository;
use App\Repositories\ContactRepository;
use App\Repositories\ProviderRepository;
use App\Services\Abstracts\BaseTenantService;
use App\Support\ServiceResult;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ProviderService extends BaseTenantService implements ServiceInterface
{
    private ProviderRepository   $providerRepository;
    private CommonDataRepository $commonDataRepository;
    private ContactRepository    $contactRepository;
    private AddressRepository    $addressRepository;

    public function __construct(
        ProviderRepository $providerRepository,
        CommonDataRepository $commonDataRepository,
        ContactRepository $contactRepository,
        AddressRepository $addressRepository,
    ) {
        $this->providerRepository   = $providerRepository;
        $this->commonDataRepository = $commonDataRepository;
        $this->contactRepository    = $contactRepository;
        $this->addressRepository    = $addressRepository;
    }

    protected function findEntityByIdAndTenantId( int $id, int $tenant_id ): ?EloquentModel
    {
        return $this->providerRepository->findByIdAndTenantId( $id, $tenant_id );
    }

    protected function listEntitiesByTenantId( int $tenant_id, array $filters = [], ?array $orderBy = null, ?int $limit = null, ?int $offset = null ): array
    {
        return $this->providerRepository->findAllByTenantId( $tenant_id, $filters, $orderBy, $limit, $offset );
    }

    protected function createEntity( array $data, int $tenant_id ): EloquentModel
    {
        $data[ 'tenant_id' ] = $tenant_id;
        $provider            = new \App\Models\Provider();
        $provider->fill( $data );
        return $provider;
    }

    protected function updateEntity( EloquentModel $entity, array $data, int $tenant_id ): void
    {
        $entity->fill( $data );
    }

    protected function deleteEntity( EloquentModel $entity ): bool
    {
        return $entity->delete();
    }

    protected function canDeleteEntity( EloquentModel $entity ): bool
    {
        $serviceCount = \App\Models\Service::where( 'provider_id', $entity->id )->count();
        $invoiceCount = \App\Models\Invoice::where( 'provider_id', $entity->id )->count();
        return $serviceCount === 0 && $invoiceCount === 0;
    }

    protected function saveEntity( EloquentModel $entity ): bool
    {
        return $entity->save();
    }

    protected function belongsToTenant( EloquentModel $entity, int $tenant_id ): bool
    {
        return (int) $entity->tenant_id === $tenant_id;
    }

    public function validateForTenant( array $data, int $tenantId, bool $isUpdate = false ): ServiceResult
    {
        $id        = $data[ 'id' ] ?? null;
        $rules     = [ 
            'company_name' => 'required|string|max:255',
            'cnpj'         => [ 
                'required',
                'string',
                Rule::unique( 'providers', 'cnpj' )->where( fn( $q ) => $q->where( 'tenant_id', $tenantId ) )->ignore( $id )
            ],
            'category_id'  => [ 
                'required',
                Rule::exists( 'categories', 'id' )->where( fn( $q ) => $q->where( 'tenant_id', $tenantId ) )
            ],
            'status'       => 'required|in:active,inactive',
        ];
        $validator = Validator::make( $data, $rules );
        if ( $validator->fails() ) {
            $messages = $validator->errors()->all();
            return $this->error( OperationStatus::INVALID_DATA, implode( ', ', $messages ) );
        }
        return $this->success();
    }

    public function getByIdAndTenantId( int $id, int $tenant_id ): ServiceResult
    {
        $entity = $this->findEntityByIdAndTenantId( $id, $tenant_id );
        if ( !$entity ) {
            return $this->error( OperationStatus::NOT_FOUND, 'Fornecedor não encontrado.' );
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
        $result = parent::createByTenantId( $data, $tenantId );
        if ( !$result->isSuccess() ) {
            return $result;
        }

        $entity = $result->getData();
        if ( isset( $data[ 'common_data' ] ) && is_array( $data[ 'common_data' ] ) ) {
            $this->commonDataRepository->createForProvider( $data[ 'common_data' ], $tenantId, $entity->id );
        }
        if ( isset( $data[ 'contact' ] ) && is_array( $data[ 'contact' ] ) ) {
            $this->contactRepository->createForProvider( $data[ 'contact' ], $tenantId, $entity->id );
        }
        if ( isset( $data[ 'addresses' ] ) && is_array( $data[ 'addresses' ] ) ) {
            $this->addressRepository->createForProvider( $data[ 'addresses' ], $tenantId, $entity->id );
        }
        $entity->load( [ 'commonData', 'contact', 'addresses' ] );
        return $this->success( $entity, 'Fornecedor criado com sucesso.' );
    }

    public function updateByIdAndTenantId( int $id, array $data, int $tenantId ): ServiceResult
    {
        $result = parent::updateByIdAndTenantId( $id, $tenant_id, $data );
        if ( !$result->isSuccess() ) {
            return $result;
        }

        $entity = $result->getData();
        if ( isset( $data[ 'common_data' ] ) && is_array( $data[ 'common_data' ] ) ) {
            $this->commonDataRepository->updateForProvider( $data[ 'common_data' ], $tenant_id, $entity->id );
        }
        if ( isset( $data[ 'contact' ] ) && is_array( $data[ 'contact' ] ) ) {
            $this->contactRepository->updateForProvider( $data[ 'contact' ], $tenant_id, $entity->id );
        }
        if ( isset( $data[ 'addresses' ] ) && is_array( $data[ 'addresses' ] ) ) {
            $this->addressRepository->updateForProvider( $data[ 'addresses' ], $tenant_id, $entity->id );
        }
        $entity->load( [ 'commonData', 'contact', 'addresses' ] );
        return $this->success( $entity, 'Fornecedor atualizado com sucesso.' );
    }

    public function deleteByIdAndTenantId( int $id, int $tenantId ): ServiceResult
    {
        $entity = $this->findEntityByIdAndTenantId( $id, $tenantId );
        if ( !$entity ) {
            return $this->error( OperationStatus::NOT_FOUND, 'Fornecedor não encontrado.' );
        }
        if ( !$this->canDeleteEntity( $entity ) ) {
            return $this->error( OperationStatus::CONFLICT, 'Não é possível deletar fornecedor com serviços ou faturas.' );
        }
        $this->deleteEntity( $entity );
        return $this->success( true, 'Fornecedor deletado com sucesso.' );
    }

    public function deleteManyByTenantId( array $id, int $tenantId ): ServiceResult
    {
        $deleted = $this->providerRepository->deleteManyByIdsAndTenantId( $id, $tenantId );
        return $this->success( $deleted, 'Fornecedores deletados.' );
    }

    public function paginateByTenantId( int $tenantId, int $page = 1, int $perPage = 15, array $criteria = [], ?array $orderBy = null ): ServiceResult
    {
        $data = $this->providerRepository->paginateByTenantId( $tenantId, $page, $perPage, $criteria, $orderBy );
        return $this->success( $data );
    }

    public function countByTenantId( int $tenantId, array $criteria = [] ): ServiceResult
    {
        $count = $this->providerRepository->countByTenantId( $tenantId, $criteria );
        return $this->success( $count );
    }

    public function existsByTenantId( array $criteria, int $tenantId ): ServiceResult
    {
        $count  = $this->providerRepository->countByTenantId( $tenantId, $criteria );
        $exists = $count > 0;
        return $this->success( $exists );
    }

    public function updateManyByTenantId( array $id, array $data, int $tenantId ): ServiceResult
    {
        $updated = $this->providerRepository->updateManyByTenantId( $id, $data, $tenantId );
        return $this->success( $updated );
    }

    public function findByAndTenantId( array $criteria, int $tenantId, ?array $orderBy = null, ?int $limit = null ): ServiceResult
    {
        $data = $this->providerRepository->findAllByTenantId( $tenantId, $criteria, $orderBy, $limit );
        return $this->success( $data );
    }

    public function listActiveByTenantId( int $tenantId, ?array $orderBy = null, ?int $limit = null, ?int $offset = null ): ServiceResult
    {
        $criteria = [ 'status' => 'active' ];
        $data     = $this->providerRepository->findAllByTenantId( $tenantId, $criteria, $orderBy, $limit, $offset );
        return $this->success( $data, 'Fornecedores ativos listados.' );
    }

    public function getBySlugAndTenantId( string $slug, int $tenantId ): ServiceResult
    {
        $tenantId = (int) $tenantId;
        return $this->error( OperationStatus::NOT_SUPPORTED, 'Método não suportado para ProviderService (sem slug).' );
    }

    public function validate( array $data, bool $isUpdate = false ): ServiceResult
    {
        return $this->validateForTenant( $data, $this->tenantId(), $isUpdate );
    }

}
