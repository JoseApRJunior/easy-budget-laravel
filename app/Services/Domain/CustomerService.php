<?php

declare(strict_types=1);

namespace App\Services\Domain;

use App\Helpers\DateHelper;
use App\Models\Address;
use App\Models\CommonData;
use App\Models\Contact;
use App\Models\Customer;
use App\Repositories\CustomerRepository;
use App\Services\Application\CustomerInteractionService;
use App\Services\Core\Abstracts\AbstractBaseService;
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
            // Validação básica dos dados
            $validation = $this->validateCustomerData( $data );
            if ( !$validation->isSuccess() ) {
                return $validation;
            }

            // Criação com transação
            $customer = DB::transaction( function () use ($data) {
                // Criar CommonData
                $commonData = CommonData::create( [
                    'tenant_id'           => Auth::user()->tenant_id,
                    'first_name'          => $data[ 'first_name' ] ?? null,
                    'last_name'           => $data[ 'last_name' ] ?? null,
                    'birth_date'          => DateHelper::parseBirthDate( $data[ 'birth_date' ] ?? null ),
                    'area_of_activity_id' => $data[ 'area_of_activity_id' ] ?? null,
                    'profession_id'       => $data[ 'profession_id' ] ?? null,
                    'description'         => $data[ 'description' ] ?? null,
                    'website'             => $data[ 'website' ] ?? null,
                    'cnpj'                => clean_document_number( $data[ 'cnpj' ] ?? null ),
                    'cpf'                 => clean_document_number( $data[ 'cpf' ] ?? null ),
                ] );

                // Criar Contact
                $contact = Contact::create( [
                    'tenant_id'      => Auth::user()->tenant_id,
                    'email'          => $data[ 'email_personal' ] ?? null,
                    'phone'          => $data[ 'phone_personal' ] ?? null,
                    'email_business' => $data[ 'email_business' ] ?? null,
                    'phone_business' => $data[ 'phone_business' ] ?? null,
                ] );

                // Criar Address
                $address = Address::create( [
                    'tenant_id'      => Auth::user()->tenant_id,
                    'cep'            => $data[ 'cep' ] ?? null,
                    'address'        => $data[ 'address' ] ?? null,
                    'address_number' => $data[ 'address_number' ] ?? null,
                    'neighborhood'   => $data[ 'neighborhood' ] ?? null,
                    'city'           => $data[ 'city' ] ?? null,
                    'state'          => $data[ 'state' ] ?? null,
                ] );

                // Criar Customer
                $customer = Customer::create( [
                    'tenant_id'      => Auth::user()->tenant_id,
                    'common_data_id' => $commonData->id,
                    'contact_id'     => $contact->id,
                    'address_id'     => $address->id,
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
    private function validateCustomerData( array $data ): ServiceResult
    {
        // Validações básicas
        if ( empty( $data[ 'first_name' ] ) || empty( $data[ 'last_name' ] ) ) {
            return $this->error( 'Nome e sobrenome são obrigatórios' );
        }

        if ( empty( $data[ 'email_personal' ] ) ) {
            return $this->error( 'Email pessoal é obrigatório' );
        }

        if ( empty( $data[ 'phone_personal' ] ) ) {
            return $this->error( 'Telefone pessoal é obrigatório' );
        }

        // Verificar se tem pelo menos um documento (CPF ou CNPJ)
        $hasCpf  = !empty( $data[ 'cpf' ] );
        $hasCnpj = !empty( $data[ 'cnpj' ] );

        if ( !$hasCpf && !$hasCnpj ) {
            return $this->error( 'CPF ou CNPJ é obrigatório' );
        }

        // Validação específica por documento fornecido
        if ( $hasCpf && strlen( preg_replace( '/\D/', '', $data[ 'cpf' ] ) ) !== 11 ) {
            return $this->error( 'CPF deve ter 11 dígitos' );
        }

        if ( $hasCnpj && strlen( preg_replace( '/\D/', '', $data[ 'cnpj' ] ) ) !== 14 ) {
            return $this->error( 'CNPJ deve ter 14 dígitos' );
        }

        // Validações de endereço
        if ( empty( $data[ 'cep' ] ) || empty( $data[ 'address' ] ) || empty( $data[ 'neighborhood' ] ) || empty( $data[ 'city' ] ) || empty( $data[ 'state' ] ) ) {
            return $this->error( 'Endereço completo é obrigatório' );
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
            $customer = $this->customerRepository->findByIdAndTenantId( $id, auth()->user()->tenant_id );
            if ( !$customer ) {
                return $this->error( 'Cliente não encontrado' );
            }

            $validation = $this->validateForTenant( $data, auth()->user()->tenant_id );
            if ( !$validation->isSuccess() ) {
                return $validation;
            }

            $updated = $this->customerRepository->update( $customer, $data );
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

}
