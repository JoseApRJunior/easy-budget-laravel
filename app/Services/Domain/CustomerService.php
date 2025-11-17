<?php

declare(strict_types=1);

namespace App\Services\Domain;

use App\Enums\CustomerStatus;
use App\Enums\OperationStatus;
use App\Repositories\AddressRepository;
use App\Repositories\BusinessDataRepository;
use App\Repositories\CommonDataRepository;
use App\Repositories\ContactRepository;
use App\Repositories\CustomerRepository;
use App\Support\ServiceResult;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Serviço de Clientes - Lógica de negócio para gestão de clientes
 *
 * CRÍTICO: Service deve gerenciar transações em 5 tabelas simultaneamente.
 * Estrutura real: Customer (principal) -> CommonData, Contact, Address, BusinessData (HasMany)
 */
class CustomerService
{
    public function __construct(
        private CustomerRepository $customerRepository,
        private CommonDataRepository $commonDataRepository,
        private ContactRepository $contactRepository,
        private AddressRepository $addressRepository,
        private BusinessDataRepository $businessDataRepository,
    ) {}

    /**
     * Criar cliente pessoa física com transação em 4 tabelas
     */
    public function createPessoaFisica( array $data, int $tenantId ): ServiceResult
    {
        try {
            return DB::transaction( function () use ($data, $tenantId) {
                // 1. Validar unicidade (email, CPF) - exceção para próprio registro
                if ( !$this->customerRepository->isEmailUnique( $data[ 'email_personal' ], $tenantId ) ) {
                    return ServiceResult::error( OperationStatus::VALIDATION_ERROR, 'E-mail já está em uso' );
                }

                $cpf = preg_replace( '/[^0-9]/', '', $data[ 'cpf' ] );
                if ( !$this->customerRepository->isCpfUnique( $cpf, $tenantId ) ) {
                    return ServiceResult::error( OperationStatus::VALIDATION_ERROR, 'CPF já está em uso' );
                }

                // 2. Criar dados em cascata (CommonData -> Contact -> Address -> Customer)
                $commonData = $this->commonDataRepository->create( [
                    'tenant_id'           => $tenantId,
                    'first_name'          => $data[ 'first_name' ],
                    'last_name'           => $data[ 'last_name' ],
                    'birth_date'          => $data[ 'birth_date' ],
                    'cpf'                 => $cpf,
                    'area_of_activity_id' => $data[ 'area_of_activity_id' ] ?? null,
                    'profession_id'       => $data[ 'profession_id' ] ?? null,
                    'description'         => $data[ 'description' ] ?? null,
                ] );

                $contact = $this->contactRepository->create( [
                    'tenant_id'      => $tenantId,
                    'email_personal' => $data[ 'email_personal' ],
                    'phone_personal' => $data[ 'phone_personal' ],
                    'website'        => $data[ 'website' ] ?? null,
                ] );

                $address = $this->addressRepository->create( [
                    'tenant_id'      => $tenantId,
                    'address'        => $data[ 'address' ],
                    'address_number' => $data[ 'address_number' ] ?? null,
                    'neighborhood'   => $data[ 'neighborhood' ],
                    'city'           => $data[ 'city' ],
                    'state'          => strtoupper( $data[ 'state' ] ),
                    'cep'            => preg_replace( '/[^0-9]/', '', $data[ 'cep' ] ),
                ] );

                // 3. Criar Customer com relacionamentos
                $customer = $this->customerRepository->create( [
                    'tenant_id'      => $tenantId,
                    'status'         => CustomerStatus::ACTIVE->value,
                    'common_data_id' => $commonData->id,
                    'contact_id'     => $contact->id,
                    'address_id'     => $address->id,
                ] );

                // 4. Carregar relacionamentos para retorno
                $customer->load( [ 'commonData', 'contact', 'address' ] );
                return ServiceResult::success( $customer, 'Cliente PF criado com sucesso' );
            } );
        } catch ( \Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao criar cliente PF', null, $e );
        }
    }

