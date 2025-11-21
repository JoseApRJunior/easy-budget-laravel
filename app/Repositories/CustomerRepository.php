<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Address;
use App\Models\BusinessData;
use App\Models\CommonData;
use App\Models\Contact;
use App\Models\Customer;
use App\Repositories\Abstracts\AbstractTenantRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Repositório para gerenciamento de clientes.
 *
 * Estende AbstractTenantRepository para operações tenant-aware
 * com isolamento automático de dados por empresa.
 */
class CustomerRepository extends AbstractTenantRepository
{
    /**
     * Define o Model a ser utilizado pelo Repositório.
     */
    protected function makeModel(): Model
    {
        return new Customer();
    }

    /**
     * Lista clientes ativos dentro do tenant atual.
     *
     * @param array<string, string>|null $orderBy
     * @param int|null $limit
     * @return Collection<Customer>
     */
    public function listActive( ?array $orderBy = null, ?int $limit = null ): Collection
    {
        return $this->getAllByTenant(
            [ 'status' => 'active' ],
            $orderBy,
            $limit,
        );
    }

    /**
     * Conta clientes dentro do tenant atual com filtros opcionais.
     *
     * @param array<string, mixed> $filters
     * @return int
     */
    public function countByFilters( array $filters = [] ): int
    {
        return $this->countByTenant( $filters );
    }

    /**
     * Verifica existência por critérios dentro do tenant atual.
     *
     * @param array<string, mixed> $criteria
     * @return bool
     */
    public function existsByCriteria( array $criteria ): bool
    {
        return $this->findByMultipleCriteria( $criteria )->isNotEmpty();
    }

    /**
     * Remove múltiplos clientes por IDs dentro do tenant atual.
     *
     * @param array<int> $ids
     * @return int Número de registros removidos
     */
    public function deleteManyByIds( array $ids ): int
    {
        return $this->deleteManyByTenant( $ids );
    }

    /**
     * Atualiza múltiplos registros por critérios dentro do tenant atual.
     *
     * @param array<string, mixed> $criteria
     * @param array<string, mixed> $updates
     * @return int Número de registros atualizados
     */
    public function updateManyByCriteria( array $criteria, array $updates ): int
    {
        $query = $this->model->newQuery();
        $this->applyFilters( $query, $criteria );
        return $query->update( $updates );
    }

    /**
     * Busca clientes por múltiplos critérios dentro do tenant atual.
     *
     * @param array<string, mixed> $criteria
     * @param array<string, string>|null $orderBy
     * @param int|null $limit
     * @param int|null $offset
     * @return Collection<Customer>
     */
    public function findByCriteria(
        array $criteria,
        ?array $orderBy = null,
        ?int $limit = null,
        ?int $offset = null,
    ): Collection {
        return $this->getAllByTenant( $criteria, $orderBy, $limit, $offset );
    }

    /**
     * Retorna clientes paginados dentro do tenant atual.
     *
     * @param int $perPage
     * @param array<string, mixed> $criteria
     * @param array<string, string>|null $orderBy
     * @return LengthAwarePaginator
     */
    public function paginateByCriteria(
        int $perPage = 15,
        array $criteria = [],
        ?array $orderBy = null,
    ): LengthAwarePaginator {
        return $this->paginateByTenant( $perPage, $criteria, $orderBy );
    }

    /**
     * Lista clientes por filtros (compatibilidade com service).
     *
     * @param array<string, mixed> $filters
     * @param array<string, string>|null $orderBy
     * @param int|null $limit
     * @param int|null $offset
     * @return Collection<Customer>
     */
    public function listByFilters(
        array $filters = [],
        ?array $orderBy = null,
        ?int $limit = null,
        ?int $offset = null,
    ): Collection {
        return $this->getAllByTenant( $filters, $orderBy, $limit, $offset );
    }

    // ========================================
    // VALIDAÇÕES DE UNICIDADE (GRUPO 1.3)
    // ========================================

