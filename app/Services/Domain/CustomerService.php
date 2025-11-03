<?php

declare(strict_types=1);

namespace App\Services\Domain;

use App\Helpers\DateHelper;
use App\Helpers\ValidationHelper;
use App\Models\Address;
use App\Models\CommonData;
use App\Models\Contact;
use App\Models\Customer;
use App\Repositories\CustomerRepository;
use App\Services\Application\CustomerInteractionService;
use App\Services\Core\Abstracts\AbstractBaseService;
use App\Services\Shared\EntityDataService;
use App\Support\ServiceResult;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Serviço de Clientes - Lógica de negócio para gestão de clientes
 *
 * Centraliza operações complexas relacionadas a clientes,
 * incluindo criação, atualização, busca e relacionamentos.
 */
class CustomerService extends AbstractBaseService
{
    public function __construct(
        private CustomerRepository $customerRepository,
        private CustomerInteractionService $interactionService,
        private EntityDataService $entityDataService,
    ) {}

    /**
     * Lista clientes com filtros
     */
    public function listCustomers( array $filters = [] ): ServiceResult
    {
        try {
            $customers = $this->customerRepository->listByFilters( $filters );
            return $this->success( $customers, 'Clientes listados com sucesso' );
        } catch ( \Exception $e ) {
            return $this->error( 'Erro ao listar clientes: ' . $e->getMessage() );
        }
    }

    /**
     * Cria novo cliente
     */
    public function createCustomer( array $data ): ServiceResult
    {
        try {
            // Converter data de nascimento do formato brasileiro para americano se necessário
            if ( !empty( $data[ 'birth_date' ] ) ) {
                try {
                    // Verificar se já está no formato YYYY-MM-DD
                    if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $data[ 'birth_date' ] ) ) {
                        // Já está no formato correto
                    } else {
                        // Tentar converter de DD/MM/YYYY para YYYY-MM-DD
                        $data[ 'birth_date' ] = \Carbon\Carbon::createFromFormat( 'd/m/Y', $data[ 'birth_date' ] )->format( 'Y-m-d' );
                    }
                } catch ( \Exception $e ) {
                    // Se não conseguir converter, assumir que já está no formato correto
                    \Illuminate\Support\Facades\Log::warning( 'Erro ao converter data de nascimento: ' . $e->getMessage(), [
                        'original_date' => $data[ 'birth_date' ]
                    ] );
                }
            }

            $validation = $this->validateCustomerData( $data );
            if ( !$validation->isSuccess() ) {
                return $validation;
            }

            $tenantId = auth()->user()->tenant_id;

            $customer = DB::transaction( function () use ($data, $tenantId) {
                // Usar EntityDataService para criar dados compartilhados
                $entityData = $this->entityDataService->createCompleteEntityData( $data, $tenantId );

                // Criar Customer com IDs gerados
                $customer = Customer::create( [
                    'tenant_id'      => $tenantId,
                    'common_data_id' => $entityData[ 'common_data' ]->id,
                    'contact_id'     => $entityData[ 'contact' ]->id,
                    'address_id'     => $entityData[ 'address' ]->id,
                    'status'         => 'active',
                ] );

                return $customer->load( [ 'commonData', 'contact', 'address' ] );
            } );