    /**
     * Criar cliente pessoa jurídica com transação em 5 tabelas
     */
    public function createPessoaJuridica( array $data, int $tenantId ): ServiceResult
    {
        try {
            return DB::transaction( function () use ($data, $tenantId) {
                // 1. Validar unicidade (email, CNPJ)
                if ( !$this->customerRepository->isEmailUnique( $data[ 'email_business' ], $tenantId ) ) {
                    return ServiceResult::error( OperationStatus::VALIDATION_ERROR, 'E-mail empresarial já está em uso' );
                }

                $cnpj = preg_replace( '/[^0-9]/', '', $data[ 'cnpj' ] );
                if ( !$this->customerRepository->isCnpjUnique( $cnpj, $tenantId ) ) {
                    return ServiceResult::error( OperationStatus::VALIDATION_ERROR, 'CNPJ já está em uso' );
                }

                // 2. Criar dados em cascata
                $commonData = $this->commonDataRepository->create( [
                    'tenant_id'           => $tenantId,
                    'company_name'        => $data[ 'company_name' ],
                    'area_of_activity_id' => $data[ 'area_of_activity_id' ] ?? null,
                    'profession_id'       => $data[ 'profession_id' ] ?? null,
                    'description'         => $data[ 'description' ] ?? null,
                ] );

                $businessData = $this->businessDataRepository->create( [
                    'tenant_id'              => $tenantId,
                    'fantasy_name'           => $data[ 'fantasy_name' ],
                    'cnpj'                   => $cnpj,
                    'state_registration'     => $data[ 'state_registration' ] ?? null,
                    'municipal_registration' => $data[ 'municipal_registration' ] ?? null,
                    'founding_date'          => $data[ 'founding_date' ] ?? null,
                    'industry'               => $data[ 'industry' ] ?? null,
                    'company_size'           => $data[ 'company_size' ] ?? null,
                    'notes'                  => $data[ 'business_notes' ] ?? null,
                ] );

                $contact = $this->contactRepository->create( [
                    'tenant_id'      => $tenantId,
                    'email_business' => $data[ 'email_business' ],
                    'phone_business' => $data[ 'phone_business' ],
                    'website'        => $data[ 'website' ] ?? null,
                ] );

                $address = $this->addressRepository->create( [
                    'tenant_id'      => $tenantId,
                    'address'        => $data[ 'address' ],
                    'address_number' => $data[ 'address_number' ] ?? null,
                    'neighborhood'   => $data[ 'neighborhood' ],
                    'city'           => $data[ 'city' ],
                    'state'          => strtoupper( $data[ 'state' ] ),
                    'cep'            => preg_replace( '/[^0-9]/', '', $data[ 'cep' ] ),
                ] );

                // 3. Criar Customer com relacionamentos
                $customer = $this->customerRepository->create( [
                    'tenant_id'        => $tenantId,
                    'status'           => CustomerStatus::ACTIVE->value,
                    'common_data_id'   => $commonData->id,
                    'contact_id'       => $contact->id,
                    'address_id'       => $address->id,
                    'business_data_id' => $businessData->id,
                ] );

                // 4. Carregar relacionamentos para retorno
                $customer->load( [ 'commonData', 'contact', 'address', 'businessData' ] );
                return ServiceResult::success( $customer, 'Cliente PJ criado com sucesso' );
            } );
        } catch ( \Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao criar cliente PJ', null, $e );
        }
    }

    /**
     * Listar clientes com filtros (alias para getFilteredCustomers)
     */
    public function listCustomers( int $tenantId, array $filters = [] ): ServiceResult
    {
        return $this->getFilteredCustomers( $filters, $tenantId );
    }

    /**
     * Obter clientes filtrados com paginação
     */
    public function getFilteredCustomers( array $filters, int $tenantId ): ServiceResult
    {
        try {
            $customers = $this->customerRepository->getPaginated( $filters, 15 );
            return ServiceResult::success( $customers, 'Clientes filtrados' );
        } catch ( \Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao filtrar clientes', null, $e );
        }
    }

    /**
     * Buscar cliente com dados completos
     */
    public function findCustomer( int $id, int $tenantId ): ServiceResult
    {
        try {
            $customer = $this->customerRepository->findWithCompleteData( $id, $tenantId );
            if ( !$customer ) {
                return ServiceResult::error( OperationStatus::NOT_FOUND, 'Cliente não encontrado' );
            }
            return ServiceResult::success( $customer, 'Cliente encontrado' );
        } catch ( \Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao buscar cliente', null, $e );
        }
    }

