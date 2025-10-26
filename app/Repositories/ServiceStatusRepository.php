<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Enums\ServiceStatusEnum;
use App\Repositories\Contracts\GlobalRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use LogicException;

class ServiceStatusRepository implements GlobalRepositoryInterface
{
    /**
     * Busca status por slug
     */
    public function findBySlug( string $slug ): ?ServiceStatusEnum
    {
        return ServiceStatusEnum::tryFrom( $slug );
    }

    /**
     * Busca status ativos
     */
    public function findActive( ?array $orderBy = null, ?int $limit = null ): array
    {
        $activeStatuses = array_filter(
            ServiceStatusEnum::cases(),
            fn( ServiceStatusEnum $status ) => $status->isActive()
        );

        // Ordena por order_index se não especificado
        $orderBy = $orderBy ?? [ 'order_index' => 'asc' ];

        if ( $orderBy[ 'order_index' ] === 'asc' ) {
            usort( $activeStatuses, fn( $a, $b ) => $a->getOrderIndex() <=> $b->getOrderIndex() );
        } else {
            usort( $activeStatuses, fn( $a, $b ) => $b->getOrderIndex() <=> $a->getOrderIndex() );
        }

        if ( $limit ) {
            $activeStatuses = array_slice( $activeStatuses, 0, $limit );
        }

        return array_values( $activeStatuses );
    }

    /**
     * Busca todos os status ordenados
     */
    public function findOrderedBy( string $field, string $direction = 'asc', ?int $limit = null ): array
    {
        $allStatuses = ServiceStatusEnum::cases();

        if ( $field === 'order_index' ) {
            if ( $direction === 'asc' ) {
                usort( $allStatuses, fn( $a, $b ) => $a->getOrderIndex() <=> $b->getOrderIndex() );
            } else {
                usort( $allStatuses, fn( $a, $b ) => $b->getOrderIndex() <=> $a->getOrderIndex() );
            }
        } elseif ( $field === 'name' ) {
            if ( $direction === 'asc' ) {
                usort( $allStatuses, fn( $a, $b ) => $a->getName() <=> $b->getName() );
            } else {
                usort( $allStatuses, fn( $a, $b ) => $b->getName() <=> $a->getName() );
            }
        }

        if ( $limit ) {
            $allStatuses = array_slice( $allStatuses, 0, $limit );
        }

        return array_values( $allStatuses );
    }

    /**
     * Busca status por ID (mantido para compatibilidade)
     */
    public function findById( int $id ): ?ServiceStatusEnum
    {
        // Mapeia IDs antigos para enum values (compatibilidade com código legado)
        $idMapping = [
            1 => 'scheduled',
            2 => 'preparing',
            3 => 'on-hold',
            4 => 'in-progress',
            5 => 'partially-completed',
            6 => 'approved',    // Corrigido: era 'completed', agora 'approved'
            7 => 'rejected',    // Corrigido: era 'cancelled', agora 'rejected'
            8 => 'completed',   // Corrigido: era ID 6, agora ID 8
            9 => 'cancelled',   // Corrigido: era ID 7, agora ID 9
        ];

        $slug = $idMapping[ $id ] ?? null;
        return $slug ? ServiceStatusEnum::tryFrom( $slug ) : null;
    }

    /**
     * Retorna todos os status disponíveis
     */
    public function findAll(): array
    {
        return ServiceStatusEnum::cases();
    }

    /**
     * Busca status por múltiplos critérios (implementação básica)
     */
    public function findBy( array $criteria, ?array $orderBy = null, ?int $limit = null ): array
    {
        $results = [];

        foreach ( ServiceStatusEnum::cases() as $status ) {
            $matches = true;

            foreach ( $criteria as $field => $value ) {
                switch ( $field ) {
                    case 'is_active':
                    case 'active':
                        if ( $status->isActive() !== $value ) {
                            $matches = false;
                        }
                        break;
                    case 'slug':
                        if ( $status->value !== $value ) {
                            $matches = false;
                        }
                        break;
                }

                if ( !$matches ) break;
            }

            if ( $matches ) {
                $results[] = $status;
            }
        }

        // Aplica ordenação
        if ( $orderBy ) {
            foreach ( $orderBy as $field => $direction ) {
                if ( $field === 'order_index' ) {
                    if ( $direction === 'asc' ) {
                        usort( $results, fn( $a, $b ) => $a->getOrderIndex() <=> $b->getOrderIndex() );
                    } else {
                        usort( $results, fn( $a, $b ) => $b->getOrderIndex() <=> $a->getOrderIndex() );
                    }
                }
            }
        }

        if ( $limit ) {
            $results = array_slice( $results, 0, $limit );
        }

        return $results;
    }

    /**
     * Busca um status por critérios (implementação básica)
     */
    public function findOneBy( array $criteria ): ?ServiceStatusEnum
    {
        $results = $this->findBy( $criteria, null, 1 );
        return $results[ 0 ] ?? null;
    }

