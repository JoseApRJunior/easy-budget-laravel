<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Address;
use App\Models\BusinessData;
use App\Models\CommonData;
use App\Models\Contact;
use App\Models\Customer;
use App\Repositories\Abstracts\AbstractTenantRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Repositório para gerenciamento de clientes.
 *
 * Estende AbstractTenantRepository para operações tenant-aware
 * com isolamento automático de dados por empresa.
 */
class CustomerRepository extends AbstractTenantRepository
{
    use \App\Repositories\Traits\RepositoryFiltersTrait;

    /**
     * Define o Model a ser utilizado pelo Repositório.
     */
    protected function makeModel(): Model
    {
        return new Customer;
    }

    /**
     * Lista clientes ativos dentro do tenant atual.
     *
     * @param  array<string, string>|null  $orderBy
     * @return Collection<Customer>
     */
    public function listActive(?array $orderBy = null, ?int $limit = null): Collection
    {
        return $this->model->newQuery()
            ->where('status', 'active')
            ->when($orderBy, function ($q) use ($orderBy) {
                foreach ($orderBy as $field => $dir) {
                    $q->orderBy($field, $dir);
                }
            })
            ->when($limit, fn ($q) => $q->limit($limit))
            ->get();
    }

    /**
     * Conta clientes dentro do tenant atual com filtros opcionais.
     */
    public function countByFilters(array $filters = []): int
    {
        $query = $this->model->newQuery();
        $this->applyFilters($query, $filters);

        return $query->count();
    }

    /**
     * Verifica existência por critérios dentro do tenant atual.
     */
    public function existsByCriteria(array $criteria): bool
    {
        $query = $this->model->newQuery();
        $this->applyFilters($query, $criteria);

        return $query->exists();
    }

    /**
     * Remove múltiplos clientes por IDs dentro do tenant atual.
     */
    public function deleteManyByIds(array $ids): int
    {
        return $this->model->newQuery()->whereIn('id', $ids)->delete();
    }

    /**
     * Atualiza múltiplos registros por critérios dentro do tenant atual.
     */
    public function updateManyByCriteria(array $criteria, array $updates): int
    {
        $query = $this->model->newQuery();
        $this->applyFilters($query, $criteria);

        return $query->update($updates);
    }

    /**
     * Busca clientes por múltiplos critérios dentro do tenant atual.
     */
    public function findByCriteria(
        array $criteria,
        ?array $orderBy = null,
        ?int $limit = null,
        ?int $offset = null,
    ): Collection {
        $query = $this->model->newQuery();
        $this->applyFilters($query, $criteria);
        $this->applyOrderBy($query, $orderBy ?: ['created_at' => 'desc']);

        if ($limit) {
            $query->limit($limit);
        }
        if ($offset) {
            $query->offset($offset);
        }

        return $query->get();
    }

    public function findByIdAndTenantId(int $id): ?Customer
    {
        return $this->find($id);
    }

    /**
     * Lista clientes por filtros (compatibilidade com service).
     */
    public function listByFilters(
        array $filters = [],
        ?array $orderBy = null,
        ?int $limit = null,
        ?int $offset = null,
    ): Collection {
        return $this->findByCriteria($filters, $orderBy, $limit, $offset);
    }

    // ========================================
    // VALIDAÇÕES DE UNICIDADE (GRUPO 1.3)
    // ========================================

    /**
     * Verifica se email é único no tenant (exclui customer atual se especificado)
     */
    public function isEmailUnique(string $email, ?int $excludeCustomerId = null): bool
    {
        $query = Contact::query()
            ->where(function ($q) use ($email) {
                $q->where('email_personal', $email)
                    ->orWhere('email_business', $email);
            });

        if ($excludeCustomerId) {
            $query->where('customer_id', '!=', $excludeCustomerId);
        }

        return ! $query->exists();
    }

    /**
     * Verifica se CPF é único no tenant (exclui customer atual se especificado)
     */
    public function isCpfUnique(string $cpf, ?int $excludeCustomerId = null): bool
    {
        if (strlen($cpf) !== 11) {
            return false;
        }

        $query = CommonData::query()
            ->where('cpf', $cpf)
            ->whereNotNull('cpf');

        if ($excludeCustomerId) {
            $query->whereDoesntHave('customer', function ($q) use ($excludeCustomerId) {
                $q->where('id', $excludeCustomerId);
            });
        }

        return ! $query->exists();
    }