    /**
     * Atualizar cliente com transação multi-tabela
     */
    public function updateCustomer( int $id, array $data, int $tenantId ): ServiceResult
    {
        try {
            return DB::transaction( function () use ($id, $data, $tenantId) {
                $customer = $this->customerRepository->findWithCompleteData( $id, $tenantId );
                if ( !$customer ) {
                    return ServiceResult::error( OperationStatus::NOT_FOUND, 'Cliente não encontrado' );
                }

                // Detectar tipo baseado no documento
                $isCompany = !empty( $data[ 'cnpj' ] ) || $customer->commonData?->cnpj;

                if ( $isCompany ) {
                    // Use Pessoas Jurídicas logic
                    return $this->updatePessoaJuridicaData( $customer, $data, $tenantId );
                } else {
                    // Use Pessoas Físicas logic
                    return $this->updatePessoaFisicaData( $customer, $data, $tenantId );
                }
            } );
        } catch ( \Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao atualizar cliente', null, $e );
        }
    }

    /**
     * Update logic específico para Pessoa Física
     */
    private function updatePessoaFisicaData( $customer, array $data, int $tenantId ): ServiceResult
    {
        // Validar unicidade (email, CPF) - exceção para próprio registro
        if ( isset( $data[ 'email_personal' ] ) && !$this->customerRepository->isEmailUnique( $data[ 'email_personal' ], $tenantId, $customer->id ) ) {
            return ServiceResult::error( OperationStatus::VALIDATION_ERROR, 'E-mail já está em uso' );
        }

        if ( isset( $data[ 'cpf' ] ) ) {
            $cpf = preg_replace( '/[^0-9]/', '', $data[ 'cpf' ] );
            if ( !$this->customerRepository->isCpfUnique( $cpf, $tenantId, $customer->id ) ) {
                return ServiceResult::error( OperationStatus::VALIDATION_ERROR, 'CPF já está em uso' );
            }
            $data[ 'cpf' ] = $cpf;
        }

        return $this->updateCustomerData( $customer, $data );
    }

    /**
     * Update logic específico para Pessoa Jurídica
     */
    private function updatePessoaJuridicaData( $customer, array $data, int $tenantId ): ServiceResult
    {
        // Validar unicidade (email, CNPJ) - exceção para próprio registro
        if ( isset( $data[ 'email_business' ] ) && !$this->customerRepository->isEmailUnique( $data[ 'email_business' ], $tenantId, $customer->id ) ) {
            return ServiceResult::error( OperationStatus::VALIDATION_ERROR, 'E-mail empresarial já está em uso' );
        }

        if ( isset( $data[ 'cnpj' ] ) ) {
            $cnpj = preg_replace( '/[^0-9]/', '', $data[ 'cnpj' ] );
            if ( !$this->customerRepository->isCnpjUnique( $cnpj, $tenantId, $customer->id ) ) {
                return ServiceResult::error( OperationStatus::VALIDATION_ERROR, 'CNPJ já está em uso' );
            }
            $data[ 'cnpj' ] = $cnpj;
        }

        return $this->updateCustomerData( $customer, $data );
    }

    /**
     * Lógica base de atualização em cascata
     */
    private function updateCustomerData( $customer, array $data ): ServiceResult
    {
        // Separar dados por tabela
        $customerData = [];
        $commonData   = [];
        $contact      = [];
        $address      = [];
        $businessData = [];

        // Dados do Customer (apenas status)
        if ( isset( $data[ 'status' ] ) ) $customerData[ 'status' ] = $data[ 'status' ];

        // Dados da CommonData
        $commonDataFields = [ 'first_name', 'last_name', 'birth_date', 'cpf', 'cnpj', 'company_name',
            'area_of_activity_id', 'profession_id', 'description' ];
        foreach ( $commonDataFields as $field ) {
            if ( array_key_exists( $field, $data ) ) $commonData[ $field ] = $data[ $field ];
        }

        // Dados do Contact
        $contactFields = [ 'email_personal', 'phone_personal', 'email_business', 'phone_business', 'website' ];
        foreach ( $contactFields as $field ) {
            if ( array_key_exists( $field, $data ) ) $contact[ $field ] = $data[ $field ];
        }

        // Dados do Address
        $addressFields = [ 'address', 'address_number', 'neighborhood', 'city', 'state', 'cep' ];
        foreach ( $addressFields as $field ) {
            if ( array_key_exists( $field, $data ) ) {
                $address[ $field ] = $field === 'state' ? strtoupper( $data[ $field ] ) :
                    ( $field === 'cep' ? preg_replace( '/[^0-9]/', '', $data[ $field ] ) :
                        $data[ $field ] );
            }
        }

        // Dados do BusinessData (apenas para Pessoa Jurídica)
        if ( !empty( $data[ 'cnpj' ] ) || !empty( $customer->commonData?->cnpj ) ) {
            $businessDataFields = [ 'fantasy_name', 'state_registration', 'municipal_registration',
                'founding_date', 'industry', 'company_size', 'notes' ];
            foreach ( $businessDataFields as $field ) {
                if ( array_key_exists( $field, $data ) ) $businessData[ $field ] = $data[ $field ];
            }
        }

        // Atualizar em cascata
        if ( !empty( $commonData ) ) {
            $this->commonDataRepository->update( $customer->commonData->id, $commonData );
        }

        if ( !empty( $contact ) ) {
            $this->contactRepository->update( $customer->contact->id, $contact );
        }

        if ( !empty( $address ) ) {
            $this->addressRepository->update( $customer->address->id, $address );
        }

        // Atualizar BusinessData (apenas para PJ e apenas se existir)
        if ( !empty( $businessData ) && $customer->businessData ) {
            $this->businessDataRepository->update( $customer->businessData->id, $businessData );
        }

        if ( !empty( $customerData ) ) {
            $this->customerRepository->update( $customer->id, $customerData );
        }

        // Retornar com dados atualizados
        $customer = $this->customerRepository->findWithCompleteData( $customer->id, $customer->tenant_id );
        return ServiceResult::success( $customer, 'Cliente atualizado com sucesso' );
    }

