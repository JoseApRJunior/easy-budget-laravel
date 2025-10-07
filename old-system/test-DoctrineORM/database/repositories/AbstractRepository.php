<?php
declare(strict_types=1);

namespace app\database\repositories;

use app\interfaces\EntityORMInterface;
use app\interfaces\RepositoryInterface;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\Mapping\ClassMetadata;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

/**
 * Classe abstrata base para repositórios com controle de tenant.
 *
 * Fornece implementação padrão dos métodos da interface RepositoryInterface,
 * garantindo isolamento de dados entre tenants e validação de segurança.
 *
 * @template T of EntityORMInterface
 * @extends EntityRepository<T>
 */
/**
 * @property EntityManager $_em
 * @property-read EntityManager $entityManager
 */
abstract class AbstractRepository extends EntityRepository implements RepositoryInterface
{

    /** @var LoggerInterface */
    protected LoggerInterface $logger;

    /** @var EntityManager */
    protected EntityManager $entityManager;

    /**
     * @method \Doctrine\ORM\EntityManagerInterface merge(object $entity): object
     */

    /**
     * Salva uma entidade no banco de dados com validação multi-tenant.
     * Para inserts, o ID é gerado e populado na entidade após flush().
     * Para updates, o ID existente é preservado.
     *
     * @param EntityORMInterface $entity Entidade a ser salva
     * @param int $tenant_id ID do tenant (deve ser > 0)
     * @return EntityORMInterface|false Entidade salva em sucesso; false em falha (logada)
     * @throws RuntimeException Para cross-tenant irrecuperável ou configuração inválida
     */
    public function save( EntityORMInterface $entity, int $tenant_id ): EntityORMInterface|false
    {
        if ( $tenant_id <= 0 ) {
            $this->logger->error( 'Tentativa de save com tenant_id inválido: {tenant_id}', [ 'tenant_id' => $tenant_id ] );
            return false;
        }

        try {
            $this->entityManager->beginTransaction();

            // Validar tenant ownership early
            $this->validateTenantOwnership( $entity, $tenant_id );

            // Garantir consistência de tenant (refatorado)
            $this->ensureTenantConsistency( $entity, $tenant_id );

            $entityClass = get_class( $entity );
            $hasIdMethod = method_exists( $entity, 'getId' );
            $entityId    = $hasIdMethod ? $entity->getId() : null;

            // Usar _em para acesso ao EntityManager concreto (resolve type issues)

            // Para inserts (nova entidade, sem ID): persist
            if ( !$hasIdMethod || $entityId === null || $entityId === 0 ) {
                $this->entityManager->persist( $entity );
            }
            // Para updates (com ID): se detached, verificar existência e merge
            elseif ( !$this->entityManager->contains( $entity ) ) {
                // Verificar se entidade existe no DB para evitar insert acidental
                if ( !$this->existsByTenantId( $entityId, $tenant_id ) ) {
                    $this->entityManager->rollback();
                    $this->logger->warning( 'Entidade não encontrada para merge/update no tenant {tenant_id}: {entity_class} (ID: {entity_id})', [ 
                        'tenant_id'    => $tenant_id,
                        'entity_class' => $entityClass,
                        'entity_id'    => $entityId
                    ] );
                    return false;
                }

                $entity = $this->entityManager->merge( $entity );
            }
            // Se já managed, noop - apenas flush salvará mudanças

            $this->entityManager->flush();
            $this->entityManager->commit();

            // Atualizar entityId pós-flush para inserts
            $entityId = $hasIdMethod ? $entity->getId() : null;

            $this->logger->info( 'Entidade salva com sucesso para tenant {tenant_id}: {entity_class} (ID: {entity_id})', [ 
                'tenant_id'    => $tenant_id,
                'entity_class' => $entityClass,
                'entity_id'    => $entityId
            ] );

            // Verificar ID para inserts (edge: se null, error)
            if ( $entityId === null || $entityId === 0 ) {
                $this->logger->error( 'ID não gerado após save para tenant {tenant_id}: {entity_class} - falha crítica', [ 
                    'tenant_id'    => $tenant_id,
                    'entity_class' => $entityClass
                ] );
                return false;
            }

            return $entity; // ID populado para inserts
        } catch ( ORMException | Exception $e ) {
            $this->rollbackIfInTransaction();
            $this->logSaveFailure( $entity, $tenant_id, $e, 'Erro de persistência Doctrine' );

            return false;
        } catch ( RuntimeException $e ) {
            // Cross-tenant ou validação: re-throw se irrecuperável
            $this->logger->error( 'Falha cross-tenant detectada para tenant {tenant_id}: {message}', [ 
                'tenant_id' => $tenant_id,
                'message'   => $e->getMessage()
            ] );
            throw $e; // Preserva throw para security
        } catch ( Throwable $e ) {
            // Genérico: rollback e log
            $this->rollbackIfInTransaction();
            $this->logSaveFailure( $entity, $tenant_id, $e, 'Erro inesperado' );

            return false;
        }
    }

