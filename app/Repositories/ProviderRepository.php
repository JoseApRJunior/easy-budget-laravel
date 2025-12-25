<?php

declare(strict_types=1);

namespace App\Repositories;

use App\DTOs\Provider\ProviderDTO;
use App\Models\Provider;
use App\Models\User;
use App\Repositories\Abstracts\AbstractTenantRepository;
use Illuminate\Database\Eloquent\Model;

/**
 * Repositório para gerenciamento de provedores.
 *
 * Estende AbstractTenantRepository para operações tenant-aware
 * com isolamento automático de dados por empresa.
 */
class ProviderRepository extends AbstractTenantRepository
{
    /**
     * Define o Model a ser utilizado pelo Repositório.
     */
    protected function makeModel(): Model
    {
        return new Provider();
    }

    /**
     * Cria um novo provider a partir de um DTO.
     */
    public function createFromDTO(ProviderDTO $dto): Provider
    {
        return $this->create($dto->toArray());
    }

    /**
     * Atualiza um provider a partir de um DTO.
     */
    public function updateFromDTO(int $id, ProviderDTO $dto): ?Provider
    {
        return $this->update($id, $dto->toArray());
    }

    /**
     * Busca provedor por ID de usuário dentro do tenant atual.
     *
     * @param int $userId
     * @return Provider|null
     */
    public function findByUserId( int $userId ): ?Provider
    {
        return $this->model->where( 'user_id', $userId )->first();
    }

    /**
     * Busca provedor por slug dentro do tenant atual.
     *
     * @param string $slug
     * @param bool $withTrashed
     * @return Provider|null
     */
    public function findBySlug( string $slug, bool $withTrashed = false ): ?Provider
    {
        $query = $this->model->where('slug', $slug);
        
        if ($withTrashed) {
            $query->withTrashed();
        }

        return $query->first();
    }

    /**
     * Busca Provider por user_id com tenant específico.
     */
    public function findByUserIdAndTenant( int $userId, int $tenantId ): ?Provider
    {
        return $this->model->where( 'user_id', $userId )
            ->where( 'tenant_id', $tenantId )
            ->with( [ 'user', 'commonData', 'contact', 'address', 'businessData' ] )
            ->first();
    }

    /**
     * Verifica disponibilidade de email.
     */
    public function isEmailAvailable( string $email, int $excludeUserId, int $tenantId ): bool
    {
        return !User::where( 'email', $email )
            ->where( 'tenant_id', $tenantId )
            ->where( 'id', '!=', $excludeUserId )
            ->exists();
    }

    /**
     * Busca Provider com relacionamentos específicos.
     */
    public function findWithRelations( int $providerId, array $relations = [] ): ?Provider
    {
        return $this->model->with( $relations )->find( $providerId );
    }

    /**
     * Obtém estatísticas do dashboard para o provider.
     */
    public function getDashboardStats(int $tenantId): array
    {
        // Esta lógica pode ser expandida conforme necessário
        return [
            'total_customers' => \App\Models\Customer::where('tenant_id', $tenantId)->count(),
            'total_budgets'   => \App\Models\Budget::where('tenant_id', $tenantId)->count(),
            'total_invoices'  => \App\Models\Invoice::where('tenant_id', $tenantId)->count(),
            'total_services'  => \App\Models\Service::where('tenant_id', $tenantId)->count(),
        ];
    }
}