            return $this->success( $customer, 'Cliente criado com sucesso' );
        } catch ( \Exception $e ) {
            return $this->error( 'Erro ao criar cliente: ' . $e->getMessage() );
        }
    }

    /**
     * Valida dados do cliente
     */
    private function validateCustomerData( array $data, ?int $excludeCustomerId = null ): ServiceResult
    {
        // Validar campos obrigatórios
        if ( empty( $data[ 'first_name' ] ) || empty( $data[ 'last_name' ] ) ) {
            return $this->error( 'Nome e sobrenome são obrigatórios' );
        }

        // Validar email usando helper
        if ( empty( $data[ 'email_personal' ] ) || !ValidationHelper::isValidEmail( $data[ 'email_personal' ] ) ) {
            return $this->error( 'Email pessoal válido é obrigatório' );
        }

        // Validar telefone usando helper
        if ( empty( $data[ 'phone_personal' ] ) || !ValidationHelper::isValidPhone( $data[ 'phone_personal' ] ) ) {
            return $this->error( 'Telefone pessoal válido é obrigatório' );
        }

        // Verificar se tem pelo menos um documento
        $hasCpf  = !empty( $data[ 'cpf' ] );
        $hasCnpj = !empty( $data[ 'cnpj' ] );

        if ( !$hasCpf && !$hasCnpj ) {
            return $this->error( 'CPF ou CNPJ é obrigatório' );
        }

        // Validar CPF usando helper
        if ( $hasCpf && !ValidationHelper::isValidCpf( $data[ 'cpf' ] ) ) {
            return $this->error( 'CPF inválido' );
        }

        // Validar CNPJ usando helper
        if ( $hasCnpj && !ValidationHelper::isValidCnpj( $data[ 'cnpj' ] ) ) {
            return $this->error( 'CNPJ inválido' );
        }

        // Validar CEP usando helper
        if ( !empty( $data[ 'cep' ] ) && !ValidationHelper::isValidCep( $data[ 'cep' ] ) ) {
            return $this->error( 'CEP inválido' );
        }

        // Validar data de nascimento se fornecida
        if ( !empty( $data[ 'birth_date' ] ) ) {
            try {
                $birthDate = \Carbon\Carbon::parse( $data[ 'birth_date' ] );
                $age       = $birthDate->age;

                if ( $age < 18 ) {
                    return $this->error( 'Cliente deve ter pelo menos 18 anos' );
                }

                if ( $birthDate->isFuture() ) {
                    return $this->error( 'Data de nascimento não pode ser no futuro' );
                }
            } catch ( \Exception $e ) {
                return $this->error( 'Data de nascimento inválida' );
            }
        }

        // Validar endereço completo
        $requiredAddressFields = [ 'cep', 'address', 'neighborhood', 'city', 'state' ];
        foreach ( $requiredAddressFields as $field ) {
            if ( empty( $data[ $field ] ) ) {
                return $this->error( 'Endereço completo é obrigatório' );
            }
        }

        // Validar unicidade de email no tenant
        if ( !$this->isEmailUniqueInTenant( $data[ 'email_personal' ], $excludeCustomerId ) ) {
            return $this->error( 'Email já cadastrado para outro cliente' );
        }

        return $this->success();
    }

    /**
     * Busca cliente por ID
     */
    public function findCustomer( int $id ): ServiceResult
    {
        try {
            $customer = $this->customerRepository->findByIdAndTenantId( $id, auth()->user()->tenant_id );
            if ( !$customer ) {
                return $this->error( 'Cliente não encontrado' );
            }
            return $this->success( $customer );
        } catch ( \Exception $e ) {
            return $this->error( 'Erro ao buscar cliente: ' . $e->getMessage() );
        }
    }

    /**
     * Atualiza cliente
     */
    public function updateCustomer( int $id, array $data ): ServiceResult
    {
        try {
            // Converter data de nascimento do formato brasileiro para americano se necessário
            if ( !empty( $data[ 'birth_date' ] ) ) {
                try {
                    // Verificar se já está no formato YYYY-MM-DD
                    if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $data[ 'birth_date' ] ) ) {
                        // Já está no formato correto
                    } else {
                        // Tentar converter de DD/MM/YYYY para YYYY-MM-DD
                        $data[ 'birth_date' ] = \Carbon\Carbon::createFromFormat( 'd/m/Y', $data[ 'birth_date' ] )->format( 'Y-m-d' );
                    }
                } catch ( \Exception $e ) {
                    // Se não conseguir converter, assumir que já está no formato correto
                    \Illuminate\Support\Facades\Log::warning( 'Erro ao converter data de nascimento: ' . $e->getMessage(), [
                        'original_date' => $data[ 'birth_date' ]
                    ] );
                }
            }

            $customer = $this->customerRepository->findByIdAndTenantId( $id, auth()->user()->tenant_id );
            if ( !$customer ) {
                return $this->error( 'Cliente não encontrado' );
            }

            $validation = $this->validateCustomerData( $data, $customer->id );
            if ( !$validation->isSuccess() ) {
                return $validation;
            }

            $updated = DB::transaction( function () use ($customer, $data) {
                // Carregar relacionamentos
                $customer->load( [ 'commonData', 'contact', 'address' ] );

                // Usar EntityDataService para atualizar
                $this->entityDataService->updateCompleteEntityData(
                    $customer->commonData,
                    $customer->contact,
                    $customer->address,
                    $data,
                );

                // Atualizar status do Customer se fornecido
                if ( isset( $data[ 'status' ] ) ) {
                    $customer->update( [ 'status' => $data[ 'status' ] ] );
                }

                return $customer->fresh( [ 'commonData', 'contact', 'address' ] );
            } );

            return $this->success( $updated, 'Cliente atualizado com sucesso' );
        } catch ( \Exception $e ) {
            return $this->error( 'Erro ao atualizar cliente: ' . $e->getMessage() );
        }
    }

    /**
     * Remove cliente
     */
    public function deleteCustomer( int $id ): ServiceResult
    {
        try {
            $customer = $this->customerRepository->findByIdAndTenantId( $id, auth()->user()->tenant_id );
            if ( !$customer ) {
                return $this->error( 'Cliente não encontrado' );
            }

            $this->customerRepository->delete( $customer );
            return $this->success( null, 'Cliente removido com sucesso' );
        } catch ( \Exception $e ) {
            return $this->error( 'Erro ao remover cliente: ' . $e->getMessage() );
        }
    }

    /**
     * Cria interação com cliente
     */
    public function createInteraction( int $customerId, array $data ): ServiceResult
    {
        try {
            $customer = $this->customerRepository->findByIdAndTenantId( $customerId, auth()->user()->tenant_id );
            if ( !$customer ) {
                return $this->error( 'Cliente não encontrado' );
            }

            $interaction = $this->interactionService->createInteraction( $customer, $data, auth()->user() );
            return $this->success( $interaction, 'Interação criada com sucesso' );
        } catch ( \Exception $e ) {
            return $this->error( 'Erro ao criar interação: ' . $e->getMessage() );
        }
    }

    /**
     * Lista interações de um cliente
     */
    public function listInteractions( int $customerId, array $filters = [] ): ServiceResult
    {
        try {
            $customer = $this->customerRepository->findByIdAndTenantId( $customerId, auth()->user()->tenant_id );
            if ( !$customer ) {
                return $this->error( 'Cliente não encontrado' );
            }

            $interactions = $this->interactionService->getCustomerInteractions( $customer, $filters );
            return $this->success( $interactions, 'Interações listadas com sucesso' );
        } catch ( \Exception $e ) {
            return $this->error( 'Erro ao listar interações: ' . $e->getMessage() );
        }
    }

    /**
     * Verifica se email é único no tenant.
     */
    private function isEmailUniqueInTenant( string $email, ?int $excludeCustomerId = null ): bool
    {
        $query = Contact::where( 'tenant_id', auth()->user()->tenant_id )
            ->where( function ( $q ) use ( $email ) {
                $q->where( 'email_personal', $email )
                    ->orWhere( 'email_business', $email );
            } );

        if ( $excludeCustomerId ) {
            $query->whereHas( 'customer', function ( $q ) use ( $excludeCustomerId ) {
                $q->where( 'id', '!=', $excludeCustomerId );
            } );
        }

        return !$query->exists();
    }

    /**
     * Busca clientes com filtros avançados.
     */
    public function searchCustomers( array $filters = [] ): ServiceResult
    {
        try {
            $customers = $this->customerRepository->listByFilters( $filters );
            return $this->success( $customers, 'Busca realizada com sucesso' );
        } catch ( \Exception $e ) {
            return $this->error( 'Erro na busca: ' . $e->getMessage() );
        }
    }

    /**
     * Verifica se cliente tem relacionamentos (budgets, invoices).
     */
    public function hasRelationships( int $customerId ): ServiceResult
    {
        try {
            $customer = $this->customerRepository->findByIdAndTenantId(
                $customerId,
                auth()->user()->tenant_id,
            );

            if ( !$customer ) {
                return $this->error( 'Cliente não encontrado' );
            }

            $budgetsCount  = $customer->budgets()->count();
            $invoicesCount = $customer->invoices()->count();

            $hasRelationships = ( $budgetsCount + $invoicesCount ) > 0;

            return $this->success( [
                'has_relationships' => $hasRelationships,
                'budgets_count'     => $budgetsCount,
                'invoices_count'    => $invoicesCount,
            ] );
        } catch ( \Exception $e ) {
            return $this->error( 'Erro ao verificar relacionamentos: ' . $e->getMessage() );
        }
    }

    /**
     * Duplica cliente existente.
     */
    public function duplicateCustomer( int $customerId ): ServiceResult
    {
        try {
            $original = $this->customerRepository->findByIdAndTenantId(
                $customerId,
                auth()->user()->tenant_id,
            );

            if ( !$original ) {
                return $this->error( 'Cliente não encontrado' );
            }

            $original->load( [ 'commonData', 'contact', 'address' ] );

            $tenantId = auth()->user()->tenant_id;

            $duplicate = DB::transaction( function () use ($original, $tenantId) {
                // Duplicar dados usando EntityDataService
                $data = [
                    'first_name'          => $original->commonData->first_name . ' (Cópia)',
                    'last_name'           => $original->commonData->last_name,
                    'birth_date'          => $original->commonData->birth_date?->format( 'd/m/Y' ),
                    'cpf'                 => $original->commonData->cpf,
                    'cnpj'                => $original->commonData->cnpj,
                    'company_name'        => $original->commonData->company_name,
                    'description'         => $original->commonData->description,
                    'area_of_activity_id' => $original->commonData->area_of_activity_id,
                    'profession_id'       => $original->commonData->profession_id,
                    'email_personal'      => null, // Email deve ser único
                    'phone_personal'      => $original->contact->phone_personal,
                    'email_business'      => $original->contact->email_business,
                    'phone_business'      => $original->contact->phone_business,
                    'website'             => $original->contact->website,
                    'cep'                 => $original->address->cep,
                    'address'             => $original->address->address,
                    'address_number'      => $original->address->address_number,
                    'neighborhood'        => $original->address->neighborhood,
                    'city'                => $original->address->city,
                    'state'               => $original->address->state,
                ];

                $entityData = $this->entityDataService->createCompleteEntityData( $data, $tenantId );

                return Customer::create( [
                    'tenant_id'      => $tenantId,
                    'common_data_id' => $entityData[ 'common_data' ]->id,
                    'contact_id'     => $entityData[ 'contact' ]->id,
                    'address_id'     => $entityData[ 'address' ]->id,
                    'status'         => 'active',
                ] )->load( [ 'commonData', 'contact', 'address' ] );
            } );

            return $this->success( $duplicate, 'Cliente duplicado com sucesso' );
        } catch ( \Exception $e ) {
            return $this->error( 'Erro ao duplicar cliente: ' . $e->getMessage() );
        }
    }

}