    /**
     * Garante consistência de tenant na entidade.
     *
     * @param EntityORMInterface $entity
     * @param int $tenant_id
     * @throws RuntimeException Para inconsistências
     */
    private function ensureTenantConsistency( EntityORMInterface $entity, int $tenant_id ): void
    {
        $entityClass = get_class( $entity );

        // Skip para TenantEntity (self-referential)
        if ( $entityClass === 'app\\database\\entities\\ORM\\TenantEntity' ) {
            return;
        }

        // Prioridade: setTenantId se disponível
        if ( method_exists( $entity, 'setTenantId' ) ) {
            $entity->setTenantId( $tenant_id );
            return;
        }

        // Verificar getTenantId
        if ( method_exists( $entity, 'getTenantId' ) ) {
            $currentTenantId = $entity->getTenantId();
            if ( $currentTenantId !== null && $currentTenantId !== $tenant_id ) {
                throw new RuntimeException(
                    "Tentativa de manipulação cross-tenant: entidade tem tenant {$currentTenantId}, mas solicitada para {$tenant_id}.",
                );
            }
            return;
        }

        // Verificar getTenant relationship
        if ( method_exists( $entity, 'getTenant' ) ) {
            $tenant = $entity->getTenant();
            if ( $tenant && method_exists( $tenant, 'getId' ) && $tenant->getId() !== $tenant_id ) {
                throw new RuntimeException(
                    "Tentativa de manipulação cross-tenant: entidade ligada ao tenant {$tenant->getId()}, mas solicitada para {$tenant_id}.",
                );
            }
            return;
        }

        // Fallback: validateTenantOwnership cobre
        $this->validateTenantOwnership( $entity, $tenant_id );
    }

    /**
     * Rollback se em transação.
     */
    private function rollbackIfInTransaction(): void
    {
        if ( $this->entityManager->isOpen() && $this->entityManager->getConnection()->getTransactionNestingLevel() > 0 ) {
            $this->entityManager->getConnection()->rollback();
        }
    }

    /**
     * Log falha de save com contexto.
     */
    private function logSaveFailure( EntityORMInterface $entity, int $tenant_id, Throwable $e, string $context ): void
    {
        $entityClass = get_class( $entity );
        $entityId    = method_exists( $entity, 'getId' ) ? $entity->getId() : null;
        $this->logger->error( "Falha ao salvar entidade para tenant {tenant_id} ({context}): {message}", [ 
            'tenant_id'    => $tenant_id,
            'context'      => $context,
            'entity_class' => $entityClass,
            'entity_id'    => $entityId,
            'message'      => $e->getMessage(),
            'trace'        => $e->getTraceAsString()
        ] );
    }

    /**
     * Busca uma entidade pelo seu ID e ID do tenant.
     *
     * @param int $id ID da entidade
     * @param int $tenant_id ID do tenant
     * @return EntityORMInterface|null Retorna a entidade encontrada ou null.
     */
    public function findByIdAndTenantId( int $id, int $tenant_id ): ?EntityORMInterface
    {
        try {
            return $this->findOneBy( [ 'id' => $id, 'tenantId' => $tenant_id ] );
        } catch ( Exception $e ) {
            // Log de segurança para tentativas de acesso cross-tenant
            $this->logger->warning( "Tentativa de acesso cross-tenant - ID: {$id}, Tenant: {$tenant_id} - " . $e->getMessage() );
            return null;
        }
    }