    /**
     * Verifica se email é único no tenant (exclui customer atual se especificado)
     */
    public function isEmailUnique( string $email, int $tenantId, ?int $excludeCustomerId = null ): bool
    {
        $query = Contact::where( 'tenant_id', $tenantId )
            ->where( function ( $q ) use ( $email ) {
                $q->where( 'email_personal', $email )
                    ->orWhere( 'email_business', $email );
            } );

        if ( $excludeCustomerId ) {
            $query->where( 'customer_id', '!=', $excludeCustomerId );
        }

        return !$query->exists();
    }

    /**
     * Verifica se CPF é único no tenant (exclui customer atual se especificado)
     */
    public function isCpfUnique( string $cpf, int $tenantId, ?int $excludeCustomerId = null ): bool
    {
        if ( strlen( $cpf ) !== 11 ) return false; // CPF deve ter 11 dígitos

        $query = CommonData::query()
            ->where( 'tenant_id', $tenantId )
            ->where( 'cpf', $cpf )
            ->whereNotNull( 'cpf' );

        // Corrigido: Filtrar customers que utilizam este common_data, exceto o customer especificado
        if ( $excludeCustomerId ) {
            $query->whereDoesntHave( 'customer', function ( $q ) use ( $excludeCustomerId ) {
                $q->where( 'id', $excludeCustomerId );
            } );
        }

        return !$query->exists();
    }

    /**
     * Verifica se CNPJ é único no tenant (exclui customer atual se especificado)
     */
    public function isCnpjUnique( string $cnpj, int $tenantId, ?int $excludeCustomerId = null ): bool
    {
        if ( strlen( $cnpj ) !== 14 ) return false; // CNPJ deve ter 14 dígitos

        $query = CommonData::query()
            ->where( 'tenant_id', $tenantId )
            ->where( 'cnpj', $cnpj )
            ->whereNotNull( 'cnpj' );

        // Corrigido: Filtrar customers que utilizam este common_data, exceto o customer especificado
        if ( $excludeCustomerId ) {
            $query->whereDoesntHave( 'customer', function ( $q ) use ( $excludeCustomerId ) {
                $q->where( 'id', $excludeCustomerId );
            } );
        }

        return !$query->exists();
    }

    // ========================================
    // FILTROS AVANÇADOS (GRUPO 1.2)
    // ========================================

    /**
     * Retorna clientes paginados com filtros avançados
     */
    public function getPaginated( array $filters = [], int $perPage = 15 ): LengthAwarePaginator
    {
        $query = Customer::with( [
            'commonData' => function ( $q ) {
                $q->with( [ 'areaOfActivity', 'profession' ] );
            },
            'contact', 'address', 'businessData'
        ] );

        // Filtro por texto (busca em nome, email, CPF/CNPJ, razão social)
        if ( !empty( $filters[ 'search' ] ) ) {
            $query->where( function ( $q ) use ( $filters ) {
                $q->whereHas( 'commonData', function ( $cq ) use ( $filters ) {
                    $cq->where( 'first_name', 'like', '%' . $filters[ 'search' ] . '%' )
                        ->orWhere( 'last_name', 'like', '%' . $filters[ 'search' ] . '%' )
                        ->orWhere( 'company_name', 'like', '%' . $filters[ 'search' ] . '%' )
                        ->orWhere( 'cpf', 'like', '%' . $filters[ 'search' ] . '%' )
                        ->orWhere( 'cnpj', 'like', '%' . $filters[ 'search' ] . '%' );
                } )->orWhereHas( 'contact', function ( $cq ) use ( $filters ) {
                    $cq->where( 'email_personal', 'like', '%' . $filters[ 'search' ] . '%' )
                        ->orWhere( 'email_business', 'like', '%' . $filters[ 'search' ] . '%' )
                        ->orWhere( 'phone_personal', 'like', '%' . $filters[ 'search' ] . '%' )
                        ->orWhere( 'phone_business', 'like', '%' . $filters[ 'search' ] . '%' );
                } );
            } );
        }

        // Filtro por tipo (PF/PJ)
        if ( !empty( $filters[ 'type' ] ) ) {
            $query->whereHas( 'commonData', function ( $q ) use ( $filters ) {
                if ( $filters[ 'type' ] === 'pessoa_fisica' ) {
                    $q->whereNotNull( 'cpf' );
                } else {
                    $q->whereNotNull( 'cnpj' );
                }
            } );
        }

        // Filtro por status
        if ( !empty( $filters[ 'status' ] ) ) {
            $query->where( 'status', $filters[ 'status' ] );
        }

        // Filtro por área de atuação
        if ( !empty( $filters[ 'area_of_activity_id' ] ) ) {
            $query->whereHas( 'commonData', function ( $q ) use ( $filters ) {
                $q->where( 'area_of_activity_id', $filters[ 'area_of_activity_id' ] );
            } );
        }

        // Filtro por profissão
        if ( !empty( $filters[ 'profession_id' ] ) ) {
            $query->whereHas( 'commonData', function ( $q ) use ( $filters ) {
                $q->where( 'profession_id', $filters[ 'profession_id' ] );
            } );
        }

        return $query->orderBy( 'created_at', 'desc' )->paginate( $perPage );
    }

