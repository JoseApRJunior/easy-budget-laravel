<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\CustomerContact;
use App\Models\CustomerTag;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Serviço de Clientes - Lógica de negócio para gestão de clientes
 *
 * Centraliza operações complexas relacionadas a clientes,
 * incluindo criação, atualização, busca e relacionamentos.
 */
class CustomerService
{
    public function __construct(
        private GeolocationService $geolocationService,
    ) {}

    /**
     * Cria um novo cliente pessoa física.
     */
    public function createPessoaFisica( array $data, User $user ): Customer
    {
        return DB::transaction( function () use ($data, $user) {
            // Criar cliente básico
            $customer = Customer::create( [
                'tenant_id'      => $user->tenant_id,
                'customer_type'  => 'individual',
                'priority_level' => $data[ 'priority_level' ] ?? 'normal',
                'status'         => $data[ 'status' ] ?? 'active',
                'metadata'       => $data[ 'metadata' ] ?? null,
            ] );

            // Criar dados pessoais (reutilizando estrutura existente)
            $commonData = $this->createCommonData( $data, $user->tenant_id );

            // Atualizar cliente com dados pessoais
            $customer->update( [ 'common_data_id' => $commonData->id ] );

            // Criar endereços
            if ( isset( $data[ 'addresses' ] ) ) {
                $this->createAddresses( $customer, $data[ 'addresses' ] );
            }

            // Criar contatos
            if ( isset( $data[ 'contacts' ] ) ) {
                $this->createContacts( $customer, $data[ 'contacts' ] );
            }

            // Associar tags
            if ( isset( $data[ 'tags' ] ) ) {
                $customer->syncTags( $data[ 'tags' ] );
            }

            Log::info( 'Cliente pessoa física criado', [
                'customer_id' => $customer->id,
                'user_id'     => $user->id,
                'tenant_id'   => $user->tenant_id,
            ] );

            return $customer->load( [ 'addresses', 'contacts', 'tags' ] );
        } );
    }

    /**
     * Cria um novo cliente pessoa jurídica.
     */
    public function createPessoaJuridica( array $data, User $user ): Customer
    {
        return DB::transaction( function () use ($data, $user) {
            // Criar cliente básico
            $customer = Customer::create( [
                'tenant_id'              => $user->tenant_id,
                'customer_type'          => 'company',
                'company_name'           => $data[ 'company_name' ],
                'fantasy_name'           => $data[ 'fantasy_name' ] ?? null,
                'state_registration'     => $data[ 'state_registration' ] ?? null,
                'municipal_registration' => $data[ 'municipal_registration' ] ?? null,
                'priority_level'         => $data[ 'priority_level' ] ?? 'normal',
                'status'                 => $data[ 'status' ] ?? 'active',
                'metadata'               => $data[ 'metadata' ] ?? null,
            ] );

            // Criar dados pessoais (reutilizando estrutura existente)
            $commonData = $this->createCommonData( $data, $user->tenant_id );

            // Atualizar cliente com dados pessoais
            $customer->update( [ 'common_data_id' => $commonData->id ] );

            // Criar endereços
            if ( isset( $data[ 'addresses' ] ) ) {
                $this->createAddresses( $customer, $data[ 'addresses' ] );
            }

            // Criar contatos
            if ( isset( $data[ 'contacts' ] ) ) {
                $this->createContacts( $customer, $data[ 'contacts' ] );
            }

            // Associar tags
            if ( isset( $data[ 'tags' ] ) ) {
                $customer->syncTags( $data[ 'tags' ] );
            }

            Log::info( 'Cliente pessoa jurídica criado', [
                'customer_id' => $customer->id,
                'user_id'     => $user->id,
                'tenant_id'   => $user->tenant_id,
            ] );

            return $customer->load( [ 'addresses', 'contacts', 'tags' ] );
        } );
    }

    /**
     * Atualiza um cliente existente.
     */
    public function updateCustomer( Customer $customer, array $data, User $user ): Customer
    {
        return DB::transaction( function () use ($customer, $data, $user) {
            // Atualizar dados básicos do cliente
            $customer->update( [
                'priority_level' => $data[ 'priority_level' ] ?? $customer->priority_level,
                'status'         => $data[ 'status' ] ?? $customer->status,
                'metadata'       => $data[ 'metadata' ] ?? $customer->metadata,
            ] );

            // Atualizar dados específicos por tipo
            if ( $customer->customer_type === 'company' ) {
                $customer->update( [
                    'company_name'           => $data[ 'company_name' ] ?? $customer->company_name,
                    'fantasy_name'           => $data[ 'fantasy_name' ] ?? $customer->fantasy_name,
                    'state_registration'     => $data[ 'state_registration' ] ?? $customer->state_registration,
                    'municipal_registration' => $data[ 'municipal_registration' ] ?? $customer->municipal_registration,
                ] );
            }

            // Atualizar endereços
            if ( isset( $data[ 'addresses' ] ) ) {
                $this->updateAddresses( $customer, $data[ 'addresses' ] );
            }

            // Atualizar contatos
            if ( isset( $data[ 'contacts' ] ) ) {
                $this->updateContacts( $customer, $data[ 'contacts' ] );
            }

            // Atualizar tags
            if ( isset( $data[ 'tags' ] ) ) {
                $customer->syncTags( $data[ 'tags' ] );
            }

            Log::info( 'Cliente atualizado', [
                'customer_id' => $customer->id,
                'user_id'     => $user->id,
            ] );

            return $customer->fresh( [ 'addresses', 'contacts', 'tags' ] );
        } );
    }

