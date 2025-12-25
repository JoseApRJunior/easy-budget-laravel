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
    public function findByUserId(int $userId): ?Provider
    {
        return $this->model->where('user_id', $userId)->first();
    }

    /**
     * Busca provedor por slug dentro do tenant atual.
     *
     * @param string $slug
     * @param bool $withTrashed
     * @return Provider|null
     */
    public function findBySlug(string $slug, bool $withTrashed = false): ?Provider
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
    public function findByUserIdAndTenant(int $userId, int $tenantId): ?Provider
    {
        return $this->model->where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->with(['user', 'commonData', 'contact', 'address', 'businessData'])
            ->first();
    }

    /**
     * Verifica disponibilidade de email.
     */
    public function isEmailAvailable(string $email, int $excludeUserId, int $tenantId): bool
    {
        return !User::where('email', $email)
            ->where('tenant_id', $tenantId)
            ->where('id', '!=', $excludeUserId)
            ->exists();
    }

    /**
     * Busca Provider com relacionamentos específicos, bypassando o escopo de tenant.
     * Útil para operações administrativas globais.
     */
    public function findGlobalWithRelations(int $providerId, array $relations = []): ?Provider
    {
        return $this->model->newQuery()
            ->withoutGlobalScopes()
            ->with($relations)
            ->find($providerId);
    }

    /**
     * Busca Provider com relacionamentos específicos dentro do escopo do tenant.
     */
    public function findWithRelations(int $providerId, array $relations = []): ?Provider
    {
        return $this->model->with($relations)->find($providerId);
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

    /**
     * Busca provedores para a área administrativa com filtros.
     * Bypassa o escopo de tenant se necessário.
     */
    public function getAdminPaginated(array $filters = [], int $perPage = 25): \Illuminate\Pagination\LengthAwarePaginator
    {
        $query = $this->model->newQuery()
            ->withoutGlobalScopes() // Bypassa o escopo de tenant para administradores
            ->with(['tenant', 'user', 'commonData', 'address', 'planSubscriptions.plan'])
            ->withCount(['customers', 'budgets', 'services', 'invoices']);

        if (isset($filters['search']) && !empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                // Busca no usuário associado
                $q->whereHas('user', function ($uq) use ($search) {
                    $uq->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                })
                    // Busca nos dados comuns
                    ->orWhereHas('commonData', function ($cq) use ($search) {
                        $cq->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('company_name', 'like', "%{$search}%")
                            ->orWhere('cpf', 'like', "%{$search}%")
                            ->orWhere('cnpj', 'like', "%{$search}%");
                    })
                    // Busca nos contatos
                    ->orWhereHas('contact', function ($conq) use ($search) {
                        $conq->where('email_personal', 'like', "%{$search}%")
                            ->orWhere('phone_personal', 'like', "%{$search}%")
                            ->orWhere('email_business', 'like', "%{$search}%")
                            ->orWhere('phone_business', 'like', "%{$search}%");
                    });
            });
        }

        if (isset($filters['status']) && $filters['status'] !== 'all') {
            $query->whereHas('user', function ($q) use ($filters) {
                $q->where('is_active', $filters['status'] === 'active');
            });
        }

        if (isset($filters['tenant_id']) && !empty($filters['tenant_id'])) {
            $query->where('tenant_id', $filters['tenant_id']);
        }

        if (isset($filters['plan_id']) && !empty($filters['plan_id'])) {
            $query->where('plan_id', $filters['plan_id']);
        }

        $sortBy = $filters['sort_by'] ?? 'name';
        $sortOrder = $filters['sort_order'] ?? 'asc';

        return $query->orderBy($sortBy, $sortOrder)->paginate($perPage);
    }

    /**
     * Obtém estatísticas globais de provedores para o admin.
     */
    public function getGlobalStatistics(): array
    {
        return [
            'total' => $this->model->withoutGlobalScopes()->count(),
            'active' => $this->model->withoutGlobalScopes()->where('is_active', true)->count(),
            'inactive' => $this->model->withoutGlobalScopes()->where('is_active', false)->count(),
            'with_customers' => $this->model->withoutGlobalScopes()->has('customers')->count(),
            'with_budgets' => $this->model->withoutGlobalScopes()->has('budgets')->count(),
            'with_services' => $this->model->withoutGlobalScopes()->has('services')->count(),
            'with_invoices' => $this->model->withoutGlobalScopes()->has('invoices')->count(),
            'by_tenant' => $this->model->withoutGlobalScopes()
                ->select('tenant_id', \Illuminate\Support\Facades\DB::raw('count(*) as count'))
                ->groupBy('tenant_id')
                ->with('tenant:id,name')
                ->get(),
            'by_plan' => $this->model->withoutGlobalScopes()
                ->select('plan_id', \Illuminate\Support\Facades\DB::raw('count(*) as count'))
                ->groupBy('plan_id')
                ->with('plan:id,name')
                ->get(),
        ];
    }

    /**
     * Busca provedores ativos de um tenant com filtro de pesquisa.
     */
    public function getByTenantWithSearch(int $tenantId, ?string $search = null): array
    {
        $query = $this->model->newQuery()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->whereHas('user', function ($q) {
                $q->where('is_active', true);
            })
            ->with(['commonData', 'contact', 'user'])
            ->orderBy('id');

        if ($search) {
            $query->where(function ($q) use ($search) {
                // User
                $q->whereHas('user', function ($uq) use ($search) {
                    $uq->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                })
                    // CommonData
                    ->orWhereHas('commonData', function ($cq) use ($search) {
                        $cq->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('company_name', 'like', "%{$search}%")
                            ->orWhere('cpf', 'like', "%{$search}%")
                            ->orWhere('cnpj', 'like', "%{$search}%");
                    })
                    // Contact
                    ->orWhereHas('contact', function ($conq) use ($search) {
                        $conq->where('email_personal', 'like', "%{$search}%")
                            ->orWhere('phone_personal', 'like', "%{$search}%")
                            ->orWhere('email_business', 'like', "%{$search}%")
                            ->orWhere('phone_business', 'like', "%{$search}%");
                    });
            });
        }

        return $query->get()->map(function ($provider) {
            $name = $provider->commonData?->company_name
                ?? ($provider->commonData ? "{$provider->commonData->first_name} {$provider->commonData->last_name}" : $provider->user?->name);

            $email = $provider->contact?->email_business
                ?? $provider->contact?->email_personal
                ?? $provider->user?->email;

            $phone = $provider->contact?->phone_business
                ?? $provider->contact?->phone_personal;

            $document = $provider->commonData?->cnpj ?? $provider->commonData?->cpf;

            return [
                'id' => $provider->id,
                'name' => trim($name),
                'email' => $email,
                'phone' => $phone,
                'document' => $document
            ];
        })->toArray();
    }
}