    /**
     * Busca customer com dados completos relacionados
     */
    public function findWithCompleteData( int $id, int $tenantId ): ?Customer
    {
        return Customer::where( 'id', $id )
            ->where( 'tenant_id', $tenantId )
            ->with( [
                'commonData' => function ( $q ) {
                    $q->with( [ 'areaOfActivity', 'profession' ] );
                },
                'contact', 'address', 'businessData', 'budgets' // REMOVIDO: 'services' - causava ambiguidade
            ] )
            ->first();
    }

    // ========================================
    // OPERAÇÕES MULTI-TABELA (GRUPO 1.2)
    // ========================================

    /**
     * Cria customer com todas as relações (estrutura de 5 tabelas)
     */
    public function createWithRelations( array $data ): Customer
    {
        return DB::transaction( function () use ($data) {
            $tenantId = $data[ 'tenant_id' ];

            // 1. Criar CommonData
            $commonData = CommonData::create( [
                'tenant_id'           => $tenantId,
                'customer_id'         => null, // Será atualizado após criar customer
                'type'                => $data[ 'type' ] ?? 'individual',
                'first_name'          => $data[ 'first_name' ] ?? null,
                'last_name'           => $data[ 'last_name' ] ?? null,
                'birth_date'          => $data[ 'birth_date' ] ?? null,
                'cpf'                 => $data[ 'cpf' ] ?? null,
                'cnpj'                => $data[ 'cnpj' ] ?? null,
                'company_name'        => $data[ 'company_name' ] ?? null,
                'description'         => $data[ 'description' ] ?? null,
                'area_of_activity_id' => $data[ 'area_of_activity_id' ] ?? null,
                'profession_id'       => $data[ 'profession_id' ] ?? null,
            ] );

            // 2. Criar Contact
            $contact = Contact::create( [
                'tenant_id'      => $tenantId,
                'customer_id'    => null, // Será atualizado após criar customer
                'email_personal' => $data[ 'email' ] ?? null,
                'phone_personal' => $data[ 'phone' ] ?? null,
                'email_business' => $data[ 'email_business' ] ?? null,
                'phone_business' => $data[ 'phone_business' ] ?? null,
                'website'        => $data[ 'website' ] ?? null,
            ] );

            // 3. Criar Address
            $address = Address::create( [
                'tenant_id'      => $tenantId,
                'customer_id'    => null, // Será atualizado após criar customer
                'address'        => $data[ 'address' ] ?? null,
                'address_number' => $data[ 'address_number' ] ?? null,
                'neighborhood'   => $data[ 'neighborhood' ] ?? null,
                'city'           => $data[ 'city' ] ?? null,
                'state'          => $data[ 'state' ] ?? null,
                'cep'            => $data[ 'cep' ] ?? null,
            ] );

            // 4. Criar Customer (tabela principal)
            $customer = Customer::create( [
                'tenant_id' => $tenantId,
                'status'    => $data[ 'status' ] ?? 'active',
            ] );

            // 5. Atualizar IDs das relações no Customer
            $customer->update( [
                'common_data_id' => $commonData->id,
                'contact_id'     => $contact->id,
                'address_id'     => $address->id,
            ] );

            // 6. Atualizar customer_id nas tabelas relacionadas
            $commonData->update( [ 'customer_id' => $customer->id ] );
            $contact->update( [ 'customer_id' => $customer->id ] );
            $address->update( [ 'customer_id' => $customer->id ] );

            // 7. Criar BusinessData se for pessoa jurídica
            if ( ( $data[ 'type' ] ?? 'individual' ) === 'company' || !empty( $data[ 'cnpj' ] ) ) {
                BusinessData::create( [
                    'tenant_id'              => $tenantId,
                    'customer_id'            => $customer->id,
                    'fantasy_name'           => $data[ 'fantasy_name' ] ?? null,
                    'state_registration'     => $data[ 'state_registration' ] ?? null,
                    'municipal_registration' => $data[ 'municipal_registration' ] ?? null,
                    'founding_date'          => $data[ 'founding_date' ] ?? null,
                    'industry'               => $data[ 'industry' ] ?? null,
                    'company_size'           => $data[ 'company_size' ] ?? null,
                    'notes'                  => $data[ 'business_notes' ] ?? null,
                ] );
            }

            return $customer->fresh( [ 'commonData', 'contact', 'address', 'businessData' ] );
        } );
    }