    /**
     * Conta status por critérios (implementação básica)
     */
    public function countBy( array $criteria ): int
    {
        return count( $this->findBy( $criteria ) );
    }

    // Implementações da BaseRepositoryInterface

    /**
     * Encontra um registro pelo ID (adaptado para enum)
     */
    public function find( int $id ): ?Model
    {
        $status = $this->findById( $id );
        return $status ? $this->enumToModel( $status ) : null;
    }

    /**
     * Retorna todos os registros (adaptado para enum)
     */
    public function getAll(): Collection
    {
        $statuses = ServiceStatusEnum::cases();
        $models   = collect();

        foreach ( $statuses as $status ) {
            $models->push( $this->enumToModel( $status ) );
        }

        return $models;
    }

    /**
     * Cria um novo registro (não aplicável para enums)
     */
    public function create( array $data ): Model
    {
        throw new LogicException( 'Cannot create new ServiceStatus - it is now an enum' );
    }

    /**
     * Atualiza um registro (não aplicável para enums)
     */
    public function update( int $id, array $data ): ?Model
    {
        throw new LogicException( 'Cannot update ServiceStatus - it is now an enum' );
    }

    /**
     * Remove um registro (não aplicável para enums)
     */
    public function delete( int $id ): bool
    {
        throw new LogicException( 'Cannot delete ServiceStatus - it is now an enum' );
    }

    // Implementações da GlobalRepositoryInterface

    /**
     * Busca registros globais com filtros
     */
    public function getAllGlobal(
        array $criteria = [],
        ?array $orderBy = null,
        ?int $limit = null,
        ?int $offset = null,
    ): Collection {
        $results = $this->findBy( $criteria, $orderBy, $limit );

        if ( $offset ) {
            $results = array_slice( $results, $offset );
        }

        $models = collect();
        foreach ( $results as $status ) {
            $models->push( $this->enumToModel( $status ) );
        }

        return $models;
    }

    /**
     * Busca registro global por ID
     */
    public function findGlobal( int $id ): ?Model
    {
        return $this->find( $id );
    }

    /**
     * Cria registro global (não aplicável para enums)
     */
    public function createGlobal( array $data ): Model
    {
        throw new LogicException( 'Cannot create new ServiceStatus - it is now an enum' );
    }

    /**
     * Atualiza registro global (não aplicável para enums)
     */
    public function updateGlobal( int $id, array $data ): ?Model
    {
        throw new LogicException( 'Cannot update ServiceStatus - it is now an enum' );
    }

    /**
     * Remove registro global (não aplicável para enums)
     */
    public function deleteGlobal( int $id ): bool
    {
        throw new LogicException( 'Cannot delete ServiceStatus - it is now an enum' );
    }

    /**
     * Paginação global (adaptada para enum)
     */
    public function paginateGlobal( int $perPage = 15, array $filters = [] ): LengthAwarePaginator
    {
        $allStatuses = $this->findBy( $filters );
        $total       = count( $allStatuses );

        // Simula paginação para enums
        $page   = request( 'page', 1 );
        $offset = ( $page - 1 ) * $perPage;
        $items  = array_slice( $allStatuses, $offset, $perPage );

        $models = collect();
        foreach ( $items as $status ) {
            $models->push( $this->enumToModel( $status ) );
        }

        return new LengthAwarePaginator(
            $models,
            $total,
            $perPage,
            $page,
            [ 'path' => request()->url(), 'pageName' => 'page' ],
        );
    }

    /**
     * Conta registros globais
     */
    public function countGlobal( array $filters = [] ): int
    {
        return $this->countBy( $filters );
    }

    /**
     * Converte enum para um objeto Model-like para compatibilidade
     */
    private function enumToModel( ServiceStatusEnum $status ): Model
    {
        // Cria um objeto anônimo que se comporta como Model
        return new class ($status) extends Model
        {
            public ServiceStatusEnum $enum;

            public function __construct( ServiceStatusEnum $enum )
            {
                $this->enum = $enum;
            }

            public function getKey()
            {
                return $this->enum->getOrderIndex();
            }

            public function getKeyName()
            {
                return 'id';
            }

            public function __get( $key )
            {
                return match ( $key ) {
                    'id'          => $this->enum->getOrderIndex(),
                    'slug'        => $this->enum->value,
                    'name'        => $this->enum->getName(),
                    'color'       => $this->enum->getColor(),
                    'icon'        => $this->enum->getIcon(),
                    'order_index' => $this->enum->getOrderIndex(),
                    'is_active'   => $this->enum->isActive(),
                    default       => null,
                };
            }

            public function toArray(): array
            {
                return [
                    'id'          => $this->enum->getOrderIndex(),
                    'slug'        => $this->enum->value,
                    'name'        => $this->enum->getName(),
                    'color'       => $this->enum->getColor(),
                    'icon'        => $this->enum->getIcon(),
                    'order_index' => $this->enum->getOrderIndex(),
                    'is_active'   => $this->enum->isActive(),
                ];
            }

        };
    }

}