    /**
     * Busca todas as entidades de um tenant.
     *
     * @param int $tenant_id ID do tenant
     * @param array<string, mixed> $criteria Critérios adicionais de busca
     * @param array<string, string>|null $orderBy Ordenação dos resultados
     * @param int|null $limit Limite de resultados
     * @param int|null $offset Deslocamento na consulta
     * @return array<int, ?EntityORMInterface> Retorna um array de entidades (pode ser vazio).
     */
    public function findAllByTenantId( int $tenant_id, array $criteria = [], ?array $orderBy = null, ?int $limit = null, ?int $offset = null ): array
    {
        try {
            // Adicionar tenant_id aos critérios de busca
            $criteria[ 'tenantId' ] = $tenant_id;
            return $this->findBy( $criteria, $orderBy, $limit, $offset );
        } catch ( Exception $e ) {
            $criteriaStr = json_encode( $criteria );
            throw new RuntimeException(
                "Falha ao buscar entidades do tenant {$tenant_id} com critérios {$criteriaStr}, tente mais tarde ou entre em contato com suporte.",
                0,
                $e,
            );
        }
    }

    // /**
    //  * Salva uma entidade no banco de dados.
    //  *
    //  * @param EntityORMInterface $entity Entidade a ser salva
    //  * @param int $tenant_id ID do tenant
    //  * @return EntityORMInterface Resultado da operação
    //  */
    // public function save( EntityORMInterface $entity, int $tenant_id ): EntityORMInterface
    // {
    //     try {
    //         $entityManager = $this->getEntityManager();

    //         // Validar se a entidade pertence ao tenant correto
    //         $this->validateTenantOwnership( $entity, $tenant_id );

    //         // Garantir que o tenant_id está definido na entidade
    //         // Verificamos primeiro se a entidade tem o método setTenantId
    //         if ( method_exists( $entity, 'setTenantId' ) ) {
    //             /** @var object $entity */
    //             $entity->setTenantId( $tenant_id );
    //         }
    //         // Se não tem setTenantId, mas tem getTenantId, verificamos se o tenant está correto
    //         elseif ( method_exists( $entity, 'getTenantId' ) ) {
    //             /** @var object $entity */
    //             $entityTenantId = $entity->getTenantId();
    //             if ( $entityTenantId !== null && $entityTenantId !== $tenant_id ) {
    //                 throw new RuntimeException(
    //                     "Tentativa de manipulação cross-tenant detectada. Entidade pertence ao tenant {$entityTenantId}, mas operação solicitada para tenant {$tenant_id}.",
    //                 );
    //             }
    //         }
    //         // Se não tem os métodos acima, mas tem getTenant, verificamos o relacionamento
    //         elseif ( method_exists( $entity, 'getTenant' ) ) {
    //             /** @var object $entity */
    //             $tenant = $entity->getTenant();
    //             if ( $tenant && method_exists( $tenant, 'getId' ) ) {
    //                 /** @var object $tenant */
    //                 if ( $tenant->getId() !== $tenant_id ) {
    //                     throw new RuntimeException(
    //                         "Tentativa de manipulação cross-tenant detectada. Entidade pertence ao tenant {$tenant->getId()}, mas operação solicitada para tenant {$tenant_id}.",
    //                     );
    //                 }
    //             }
    //         }
    //         // Para entidades que não têm métodos de tenant, validar através do validateTenantOwnership
    //         else {
    //             $this->validateTenantOwnership( $entity, $tenant_id );
    //         }

    //         $entityManager->persist( $entity );
    //         $entityManager->flush();

    //         return $entity;
    //     } catch ( Exception $e ) {
    //         // Log de segurança para tentativas de manipulação cross-tenant
    //         error_log( "Tentativa de salvamento cross-tenant - Tenant: {$tenant_id} - " . $e->getMessage() );