    /**
     * Atualiza customer com todas as relações
     */
    public function updateWithRelations( Customer $customer, array $data ): bool
    {
        return DB::transaction( function () use ($customer, $data) {
            // Atualizar CommonData
            if ( $customer->commonData ) {
                $customer->commonData->update( [
                    'type'                => $data[ 'type' ] ?? $customer->commonData->type,
                    'first_name'          => $data[ 'first_name' ] ?? $customer->commonData->first_name,
                    'last_name'           => $data[ 'last_name' ] ?? $customer->commonData->last_name,
                    'birth_date'          => $data[ 'birth_date' ] ?? $customer->commonData->birth_date,
                    'cpf'                 => $data[ 'cpf' ] ?? $customer->commonData->cpf,
                    'cnpj'                => $data[ 'cnpj' ] ?? $customer->commonData->cnpj,
                    'company_name'        => $data[ 'company_name' ] ?? $customer->commonData->company_name,
                    'description'         => $data[ 'description' ] ?? $customer->commonData->description,
                    'area_of_activity_id' => $data[ 'area_of_activity_id' ] ?? $customer->commonData->area_of_activity_id,
                    'profession_id'       => $data[ 'profession_id' ] ?? $customer->commonData->profession_id,
                ] );
            }

            // Atualizar Contact
            if ( $customer->contact ) {
                $customer->contact->update( [
                    'email_personal' => $data[ 'email_personal' ] ?? $customer->contact->email_personal,
                    'phone_personal' => $data[ 'phone_personal' ] ?? $customer->contact->phone_personal,
                    'email_business' => $data[ 'email_business' ] ?? $customer->contact->email_business,
                    'phone_business' => $data[ 'phone_business' ] ?? $customer->contact->phone_business,
                    'website'        => $data[ 'website' ] ?? $customer->contact->website,
                ] );
            }

            // Atualizar Address
            if ( $customer->address ) {
                $customer->address->update( [
                    'address'        => $data[ 'address' ] ?? $customer->address->address,
                    'address_number' => $data[ 'address_number' ] ?? $customer->address->address_number,
                    'neighborhood'   => $data[ 'neighborhood' ] ?? $customer->address->neighborhood,
                    'city'           => $data[ 'city' ] ?? $customer->address->city,
                    'state'          => $data[ 'state' ] ?? $customer->address->state,
                    'cep'            => $data[ 'cep' ] ?? $customer->address->cep,
                ] );
            }

            // Atualizar ou criar BusinessData
            if ( ( $data[ 'type' ] ?? 'individual' ) === 'company' || !empty( $data[ 'cnpj' ] ) ) {
                if ( $customer->businessData ) {
                    $customer->businessData->update( [
                        'fantasy_name'           => $data[ 'fantasy_name' ] ?? $customer->businessData->fantasy_name,
                        'state_registration'     => $data[ 'state_registration' ] ?? $customer->businessData->state_registration,
                        'municipal_registration' => $data[ 'municipal_registration' ] ?? $customer->businessData->municipal_registration,
                        'founding_date'          => $data[ 'founding_date' ] ?? $customer->businessData->founding_date,
                        'industry'               => $data[ 'industry' ] ?? $customer->businessData->industry,
                        'company_size'           => $data[ 'company_size' ] ?? $customer->businessData->company_size,
                        'notes'                  => $data[ 'business_notes' ] ?? $customer->businessData->notes,
                    ] );
                } else {
                    BusinessData::create( [
                        'tenant_id'              => $customer->tenant_id,
                        'customer_id'            => $customer->id,
                        'fantasy_name'           => $data[ 'fantasy_name' ] ?? null,
                        'state_registration'     => $data[ 'state_registration' ] ?? null,
                        'municipal_registration' => $data[ 'municipal_registration' ] ?? null,
                        'founding_date'          => $data[ 'founding_date' ] ?? null,
                        'industry'               => $data[ 'industry' ] ?? null,
                        'company_size'           => $data[ 'company_size' ] ?? null,
                        'notes'                  => $data[ 'business_notes' ] ?? null,
                    ] );
                }
            }

            // Atualizar Customer (status)
            $customer->update( [
                'status' => $data[ 'status' ] ?? $customer->status,
            ] );

            return true;
        } );
    }

