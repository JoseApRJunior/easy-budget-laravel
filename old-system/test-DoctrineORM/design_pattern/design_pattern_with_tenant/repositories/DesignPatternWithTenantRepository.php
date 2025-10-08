<?php

namespace design_patern\design_pattern_with_tenant\repositories;

use app\database\repositories\AbstractRepository;
use app\interfaces\EntityORMInterface;
use app\interfaces\RepositoryInterface;
use design_patern\design_pattern_with_tenant\entities\DesignPatternWithTenantEntity;
use Doctrine\ORM\EntityRepository;
use Exception;
use RuntimeException;

/**
 * Padrão de Repository WithTenant - Easy Budget
 *
 * PADRÕES IMPLEMENTADOS:
 * ✅ Retorno EntityORMInterface para save() - Acesso completo à entidade
 * ✅ Retorno bool para delete() - Verificação simples de sucesso/falha
 * ✅ Implementa RepositoryInterface - Padronização de contratos multi-tenant
 * ✅ Métodos *ByTenantId - Controle rigoroso de tenant
 * ✅ Validação obrigatória de tenant_id - Segurança multi-tenant
 * ✅ Tratamento consistente de exceções - RuntimeException para falhas
 * ✅ Comentários em português brasileiro - Padrão do projeto
 * ✅ Verificação real de operações no banco - Detecção de falhas
 * ✅ Métodos auxiliares específicos para tenant - isSlugUniqueInTenant
 *
 * BENEFÍCIOS:
 * - Isolamento completo de dados entre tenants
 * - Acesso imediato a ID, timestamps, slug após save()
 * - Menos consultas ao banco (dados já disponíveis)
 * - Compatível com Doctrine ORM
 * - Detecção real de falhas de operação
 * - Validação de segurança em todas as operações
 *
 * @template T of DesignPatternWithTenantEntity
 * @extends AbstractRepository<T>
 *
 */
class DesignPatternWithTenantRepository extends AbstractRepository
{
    // Todos os métodos obrigatórios já estão implementados na classe abstrata:
    // - findByIdAndTenantId(int $id, int $tenant_id): ?EntityORMInterface
    // - findAllByTenantId(int $tenant_id, array $criteria = []): array
    // - save(EntityORMInterface $entity, int $tenant_id): EntityORMInterface
    // - deleteByIdAndTenantId(int $id, int $tenant_id): bool

    // Métodos auxiliares disponíveis da classe pai:
    // - findBySlugAndTenantId(string $slug, int $tenant_id): ?EntityORMInterface (protegido)
    // - findActiveByTenantId(int $tenant_id): array (protegido)
    // - countByTenantId(int $tenant_id, array $criteria = []): int (protegido)
    // - existsByTenantId(int $id, int $tenant_id): bool (protegido)
    // - validateTenantOwnership(EntityORMInterface $entity, int $tenant_id): void (protegido)
    // - isSlugUniqueInTenant(string $slug, int $tenant_id, ?int $excludeId = null): bool (protegido)
}
