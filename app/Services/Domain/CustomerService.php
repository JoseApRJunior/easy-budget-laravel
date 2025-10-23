<?php

declare(strict_types=1);

namespace App\Services\Domain;

use App\Models\Customer;
use App\Repositories\CustomerRepository;
use App\Services\Application\CustomerInteractionService;
use App\Services\Core\Abstracts\AbstractBaseService;
use App\Support\ServiceResult;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

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
            $validation = $this->validateForTenant( $data, auth()->user()->tenant_id );
            if ( !$validation->isSuccess() ) {
                return $validation;
            }

            $customer = $this->customerRepository->create( $data );
            return $this->success( $customer, 'Cliente criado com sucesso' );
        } catch ( \Exception $e ) {
            return $this->error( 'Erro ao criar cliente: ' . $e->getMessage() );
        }
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