    /**
     * Verifica se customer pode ser deletado (verifica relacionamentos)
     */
    public function canDelete( int $id, int $tenantId ): array
    {
        $customer = Customer::select( 'customers.*' )
            ->where( 'customers.id', $id )
            ->where( 'customers.tenant_id', $tenantId )
            ->where( 'customers.deleted_at', null )
            ->addSelect( [
                'budgets_count'  => function ( $query ) use ( $tenantId ) {
                    $query->selectRaw( 'count(*)' )
                        ->from( 'budgets' )
                        ->whereColumn( 'budgets.customer_id', 'customers.id' )
                        ->where( 'budgets.tenant_id', $tenantId );
                },
                'services_count' => function ( $query ) use ( $tenantId ) {
                    $query->selectRaw( 'count(*)' )
                        ->from( 'services' )
                        ->join( 'budgets', 'services.budget_id', '=', 'budgets.id' )
                        ->whereColumn( 'budgets.customer_id', 'customers.id' )
                        ->where( 'budgets.tenant_id', $tenantId );
                },
                'invoices_count' => function ( $query ) use ( $tenantId ) {
                    $query->selectRaw( 'count(*)' )
                        ->from( 'invoices' )
                        ->whereColumn( 'invoices.customer_id', 'customers.id' )
                        ->where( 'invoices.tenant_id', $tenantId )
                        ->whereNull( 'invoices.deleted_at' );
                }
            ] )
            ->first();

        if ( !$customer ) {
            return [ 'canDelete' => false, 'reason' => 'Customer não encontrado' ];
        }

        $budgetsCount   = (int) $customer->budgets_count;
        $servicesCount  = (int) $customer->services_count;
        $invoicesCount  = (int) $customer->invoices_count;
        $totalRelations = $budgetsCount + $servicesCount + $invoicesCount;

        $reasons = [];
        if ( $budgetsCount > 0 ) {
            $reasons[] = "{$budgetsCount} orçamento(s)";
        }
        if ( $servicesCount > 0 ) {
            $reasons[] = "{$servicesCount} serviço(s)";
        }
        if ( $invoicesCount > 0 ) {
            $reasons[] = "{$invoicesCount} fatura(s)";
        }

        return [
            'canDelete'           => $totalRelations === 0,
            'reason'              => $totalRelations > 0
                ? 'Cliente não pode ser excluído pois possui: ' . implode( ', ', $reasons )
                : null,
            'budgetsCount'        => $budgetsCount,
            'servicesCount'       => $servicesCount,
            'invoicesCount'       => $invoicesCount,
            'totalRelationsCount' => $totalRelations
        ];
    }

    /**
     * Busca por email (qualquer campo de email)
     */
    public function findByEmail( string $email, int $tenantId ): ?Customer
    {
        return Customer::where( 'tenant_id', $tenantId )
            ->whereHas( 'contact', function ( $q ) use ( $email ) {
                $q->where( 'email_personal', $email )
                    ->orWhere( 'email_business', $email );
            } )
            ->first();
    }

