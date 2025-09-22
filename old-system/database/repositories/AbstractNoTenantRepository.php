<?php

namespace app\database\repositories;

use app\interfaces\EntityORMInterface;
use app\interfaces\RepositoryNoTenantInterface;
use Doctrine\ORM\EntityRepository;
use Exception;
use RuntimeException;

/**
 * Classe abstrata base para repositórios sem controle de tenant.
 *
 * Fornece implementação padrão dos métodos da interface RepositoryNoTenantInterface,
 * podendo ser sobrescrita pelos repositórios específicos quando necessário.
 *
 * @template T of EntityORMInterface
 * @extends BaseAbstractRepository<T>
 */
abstract class AbstractNoTenantRepository extends BaseAbstractRepository implements RepositoryNoTenantInterface
{
    /**
     * Busca uma entidade pelo seu ID.
     *
     * @param int $id ID da entidade
     * @return EntityORMInterface|null Retorna a entidade encontrada ou null.
     */
    public function findById( int $id ): ?EntityORMInterface
    {
        try {
            return $this->find( $id );
        } catch ( Exception $e ) {
            // Log do erro se necessário
            error_log( "Erro ao buscar entidade por ID {$id}: " . $e->getMessage() );
            return null;
        }
    }

    /**
     * Busca entidades com base em critérios específicos.
     *
     * @param array<string, mixed> $criteria Critérios de busca
     * @param array<string, string>|null $orderBy Ordenação dos resultados
     * @param int|null $limit Limite de resultados
     * @param int|null $offset Deslocamento na consulta
     * @return array<int, ?EntityORMInterface> Retorna um array de entidades (pode ser vazio).
     */
    public function findBy( array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null ): array
    {
        try {
            return parent::findBy( $criteria, $orderBy, $limit, $offset );
        } catch ( Exception $e ) {
            $criteriaStr = json_encode( $criteria );
            throw new RuntimeException(
                "Falha ao buscar entidades com critérios {$criteriaStr}, tente mais tarde ou entre em contato com suporte.",
                0,
                $e,
            );
        }
    }

    /**
     * Busca todas as entidades.
     *
     * @param array<string, mixed> $criteria Critérios adicionais de busca
     * @param array<string, string>|null $orderBy Ordenação dos resultados
     * @param int|null $limit Limite de resultados
     * @param int|null $offset Deslocamento na consulta
     * @return array<int, ?EntityORMInterface> Retorna um array de entidades (pode ser vazio).
     */
    public function findAll( array $criteria = [], ?array $orderBy = null, ?int $limit = null, ?int $offset = null ): array
    {
        try {
            return $this->findBy( $criteria, $orderBy, $limit, $offset );
        } catch ( Exception $e ) {
            throw new RuntimeException(
                "Falha ao buscar todas as entidades, tente mais tarde ou entre em contato com suporte.",
                0,
                $e,
            );
        }
    }

    /**
     * Salva uma entidade no banco de dados.
     *
     * @param EntityORMInterface $entity Entidade a ser salva
     * @return EntityORMInterface|false Entidade completa com dados atualizados ou false em caso de falha
     */
    public function save( EntityORMInterface $entity ): EntityORMInterface|false
    {
        try {
            $entityManager = $this->getEntityManager();

            // Verificar se é uma entidade nova (sem ID)
            // Usar reflexão para acessar o ID de forma segura
            $reflection = new \ReflectionClass( $entity );
            $idProperty = null;

            // Tentar encontrar propriedade 'id'
            if ( $reflection->hasProperty( 'id' ) ) {
                $idProperty = $reflection->getProperty( 'id' );
                $idProperty->setAccessible( true );
            }

            $isNewEntity = $idProperty === null || $idProperty->getValue( $entity ) === null;

            if ( $isNewEntity ) {
                $entityManager->persist( $entity );
            }

            $entityManager->flush();

            // Para entidades novas, verificar se recebeu ID após flush
            if ( $isNewEntity && $idProperty !== null && $idProperty->getValue( $entity ) === null ) {
                return false;
            }

            // Retornar a entidade completa com todos os dados atualizados
            return $entity;
        } catch ( Exception $e ) {
            // Log do erro para debug
            error_log( "Erro ao salvar entidade: " . $e->getMessage() );

            throw new RuntimeException(
                "Falha ao salvar entidade, tente mais tarde ou entre em contato com suporte.",
                0,
                $e,
            );
        }
    }