    //         throw new RuntimeException(
    //             "Falha ao salvar entidade do tenant {$tenant_id}, tente mais tarde ou entre em contato com suporte.",
    //             0,
    //             $e,
    //         );
    //     }
    // }

    /**
     * Exclui uma entidade do banco de dados.
     *
     * @param int $id ID da entidade
     * @param int $tenant_id ID do tenant
     * @return bool Retorna true em caso de sucesso na exclusão, false caso contrário.
     */
    public function deleteByIdAndTenantId( int $id, int $tenant_id ): bool
    {
        try {
            $entity = $this->findOneBy( [ 'id' => $id, 'tenantId' => $tenant_id ] );

            if ( !$entity ) {
                // Log de segurança para tentativas de exclusão cross-tenant
                $this->logger->warning( "Tentativa de exclusão cross-tenant - ID: {$id}, Tenant: {$tenant_id}" );
                return false;
            }

            $entityManager = $this->getEntityManager();
            $entityManager->remove( $entity );
            $entityManager->flush();

            return true;
        } catch ( Exception $e ) {
            // Log de segurança
            $this->logger->error( "Erro ao excluir entidade ID {$id} do tenant {$tenant_id}: " . $e->getMessage() );

            throw new RuntimeException(
                "Falha ao excluir entidade do tenant {$tenant_id}, tente mais tarde ou entre em contato com suporte.",
                0,
                $e,
            );
        }
    }

    /**
     * Método auxiliar para buscar entidades por slug em um tenant específico.
     *
     * @param string $slug Slug da entidade
     * @param int $tenant_id ID do tenant
     * @return EntityORMInterface|null
     */
    protected function findBySlugAndTenantId( string $slug, int $tenant_id ): ?EntityORMInterface
    {
        try {
            return $this->findOneBy( [ 'slug' => $slug, 'tenantId' => $tenant_id ] );
        } catch ( Exception $e ) {
            $this->logger->error( "Erro ao buscar entidade por slug '{$slug}' do tenant {$tenant_id}: " . $e->getMessage() );
            return null;
        }
    }

    /**
     * Método auxiliar para buscar entidades ativas de um tenant.
     *
     * @param int $tenant_id ID do tenant
     * @param array<string, string>|null $orderBy Ordenação dos resultados
     * @param int|null $limit Limite de resultados
     * @param int|null $offset Deslocamento na consulta
     * @return array<int, EntityORMInterface>
     */
    protected function findActiveByTenantId( int $tenant_id, ?array $orderBy = null, ?int $limit = null, ?int $offset = null ): array
    {
        try {
            return $this->findBy( [ 'tenantId' => $tenant_id, 'isActive' => true ], $orderBy, $limit, $offset );
        } catch ( Exception $e ) {
            throw new RuntimeException(
                "Falha ao buscar entidades ativas do tenant {$tenant_id}, tente mais tarde ou entre em contato com suporte.",
                0,
                $e,
            );
        }
    }

    /**
     * Método auxiliar para contar entidades de um tenant com critérios específicos.
     *
     * @param int $tenant_id ID do tenant
     * @param array<string, mixed> $criteria Critérios de busca
     * @return int Número de entidades encontradas
     */
    protected function countByTenantId( int $tenant_id, array $criteria = [] ): int
    {
        try {
            $qb = $this->createQueryBuilder( 'e' )
                ->select( 'COUNT(e.id)' )
                ->where( 'e.tenantId = :tenantId' )
                ->setParameter( 'tenantId', $tenant_id );

            foreach ( $criteria as $field => $value ) {
                $qb->andWhere( "e.{$field} = :{$field}" )
                    ->setParameter( $field, $value );
            }

            return (int) $qb->getQuery()->getSingleScalarResult();
        } catch ( Exception $e ) {
            throw new RuntimeException(
                "Falha ao contar entidades do tenant {$tenant_id}, tente mais tarde ou entre em contato com suporte.",
                0,
                $e,
            );
        }
    }