    /**
     * Busca por CPF
     */
    public function findByCpf( string $cpf, int $tenantId ): ?Customer
    {
        return Customer::where( 'tenant_id', $tenantId )
            ->whereHas( 'commonData', function ( $q ) use ( $cpf ) {
                $q->where( 'cpf', $cpf );
            } )
            ->first();
    }

    /**
     * Busca por CNPJ
     */
    public function findByCnpj( string $cnpj, int $tenantId ): ?Customer
    {
        return Customer::where( 'tenant_id', $tenantId )
            ->whereHas( 'commonData', function ( $q ) use ( $cnpj ) {
                $q->where( 'cnpj', $cnpj );
            } )
            ->first();
    }

    /**
     * Verifica relacionamentos (método aliases para canDelete)
     */
    public function checkRelationships( int $id, int $tenantId ): array
    {
        $canDelete = $this->canDelete( $id, $tenantId );
        return [
            'hasRelationships' => !$canDelete[ 'canDelete' ],
            'budgets'          => $canDelete[ 'budgetsCount' ] ?? 0,
            'services'         => $canDelete[ 'servicesCount' ] ?? 0,
            'invoices'         => $canDelete[ 'invoicesCount' ] ?? 0,
            'interactions'     => $canDelete[ 'interactionsCount' ] ?? 0,
            'totalRelations'   => $canDelete[ 'totalRelationsCount' ] ?? 0,
            'reason'           => $canDelete[ 'reason' ] ?? null
        ];
    }

    /**
     * Busca customer com registros trashed
     */
    public function findWithTrashed( int $id, int $tenantId ): ?Customer
    {
        return Customer::withTrashed()
            ->where( 'id', $id )
            ->where( 'tenant_id', $tenantId )
            ->with( [
                'commonData'   => function ( $q ) {
                    $q->withTrashed()->with( [ 'areaOfActivity', 'profession' ] );
                },
                'contact'      => function ( $q ) {
                    $q->withTrashed();
                },
                'address'      => function ( $q ) {
                    $q->withTrashed();
                },
                'businessData' => function ( $q ) {
                    $q->withTrashed();
                }
            ] )
            ->first();
    }

    /**
     * Conta total de customers por tenant
     */
    public function countByTenantId( int $tenantId ): int
    {
        return Customer::where( 'tenant_id', $tenantId )->count();
    }

    /**
     * Conta customers por status no tenant
     */
    public function countByStatus( string $status, int $tenantId ): int
    {
        return Customer::where( 'tenant_id', $tenantId )
            ->where( 'status', $status )
            ->count();
    }

    /**
     * Busca customers recentes por tenant
     */
    public function getRecentByTenantId( int $tenantId, int $limit = 10 ): Collection
    {
        return Customer::where( 'tenant_id', $tenantId )
            ->orderBy( 'created_at', 'desc' )
            ->limit( $limit )
            ->get();
    }

    /**
     * Busca para autocomplete
     */
    public function searchForAutocomplete( string $query, int $tenantId ): Collection
    {
        return Customer::where( 'tenant_id', $tenantId )
            ->whereHas( 'commonData', function ( $q ) use ( $query ) {
                $q->where( 'first_name', 'like', '%' . $query . '%' )
                    ->orWhere( 'last_name', 'like', '%' . $query . '%' )
                    ->orWhere( 'company_name', 'like', '%' . $query . '%' )
                    ->orWhere( 'cpf', 'like', '%' . $query . '%' )
                    ->orWhere( 'cnpj', 'like', '%' . $query . '%' );
            } )
            ->orWhereHas( 'contact', function ( $q ) use ( $query ) {
                $q->where( 'email_personal', 'like', '%' . $query . '%' )
                    ->orWhere( 'email_business', 'like', '%' . $query . '%' )
                    ->orWhere( 'phone_personal', 'like', '%' . $query . '%' )
                    ->orWhere( 'phone_business', 'like', '%' . $query . '%' );
            } )
            ->with( 'commonData.contact' )
            ->limit( 20 )
            ->get();
    }

}