    /**
     * Exclui uma entidade do banco de dados.
     *
     * @param int $id ID da entidade
     * @return bool Resultado da operação (true = sucesso, false = falha)
     */
    public function delete( int $id ): bool
    {
        try {
            $entity = $this->find( $id );

            if ( !$entity ) {
                return false; // Entidade não encontrada
            }

            $entityManager = $this->getEntityManager();
            $entityManager->remove( $entity );
            $entityManager->flush();

            // Verificar se realmente foi removida
            $checkEntity = $this->find( $id );
            return $checkEntity === null;
        } catch ( Exception $e ) {
            // Log do erro para debug
            error_log( "Erro ao excluir entidade ID {$id}: " . $e->getMessage() );

            throw new RuntimeException(
                "Falha ao excluir entidade, tente mais tarde ou entre em contato com suporte.",
                0,
                $e,
            );
        }
    }

    /**
     * Método auxiliar para buscar uma entidade por slug.
     * Útil para repositórios que possuem campo slug.
     *
     * @param string $slug Slug da entidade
     * @return EntityORMInterface|null
     */
    protected function findBySlug( string $slug ): ?EntityORMInterface
    {
        try {
            return $this->findOneBy( [ 'slug' => $slug ] );
        } catch ( Exception $e ) {
            error_log( "Erro ao buscar entidade por slug '{$slug}': " . $e->getMessage() );
            return null;
        }
    }

    /**
     * Método auxiliar para buscar entidades ativas.
     * Útil para repositórios que possuem campo isActive.
     *
     * @param array<string, string>|null $orderBy Ordenação dos resultados
     * @param int|null $limit Limite de resultados
     * @param int|null $offset Deslocamento na consulta
     * @return array<int, EntityORMInterface>
     */
    protected function findActive( ?array $orderBy = null, ?int $limit = null, ?int $offset = null ): array
    {
        try {
            return $this->findBy( [ 'isActive' => true ], $orderBy, $limit, $offset );
        } catch ( Exception $e ) {
            throw new RuntimeException(
                "Falha ao buscar entidades ativas, tente mais tarde ou entre em contato com suporte.",
                0,
                $e,
            );
        }
    }

    /**
     * Método auxiliar para contar entidades com critérios específicos.
     * Sobrescrevendo método público do Doctrine EntityRepository.
     *
     * @param array<string, mixed> $criteria Critérios de busca
     * @return int Número de entidades encontradas
     */
    public function count( array $criteria = [] ): int
    {
        try {
            $qb = $this->createQueryBuilder( 'e' )
                ->select( 'COUNT(e.id)' );

            foreach ( $criteria as $field => $value ) {
                $qb->andWhere( "e.{$field} = :{$field}" )
                    ->setParameter( $field, $value );
            }

            return (int) $qb->getQuery()->getSingleScalarResult();
        } catch ( Exception $e ) {
            throw new RuntimeException(
                "Falha ao contar entidades, tente mais tarde ou entre em contato com suporte.",
                0,
                $e,
            );
        }
    }

    /**
     * Método auxiliar para verificar se uma entidade existe.
     *
     * @param int $id ID da entidade
     * @return bool
     */
    protected function exists( int $id ): bool
    {
        try {
            $qb = $this->createQueryBuilder( 'e' )
                ->select( 'COUNT(e.id)' )
                ->where( 'e.id = :id' )
                ->setParameter( 'id', $id );

            return (int) $qb->getQuery()->getSingleScalarResult() > 0;
        } catch ( Exception $e ) {
            error_log( "Erro ao verificar existência da entidade ID {$id}: " . $e->getMessage() );
            return false;
        }
    }

}