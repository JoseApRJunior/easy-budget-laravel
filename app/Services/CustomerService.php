<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\OperationStatus;
use App\Models\Customer;
use App\Repositories\AddressRepository;
use App\Repositories\CommonDataRepository;
use App\Repositories\ContactRepository;
use App\Repositories\CustomerRepository;
use App\Services\Abstracts\BaseTenantService;
use App\Support\ServiceResult;
use App\Traits\SlugGenerator;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CustomerService extends BaseTenantService
{
    use SlugGenerator;

    private CustomerRepository   $customerRepository;
    private CommonDataRepository $commonDataRepository;
    private ContactRepository    $contactRepository;
    private AddressRepository    $addressRepository;

    public function __construct(
        CustomerRepository $customerRepository,
        CommonDataRepository $commonDataRepository,
        ContactRepository $contactRepository,
        AddressRepository $addressRepository,
    ) {
        $this->customerRepository   = $customerRepository;
        $this->commonDataRepository = $commonDataRepository;
        $this->contactRepository    = $contactRepository;
        $this->addressRepository    = $addressRepository;
    }

    protected function findEntityByIdAndTenantId( int $id, int $tenantId ): ?Model
    {
        return $this->customerRepository->findByIdAndTenantId( $id, (int) $tenantId );
    }

    protected function listEntitiesByTenantId( int $tenantId, array $filters = [], ?array $orderBy = null, ?int $limit = null, ?int $offset = null ): array
    {
        return $this->customerRepository->listByTenantId( $tenantId, $filters, $orderBy, $limit, $offset );
    }

    protected function createEntity( array $data, int $tenantId ): Model
    {
        $customer = new Customer();
        $customer->fill( [ 
            'tenant_id' => $tenantId,
            'name'      => $data[ 'name' ],
            'status'    => $data[ 'status' ] ?? 'active',
            'slug'      => $this->generateSlug( $data[ 'name' ] ),
        ] );
        return $customer;
    }

    protected function updateEntity( Model $entity, array $data, int $tenantId ): void
    {
        $entity->fill( $data );
    }

    protected function saveEntity( Model $entity ): bool
    {
        return $entity->save();
    }

    protected function deleteEntity( Model $entity ): bool
    {
        // Deletar entidades relacionadas
        $this->commonDataRepository->deleteByCustomerId( $entity->id, $entity->tenant_id );
        $this->contactRepository->deleteByCustomerId( $entity->id, $entity->tenant_id );
        $this->addressRepository->deleteByCustomerId( $entity->id, $entity->tenant_id );
        return $entity->delete();
    }

    protected function belongsToTenant( Model $entity, int $tenantId ): bool
    {
        return (int) $entity->tenant_id === (int) $tenantId;
    }

    protected function canDeleteEntity( Model $entity ): bool
    {

        // Verificar se tem budgets ou invoices associadas
        return $entity->budgets()->count() === 0 && $entity->invoices()->count() === 0;
    }

    public function validateForTenant( array $data, int $tenant_id, bool $isUpdate = false ): ServiceResult
    {
        $rules = [ 
            'name'  => 'required|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:20',
        ];

        $nameRule = 'required|string|max:255';
        if ( $isUpdate ) {
            $nameRule .= '|unique:customers,name,NULL,id,tenant_id,' . $tenant_id;
        } else {
            $nameRule .= '|unique:customers,name,NULL,id,tenant_id,' . $tenant_id;
        }
        $rules[ 'name' ] = $nameRule;

        $validator = Validator::make( $data, $rules );
        if ( $validator->fails() ) {
            $messages = $validator->errors()->all();
            return $this->error( OperationStatus::INVALID_DATA, implode( ', ', $messages ) );
        }

        return $this->success();
    }

    /**
     * Valida os dados de entrada para operações de criação ou atualização.
     *
     * @param array<string, mixed> $data Dados a serem validados
     * @param bool $isUpdate Indica se é uma operação de atualização
     * @return ServiceResult Resultado da validação
     */
    public function validate( array $data, bool $isUpdate = false ): ServiceResult
    {
        // Extract tenant_id from data if available, otherwise use 0 as default
        $tenantId = $data[ 'tenant_id' ] ?? 0;

        // Delegate to validateForTenant method
        return $this->validateForTenant( $data, $tenantId, $isUpdate );
    }

    // MÉTODOS ABSTRATOS OBRIGATÓRIOS DA BaseTenantService

    /**
     * Busca um cliente pelo ID e tenant_id.
     *
     * @param int $id ID do cliente
     * @param int $tenantId ID do tenant
     * @return ServiceResult
     */
    public function getByIdAndTenantId( int $id, int $tenantId ): ServiceResult
    {
        try {
            $customer = $this->findEntityByIdAndTenantId( $id, $tenantId );
            if ( !$customer ) {
                return $this->error( OperationStatus::NOT_FOUND, 'Cliente não encontrado.' );
            }

            return $this->success( $customer, 'Cliente encontrado com sucesso.' );
        } catch ( Exception $e ) {
            return $this->error( OperationStatus::ERROR, 'Falha ao buscar cliente: ' . $e->getMessage(), null, $e );
        }
    }

    /**
     * Lista clientes por tenant_id com filtros.
     *
     * @param int $tenantId ID do tenant
     * @param array $filters Filtros opcionais
     * @param ?array $orderBy Ordem dos resultados
     * @param ?int $limit Limite de resultados
     * @param ?int $offset Offset dos resultados
     * @return ServiceResult
     */
    public function listByTenantId( int $tenantId, array $filters = [], ?array $orderBy = null, ?int $limit = null, ?int $offset = null ): ServiceResult
    {
        try {
            $customers = $this->listEntitiesByTenantId( $tenantId, $filters, $orderBy, $limit, $offset );
            return $this->success( $customers, 'Clientes listados com sucesso.' );
        } catch ( Exception $e ) {
            return $this->error( OperationStatus::ERROR, 'Falha ao listar clientes: ' . $e->getMessage(), null, $e );
        }
    }

    /**
     * Cria cliente para tenant_id.
     *
     * @param array $data Dados do cliente
     * @param int $tenantId ID do tenant
     * @return ServiceResult
     */
    public function createByTenantId( array $data, int $tenantId ): ServiceResult
    {
        try {
            // Validação específica
            $validation = $this->validateForTenant( $data, $tenantId, false );
            if ( !$validation->isSuccess() ) {
                return $validation;
            }

            DB::beginTransaction();

            $customer = $this->createEntity( $data, $tenantId );
            $saved    = $this->saveEntity( $customer );

            if ( !$saved ) {
                DB::rollBack();
                return $this->error( OperationStatus::ERROR, 'Falha ao criar cliente.' );
            }

            DB::commit();
            return $this->success( $customer, 'Cliente criado com sucesso.' );
        } catch ( Exception $e ) {
            DB::rollBack();
            return $this->error( OperationStatus::ERROR, 'Falha ao criar cliente: ' . $e->getMessage(), null, $e );
        }
    }

    /**
     * Atualiza cliente por ID e tenant_id.
     *
     * @param int $id ID do cliente
     * @param int $tenantId ID do tenant
     * @param array $data Dados de atualização
     * @return ServiceResult
     */
    public function updateByIdAndTenantId( int $id, array $data, int $tenantId ): ServiceResult
    {
        try {
            $customer = $this->findEntityByIdAndTenantId( $id, $tenantId );
            if ( !$customer ) {
                return $this->error( OperationStatus::NOT_FOUND, 'Cliente não encontrado.' );
            }

            // Validação específica
            $validation = $this->validateForTenant( $data, $tenantId, true );
            if ( !$validation->isSuccess() ) {
                return $validation;
            }

            DB::beginTransaction();

            $this->updateEntity( $customer, $data, $tenantId );
            $saved = $this->saveEntity( $customer );

            if ( !$saved ) {
                DB::rollBack();
                return $this->error( OperationStatus::ERROR, 'Falha ao atualizar cliente.' );
            }

            DB::commit();
            return $this->success( $customer, 'Cliente atualizado com sucesso.' );
        } catch ( Exception $e ) {
            DB::rollBack();
            return $this->error( OperationStatus::ERROR, 'Falha ao atualizar cliente: ' . $e->getMessage(), null, $e );
        }
    }

    /**
     * Deleta cliente por ID e tenant_id.
     *
     * @param int $id ID do cliente
     * @param int $tenantId ID do tenant
     * @return ServiceResult
     */
    public function deleteByIdAndTenantId( int $id, int $tenantId ): ServiceResult
    {
        try {
            $customer = $this->findEntityByIdAndTenantId( $id, $tenantId );
            if ( !$customer ) {
                return $this->error( OperationStatus::NOT_FOUND, 'Cliente não encontrado.' );
            }

            // Verificar se pode deletar
            if ( !$this->canDeleteEntity( $customer ) ) {
                return $this->error( OperationStatus::CONFLICT, 'Não é possível deletar este cliente.' );
            }

            DB::beginTransaction();

            $deleted = $this->deleteEntity( $customer );

            if ( !$deleted ) {
                DB::rollBack();
                return $this->error( OperationStatus::ERROR, 'Falha ao deletar cliente.' );
            }

            DB::commit();
            return $this->success( null, 'Cliente deletado com sucesso.' );
        } catch ( Exception $e ) {
            DB::rollBack();
            return $this->error( OperationStatus::ERROR, 'Falha ao deletar cliente: ' . $e->getMessage(), null, $e );
        }
    }

    /**
     * Cria cliente com entidades relacionadas em transação.
     */
    public function createCustomerWithRelations( array $data, int $tenantId ): ServiceResult
    {
        return DB::transaction( function () use ($data, $tenantId) {
            $validation = $this->validateForTenant( $data, $tenantId, false );
            if ( !$validation->isSuccess() ) {
                return $validation;
            }

            $customer = $this->createEntity( $data, $tenantId );
            if ( !$this->saveEntity( $customer ) ) {
                return $this->error( OperationStatus::ERROR, 'Falha ao salvar cliente.' );
            }

            // Criar entidades relacionadas se presentes
            if ( isset( $data[ 'common_data' ] ) && is_array( $data[ 'common_data' ] ) ) {
                $this->commonDataRepository->createForCustomer( $data[ 'common_data' ], (int) $tenantId, $customer->id );
            }

            if ( isset( $data[ 'contact' ] ) && is_array( $data[ 'contact' ] ) ) {
                $this->contactRepository->createForCustomer( $data[ 'contact' ], (int) $tenantId, $customer->id );
            }

            if ( isset( $data[ 'addresses' ] ) && is_array( $data[ 'addresses' ] ) ) {
                $this->addressRepository->createForCustomer( $data[ 'addresses' ], (int) $tenantId, $customer->id );
            }

            // Carregar relações
            $customer->load( [ 'commonData', 'contact', 'addresses' ] );

            return $this->success( $customer, 'Cliente criado com sucesso.' );
        } );
    }

}