    /**
     * Excluir cliente com verificação de relacionamentos
     */
    public function deleteCustomer( int $id, int $tenantId ): ServiceResult
    {
        try {
            return DB::transaction( function () use ($id, $tenantId) {
                $customer = $this->customerRepository->findWithCompleteData( $id, $tenantId );
                if ( !$customer ) {
                    return ServiceResult::error( OperationStatus::NOT_FOUND, 'Cliente não encontrado' );
                }

                // Verificar se pode excluir (relacionamentos com orçamentos, faturas, etc.)
                $canDelete = $this->customerRepository->canDelete( $id, $tenantId );
                if ( !$canDelete[ 'canDelete' ] ) {
                    $reason = $canDelete[ 'reason' ];

                    // Construir mensagem mais detalhada
                    if ( isset( $canDelete[ 'budgetsCount' ] ) && $canDelete[ 'budgetsCount' ] > 0 ) {
                        $reason  .= " (Possui {$canDelete[ 'budgetsCount' ]} orçamento(s) associado(s))";
                    }
                    if ( isset( $canDelete[ 'servicesCount' ] ) && $canDelete[ 'servicesCount' ] > 0 ) {
                        $reason  .= " (Possui {$canDelete[ 'servicesCount' ]} serviço(s) associado(s))";
                    }
                    if ( isset( $canDelete[ 'invoicesCount' ] ) && $canDelete[ 'invoicesCount' ] > 0 ) {
                        $reason  .= " (Possui {$canDelete[ 'invoicesCount' ]} fatura(s) associada(s))";
                    }

                    return ServiceResult::error( OperationStatus::VALIDATION_ERROR, $reason );
                }

                // Verificar se há interações do cliente (tabela não existe no momento)
                // TODO: Implementar verificação de interações quando a tabela for criada
                // if ( method_exists( $customer, 'interactions' ) && $customer->interactions()->count() > 0 ) {
                //     return ServiceResult::error(
                //         OperationStatus::VALIDATION_ERROR,
                //         'Cliente não pode ser excluído pois possui interações registradas no histórico',
                //     );
                // }

                // Soft delete em cascata
                $customer->delete();

                // Verificar e deletar dados relacionados se existirem
                if ( $customer->commonData ) {
                    $customer->commonData->delete();
                }
                if ( $customer->contact ) {
                    $customer->contact->delete();
                }
                if ( $customer->address ) {
                    $customer->address->delete();
                }
                if ( isset( $customer->businessData ) && $customer->businessData ) {
                    $customer->businessData->delete();
                }

                return ServiceResult::success( null, 'Cliente excluído com sucesso' );
            } ); // Closing DB::transaction
        } catch ( \Exception $e ) {
            // Log do erro para debug
            Log::error( 'Erro ao excluir cliente', [
                'customer_id' => $id,
                'tenant_id'   => $tenantId,
                'error'       => $e->getMessage(),
                'trace'       => $e->getTraceAsString()
            ] );

            return ServiceResult::error( OperationStatus::ERROR,
                'Erro ao excluir cliente: ' . $e->getMessage(), null, $e );
        }
    }