    /**
     * Método auxiliar para verificar se uma entidade existe em um tenant.
     *
     * @param int $id ID da entidade
     * @param int $tenant_id ID do tenant
     * @return bool
     */
    protected function existsByTenantId( int $id, int $tenant_id ): bool
    {
        try {
            $qb = $this->createQueryBuilder( 'e' )
                ->select( 'COUNT(e.id)' )
                ->where( 'e.id = :id' )
                ->andWhere( 'e.tenantId = :tenantId' )
                ->setParameter( 'id', $id )
                ->setParameter( 'tenantId', $tenant_id );

            return (int) $qb->getQuery()->getSingleScalarResult() > 0;
        } catch ( Exception $e ) {
            $this->logger->error( "Erro ao verificar existência da entidade ID {$id} no tenant {$tenant_id}: " . $e->getMessage() );
            return false;
        }
    }

    /**
     * Verifica se a entidade pertence ao tenant especificado.
     *
     * @param EntityORMInterface $entity Entidade a ser validada
     * @param int $tenant_id ID do tenant esperado
     * @throws RuntimeException Se a entidade não pertencer ao tenant
     */
    protected function validateTenantOwnership( EntityORMInterface $entity, int $tenant_id ): void
    {
        $entityClass = get_class( $entity );

        // Skip para TenantEntity (self-referential)
        if ( $entityClass === 'app\\database\\entities\\ORM\\TenantEntity' ) {
            return;
        }

        // Verificar se a entidade tem método getTenantId (campos diretos)
        if ( method_exists( $entity, 'getTenantId' ) ) {
            /** @var object $entity */
            $entityTenantId = $entity->getTenantId();

            // Para entidades novas, o tenant_id pode ser null
            if ( $entityTenantId !== null && $entityTenantId !== $tenant_id ) {
                throw new RuntimeException(
                    "Tentativa de manipulação cross-tenant detectada. Entidade pertence ao tenant {$entityTenantId}, mas operação solicitada para tenant {$tenant_id}.",
                );
            }
        }
        // Verificar se a entidade tem método getTenant (relacionamentos)
        elseif ( method_exists( $entity, 'getTenant' ) ) {
            /** @var object $entity */
            $tenant = $entity->getTenant();
            if ( $tenant && method_exists( $tenant, 'getId' ) ) {
                /** @var object $tenant */
                $entityTenantId = $tenant->getId();

                if ( $entityTenantId !== $tenant_id ) {
                    throw new RuntimeException(
                        "Tentativa de manipulação cross-tenant detectada. Entidade pertence ao tenant {$entityTenantId}, mas operação solicitada para tenant {$tenant_id}.",
                    );
                }
            } else {
                throw new RuntimeException(
                    "Entidade possui método getTenant, mas o objeto retornado não tem método getId. Verifique a implementação da entidade.",
                );
            }
        }
        // Se não tem nenhum método de verificação de tenant, lançar exceção
        else {
            throw new RuntimeException(
                "Entidade não possui método para verificação de tenant. Verifique a implementação da entidade.",
            );
        }
    }

    /**
     * Verifica se um slug é único dentro de um tenant.
     *
     * @param string $slug Slug a ser verificado
     * @param int $tenant_id ID do tenant
     * @param int|null $excludeId ID da entidade a ser excluída da verificação (para updates)
     * @return bool
     */
    protected function isSlugUniqueInTenant( string $slug, int $tenant_id, ?int $excludeId = null ): bool
    {
        try {
            $qb = $this->createQueryBuilder( 'e' )
                ->select( 'COUNT(e.id)' )
                ->where( 'e.slug = :slug' )
                ->andWhere( 'e.tenantId = :tenantId' )
                ->setParameter( 'slug', $slug )
                ->setParameter( 'tenantId', $tenant_id );

            if ( $excludeId !== null ) {
                $qb->andWhere( 'e.id != :excludeId' )
                    ->setParameter( 'excludeId', $excludeId );
            }

            return (int) $qb->getQuery()->getSingleScalarResult() === 0;
        } catch ( Exception $e ) {
            $this->logger->error( "Erro ao verificar unicidade do slug '{$slug}' no tenant {$tenant_id}: " . $e->getMessage() );
            return false;
        }
    }

}