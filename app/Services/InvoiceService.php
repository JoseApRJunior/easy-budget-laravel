<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\OperationStatus;
use App\Interfaces\ServiceInterface;
use App\Repositories\InvoiceRepository;
use App\Services\Abstracts\BaseTenantService;
use App\Support\ServiceResult;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class InvoiceService extends BaseTenantService implements ServiceInterface
{
    private InvoiceRepository $invoiceRepository;

    public function __construct( InvoiceRepository $invoiceRepository )
    {
        $this->invoiceRepository = $invoiceRepository;
    }

    protected function findEntityByIdAndTenantId( mixed $id, int $tenantId ): ?Model
    {
        $tenantId = (int) $tenantId;
        return $this->invoiceRepository->findByIdAndTenantId( $id, (int) $tenantId );
    }

    protected function listEntitiesByTenantId( int $tenantId, array $criteria = [], ?array $orderBy = null, ?int $limit = null, ?int $offset = null ): array
    {
        $tenantId = (int) $tenantId;
        return $this->invoiceRepository->findAllByTenantId( (int) $tenantId, $criteria, $orderBy, $limit, $offset );
    }

    protected function createEntity( array $data, int $tenantId ): Model
    {
        $tenantId            = (int) $tenantId;
        $data[ 'tenant_id' ] = $tenantId;
        $invoice             = new \App\Models\Invoice();
        $invoice->fill( $data );
        $this->saveEntity( $invoice );
        return $invoice;
    }

    protected function updateEntity( Model $entity, array $data, int $tenantId ): void
    {
        $tenantId = (int) $tenantId;
        $entity->fill( $data );
        $this->saveEntity( $entity );
    }

    protected function deleteEntity( Model $entity ): bool
    {
        return $entity->delete();
    }

    protected function canDeleteEntity( Model $entity ): bool
    {
        if ( $entity->status !== 'pending' ) {
            return false;
        }
        $paymentCount = \App\Models\Payment::where( 'invoice_id', $entity->id )->count();
        return $paymentCount === 0;
    }

    protected function saveEntity( Model $entity ): bool
    {
        return $entity->save();
    }

    protected function belongsToTenant( Model $entity, int $tenantId ): bool
    {
        $tenantId = (int) $tenantId;
        return (int) $entity->tenant_id === $tenantId;
    }

    public function validateForTenant( array $data, int $tenantId, bool $isUpdate = false ): ServiceResult
    {
        $tenantId  = (int) $tenantId;
        $rules     = [
            'customer_id' => [
                'required',
                Rule::exists( 'customers', 'id' )->where( fn( $q ) => $q->where( 'tenant_id', $tenantId ) )
            ],
            'provider_id' => [
                'required',
                Rule::exists( 'providers', 'id' )->where( fn( $q ) => $q->where( 'tenant_id', $tenantId ) )
            ],
            'service_id'  => [
                'nullable',
                Rule::exists( 'services', 'id' )->where( fn( $q ) => $q->where( 'tenant_id', $tenantId ) )
            ],
            'budget_id'   => [
                'nullable',
                Rule::exists( 'budgets', 'id' )->where( fn( $q ) => $q->where( 'tenant_id', $tenantId ) )
            ],
            'amount'      => 'required|numeric|min:0',
            'status'      => 'required|in:pending,paid,canceled',
            'items'       => 'required|array|min:1',
        ];
        $validator = Validator::make( $data, $rules );
        if ( $validator->fails() ) {
            $messages = $validator->errors()->all();
            return $this->error( OperationStatus::INVALID_DATA, implode( ', ', $messages ) );
        }
        if ( isset( $data[ 'items' ] ) ) {
            foreach ( $data[ 'items' ] as $item ) {
                $itemRules     = [
                    'description' => 'required|string|max:255',
                    'quantity'    => 'required|numeric|min:1',
                    'price'       => 'required|numeric|min:0',
                ];
                $itemValidator = Validator::make( $item, $itemRules );
                if ( $itemValidator->fails() ) {
                    $messages = $itemValidator->errors()->all();
                    return $this->error( OperationStatus::INVALID_DATA, 'Item inválido: ' . implode( ', ', $messages ) );
                }
            }
        }
        return $this->success();
    }

    public function getByIdAndTenantId( mixed $id, int $tenantId ): ServiceResult
    {
        $tenantId = (int) $tenantId;
        $entity   = $this->findEntityByIdAndTenantId( $id, $tenantId );
        if ( !$entity ) {
            return $this->error( OperationStatus::NOT_FOUND, 'Fatura não encontrada.' );
        }
        return $this->success( $entity );
    }

    public function listByTenantId( int $tenantId, array $criteria = [], ?array $orderBy = null, ?int $limit = null, ?int $offset = null ): ServiceResult
    {
        $tenantId = (int) $tenantId;
        $data     = $this->listEntitiesByTenantId( $tenantId, $criteria, $orderBy, $limit, $offset );
        return $this->success( $data );
    }

    public function createByTenantId( array $data, int $tenantId ): ServiceResult
    {
        $tenantId = (int) $tenantId;
        return DB::transaction( function () use ($data, $tenantId) {
            $validation = $this->validateForTenant( $data, $tenantId, false );
            if ( !$validation->isSuccess() ) {
                return $validation;
            }
            $entity = $this->createEntity( $data, $tenantId );
            if ( isset( $data[ 'items' ] ) && is_array( $data[ 'items' ] ) ) {
                foreach ( $data[ 'items' ] as $itemData ) {
                    $entity->items()->create( [
                        'tenant_id'   => $tenantId,
                        'invoice_id'  => $entity->id,
                        'description' => $itemData[ 'description' ],
                        'quantity'    => $itemData[ 'quantity' ],
                        'price'       => $itemData[ 'price' ],
                    ] );
                }
            }
            $entity->load( 'items' );
            return $this->success( $entity, 'Fatura criada com sucesso.' );
        } );
    }

    public function updateByIdAndTenantId( mixed $id, int $tenant_id, array $data ): ServiceResult
    {
        $tenantId = (int) $tenant_id;
        return DB::transaction( function () use ($id, $data, $tenantId) {
            $entity = $this->findEntityByIdAndTenantId( $id, $tenantId );
            if ( !$entity ) {
                return $this->error( OperationStatus::NOT_FOUND, 'Fatura não encontrada.' );
            }
            $validation = $this->validateForTenant( $data, $tenantId, true );
            if ( !$validation->isSuccess() ) {
                return $validation;
            }
            $this->updateEntity( $entity, $data, $tenantId );
            if ( isset( $data[ 'items' ] ) && is_array( $data[ 'items' ] ) ) {
                $entity->items()->delete();
                foreach ( $data[ 'items' ] as $itemData ) {
                    $entity->items()->create( [
                        'tenant_id'   => $tenantId,
                        'invoice_id'  => $entity->id,
                        'description' => $itemData[ 'description' ],
                        'quantity'    => $itemData[ 'quantity' ],
                        'price'       => $itemData[ 'price' ],
                    ] );
                }
            }
            $entity->load( 'items' );
            return $this->success( $entity, 'Fatura atualizada com sucesso.' );
        } );
    }

    public function deleteByIdAndTenantId( int $id, int $tenantId ): ServiceResult
    {
        $tenantId = (int) $tenantId;
        $entity   = $this->findEntityByIdAndTenantId( $id, $tenantId );
        if ( !$entity ) {
            return $this->error( OperationStatus::NOT_FOUND, 'Fatura não encontrada.' );
        }
        if ( !$this->canDeleteEntity( $entity ) ) {
            return $this->error( OperationStatus::CONFLICT, 'Não é possível deletar fatura paga ou com pagamentos.' );
        }
        $this->deleteEntity( $entity );
        return $this->success( true, 'Fatura deletada com sucesso.' );
    }

    public function deleteManyByTenantId( array $id, int $tenantId ): ServiceResult
    {
        $tenantId = (int) $tenantId;
        $deleted  = $this->invoiceRepository->deleteManyByIdsAndTenantId( $id, (int) $tenantId );
        return $this->success( $deleted, 'Faturas deletadas.' );
    }

    public function paginateByTenantId( int $tenantId, int $page = 1, int $perPage = 15, array $criteria = [], ?array $orderBy = null ): ServiceResult
    {
        $tenantId = (int) $tenantId;
        $data     = $this->invoiceRepository->paginateByTenantId( (int) $tenantId, $page, $perPage, $criteria, $orderBy );
        return $this->success( $data );
    }

    public function countByTenantId( int $tenantId, array $criteria = [] ): ServiceResult
    {
        $tenantId = (int) $tenantId;
        $count    = $this->invoiceRepository->countByTenantId( (int) $tenantId, $criteria );
        return $this->success( $count );
    }

    public function existsByTenantId( array $criteria, int $tenantId ): ServiceResult
    {
        $tenantId = (int) $tenantId;
        $count    = $this->invoiceRepository->countByTenantId( (int) $tenantId, $criteria );
        $exists   = $count > 0;
        return $this->success( $exists );
    }

    public function updateManyByTenantId( array $id, array $data, int $tenantId ): ServiceResult
    {
        $tenantId = (int) $tenantId;
        $updated  = $this->invoiceRepository->updateManyByTenantId( $id, $data, (int) $tenantId );
        return $this->success( $updated );
    }

    public function findByAndTenantId( array $criteria, int $tenantId, ?array $orderBy = null, ?int $limit = null ): ServiceResult
    {
        $tenantId = (int) $tenantId;
        $data     = $this->invoiceRepository->findAllByTenantId( (int) $tenantId, $criteria, $orderBy, $limit );
        return $this->success( $data );
    }

    public function listByStatusAndTenantId( string $status, int $tenantId, ?array $orderBy = null, ?int $limit = null, ?int $offset = null ): ServiceResult
    {
        $tenantId = (int) $tenantId;
        $data     = $this->invoiceRepository->listByStatusAndTenantId( $status, (int) $tenantId, $orderBy, $limit, $offset );
        return $this->success( $data, 'Faturas por status listadas.' );
    }

    public function countByStatusByTenantId( string $status, int $tenantId ): ServiceResult
    {
        $tenantId = (int) $tenantId;
        $count    = $this->invoiceRepository->countByStatusByTenantId( $status, (int) $tenantId );
        return $this->success( $count, 'Contagem de faturas por status.' );
    }

    public function listByBudgetIdAndTenantId( int $budgetId, int $tenantId ): ServiceResult
    {
        $tenantId = (int) $tenantId;
        $data     = $this->invoiceRepository->listByBudgetIdAndTenantId( $budgetId, (int) $tenantId );
        return $this->success( $data, 'Faturas do orçamento listadas.' );
    }

    public function getBySlugAndTenantId( string $slug, int $tenantId ): ServiceResult
    {
        $tenantId = (int) $tenantId;
        return $this->error( OperationStatus::NOT_SUPPORTED, 'Método não suportado para InvoiceService (sem slug).' );
    }

    public function listActiveByTenantId( int $tenantId, ?array $orderBy = null, ?int $limit = null ): ServiceResult
    {
        $tenantId = (int) $tenantId;
        $criteria = [ 'status' => 'pending' ];
        $data     = $this->listEntitiesByTenantId( $tenantId, $criteria, $orderBy, $limit );
        return $this->success( $data, 'Faturas ativas listadas.' );
    }

    public function validate( array $data, bool $isUpdate = false ): ServiceResult
    {
        $rules     = [
            'amount' => 'required|numeric|min:0',
            'status' => 'required|in:pending,paid,canceled',
            'items'  => 'required|array|min:1',
        ];
        $validator = Validator::make( $data, $rules );
        if ( $validator->fails() ) {
            $messages = $validator->errors()->all();
            return $this->error( OperationStatus::INVALID_DATA, implode( ', ', $messages ) );
        }
        return $this->success();
    }

}
