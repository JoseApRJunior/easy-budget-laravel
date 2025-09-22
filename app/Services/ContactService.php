<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\OperationStatus;
use App\Interfaces\ServiceInterface;
use App\Repositories\ContactRepository;
use App\Services\Abstracts\BaseTenantService;
use App\Support\ServiceResult;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ContactService extends BaseTenantService implements ServiceInterface
{
    private ContactRepository $contactRepository;

    public function __construct( ContactRepository $contactRepository )
    {
        $this->contactRepository = $contactRepository;
    }

    protected function findEntityByIdAndTenantId( mixed $id, int $tenantId ): ?Model
    {
        return $this->contactRepository->findByIdAndTenantId( $id, $tenantId );
    }

    protected function listEntitiesByTenantId( int $tenantId, array $criteria = [], ?array $orderBy = null, ?int $limit = null, ?int $offset = null ): array
    {
        return $this->contactRepository->findAllByTenantId( $tenantId, $criteria, $orderBy, $limit, $offset );
    }

    protected function createEntity( array $data, int $tenantId ): Model
    {
        $data[ 'tenant_id' ] = $tenantId;
        $contact             = new \App\Models\Contact();
        $contact->fill( $data );
        return $contact;
    }

    protected function updateEntity( Model $entity, array $data, int $tenantId ): void
    {
        $entity->fill( $data );
    }

    protected function deleteEntity( Model $entity ): bool
    {
        return $entity->delete();
    }

    protected function canDeleteEntity( Model $entity ): bool
    {
        // Checar se contact é usado em customer ativo
        $customer = \App\Models\Customer::where( 'contact_id', $entity->id )->first();
        if ( $customer && $customer->status === 'active' ) {
            return false;
        }
        return true;
    }

    protected function saveEntity( Model $entity ): bool
    {
        return $entity->save();
    }

    protected function belongsToTenant( Model $entity, int $tenantId ): bool
    {
        return $entity->tenant_id === $tenantId;
    }

    public function validateForTenant( array $data, int $tenantId, bool $isUpdate = false ): ServiceResult
    {
        $id        = $data[ 'id' ] ?? null;
        $rules     = [ 
            'phone'       => 'required|string|min:10',
            'email'       => [ 
                'required',
                'email',
                $isUpdate && $id
                ? Rule::unique( 'contacts' )->ignore( $id )->where( 'tenant_id', $tenantId )
                : Rule::unique( 'contacts' )->where( 'tenant_id', $tenantId ),
            ],
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

    public function getByIdAndTenantId( mixed $id, int $tenantId ): ServiceResult
    {
        $entity = $this->findEntityByIdAndTenantId( $id, $tenantId );
        if ( !$entity ) {
            return $this->error( OperationStatus::NOT_FOUND, 'Contato não encontrado.' );
        }
        return $this->success( $entity );
    }

    public function listByTenantId( int $tenantId, array $criteria = [], ?array $orderBy = null, ?int $limit = null, ?int $offset = null ): ServiceResult
    {
        $data = $this->listEntitiesByTenantId( $tenantId, $criteria, $orderBy, $limit, $offset );
        return $this->success( $data );
    }

    /**
     * Cria um novo contato para o tenant especificado.
     * Delega para a classe base que gerencia validação, criação e persistência.
     */
    public function createByTenantId( array $data, int $tenantId ): ServiceResult
    {
        return parent::createByTenantId( $data, $tenantId );
    }

    /**
     * Atualiza um contato existente pelo ID e tenant.
     * Delega para a classe base que gerencia validação, atualização e persistência.
     */
    public function updateByIdAndTenantId( int $id, int $tenantId, array $data ): ServiceResult
    {
        return parent::updateByIdAndTenantId( $id, $tenantId, $data );
    }

    public function deleteByIdAndTenantId( int $id, int $tenantId ): ServiceResult
    {
        $entity = $this->findEntityByIdAndTenantId( $id, $tenantId );
        if ( !$entity ) {
            return $this->error( OperationStatus::NOT_FOUND, 'Contato não encontrado.' );
        }
        if ( !$this->canDeleteEntity( $entity ) ) {
            return $this->error( OperationStatus::CONFLICT, 'Não é possível deletar contato usado.' );
        }
        $this->deleteEntity( $entity );
        return $this->success( true, 'Contato deletado com sucesso.' );
    }

    public function deleteManyByTenantId( array $id, int $tenantId ): ServiceResult
    {
        $deleted = $this->contactRepository->deleteManyByIdsAndTenantId( $id, $tenantId );
        return $this->success( $deleted, 'Contatos deletados.' );
    }

    public function paginateByTenantId( int $tenantId, int $page = 1, int $perPage = 15, array $criteria = [], ?array $orderBy = null ): ServiceResult
    {
        $data = $this->contactRepository->paginateByTenantId( $tenantId, $page, $perPage, $criteria, $orderBy );
        return $this->success( $data );
    }

    public function countByTenantId( int $tenantId, array $criteria = [] ): ServiceResult
    {
        $count = $this->contactRepository->countByTenantId( $tenantId, $criteria );
        return $this->success( $count );
    }

    public function existsByTenantId( array $criteria, int $tenantId ): ServiceResult
    {
        $count  = $this->contactRepository->countByTenantId( $tenantId, $criteria );
        $exists = $count > 0;
        return $this->success( $exists );
    }

    public function updateManyByTenantId( array $id, array $data, int $tenantId ): ServiceResult
    {
        $updated = $this->contactRepository->updateManyByTenantId( $id, $data, $tenantId );
        return $this->success( $updated );
    }

    public function findByAndTenantId( array $criteria, int $tenantId, ?array $orderBy = null, ?int $limit = null ): ServiceResult
    {
        $data = $this->contactRepository->findAllByTenantId( $tenantId, $criteria, $orderBy, $limit );
        return $this->success( $data );
    }

    public function listByCustomerIdAndTenantId( int $customerId, int $tenantId, ?array $orderBy = null ): ServiceResult
    {
        $criteria = [ 'customer_id' => $customerId ];
        $data     = $this->listEntitiesByTenantId( $tenantId, $criteria, $orderBy );
        return $this->success( $data, 'Contatos do cliente listados.' );
    }

    public function createForCustomer( array $contacts, int $tenantId, int $customerId ): ServiceResult
    {
        return DB::transaction( function () use ($contacts, $tenantId, $customerId) {
            $created = [];
            foreach ( $contacts as $contactData ) {
                $contactData[ 'tenant_id' ]   = $tenantId;
                $contactData[ 'customer_id' ] = $customerId;
                $validation                   = $this->validateForTenant( $contactData, $tenantId, false );
                if ( !$validation->isSuccess() ) {
                    return $validation;
                }
                $entity    = $this->createEntity( $contactData, $tenantId );
                $created[] = $entity;
            }
            return $this->success( $created, 'Contatos criados para cliente.' );
        } );
    }

    public function getBySlugAndTenantId( string $slug, int $tenantId ): ServiceResult
    {
        return $this->error( OperationStatus::NOT_SUPPORTED, 'Método não suportado para ContactService (sem slug).' );
    }

    public function listActiveByTenantId( int $tenantId, ?array $orderBy = null, ?int $limit = null ): ServiceResult
    {
        $criteria = []; // Assume all contacts are active, or add 'status' => 'active' if model has status
        $data     = $this->listEntitiesByTenantId( $tenantId, $criteria, $orderBy, $limit );
        return $this->success( $data, 'Contatos listados.' );
    }

    public function validate( array $data, bool $isUpdate = false ): ServiceResult
    {
        return $this->validateForTenant( $data, $this->tenantId(), $isUpdate );
    }

}