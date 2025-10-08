<?php

namespace app\database\repositories;

use app\database\entitiesORM\PlanEntity;

/**
 * Repositório para gerenciar planos do sistema.
 * 
 * Estende AbstractNoTenantRepository para ter todos os métodos básicos sem tenant
 * implementados automaticamente, já que planos são globais no sistema.
 *
 * @template T of PlanEntity
 * @extends AbstractNoTenantRepository<T>
 */
class PlanRepository extends AbstractNoTenantRepository
{
    /**
     * Busca planos ativos ordenados por preço.
     *
     * @return array<int, PlanEntity> Lista de planos ativos
     */
    public function findActivePlansOrderedByPrice(): array
    {
        /** @var array<int, PlanEntity> $result */
        $result = $this->findBy(['status' => true], ['price' => 'ASC']);
        return $result;
    }
    
    /**
     * Busca um plano por slug.
     *
     * @param string $slug Slug do plano
     * @return PlanEntity|null Plano encontrado ou null
     */
    public function findBySlug(string $slug): ?PlanEntity
    {
        /** @var PlanEntity|null $result */
        $result = parent::findBySlug($slug);
        return $result;
    }
}