    /**
     * Verifica se cliente pode ser excluído e retorna detalhes dos impedimentos
     */
    public function canDeleteCustomer( int $id, int $tenantId ): ServiceResult
    {
        try {
            $customer = $this->customerRepository->findWithCompleteData( $id, $tenantId );
            if ( !$customer ) {
                return ServiceResult::error( OperationStatus::NOT_FOUND, 'Cliente não encontrado' );
            }

            $canDeleteInfo = $this->customerRepository->canDelete( $id, $tenantId );

            return ServiceResult::success( [
                'can_delete'      => $canDeleteInfo[ 'canDelete' ],
                'reason'          => $canDeleteInfo[ 'reason' ],
                'budgets_count'   => $canDeleteInfo[ 'budgetsCount' ] ?? 0,
                'services_count'  => $canDeleteInfo[ 'servicesCount' ] ?? 0,
                'invoices_count'  => $canDeleteInfo[ 'invoicesCount' ] ?? 0,
                'total_relations' => $canDeleteInfo[ 'totalRelationsCount' ] ?? 0
            ] );

        } catch ( \Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR,
                'Erro ao verificar se cliente pode ser excluído: ' . $e->getMessage(), null, $e );
        }
    }

    /**
     * Alternar status do cliente (ativo/inativo)
     */
    public function toggleStatus( int $id, int $tenantId ): ServiceResult
    {
        try {
            return DB::transaction( function () use ($id, $tenantId) {
                $customer = $this->customerRepository->findWithCompleteData( $id, $tenantId );
                if ( !$customer ) {
                    return ServiceResult::error( OperationStatus::NOT_FOUND, 'Cliente não encontrado' );
                }

                $newStatus = $customer->status === 'active' ? CustomerStatus::INACTIVE->value : CustomerStatus::ACTIVE->value;
                $this->customerRepository->update( $id, [ 'status' => $newStatus ] );

                $customer = $this->customerRepository->findWithCompleteData( $id, $tenantId );
                return ServiceResult::success( $customer, "Cliente {$newStatus} com sucesso" );
            } );
        } catch ( \Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao alterar status', null, $e );
        }
    }

    /**
     * Restaurar cliente deletado
     */
    public function restoreCustomer( int $id, int $tenantId ): ServiceResult
    {
        try {
            return DB::transaction( function () use ($id, $tenantId) {
                $customer = $this->customerRepository->findWithTrashed( $id, $tenantId );
                if ( !$customer ) {
                    return ServiceResult::error( OperationStatus::NOT_FOUND, 'Cliente não encontrado' );
                }

                $customer->restore();
                $customer->commonData()->withTrashed()->each->restore();
                $customer->contact()->withTrashed()->each->restore();
                $customer->address()->withTrashed()->each->restore();
                $customer->businessData()->withTrashed()->each->restore();

                $customer = $this->customerRepository->findWithCompleteData( $id, $tenantId );
                return ServiceResult::success( $customer, 'Cliente restaurado com sucesso' );
            } );
        } catch ( \Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao restaurar cliente', null, $e );
        }
    }

    /**
     * Obter estatísticas de clientes para dashboard
     */
    public function getCustomerStats( int $tenantId ): ServiceResult
    {
        try {
            $stats = [
                'total_customers'    => $this->customerRepository->countByTenantId( $tenantId ),
                'active_customers'   => $this->customerRepository->countByStatus( 'active', $tenantId ),
                'inactive_customers' => $this->customerRepository->countByStatus( 'inactive', $tenantId ),
                'recent_customers'   => $this->customerRepository->getRecentByTenantId( $tenantId, 10 ),
            ];

            return ServiceResult::success( $stats, 'Estatísticas de clientes' );
        } catch ( \Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao obter estatísticas', null, $e );
        }
    }

    /**
     * Buscar clientes para autocomplete
     */
    public function searchForAutocomplete( string $query, int $tenantId ): ServiceResult
    {
        try {
            $customers = $this->customerRepository->searchForAutocomplete( $query, $tenantId );
            return ServiceResult::success( $customers, 'Clientes encontrados' );
        } catch ( \Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro na busca', null, $e );
        }
    }

}
