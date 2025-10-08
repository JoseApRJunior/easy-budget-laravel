<?php

namespace app\database\repositories;

use Doctrine\ORM\EntityRepository;
use Exception;

/**
 * Classe base abstrata para todos os repositórios.
 *
 * Fornece métodos híbridos comuns que podem ser utilizados por repositórios
 * com e sem controle de tenant, promovendo reutilização de código.
 *
 * @template T of object
 */
class BaseAbstractRepository extends EntityRepository
{
    /**
     * Lista todas as entidades com paginação - método híbrido.
     *
     * Este método pode ser usado por repositórios com ou sem tenant,
     * fornecendo funcionalidade de paginação padronizada.
     *
     * @param int $limit Limite de resultados por página
     * @param int $offset Offset para paginação
     * @param array<string, mixed> $criteria Critérios de busca adicionais
     * @param array<string, string>|null $orderBy Ordenação dos resultados
     * @param string $alias Alias para o QueryBuilder (padrão: 'e')
     * @return array<string, mixed> Array com entities e informações de paginação
     */
    protected function findAllPaginatedHybrid(
        int $limit = 20,
        int $offset = 0,
        array $criteria = [],
        ?array $orderBy = null,
        string $alias = 'e',
    ): array {
        try {
            $queryBuilder = $this->createQueryBuilder( $alias )
                ->setMaxResults( $limit )
                ->setFirstResult( $offset );

            // Aplicar critérios de busca
            foreach ( $criteria as $field => $value ) {
                if ( $value !== null ) {
                    $queryBuilder->andWhere( "{$alias}.{$field} = :{$field}" )
                        ->setParameter( $field, $value );
                }
            }

            // Aplicar ordenação
            if ( $orderBy !== null ) {
                foreach ( $orderBy as $field => $direction ) {
                    $queryBuilder->addOrderBy( "{$alias}.{$field}", $direction );
                }
            } else {
                // Ordenação padrão por ID decrescente
                $queryBuilder->orderBy( "{$alias}.id", 'DESC' );
            }

            $results = $queryBuilder->getQuery()->getResult();

            // Conta o total de registros
            $countQueryBuilder = $this->createQueryBuilder( $alias )
                ->select( "COUNT({$alias}.id)" );

            // Aplicar os mesmos critérios na contagem
            foreach ( $criteria as $field => $value ) {
                if ( $value !== null ) {
                    $countQueryBuilder->andWhere( "{$alias}.{$field} = :{$field}" )
                        ->setParameter( $field, $value );
                }
            }

            $totalCount = $countQueryBuilder->getQuery()->getSingleScalarResult();

            return [ 
                'entities'   => $results,
                'count'      => count( $results ),
                'totalCount' => (int) $totalCount,
                'pagination' => [ 
                    'limit'       => $limit,
                    'offset'      => $offset,
                    'hasMore'     => ( $offset + $limit ) < $totalCount,
                    'currentPage' => intval( $offset / $limit ) + 1,
                    'totalPages'  => $limit > 0 ? intval( ceil( $totalCount / $limit ) ) : 1,
                ],
            ];
        } catch ( Exception $e ) {
            throw new Exception( "Falha ao listar entidades: " . $e->getMessage(), 0, $e );
        }
    }

}