    /**
     * Busca clientes com filtros avançados.
     */
    public function searchCustomers( array $filters, User $user ): LengthAwarePaginator
    {
        $query = Customer::where( 'tenant_id', $user->tenant_id )
            ->with( [ 'addresses', 'contacts', 'tags', 'interactions' ] );

        // Filtro por texto de busca
        if ( !empty( $filters[ 'search' ] ) ) {
            $search = $filters[ 'search' ];
            $query->where( function ( $q ) use ( $search ) {
                $q->where( 'company_name', 'like', "%{$search}%" )
                    ->orWhere( 'fantasy_name', 'like', "%{$search}%" )
                    ->orWhereHas( 'commonData', function ( $subQuery ) use ( $search ) {
                        $subQuery->where( 'first_name', 'like', "%{$search}%" )
                            ->orWhere( 'last_name', 'like', "%{$search}%" );
                    } )
                    ->orWhereHas( 'contacts', function ( $subQuery ) use ( $search ) {
                        $subQuery->where( 'value', 'like', "%{$search}%" );
                    } );
            } );
        }

        // Filtro por status
        if ( !empty( $filters[ 'status' ] ) ) {
            $query->where( 'status', $filters[ 'status' ] );
        }

        // Filtro por tipo
        if ( !empty( $filters[ 'customer_type' ] ) ) {
            $query->where( 'customer_type', $filters[ 'customer_type' ] );
        }

        // Filtro por nível de prioridade
        if ( !empty( $filters[ 'priority_level' ] ) ) {
            $query->where( 'priority_level', $filters[ 'priority_level' ] );
        }

        // Filtro por tags
        if ( !empty( $filters[ 'tags' ] ) ) {
            $query->withTags( $filters[ 'tags' ] );
        }

        // Filtro por data de cadastro
        if ( !empty( $filters[ 'created_from' ] ) ) {
            $query->where( 'created_at', '>=', $filters[ 'created_from' ] );
        }

        if ( !empty( $filters[ 'created_to' ] ) ) {
            $query->where( 'created_at', '<=', $filters[ 'created_to' ] );
        }

        // Ordenação
        $sortBy        = $filters[ 'sort_by' ] ?? 'created_at';
        $sortDirection = $filters[ 'sort_direction' ] ?? 'desc';

        switch ( $sortBy ) {
            case 'name':
                $query->join( 'common_datas', 'customers.common_data_id', '=', 'common_datas.id' )
                    ->orderBy( 'common_datas.first_name' )
                    ->orderBy( 'common_datas.last_name' )
                    ->select( 'customers.*' );
                break;
            case 'company':
                $query->orderBy( 'company_name' )->orderBy( 'fantasy_name' );
                break;
            case 'last_interaction':
                $query->leftJoin( 'customer_interactions', function ( $join ) {
                    $join->on( 'customers.id', '=', 'customer_interactions.customer_id' )
                        ->whereRaw( 'customer_interactions.id = (
                             SELECT id FROM customer_interactions ci2
                             WHERE ci2.customer_id = customers.id
                             ORDER BY ci2.interaction_date DESC
                             LIMIT 1
                         )' );
                } )
                    ->orderBy( 'customer_interactions.interaction_date', $sortDirection )
                    ->select( 'customers.*' );
                break;
            default:
                $query->orderBy( $sortBy, $sortDirection );
        }

        return $query->paginate( $filters[ 'per_page' ] ?? 15 );
    }

    /**
     * Busca clientes próximos a uma localização.
     */
    public function findNearbyCustomers( float $latitude, float $longitude, int $radiusKm = 10, User $user ): Collection
    {
        return Customer::where( 'tenant_id', $user->tenant_id )
            ->whereHas( 'addresses', function ( $query ) use ( $latitude, $longitude, $radiusKm ) {
                $query->selectRaw( '*, (
                              6371 * acos(
                                  cos(radians(?)) * cos(radians(latitude)) *
                                  cos(radians(longitude) - radians(?)) +
                                  sin(radians(?)) * sin(radians(latitude))
                              )
                          ) as distance', [ $latitude, $longitude, $latitude ] )
                    ->having( 'distance', '<', $radiusKm )
                    ->orderBy( 'distance' );
            } )
            ->with( [ 'addresses', 'primaryAddress' ] )
            ->get();
    }