    /**
     * Verifica se CNPJ é único no tenant (exclui customer atual se especificado)
     */
    public function isCnpjUnique(string $cnpj, ?int $excludeCustomerId = null): bool
    {
        if (strlen($cnpj) !== 14) {
            return false;
        }

        $query = CommonData::query()
            ->where('cnpj', $cnpj)
            ->whereNotNull('cnpj');

        if ($excludeCustomerId) {
            $query->whereDoesntHave('customer', function ($q) use ($excludeCustomerId) {
                $q->where('id', $excludeCustomerId);
            });
        }

        return ! $query->exists();
    }

    // ========================================
    // FILTROS AVANÇADOS (GRUPO 1.2)
    // ========================================

    /**
     * {@inheritdoc}
     *
     * Implementação específica para clientes com filtros avançados.
     *
     * @param  array<string, mixed>  $filters  Filtros específicos:
     *                                         - search: termo de busca em nome, email, CPF/CNPJ, razão social
     *                                         - type: 'pessoa_fisica' ou 'pessoa_juridica'
     *                                         - status: status do cliente
     *                                         - area_of_activity_id: ID da área de atuação
     *                                         - profession_id: ID da profissão
     *                                         - per_page: número de itens por página
     *                                         - deleted: 'only' para mostrar apenas clientes deletados
     * @param  int  $perPage  Número padrão de itens por página (15)
     * @param  array<string>  $with  Relacionamentos para eager loading (padrão: ['commonData.areaOfActivity', 'commonData.profession', 'contact', 'address', 'businessData'])
     * @param  array<string, string>|null  $orderBy  Ordenação personalizada (padrão: ['created_at' => 'desc'])
     * @return LengthAwarePaginator Resultado paginado
     */
    /**
     * {@inheritdoc}
     *
     * Implementação específica para clientes com filtros avançados.
     *
     * @param  array<string, mixed>  $filters  Filtros específicos:
     *                                         - search: termo de busca em nome, email, CPF/CNPJ, razão social
     *                                         - type: 'pessoa_fisica' ou 'pessoa_juridica'
     *                                         - status: status do cliente
     *                                         - area_of_activity_id: ID da área de atuação
     *                                         - profession_id: ID da profissão
     *                                         - per_page: número de itens por página
     *                                         - deleted: 'only' para mostrar apenas clientes deletados
     * @param  int  $perPage  Número padrão de itens por página (15)
     * @param  array<string>  $with  Relacionamentos para eager loading (padrão: ['commonData.areaOfActivity', 'commonData.profession', 'contact', 'address', 'businessData'])
     * @param  array<string, string>|null  $orderBy  Ordenação personalizada (padrão: ['created_at' => 'desc'])
     * @return LengthAwarePaginator Resultado paginado
     */
    public function getPaginated(
        array $filters = [],
        int $perPage = 15,
        array $with = ['commonData', 'contact'],
        ?array $orderBy = null,
    ): LengthAwarePaginator {
        $query = $this->model->newQuery();

        if (! empty($with)) {
            $query->with($with);
        }

        // Aplicar filtros básicos do trait
        $this->applyFilters($query, $filters);

        // Aplicar filtro de soft delete
        $this->applySoftDeleteFilter($query, $filters);

        // Filtro por status (se não foi tratado pelo applyFilters)
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Busca avançada em relações (customizado para Customer)
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                if (is_numeric($search)) {
                    $q->where('id', (int) $search);
                }

                $q->orWhereHas('commonData', function ($cq) use ($search) {
                    $cq->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('company_name', 'like', "%{$search}%")
                        ->orWhere('cpf', 'like', "%{$search}%")
                        ->orWhere('cnpj', 'like', "%{$search}%");
                })->orWhereHas('contact', function ($cq) use ($search) {
                    $cq->where('email_personal', 'like', "%{$search}%")
                        ->orWhere('email_business', 'like', "%{$search}%")
                        ->orWhere('phone_personal', 'like', "%{$search}%")
                        ->orWhere('phone_business', 'like', "%{$search}%");
                });
            });
        }

        // Filtro por tipo
        if (! empty($filters['type'])) {
            $type = $filters['type'];
            $query->whereHas('commonData', function ($q) use ($type) {
                $q->where('type', $type);
            });
        }

        // Filtro por área de atuação
        if (! empty($filters['area_of_activity_id'])) {
            $areaId = $filters['area_of_activity_id'];
            $query->whereHas('commonData', function ($q) use ($areaId) {
                $q->where('area_of_activity_id', $areaId);
            });
        }

        // Filtro por CEP
        if (! empty($filters['cep'])) {
            $cepPrefix = substr(preg_replace('/[^0-9]/', '', $filters['cep']), 0, 5);
            $query->whereHas('address', function ($q) use ($cepPrefix) {
                $q->where('cep', 'like', $cepPrefix.'%');
            });
        }

        $this->applyOrderBy($query, $orderBy ?: ['created_at' => 'desc']);

        return $query->paginate($this->getEffectivePerPage($filters, $perPage));
    }

    /**
     * Busca clientes próximos por CEP (prefixo de 5 dígitos).
     */
    public function findNearbyByCep(string $cep): Collection
    {
        $cepPrefix = substr(preg_replace('/[^0-9]/', '', $cep), 0, 5);

        return $this->model->newQuery()
            ->whereHas('address', function ($query) use ($cepPrefix) {
                $query->where('cep', 'like', $cepPrefix.'%');
            })
            ->with(['commonData', 'contact', 'address'])
            ->get();
    }

    /**
     * Busca simplificada para autocompletar
     */
    public function findBySearch(string $search, int $limit = 10): Collection
    {
        return $this->model->newQuery()
            ->where('status', 'active')
            ->where(function ($q) use ($search) {
                $q->whereHas('commonData', function ($cq) use ($search) {
                    $cq->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('company_name', 'like', "%{$search}%")
                        ->orWhere('cpf', 'like', "%{$search}%")
                        ->orWhere('cnpj', 'like', "%{$search}%");
                })->orWhereHas('contact', function ($cq) use ($search) {
                    $cq->where('email_personal', 'like', "%{$search}%")
                        ->orWhere('email_business', 'like', "%{$search}%");
                });
            })
            ->with(['commonData', 'contact'])
            ->limit($limit)
            ->get();
    }

    /**
     * Busca customer com dados completos relacionados
     */
    public function findWithCompleteData(int $id): ?Customer
    {
        return $this->model->newQuery()
            ->where('id', $id)
            ->with([
                'commonData' => function ($q) {
                    $q->with(['areaOfActivity', 'profession']);
                },
                'contact',
                'address',
                'businessData',
                'budgets', // REMOVIDO: 'services' - causava ambiguidade
            ])
            ->first();
    }

    /**
     * Verifica se o cliente possui orçamentos cadastrados.
     */
    public function hasBudgets(int $customerId): bool
    {
        return $this->model->newQuery()
            ->where('id', $customerId)
            ->whereHas('budgets')
            ->exists();
    }

    /**
     * Atualiza o status do cliente.
     */
    public function updateStatus(int $id, string $status): bool
    {
        return $this->update($id, ['status' => $status]);
    }

    // ========================================
    // OPERAÇÕES MULTI-TABELA (GRUPO 1.2)
    // ========================================

    /**
     * Cria customer com todas as relações (estrutura de 5 tabelas) a partir de um DTO.
     */
    public function createFromDTO(\App\DTOs\Customer\CustomerDTO $dto): Customer
    {
        $data = $dto->toArrayWithoutNulls();
        $data['tenant_id'] = $this->getTenantId();

        return $this->createWithRelations($data);
    }

    /**
     * Atualiza customer com todas as relações a partir de um DTO.
     */
    public function updateFromDTO(Customer $customer, \App\DTOs\Customer\CustomerDTO $dto): bool
    {
        $data = $dto->toArrayWithoutNulls();

        return $this->updateWithRelations($customer, $data);
    }

    /**
     * Obtém estatísticas para o dashboard de clientes.
     */
    public function getDashboardStats(): array
    {
        $baseQuery = $this->model->newQuery();

        return [
            'total_customers' => (clone $baseQuery)->count(),
            'active_customers' => (clone $baseQuery)->where('status', 'active')->count(),
            'inactive_customers' => (clone $baseQuery)->where('status', 'inactive')->count(),
            'recent_customers' => (clone $baseQuery)->latest()
                ->limit(5)
                ->with(['commonData', 'contact'])
                ->get(),
        ];
    }

    /**
     * Restaura um cliente deletado.
     */
    public function restore(int $id): ?Customer
    {
        $customer = $this->model->onlyTrashed()
            ->where('id', $id)
            ->first();

        if ($customer) {
            $customer->restore();

            return $customer;
        }

        return null;
    }

    /**
     * Cria customer com todas as relações (estrutura de 5 tabelas)
     */
    public function createWithRelations(array $data): Customer
    {
        return DB::transaction(function () use ($data) {
            $tenantId = $data['tenant_id'];

            // 1. Criar CommonData
            $commonData = CommonData::create([
                'tenant_id' => $tenantId,
                'customer_id' => null, // Será atualizado após criar customer
                'type' => $data['type'] ?? 'individual',
                'first_name' => $data['first_name'] ?? null,
                'last_name' => $data['last_name'] ?? null,
                'birth_date' => $data['birth_date'] ?? null,
                'cpf' => $data['cpf'] ?? null,
                'cnpj' => $data['cnpj'] ?? null,
                'company_name' => $data['company_name'] ?? null,
                'description' => $data['description'] ?? null,
                'area_of_activity_id' => $data['area_of_activity_id'] ?? null,
                'profession_id' => $data['profession_id'] ?? null,
            ]);

            // 2. Criar Contact
            $contact = Contact::create([
                'tenant_id' => $tenantId,
                'customer_id' => null, // Será atualizado após criar customer
                'email_personal' => $data['email'] ?? null,
                'phone_personal' => $data['phone'] ?? null,
                'email_business' => $data['email_business'] ?? null,
                'phone_business' => $data['phone_business'] ?? null,
                'website' => $data['website'] ?? null,
            ]);

            // 3. Criar Address
            $address = Address::create([
                'tenant_id' => $tenantId,
                'customer_id' => null, // Será atualizado após criar customer
                'address' => $data['address'] ?? null,
                'address_number' => $data['address_number'] ?? null,
                'neighborhood' => $data['neighborhood'] ?? null,
                'city' => $data['city'] ?? null,
                'state' => $data['state'] ?? null,
                'cep' => $data['cep'] ?? null,
            ]);

            // 4. Criar Customer (tabela principal)
            $customer = Customer::create([
                'tenant_id' => $tenantId,
                'status' => $data['status'] ?? 'active',
            ]);

            // 5. Atualizar customer_id nas tabelas relacionadas
            $commonData->update(['customer_id' => $customer->id]);
            $contact->update(['customer_id' => $customer->id]);
            $address->update(['customer_id' => $customer->id]);

            // 6. Criar BusinessData se for pessoa jurídica
            if (($data['type'] ?? 'individual') === 'company' || ! empty($data['cnpj'])) {
                BusinessData::create([
                    'tenant_id' => $tenantId,
                    'customer_id' => $customer->id,
                    'fantasy_name' => $data['fantasy_name'] ?? null,
                    'state_registration' => $data['state_registration'] ?? null,
                    'municipal_registration' => $data['municipal_registration'] ?? null,
                    'founding_date' => $data['founding_date'] ?? null,
                    'industry' => $data['industry'] ?? null,
                    'company_size' => $data['company_size'] ?? null,
                    'notes' => $data['business_notes'] ?? null,
                ]);
            }

            return $customer->fresh(['commonData', 'contact', 'address', 'businessData']);
        });
    }

    /**
     * Atualiza customer com todas as relações
     */
    public function updateWithRelations(Customer $customer, array $data): bool
    {
        return DB::transaction(function () use ($customer, $data) {
            // Atualizar CommonData
            if ($customer->commonData) {
                $customer->commonData->update([
                    'type' => $data['type'] ?? $customer->commonData->type,
                    'first_name' => $data['first_name'] ?? $customer->commonData->first_name,
                    'last_name' => $data['last_name'] ?? $customer->commonData->last_name,
                    'birth_date' => $data['birth_date'] ?? $customer->commonData->birth_date,
                    'cpf' => $data['cpf'] ?? $customer->commonData->cpf,
                    'cnpj' => $data['cnpj'] ?? $customer->commonData->cnpj,
                    'company_name' => $data['company_name'] ?? $customer->commonData->company_name,
                    'description' => $data['description'] ?? $customer->commonData->description,
                    'area_of_activity_id' => $data['area_of_activity_id'] ?? $customer->commonData->area_of_activity_id,
                    'profession_id' => $data['profession_id'] ?? $customer->commonData->profession_id,
                ]);
            }

            // Atualizar Contact
            if ($customer->contact) {
                $customer->contact->update([
                    'email_personal' => $data['email_personal'] ?? ($data['email'] ?? $customer->contact->email_personal),
                    'phone_personal' => $data['phone_personal'] ?? ($data['phone'] ?? $customer->contact->phone_personal),
                    'email_business' => $data['email_business'] ?? $customer->contact->email_business,
                    'phone_business' => $data['phone_business'] ?? $customer->contact->phone_business,
                    'website' => $data['website'] ?? $customer->contact->website,
                ]);
            }

            // Atualizar Address
            if ($customer->address) {
                $customer->address->update([
                    'address' => $data['address'] ?? $customer->address->address,
                    'address_number' => $data['address_number'] ?? $customer->address->address_number,
                    'neighborhood' => $data['neighborhood'] ?? $customer->address->neighborhood,
                    'city' => $data['city'] ?? $customer->address->city,
                    'state' => $data['state'] ?? $customer->address->state,
                    'cep' => $data['cep'] ?? $customer->address->cep,
                ]);
            }

            // Atualizar ou criar BusinessData
            if (($data['type'] ?? 'individual') === 'company' || ! empty($data['cnpj'])) {
                if ($customer->businessData) {
                    $customer->businessData->update([
                        'fantasy_name' => $data['fantasy_name'] ?? $customer->businessData->fantasy_name,
                        'state_registration' => $data['state_registration'] ?? $customer->businessData->state_registration,
                        'municipal_registration' => $data['municipal_registration'] ?? $customer->businessData->municipal_registration,
                        'founding_date' => $data['founding_date'] ?? $customer->businessData->founding_date,
                        'industry' => $data['industry'] ?? $customer->businessData->industry,
                        'company_size' => $data['company_size'] ?? $customer->businessData->company_size,
                        'notes' => $data['business_notes'] ?? ($data['notes'] ?? $customer->businessData->notes),
                    ]);
                } else {
                    BusinessData::create([
                        'tenant_id' => $customer->tenant_id,
                        'customer_id' => $customer->id,
                        'fantasy_name' => $data['fantasy_name'] ?? null,
                        'state_registration' => $data['state_registration'] ?? null,
                        'municipal_registration' => $data['municipal_registration'] ?? null,
                        'founding_date' => $data['founding_date'] ?? null,
                        'industry' => $data['industry'] ?? null,
                        'company_size' => $data['company_size'] ?? null,
                        'notes' => $data['business_notes'] ?? ($data['notes'] ?? null),
                    ]);
                }
            }

            // Atualizar Customer (status)
            $customer->update([
                'status' => $data['status'] ?? $customer->status,
            ]);

            return true;
        });
    }

    /**
     * Verifica se customer pode ser deletado (verifica relacionamentos)
     */
    public function canDelete(int $id): array
    {
        $customer = $this->model->newQuery()
            ->where('id', $id)
            ->whereNull('deleted_at')
            ->addSelect([
                'budgets_count' => function ($query) {
                    $query->selectRaw('count(*)')
                        ->from('budgets')
                        ->whereColumn('budgets.customer_id', 'customers.id');
                },
                'services_count' => function ($query) {
                    $query->selectRaw('count(*)')
                        ->from('services')
                        ->join('budgets', 'services.budget_id', '=', 'budgets.id')
                        ->whereColumn('budgets.customer_id', 'customers.id');
                },
                'invoices_count' => function ($query) {
                    $query->selectRaw('count(*)')
                        ->from('invoices')
                        ->whereColumn('invoices.customer_id', 'customers.id')
                        ->whereNull('invoices.deleted_at');
                },
            ])
            ->first();

        if (! $customer) {
            return ['canDelete' => false, 'reason' => 'Customer não encontrado'];
        }

        $budgetsCount = (int) $customer->budgets_count;
        $servicesCount = (int) $customer->services_count;
        $invoicesCount = (int) $customer->invoices_count;
        $totalRelations = $budgetsCount + $servicesCount + $invoicesCount;

        $reasons = [];
        if ($budgetsCount > 0) {
            $reasons[] = "{$budgetsCount} orçamento(s)";
        }
        if ($servicesCount > 0) {
            $reasons[] = "{$servicesCount} serviço(s)";
        }
        if ($invoicesCount > 0) {
            $reasons[] = "{$invoicesCount} fatura(s)";
        }

        return [
            'canDelete' => $totalRelations === 0,
            'reason' => $totalRelations > 0
                ? 'Cliente não pode ser excluído pois possui: '.implode(', ', $reasons)
                : null,
            'budgetsCount' => $budgetsCount,
            'servicesCount' => $servicesCount,
            'invoicesCount' => $invoicesCount,
            'totalRelationsCount' => $totalRelations,
        ];
    }

    /**
     * Busca por email (qualquer campo de email)
     */
    public function findByEmail(string $email): ?Customer
    {
        return $this->model->newQuery()
            ->whereHas('contact', function ($q) use ($email) {
                $q->where('email_personal', $email)
                    ->orWhere('email_business', $email);
            })
            ->first();
    }

    /**
     * Busca por CPF
     */
    public function findByCpf(string $cpf): ?Customer
    {
        return $this->model->newQuery()
            ->whereHas('commonData', function ($q) use ($cpf) {
                $q->where('cpf', $cpf);
            })
            ->first();
    }

    /**
     * Busca por CNPJ
     */
    public function findByCnpj(string $cnpj): ?Customer
    {
        return $this->model->newQuery()
            ->whereHas('commonData', function ($q) use ($cnpj) {
                $q->where('cnpj', $cnpj);
            })
            ->first();
    }

    /**
     * Verifica relacionamentos (método aliases para canDelete)
     */
    public function checkRelationships(int $id): array
    {
        $canDelete = $this->canDelete($id);

        return [
            'hasRelationships' => ! $canDelete['canDelete'],
            'budgets' => $canDelete['budgetsCount'] ?? 0,
            'services' => $canDelete['servicesCount'] ?? 0,
            'invoices' => $canDelete['invoicesCount'] ?? 0,
            'interactions' => $canDelete['interactionsCount'] ?? 0,
            'totalRelations' => $canDelete['totalRelationsCount'] ?? 0,
            'reason' => $canDelete['reason'] ?? null,
        ];
    }

    /**
     * Busca customer com registros trashed
     */
    public function findWithTrashed(int $id): ?Customer
    {
        return $this->model->newQuery()->withTrashed()
            ->where('id', $id)
            ->with([
                'commonData' => function ($q) {
                    $q->withTrashed()->with(['areaOfActivity', 'profession']);
                },
                'contact' => function ($q) {
                    $q->withTrashed();
                },
                'address' => function ($q) {
                    $q->withTrashed();
                },
                'businessData' => function ($q) {
                    $q->withTrashed();
                },
            ])
            ->first();
    }

    // Métodos removidos por redundância com AbstractTenantRepository
    // countByTenantId, countByStatus, getRecentByTenantId, searchForAutocomplete

    /**
     * Busca clientes ativos com estatísticas (counts de orçamentos e faturas).
     */
    public function getActiveWithStats(int $limit = 50): Collection
    {
        return $this->model->newQuery()
            ->where('status', 'active')
            ->withCount(['budgets', 'invoices'])
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Obtém o ID do tenant atual.
     */
    protected function getTenantId(): int
    {
        return auth()->user()->tenant_id; // @phpstan-ignore-line
    }
}
