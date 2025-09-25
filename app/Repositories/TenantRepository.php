<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Interfaces\EntityORMInterface;
use App\Models\Tenant;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Repositório para gerenciar tenants (não tenant-aware)
 *
 * Estende AbstractNoTenantRepository para operações globais
 * de gerenciamento de tenants, como criação, busca e validação
 */
class TenantRepository extends AbstractNoTenantRepository
{
    /**
     * {@inheritdoc}
     */
    protected string $modelClass = Tenant::class;

    /**
     * Busca tenant por ID
     *
     * @param int $id ID do tenant
     * @return Tenant|null Tenant encontrado ou null
     */
    public function findById( int $id ): ?Tenant
    {
        try {
            $tenant = Tenant::find( $id );

            $this->logOperation( 'findById', [ 
                'id'    => $id,
                'found' => $tenant !== null
            ] );

            return $tenant;
        } catch ( Throwable $e ) {
            $this->logError( 'findById', $e, [ 'id' => $id ] );
            return null;
        }
    }

    /**
     * Busca todos os tenants ativos
     *
     * @param array|null $orderBy Ordenação opcional
     * @param int|null $limit Limite de resultados
     * @return array Array de tenants ativos
     */
    public function findActive( ?array $orderBy = null, ?int $limit = null ): array
    {
        try {
            $query = Tenant::where( 'active', true );

            if ( is_array( $orderBy ) ) {
                foreach ( $orderBy as $field => $direction ) {
                    $query->orderBy( $field, $direction );
                }
            } else if ( $orderBy ) {
                $query->orderBy( 'created_at', $orderBy );
            }

            if ( $limit !== null ) {
                $query->limit( $limit );
            }

            $tenants = $query->get()->toArray();

            $this->logOperation( 'findActive', [ 
                'order_by' => $orderBy,
                'limit'    => $limit,
                'count'    => count( $tenants )
            ] );

            return $tenants;
        } catch ( Throwable $e ) {
            $this->logError( 'findActive', $e, [ 'order_by' => $orderBy, 'limit' => $limit ] );
            return [];
        }
    }

    /**
     * Verifica se um domínio já está sendo usado por outro tenant
     *
     * @param string $domain Domínio a ser verificado
     * @param string|null $excludeId ID do tenant a ser excluído (para updates)
     * @return bool True se o domínio é único, false caso contrário
     */
    public function isDomainUnique( string $domain, ?string $excludeId = null ): bool
    {
        try {
            $query = Tenant::where( 'domain', $domain );

            if ( $excludeId !== null ) {
                $query->where( 'id', '!=', $excludeId );
            }

            $exists = $query->exists();

            $this->logOperation( 'isDomainUnique', [ 
                'domain'     => $domain,
                'exclude_id' => $excludeId,
                'unique'     => !$exists
            ] );

            return !$exists;
        } catch ( Throwable $e ) {
            $this->logError( 'isDomainUnique', $e, [ 'domain' => $domain ] );
            return false;
        }
    }

    /**
     * Cria um novo tenant com dados básicos
     *
     * @param array $data Dados do tenant
     * @return Tenant|false Tenant criado ou false em caso de erro
     */
    public function createTenant( array $data ): Tenant|false
    {
        try {
            $tenant = new Tenant( $data );
            $tenant->save();

            $this->logOperation( 'createTenant', [ 
                'data'      => $data,
                'tenant_id' => $tenant->id,
                'success'   => true
            ] );

            return $tenant;
        } catch ( Throwable $e ) {
            $this->logError( 'createTenant', $e, [ 'data' => $data ] );
            return false;
        }
    }

    /**
     * Atualiza dados do tenant
     *
     * @param string $id ID do tenant
     * @param array $data Dados para atualização
     * @return Tenant|false Tenant atualizado ou false em caso de erro
     */
    public function updateTenant( string $id, array $data ): Tenant|false
    {
        try {
            $tenant = Tenant::find( $id );

            if ( !$tenant ) {
                $this->logError( 'updateTenant', new Exception( 'Tenant not found' ), [ 'id' => $id ] );
                return false;
            }

            $tenant->fill( $data );
            $tenant->save();

            $this->logOperation( 'updateTenant', [ 
                'id'      => $id,
                'success' => true
            ] );

            return $tenant;
        } catch ( Throwable $e ) {
            $this->logError( 'updateTenant', $e, [ 'id' => $id, 'data' => $data ] );
            return false;
        }
    }

}