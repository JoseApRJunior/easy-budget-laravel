<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\InvoiceStatus;

/**
 * Repositório para gerenciamento de status de faturas.
 *
 * Esta classe estende AbstractNoTenantRepository para fornecer operações CRUD
 * básicas para InvoiceStatus, que é uma entidade global (sem tenant isolation).
 * Como tabela de lookup, não possui tenant_id e pode ser acessada por todos os tenants.
 *
 * @package App\Repositories
 */
class InvoiceStatusRepository extends AbstractGlobalRepository
{
    /**
     * Classe do modelo Eloquent associado a este repositório.
     *
     * @var string
     */
    protected string $modelClass = InvoiceStatus::class;

    /**
     * Busca status de fatura por slug.
     *
     * @param string $slug Slug único do status
     * @return InvoiceStatus|null Status encontrado ou null se não existir
     */
    public function findBySlug( string $slug ): ?InvoiceStatus
    {
        return $this->findOneBy( [ 'slug' => $slug ] );
    }

    /**
     * Busca status ativos ordenados por order_index.
     *
     * @param array|null $orderBy Ordenação personalizada (opcional)
     * @param int|null $limit Limite de registros (opcional)
     * @return array<InvoiceStatus> Lista de status ativos
     */
    public function findActive( ?array $orderBy = null, ?int $limit = null ): array
    {
        return $this->findBy( [ 'is_active' => true ], $orderBy, $limit );
    }

    /**
     * Busca status ordenados por um campo específico.
     *
     * @param string $field Campo para ordenação
     * @param string $direction Direção da ordenação (asc/desc)
     * @param int|null $limit Limite de registros (opcional)
     * @return array<InvoiceStatus> Lista ordenada de status
     */
    public function findOrderedBy( string $field, string $direction = 'asc', ?int $limit = null ): array
    {
        return $this->findOrderedBy( $field, $direction, $limit );
    }

    /**
     * Busca status por nome (case-insensitive).
     *
     * @param string $name Nome do status
     * @return InvoiceStatus|null Status encontrado ou null se não existir
     */
    public function findByName( string $name ): ?InvoiceStatus
    {
        return $this->findOneBy( [ 'name' => $name ] );
    }

    /**
     * Verifica se existe status com slug específico.
     *
     * @param string $slug Slug para verificação
     * @return bool True se existe, false caso contrário
     */
    public function existsBySlug( string $slug ): bool
    {
        return $this->existsBy( [ 'slug' => $slug ] );
    }

    /**
     * Conta total de status ativos.
     *
     * @return int Total de status ativos
     */
    public function countActive(): int
    {
        return $this->countBy( [ 'is_active' => true ] );
    }

    /**
     * Busca status por cor específica.
     *
     * @param string $color Cor do status
     * @return array<InvoiceStatus> Lista de status com a cor especificada
     */
    public function findByColor( string $color ): array
    {
        return $this->findBy( [ 'color' => $color ] );
    }

    /**
     * Busca status dentro de um range de order_index.
     *
     * @param int $minOrderIndex Mínimo order_index
     * @param int $maxOrderIndex Máximo order_index
     * @return array<InvoiceStatus> Lista de status no range especificado
     */
    public function findByOrderIndexRange( int $minOrderIndex, int $maxOrderIndex ): array
    {
        return $this->findBy( [
            [ 'order_index', '>=', $minOrderIndex ],
            [ 'order_index', '<=', $maxOrderIndex ]
        ] );
    }

}