    /**
     * Obtém estatísticas de clientes.
     */
    public function getCustomerStats( User $user ): array
    {
        $baseQuery = Customer::where( 'tenant_id', $user->tenant_id );

        return [
            'total_customers'                    => ( clone $baseQuery )->count(),
            'active_customers'                   => ( clone $baseQuery )->active()->count(),
            'inactive_customers'                 => ( clone $baseQuery )->where( 'status', 'inactive' )->count(),
            'vip_customers'                      => ( clone $baseQuery )->vip()->count(),
            'individual_customers'               => ( clone $baseQuery )->ofType( 'individual' )->count(),
            'company_customers'                  => ( clone $baseQuery )->ofType( 'company' )->count(),
            'customers_with_recent_interactions' => ( clone $baseQuery )->withRecentInteractions( 30 )->count(),
            'customers_with_pending_actions'     => ( clone $baseQuery )->withPendingActions()->count(),
            'total_addresses'                    => CustomerAddress::whereHas( 'customer', function ( $q ) use ( $user ) {
                $q->where( 'tenant_id', $user->tenant_id );
            } )->count(),
            'total_contacts'                     => CustomerContact::whereHas( 'customer', function ( $q ) use ( $user ) {
                $q->where( 'tenant_id', $user->tenant_id );
            } )->count(),
            'total_interactions'                 => \App\Models\CustomerInteraction::whereHas( 'customer', function ( $q ) use ( $user ) {
                $q->where( 'tenant_id', $user->tenant_id );
            } )->count(),
        ];
    }

    /**
     * Exclui um cliente (soft delete).
     */
    public function deleteCustomer( Customer $customer, User $user ): bool
    {
        return DB::transaction( function () use ($customer, $user) {
            // Marcar cliente como excluído
            $customer->update( [ 'status' => 'deleted' ] );

            Log::info( 'Cliente excluído', [
                'customer_id' => $customer->id,
                'user_id'     => $user->id,
            ] );

            return true;
        } );
    }

    /**
     * Restaura um cliente excluído.
     */
    public function restoreCustomer( Customer $customer, User $user ): bool
    {
        return DB::transaction( function () use ($customer, $user) {
            $customer->update( [ 'status' => 'active' ] );

            Log::info( 'Cliente restaurado', [
                'customer_id' => $customer->id,
                'user_id'     => $user->id,
            ] );

            return true;
        } );
    }

    /**
     * Duplica um cliente existente.
     */
    public function duplicateCustomer( Customer $customer, User $user ): Customer
    {
        return DB::transaction( function () use ($customer, $user) {
            // Criar novo cliente baseado no existente
            $newCustomer         = $customer->replicate();
            $newCustomer->status = 'active';
            $newCustomer->save();

            // Duplicar endereços
            foreach ( $customer->addresses as $address ) {
                $newAddress              = $address->replicate();
                $newAddress->customer_id = $newCustomer->id;
                $newAddress->is_primary  = false; // Remover flag de primário
                $newAddress->save();
            }

            // Duplicar contatos
            foreach ( $customer->contacts as $contact ) {
                $newContact              = $contact->replicate();
                $newContact->customer_id = $newCustomer->id;
                $newContact->is_primary  = false; // Remover flag de primário
                $newContact->is_verified = false; // Remover verificação
                $newContact->verified_at = null;
                $newContact->save();
            }

            // Duplicar tags
            foreach ( $customer->tags as $tag ) {
                $newCustomer->addTag( $tag );
            }

            Log::info( 'Cliente duplicado', [
                'original_customer_id' => $customer->id,
                'new_customer_id'      => $newCustomer->id,
                'user_id'              => $user->id,
            ] );

            return $newCustomer->load( [ 'addresses', 'contacts', 'tags' ] );
        } );
    }

    /**
     * Cria dados pessoais (reutilizando estrutura existente).
     */
    private function createCommonData( array $data, int $tenantId ): \App\Models\CommonData
    {
        return \App\Models\CommonData::create( [
            'tenant_id'    => $tenantId,
            'first_name'   => $data[ 'name' ] ?? $data[ 'contact_person_name' ] ?? '',
            'last_name'    => '',
            'company_name' => $data[ 'company_name' ] ?? null,
            'cpf'          => $data[ 'document' ] ?? null,
            'cnpj'         => $data[ 'document' ] ?? null,
        ] );
    }

