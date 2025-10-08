<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Interfaces\ActivatableInterface;
use App\Contracts\Interfaces\PaginatableInterface;
use App\Contracts\Interfaces\ServiceInterface;
use App\Contracts\Interfaces\SlugableInterface;
use App\Enums\OperationStatus;
use App\Models\Service;
use App\Repositories\ServiceRepository;
use App\Services\Abstracts\BaseTenantService;
use App\Support\ServiceResult;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ServiceService extends BaseTenantService implements PaginatableInterface, ActivatableInterface, SlugableInterface
{
    private ServiceRepository $serviceRepository;

    public function __construct( ServiceRepository $serviceRepository )
    {
        $this->serviceRepository = $serviceRepository;
    }

    protected function findEntityByIdAndTenantId( int $id, int $tenant_id ): ?EloquentModel
    {
        return $this->serviceRepository->findByIdAndTenantId( $id, (int) $tenant_id );
    }

    protected function listEntitiesByTenantId( int $tenant_id, array $filters = [], ?array $orderBy = null, ?int $limit = null, ?int $offset = null ): array
    {
        return $this->serviceRepository->findAllByTenantId( (int) $tenant_id, $filters, $orderBy, $limit, $offset );
    }

    protected function createEntity( array $data, int $tenant_id ): EloquentModel
    {
        $data[ 'tenant_id' ] = $tenant_id;
        $service             = new Service();
        $service->fill( $data );
        return $service;
    }

    protected function updateEntity( EloquentModel $entity, array $data, int $tenant_id ): void
    {
        $entity->fill( $data );
    }

    protected function saveEntity( EloquentModel $entity ): bool
    {
        return $entity->save();
    }

    protected function deleteEntity( EloquentModel $entity ): bool
    {
        return $entity->delete();
    }

    protected function belongsToTenant( EloquentModel $entity, int $tenant_id ): bool
    {
        return (int) $entity->tenant_id === $tenant_id;
    }

    protected function canDeleteEntity( EloquentModel $entity ): bool
    {
        $invoiceCount = \App\Models\Invoice::where( 'service_id', $entity->id )->count();
        return $invoiceCount === 0;
    }

    public function validateForTenant( array $data, int $tenant_id, bool $isUpdate = false ): ServiceResult
    {
        $rules     = [
            'name'        => [
                'required',
                'string',
                'max:255',
                $isUpdate ? Rule::unique( 'services' )->where( 'tenant_id', $tenant_id )->ignore( $data[ 'id' ] ?? null ) : Rule::unique( 'services' )->where( 'tenant_id', $tenant_id )
            ],
            'description' => 'required|string|max:1000',
            'price'       => 'required|numeric|min:0',
            'provider_id' => [ 'required', Rule::exists( 'providers', 'id' )->where( 'tenant_id', $tenant_id ) ],
            'status'      => 'required|in:active,inactive',
        ];
        $validator = Validator::make( $data, $rules );
        if ( $validator->fails() ) {
            $messages = $validator->errors()->all();
            return $this->error( OperationStatus::INVALID_DATA, implode( ', ', $messages ) );
        }
        return $this->success();
    }

    public function createByTenantId( array $data, int $tenantId ): ServiceResult
    {
        $validation = $this->validateForTenant( $data, $tenantId, false );
        if ( !$validation->isSuccess() ) {
            return $validation;
        }
        return parent::createByTenantId( $data, $tenantId );
    }

    public function updateByIdAndTenantId( int $id, array $data, int $tenantId ): ServiceResult
    {
        $validation = $this->validateForTenant( $data, $tenantId, true );
        if ( !$validation->isSuccess() ) {
            return $validation;
        }
        return parent::updateByIdAndTenantId( $id, $data, $tenantId );
    }

    public function deleteManyByTenantId( array $id, int $tenantId ): ServiceResult
    {
        $deleted = $this->serviceRepository->deleteManyByIdsAndTenantId( $id, (int) $tenantId );
        return $this->success( $deleted, 'Serviços deletados.' );
    }

    public function paginateByTenantId( int $tenantId, int $page = 1, int $perPage = 15, array $criteria = [], ?array $orderBy = null ): ServiceResult
    {
        $data = $this->serviceRepository->paginateByTenantId( (int) $tenantId, $page, $perPage, $criteria, $orderBy );
        return $this->success( $data );
    }

    public function countByTenantId( int $tenantId, array $criteria = [] ): ServiceResult
    {
        $count = $this->serviceRepository->countByTenantId( (int) $tenantId, $criteria );
        return $this->success( $count );
    }

    public function existsByTenantId( array $criteria, int $tenantId ): ServiceResult
    {
        $count  = $this->serviceRepository->countByTenantId( (int) $tenantId, $criteria );
        $exists = $count > 0;
        return $this->success( $exists );
    }

    public function updateManyByTenantId( array $id, array $data, int $tenantId ): ServiceResult
    {
        $updated = $this->serviceRepository->updateManyByTenantId( $id, $data, (int) $tenantId );
        return $this->success( $updated );
    }

    public function findByAndTenantId( array $criteria, int $tenantId, ?array $orderBy = null, ?int $limit = null ): ServiceResult
    {
        $data = $this->serviceRepository->findAllByTenantId( (int) $tenantId, $criteria, $orderBy, $limit );
        return $this->success( $data );
    }

    public function listActiveByTenantId( int $tenantId, array $filters = [], ?array $orderBy = null, ?int $limit = null ): ServiceResult
    {
        $defaultFilters = [ 'status' => 'active' ];
        $filters        = array_merge( $defaultFilters, $filters );
        $data           = $this->listEntitiesByTenantId( $tenantId, $filters, $orderBy, $limit, null );
        return $this->success( $data, 'Serviços ativos listados.' );
    }

    public function getBySlugAndTenantId( string $slug, int $tenantId ): ServiceResult
    {
        $entity = $this->serviceRepository->findBySlugAndTenantId( $slug, $tenantId );
        if ( !$entity ) {
            return $this->error( OperationStatus::NOT_FOUND, 'Serviço não encontrado pelo slug.' );
        }
        return $this->success( $entity, 'Serviço encontrado pelo slug.' );
    }

    /**
     * Busca service por ID e tenant_id.
     */
    public function getByIdAndTenantId( int $id, int $tenant_id ): ServiceResult
    {
        $service = $this->findEntityByIdAndTenantId( $id, $tenant_id );
        if ( !$service ) {
            return $this->error( OperationStatus::NOT_FOUND, 'Service não encontrado.' );
        }
        return $this->success( $service, 'Service encontrado.' );
    }

    /**
     * Lista services por tenant_id.
     */
    public function listByTenantId( int $tenant_id, array $filters = [], ?array $orderBy = null, ?int $limit = null, ?int $offset = null ): ServiceResult
    {
        $services = $this->listEntitiesByTenantId( $tenant_id, $filters, $orderBy, $limit, $offset );
        return $this->success( $services, 'Services listados.' );
    }

    /**
     * Deleta service por ID e tenant_id.
     */
    public function deleteByIdAndTenantId( int $id, int $tenant_id ): ServiceResult
    {
        $service = $this->findEntityByIdAndTenantId( $id, $tenant_id );
        if ( !$service ) {
            return $this->error( OperationStatus::NOT_FOUND, 'Service não encontrado.' );
        }

        if ( !$this->canDeleteEntity( $service ) ) {
            return $this->error( OperationStatus::INVALID_DATA, 'Service não pode ser deletado.' );
        }

        if ( !$this->deleteEntity( $service ) ) {
            return $this->error( OperationStatus::ERROR, 'Falha ao deletar service.' );
        }

        return $this->success( null, 'Service deletado com sucesso.' );
    }

    public function validate( array $data, bool $isUpdate = false ): ServiceResult
    {
        $tenantId = $this->tenantId();
        return $this->validateForTenant( $data, $tenantId, $isUpdate );
    }

    public function paginate( int $perPage = 15, array $filters = [] ): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        // Implementação básica - pode ser expandida conforme necessidade
        return new \Illuminate\Pagination\LengthAwarePaginator( [], 0, $perPage );
    }

    public function cursorPaginate( int $perPage = 15, array $filters = [] ): \Illuminate\Contracts\Pagination\CursorPaginator
    {
        // Implementação básica - pode ser expandida conforme necessidade
        return new \Illuminate\Pagination\CursorPaginator( [], $perPage, null, [] );
    }

    public function activate( int $id ): bool
    {
        $service = $this->findEntityByIdAndTenantId( $id, $this->tenantId() );
        if ( !$service ) {
            return false;
        }
        $service->active = true;
        return $service->save();
    }

    public function deactivate( int $id ): bool
    {
        $service = $this->findEntityByIdAndTenantId( $id, $this->tenantId() );
        if ( !$service ) {
            return false;
        }
        $service->active = false;
        return $service->save();
    }

    public function isActive( int $id ): bool
    {
        $service = $this->findEntityByIdAndTenantId( $id, $this->tenantId() );
        return $service ? $service->active : false;
    }

    public function generateSlug( string $name, ?int $excludeId = null ): string
    {
        return \Illuminate\Support\Str::slug( $name );
    }

    public function isSlugUnique( string $slug, ?int $excludeId = null ): bool
    {
        $query = \App\Models\Service::where( 'slug', $slug )->where( 'tenant_id', $this->tenantId() );
        if ( $excludeId ) {
            $query->where( 'id', '!=', $excludeId );
        }
        return $query->count() === 0;
    }

    public function listActive( array $filters = [], ?array $orderBy = null, ?int $limit = null ): ServiceResult
    {
        return $this->error( OperationStatus::NOT_SUPPORTED, 'Lista ativa global não suportada para ServiceService.' );
    }

    public function getBySlug( string $slug ): ServiceResult
    {
        return $this->error( OperationStatus::NOT_SUPPORTED, 'Busca por slug global não suportada para ServiceService.' );
    }

}
