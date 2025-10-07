<?php

namespace design_patern\design_pattern_no_tenant\repositories;

use app\database\repositories\AbstractNoTenantRepository;
use app\interfaces\EntityORMInterface;
use app\interfaces\RepositoryNoTenantInterface;
use design_patern\design_pattern_no_tenant\entities\DesignPatternNoTenantEntity;
use Doctrine\ORM\EntityRepository;
use Exception;
use RuntimeException;

/**
 * Padrão de Repository - Easy Budget
 *
 * PADRÕES IMPLEMENTADOS:
 * ✅ Retorno EntityORMInterface|false para save() - Acesso completo à entidade
 * ✅ Retorno bool para delete() - Verificação simples de sucesso/falha
 * ✅ Implementa RepositoryNoTenantInterface - Padronização de contratos
 * ✅ Tratamento consistente de exceções - RuntimeException para falhas
 * ✅ Comentários em português brasileiro - Padrão do projeto
 * ✅ Validação robusta - Verificação real de operações no banco
 *
 * BENEFÍCIOS:
 * - Acesso imediato a ID, timestamps, slug após save()
 * - Menos consultas ao banco (dados já disponíveis)
 * - Compatível com Doctrine ORM
 * - Detecção real de falhas de operação
 *
 * @template T of DesignPatternNoTenantEntity
 * @extends AbstractNoTenantRepository<T>
 */
class DesignPatternNoTenantRepository extends AbstractNoTenantRepository
{
    // Todos os métodos obrigatórios já estão implementados na classe abstrata:
    // - findById(int $id): ?EntityORMInterface
    // - findBy(array $criteria, ...): array
    // - findAll(array $criteria = [], ...): array
    // - save(EntityORMInterface $entity): EntityORMInterface|false
    // - delete(int $id): bool

    // Métodos auxiliares disponíveis da classe pai:
    // - findBySlug(string $slug): ?EntityORMInterface (protegido)
    // - findActive(): array (protegido)
    // - count(array $criteria = []): int (público)
    // - exists(int $id): bool (protegido)

    /**
     * Lista todos os endereços com paginação usando método híbrido da classe base.
     *
     * @param int $limit Limite de resultados por página
     * @param int $offset Offset para paginação
     * @return array<string, mixed> Array com entities e informações de paginação
     */
    public function findAllPaginated( int $limit = 20, int $offset = 0 ): array
    {
        return $this->findAllPaginatedHybrid(
            limit: $limit,
            offset: $offset,
            criteria: [],
            orderBy: [ 'id' => 'DESC' ],
            alias: 'a',
        );
    }

}