    /**
     * Cria endereços para o cliente.
     */
    private function createAddresses( Customer $customer, array $addresses ): void
    {
        foreach ( $addresses as $index => $addressData ) {
            $addressData[ 'customer_id' ] = $customer->id;

            // Buscar geolocalização se não fornecida
            if ( empty( $addressData[ 'latitude' ] ) || empty( $addressData[ 'longitude' ] ) ) {
                $geoData = $this->geolocationService->geocodeAddress( $addressData );

                if ( !empty( $geoData ) ) {
                    $addressData[ 'latitude' ]          = $geoData[ 'latitude' ];
                    $addressData[ 'longitude' ]         = $geoData[ 'longitude' ];
                    $addressData[ 'formatted_address' ] = $geoData[ 'formatted_address' ] ?? null;
                }
            }

            $address = CustomerAddress::create( $addressData );

            // Definir primeiro endereço como principal se não houver nenhum
            if ( $index === 0 && !$customer->addresses()->where( 'is_primary', true )->exists() ) {
                $address->setAsPrimary();
            }
        }
    }

    /**
     * Cria contatos para o cliente.
     */
    private function createContacts( Customer $customer, array $contacts ): void
    {
        foreach ( $contacts as $index => $contactData ) {
            $contactData[ 'customer_id' ] = $customer->id;
            $contact                    = CustomerContact::create( $contactData );

            // Definir primeiro contato como principal se não houver nenhum
            if ( $index === 0 && !$customer->contacts()->where( 'is_primary', true )->exists() ) {
                $contact->setAsPrimary();
            }
        }
    }

    /**
     * Atualiza endereços do cliente.
     */
    private function updateAddresses( Customer $customer, array $addresses ): void
    {
        // Remover endereços não incluídos na atualização
        $addressIds = array_column( $addresses, 'id' );
        $customer->addresses()->whereNotIn( 'id', array_filter( $addressIds ) )->delete();

        foreach ( $addresses as $addressData ) {
            if ( isset( $addressData[ 'id' ] ) ) {
                // Atualizar endereço existente
                $address = CustomerAddress::find( $addressData[ 'id' ] );
                if ( $address ) {
                    $address->update( $addressData );
                }
            } else {
                // Criar novo endereço
                $addressData[ 'customer_id' ] = $customer->id;
                CustomerAddress::create( $addressData );
            }
        }
    }

    /**
     * Atualiza contatos do cliente.
     */
    private function updateContacts( Customer $customer, array $contacts ): void
    {
        // Remover contatos não incluídos na atualização
        $contactIds = array_column( $contacts, 'id' );
        $customer->contacts()->whereNotIn( 'id', array_filter( $contactIds ) )->delete();

        foreach ( $contacts as $contactData ) {
            if ( isset( $contactData[ 'id' ] ) ) {
                // Atualizar contato existente
                $contact = CustomerContact::find( $contactData[ 'id' ] );
                if ( $contact ) {
                    $contact->update( $contactData );
                }
            } else {
                // Criar novo contato
                $contactData[ 'customer_id' ] = $customer->id;
                CustomerContact::create( $contactData );
            }
        }
    }

    /**
     * Valida dados de cliente antes da criação/atualização.
     */
    public function validateCustomerData( array $data, ?Customer $existingCustomer = null ): array
    {
        $errors = [];

        // Validação específica para pessoa física
        if ( ( $data[ 'customer_type' ] ?? null ) === 'individual' ) {
            if ( empty( $data[ 'name' ] ) ) {
                $errors[] = 'Nome é obrigatório para pessoa física.';
            }

            if ( empty( $data[ 'document' ] ) ) {
                $errors[] = 'CPF é obrigatório para pessoa física.';
            }
        }

        // Validação específica para pessoa jurídica
        if ( ( $data[ 'customer_type' ] ?? null ) === 'company' ) {
            if ( empty( $data[ 'company_name' ] ) ) {
                $errors[] = 'Razão social é obrigatória para pessoa jurídica.';
            }

            if ( empty( $data[ 'document' ] ) ) {
                $errors[] = 'CNPJ é obrigatório para pessoa jurídica.';
            }
        }

        // Validação de endereços
        if ( empty( $data[ 'addresses' ] ) ) {
            $errors[] = 'Pelo menos um endereço deve ser informado.';
        }

        // Validação de contatos
        if ( empty( $data[ 'contacts' ] ) ) {
            $errors[] = 'Pelo menos um contato deve ser informado.';
        }

        // Verificar unicidade de documento
        if ( !empty( $data[ 'document' ] ) ) {
            $query = Customer::where( 'document', $data[ 'document' ] );

            if ( $existingCustomer ) {
                $query->where( 'id', '!=', $existingCustomer->id );
            }

            if ( $query->exists() ) {
                $errors[] = 'Já existe um cliente com este documento.';
            }
        }

        return $errors;
    }

}
