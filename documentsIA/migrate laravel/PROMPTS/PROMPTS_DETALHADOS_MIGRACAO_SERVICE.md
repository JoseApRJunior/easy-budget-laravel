# 🎯 Prompts Detalhados - Migração Service Controller (Ordem Correta)

## 📋 CONTEXTO

**Base:** Análise completa em `ANALISE_COMPARATIVA_SERVICE_CONTROLLER.md`
**Status:** 25% implementado (3/13 métodos)
**Objetivo:** Quebrar em tarefas menores e específicas
**Ordem:** Sequência lógica seguindo dependências técnicas

---

# 🎯 GRUPO 1: REPOSITORY (Base de Dados) - **PRIMEIRO**

## 🎯 PROMPT 1.1: Implementar getFiltered() - Busca com Filtros

Implemente APENAS o método getFiltered() no ServiceRepository:

TAREFA ESPECÍFICA:

-  Filtros: Status, categoria, período, busca
-  Paginação: Automática
-  Eager loading: Relacionamentos básicos
-  Tenant scoping: Automático

IMPLEMENTAÇÃO:

```php
public function getFiltered(array $filters = [], ?array $orderBy = null, ?int $limit = null): Collection
{
    $query = $this->model->newQuery();

    // Aplicar filtros
    if (!empty($filters['status'])) {
        $query->where('status', $filters['status']);
    }

    if (!empty($filters['category_id'])) {
        $query->where('category_id', $filters['category_id']);
    }

    if (!empty($filters['date_from'])) {
        $query->whereDate('created_at', '>=', $filters['date_from']);
    }

    if (!empty($filters['date_to'])) {
        $query->whereDate('created_at', '<=', $filters['date_to']);
    }

    if (!empty($filters['search'])) {
        $query->where(function ($q) use ($filters) {
            $q->where('code', 'like', '%' . $filters['search'] . '%')
              ->orWhere('description', 'like', '%' . $filters['search'] . '%');
        });
    }

    // Eager loading padrão
    $query->with(['category', 'budget.customer', 'serviceStatus']);

    // Ordenação
    if ($orderBy) {
        foreach ($orderBy as $field => $direction) {
            $query->orderBy($field, $direction);
        }
    } else {
        $query->orderBy('created_at', 'desc');
    }

    // Limite
    if ($limit) {
        $query->limit($limit);
    }

    return $query->get();
}
```

ARQUIVOS:

-  app/Repositories/ServiceRepository.php (método getFiltered)

CRITÉRIO DE SUCESSO: Repository com filtros e eager loading

---

## 🎯 PROMPT 1.2: Implementar findByCode() - Repository

Implemente APENAS o método findByCode() no ServiceRepository:

TAREFA ESPECÍFICA:

-  Busca: Por código (string)
-  Eager loading: Relacionamentos opcionais
-  Tenant scoping: Automático via AbstractTenantRepository

IMPLEMENTAÇÃO:

```php
public function findByCode(string $code, array $with = []): ?Model
{
    $query = $this->model->where('code', $code);

    if (!empty($with)) {
        $query->with($with);
    }

    return $query->first();
}

public function findByCodeWithTenant(string $code, array $with = []): ?Model
{
    return $this->findByCode($code, $with);
}
```

ARQUIVOS:

-  app/Repositories/ServiceRepository.php (métodos findByCode, findByCodeWithTenant)

CRITÉRIO DE SUCESSO: Repository com busca por código

---

## 🎯 PROMPT 1.3: Implementar countByStatus() - Métricas

Implemente APENAS o método countByStatus() no ServiceRepository:

TAREFA ESPECÍFICA:

-  Contagem: Serviços por status dentro do tenant
-  Return: Array com status como chave e count como valor
-  Performance: Query otimizada

IMPLEMENTAÇÃO:

```php
public function countByStatus(): array
{
    return $this->model
        ->selectRaw('status, COUNT(*) as count')
        ->groupBy('status')
        ->pluck('count', 'status')
        ->toArray();
}

public function countActive(): int
{
    return $this->countByTenant(['status' => 'active']);
}

public function countByCategory(int $categoryId): int
{
    return $this->countByTenant(['category_id' => $categoryId]);
}
```

ARQUIVOS:

-  app/Repositories/ServiceRepository.php (métodos countByStatus, countActive, countByCategory)

CRITÉRIO DE SUCESSO: Repository com métricas de serviços

---

# 🎯 GRUPO 2: FORM REQUESTS (Validação) - **SEGUNDO**

## 🎯 PROMPT 2.1: Criar ServiceStoreRequest - Validação de Criação

Crie APENAS o ServiceStoreRequest:

TAREFA ESPECÍFICA:

-  Campos: budget_code, category_id, status, description, due_date, items
-  Validação: Relacionamentos (budget_id, category_id) existem
-  Items: Array de produtos com quantity, unit_value
-  Unicidade: Código de serviço único por orçamento
-  Status: Apenas status válidos

IMPLEMENTAÇÃO:

```php
class ServiceStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'budget_code' => [
                'required',
                'string',
                'exists:budgets,code'
            ],
            'category_id' => [
                'required',
                'integer',
                'exists:categories,id'
            ],
            'status' => [
                'required',
                'string',
                'in:' . implode(',', array_map(fn($case) => $case->value, ServiceStatus::cases()))
            ],
            'description' => 'nullable|string|max:1000',
            'due_date' => 'nullable|date|after:today',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_value' => 'required|numeric|min:0.01'
        ];
    }

    public function messages(): array
    {
        return [
            'budget_code.required' => 'Código do orçamento é obrigatório',
            'budget_code.exists' => 'Orçamento não encontrado',
            'category_id.required' => 'Categoria é obrigatória',
            'category_id.exists' => 'Categoria não encontrada',
            'status.required' => 'Status é obrigatório',
            'status.in' => 'Status inválido selecionado',
            'due_date.after' => 'Data de vencimento deve ser posterior a hoje',
            'items.required' => 'Itens do serviço são obrigatórios',
            'items.min' => 'Deve ter pelo menos 1 item',
            'items.*.product_id.required' => 'Produto é obrigatório em cada item',
            'items.*.product_id.exists' => 'Produto não encontrado',
            'items.*.quantity.min' => 'Quantidade deve ser maior que zero',
            'items.*.unit_value.min' => 'Valor unitário deve ser maior que zero'
        ];
    }

    public function validated(): array
    {
        $data = parent::validated();

        // Buscar budget_id pelo código
        $budget = Budget::where('code', $data['budget_code'])->first();
        $data['budget_id'] = $budget->id;
        unset($data['budget_code']);

        return $data;
    }
}
```

ARQUIVOS:

-  app/Http/Requests/ServiceStoreRequest.php (criar)
-  app/Models/Budget.php (relacionamento)
-  app/Models/Category.php (relacionamento)

CRITÉRIO DE SUCESSO: Validação robusta com mensagens em português

---

## 🎯 PROMPT 2.2: Criar ServiceUpdateRequest - Validação de Edição

Crie APENAS o ServiceUpdateRequest:

TAREFA ESPECÍFICA:

-  Campos: Sem budget_code (não pode alterar orçamento)
-  Itens: Gerenciar itens existentes (update/delete/create)
-  Status: Apenas status editáveis
-  Due date: Validação de data futura

IMPLEMENTAÇÃO:

```php
class ServiceUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'category_id' => [
                'required',
                'integer',
                'exists:categories,id'
            ],
            'status' => [
                'required',
                'string',
                'in:' . implode(',', array_map(fn($case) => $case->value, ServiceStatus::cases()))
            ],
            'description' => 'nullable|string|max:1000',
            'due_date' => 'nullable|date|after:today',
            'items' => 'required|array|min:1',
            'items.*.id' => 'nullable|integer|exists:service_items,id',
            'items.*.product_id' => 'required_without:items.*.id|integer|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_value' => 'required|numeric|min:0.01',
            'items.*.action' => 'nullable|in:create,update,delete'
        ];
    }

    public function messages(): array
    {
        return [
            'category_id.required' => 'Categoria é obrigatória',
            'category_id.exists' => 'Categoria não encontrada',
            'status.required' => 'Status é obrigatório',
            'status.in' => 'Status inválido selecionado',
            'due_date.after' => 'Data de vencimento deve ser posterior a hoje',
            'items.required' => 'Itens do serviço são obrigatórios',
            'items.min' => 'Deve ter pelo menos 1 item',
            'items.*.product_id.required' => 'Produto é obrigatório',
            'items.*.product_id.exists' => 'Produto não encontrado',
            'items.*.quantity.min' => 'Quantidade deve ser maior que zero',
            'items.*.unit_value.min' => 'Valor unitário deve ser maior que zero',
            'items.*.action.in' => 'Ação inválida para item'
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Validar que pelo menos um item está sendo criado/atualizado (não apenas deletado)
            $items = $this->items;
            $hasValidItems = false;

            foreach ($items as $item) {
                if (($item['action'] ?? 'create') !== 'delete') {
                    $hasValidItems = true;
                    break;
                }
            }

            if (!$hasValidItems) {
                $validator->errors()->add('items', 'Deve ter pelo menos 1 item ativo');
            }
        });
    }
}
```

ARQUIVOS:

-  app/Http/Requests/ServiceUpdateRequest.php (criar)
-  app/Models/ServiceItem.php (relacionamento)

CRITÉRIO DE SUCESSO: Validação para edição com gerenciamento de itens

---

# 🎯 GRUPO 3: SERVICES (Lógica de Negócio) - **TERCEIRO**

## 🎯 PROMPT 3.1: Implementar findByCode() - Service

Implemente APENAS o método findByCode() no ServiceService:

TAREFA ESPECÍFICA:

-  Busca: Por código (string) não por ID
-  Tenant scoping: Automático via TenantScoped
-  Eager loading: Relacionamentos opcionais
-  Error handling: Service não encontrado

IMPLEMENTAÇÃO:

```php
public function findByCode(string $code, array $with = []): ServiceResult
{
    try {
        $query = Service::where('code', $code);

        if (!empty($with)) {
            $query->with($with);
        }

        $service = $query->first();

        if (!$service) {
            return $this->error(
                OperationStatus::NOT_FOUND,
                "Serviço com código {$code} não encontrado"
            );
        }

        return $this->success($service, 'Serviço encontrado');

    } catch (Exception $e) {
        return $this->error(
            OperationStatus::ERROR,
            'Erro ao buscar serviço',
            null,
            $e
        );
    }
}
```

ARQUIVOS:

-  app/Services/Domain/ServiceService.php (método findByCode)

CRITÉRIO DE SUCESSO: Busca por código funcionando com eager loading opcional

---

## 🎯 PROMPT 3.2: Implementar getFilteredServices() - Busca com Filtros

Implemente APENAS o método getFilteredServices() no ServiceService:

TAREFA ESPECÍFICA:

-  Filtros: Status, categoria, período, busca por código
-  Paginação: 15 registros por página
-  Ordenação: Por data de criação (desc)
-  Eager loading: Relacionamentos básicos

IMPLEMENTAÇÃO:

```php
public function getFilteredServices(array $filters = [], array $with = []): ServiceResult
{
    try {
        $query = Service::query();

        // Filtros
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('code', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('description', 'like', '%' . $filters['search'] . '%');
            });
        }

        // Eager loading
        $withDefaults = ['category', 'budget.customer', 'serviceStatus'];
        $with = array_unique(array_merge($withDefaults, $with));
        $query->with($with);

        // Ordenação
        $query->orderBy('created_at', 'desc');

        // Paginação
        $services = $query->paginate(15);

        return $this->success($services, 'Serviços filtrados');

    } catch (Exception $e) {
        return $this->error(
            OperationStatus::ERROR,
            'Erro ao filtrar serviços',
            null,
            $e
        );
    }
}
```

ARQUIVOS:

-  app/Services/Domain/ServiceService.php (método getFilteredServices)

CRITÉRIO DE SUCESSO: Filtros funcionais com paginação

---

## 🎯 PROMPT 3.3: Implementar createService() - Criar Serviço

Implemente APENAS o método createService() no ServiceService:

TAREFA ESPECÍFICA:

-  Geração: Código único {BUDGET_CODE}-S{SEQUENCIAL}
-  Transaction: DB::transaction para atomicidade
-  Itens: Criar ServiceItems relacionados
-  Total: Calcular total do serviço e orçamento
-  Auditoria: Registrar criação

IMPLEMENTAÇÃO:

```php
public function createService(array $data): ServiceResult
{
    try {
        return DB::transaction(function () use ($data) {
            // Buscar orçamento
            $budget = Budget::where('code', $data['budget_code'])->first();
            if (!$budget) {
                return $this->error(
                    OperationStatus::NOT_FOUND,
                    'Orçamento não encontrado'
                );
            }

            // Gerar código único
            $serviceCode = $this->generateUniqueServiceCode($budget->code);

            // Criar serviço
            $service = Service::create([
                'tenant_id' => tenant()->id,
                'budget_id' => $budget->id,
                'code' => $serviceCode,
                'status' => $data['status'] ?? ServiceStatusEnum::SCHEDULED->value,
                'description' => $data['description'] ?? null,
                'due_date' => $data['due_date'] ?? null
            ]);

            // Criar itens do serviço
            if (!empty($data['items'])) {
                $this->createServiceItems($service, $data['items']);
            }

            // Atualizar total do orçamento
            $this->updateBudgetTotal($budget);

            return $this->success($service->load([
                'budget',
                'serviceItems.product',
                'serviceStatus'
            ]), 'Serviço criado com sucesso');

        });

    } catch (Exception $e) {
        return $this->error(
            OperationStatus::ERROR,
            'Erro ao criar serviço',
            null,
            $e
        );
    }
}

private function generateUniqueServiceCode(string $budgetCode): string
{
    $lastService = Service::where('budget_id', Budget::where('code', $budgetCode)->value('id'))
        ->orderBy('code', 'desc')
        ->first();

    $sequential = 1;
    if ($lastService && preg_match('/-S(\d{3})$/', $lastService->code, $matches)) {
        $sequential = (int) $matches[1] + 1;
    }

    return "{$budgetCode}-S" . str_pad($sequential, 3, '0', STR_PAD_LEFT);
}
```

ARQUIVOS:

-  app/Services/Domain/ServiceService.php (métodos createService, generateUniqueServiceCode)
-  app/Models/Service.php (relacionamentos)
-  app/Models/Budget.php (método updateTotal)

CRITÉRIO DE SUCESSO: Serviço criado com código único e itens relacionados

---

## 🎯 PROMPT 3.4: Implementar createServiceItems() - Criar Itens

Implemente APENAS o método createServiceItems() no ServiceService:

TAREFA ESPECÍFICA:

-  Validação: Produtos existem e estão ativos
-  Cálculo: Total dos itens
-  Transaction: Atomicidade com criação do serviço
-  Relacionamento: Vincular ao serviço

IMPLEMENTAÇÃO:

```php
private function createServiceItems(Service $service, array $items): void
{
    foreach ($items as $itemData) {
        // Validar produto
        $product = Product::where('id', $itemData['product_id'])
            ->where('active', true)
            ->first();

        if (!$product) {
            throw new Exception("Produto ID {$itemData['product_id']} não encontrado ou inativo");
        }

        // Calcular total do item
        $quantity = (float) $itemData['quantity'];
        $unitValue = (float) $itemData['unit_value'];
        $total = $quantity * $unitValue;

        // Criar item
        ServiceItem::create([
            'tenant_id' => $service->tenant_id,
            'service_id' => $service->id,
            'product_id' => $product->id,
            'unit_value' => $unitValue,
            'quantity' => $quantity,
            'total' => $total
        ]);
    }

    // Atualizar total do serviço
    $this->updateServiceTotal($service);
}

private function updateServiceTotal(Service $service): void
{
    $total = $service->serviceItems()->sum('total');
    $service->update(['total' => $total]);
}
```

ARQUIVOS:

-  app/Services/Domain/ServiceService.php (métodos createServiceItems, updateServiceTotal)
-  app/Models/ServiceItem.php (relacionamentos)

CRITÉRIO DE SUCESSO: Itens criados com validação e cálculo correto de totais

---

## 🎯 PROMPT 3.5: Implementar updateServiceByCode() - Atualizar por Código

Implemente APENAS o método updateServiceByCode() no ServiceService:

TAREFA ESPECÍFICA:

-  Busca: Por código + validação de existência
-  Validação: Status editável
-  Itens: Gerenciar itens (delete/update/create)
-  Transaction: Atomicidade completa

IMPLEMENTAÇÃO:

```php
public function updateServiceByCode(string $code, array $data): ServiceResult
{
    try {
        return DB::transaction(function () use ($code, $data) {
            $service = Service::where('code', $code)->first();

            if (!$service) {
                return $this->error(
                    OperationStatus::NOT_FOUND,
                    "Serviço {$code} não encontrado"
                );
            }

            // Verificar se pode editar
            if (!$service->status->canEdit()) {
                return $this->error(
                    OperationStatus::VALIDATION_ERROR,
                    "Serviço não pode ser editado no status {$service->status->value}"
                );
            }

            // Atualizar serviço
            $service->update([
                'description' => $data['description'] ?? $service->description,
                'due_date' => $data['due_date'] ?? $service->due_date,
                'status' => $data['status'] ?? $service->status
            ]);

            // Gerenciar itens se fornecidos
            if (isset($data['items'])) {
                $this->updateServiceItems($service, $data['items']);
            }

            // Atualizar total do orçamento
            $this->updateBudgetTotal($service->budget);

            return $this->success($service->fresh([
                'serviceItems.product',
                'serviceStatus'
            ]), 'Serviço atualizado');

        });

    } catch (Exception $e) {
        return $this->error(
            OperationStatus::ERROR,
            'Erro ao atualizar serviço',
            null,
            $e
        );
    }
}
```

ARQUIVOS:

-  app/Services/Domain/ServiceService.php (método updateServiceByCode)
-  app/Enums/ServiceStatusEnum.php (método canEdit)

CRITÉRIO DE SUCESSO: Serviço atualizado com gerenciamento de itens

---

## 🎯 PROMPT 3.6: Implementar changeStatus() - Mudança de Status

Implemente APENAS o método changeStatus() no ServiceService:

TAREFA ESPECÍFICA:

-  Validação: Transições permitidas via ServiceStatusEnum
-  Cascata: Atualizar orçamento relacionado se necessário
-  Auditoria: Registrar mudança
-  Transaction: Atomicidade

IMPLEMENTAÇÃO:

```php
public function changeStatus(string $code, string $newStatus): ServiceResult
{
    try {
        return DB::transaction(function () use ($code, $newStatus) {
            $service = Service::where('code', $code)->first();

            if (!$service) {
                return $this->error(
                    OperationStatus::NOT_FOUND,
                    "Serviço {$code} não encontrado"
                );
            }

            $oldStatus = $service->status;

            // Validar transição
            $allowedTransitions = ServiceStatusEnum::getAllowedTransitions($oldStatus->value);
            if (!in_array($newStatus, $allowedTransitions)) {
                return $this->error(
                    OperationStatus::VALIDATION_ERROR,
                    "Transição de {$oldStatus->value} para {$newStatus} não permitida"
                );
            }

            // Atualizar serviço
            $service->update(['status' => $newStatus]);

            // Atualizar orçamento em cascata se necessário
            $this->updateBudgetStatusIfNeeded($service, $newStatus);

            return $this->success($service, 'Status alterado com sucesso');

        });

    } catch (Exception $e) {
        return $this->error(
            OperationStatus::ERROR,
            'Erro ao alterar status',
            null,
            $e
        );
    }
}

private function updateBudgetStatusIfNeeded(Service $service, string $newStatus): void
{
    $budgetStatusMap = [
        ServiceStatusEnum::APPROVED->value => 'approved',
        ServiceStatusEnum::REJECTED->value => 'rejected',
        ServiceStatusEnum::CANCELLED->value => 'cancelled'
    ];

    if (isset($budgetStatusMap[$newStatus])) {
        $service->budget->update(['status' => $budgetStatusMap[$newStatus]]);
    }
}
```

ARQUIVOS:

-  app/Services/Domain/ServiceService.php (métodos changeStatus, updateBudgetStatusIfNeeded)

CRITÉRIO DE SUCESSO: Status alterado com validação e cascata para orçamento

---

## 🎯 PROMPT 3.7: Implementar deleteByCode() - Deletar por Código

Implemente APENAS o método deleteByCode() no ServiceService:

TAREFA ESPECÍFICA:

-  Busca: Por código + validação de deletabilidade
-  Verificação: Relacionamentos que impedem exclusão
-  Cascata: Deletar itens primeiro
-  Transaction: Atomicidade

IMPLEMENTAÇÃO:

```php
public function deleteByCode(string $code): ServiceResult
{
    try {
        return DB::transaction(function () use ($code) {
            $service = Service::where('code', $code)->first();

            if (!$service) {
                return $this->error(
                    OperationStatus::NOT_FOUND,
                    "Serviço {$code} não encontrado"
                );
            }

            // Verificar se pode deletar
            if (!$this->canDeleteService($service)) {
                return $this->error(
                    OperationStatus::VALIDATION_ERROR,
                    'Serviço não pode ser excluído devido a dependências'
                );
            }

            // Verificar se não tem agendamentos futuros
            $futureSchedules = $service->schedules()
                ->where('start_date_time', '>', now())
                ->count();

            if ($futureSchedules > 0) {
                return $this->error(
                    OperationStatus::VALIDATION_ERROR,
                    'Serviço possui agendamentos futuros e não pode ser excluído'
                );
            }

            // Deletar itens do serviço
            $service->serviceItems()->delete();

            // Deletar agendamentos
            $service->schedules()->delete();

            // Deletar o serviço
            $service->delete();

            // Atualizar total do orçamento
            $this->updateBudgetTotal($service->budget);

            return $this->success(null, 'Serviço excluído com sucesso');

        });

    } catch (Exception $e) {
        return $this->error(
            OperationStatus::ERROR,
            'Erro ao excluir serviço',
            null,
            $e
        );
    }
}

private function canDeleteService(Service $service): bool
{
    // Não pode deletar se tiver faturas
    if ($service->invoices()->count() > 0) {
        return false;
    }

    // Não pode deletar se estiver em status final
    $finalStatuses = ServiceStatusEnum::getFinalStatuses();
    if (in_array($service->status->value, $finalStatuses)) {
        return false;
    }

    return true;
}
```

ARQUIVOS:

-  app/Services/Domain/ServiceService.php (métodos deleteByCode, canDeleteService)
-  app/Enums/ServiceStatusEnum.php (método getFinalStatuses)

CRITÉRIO DE SUCESSO: Serviço deletado com validação de dependências

---

## 🎯 PROMPT 3.8: Implementar updateStatusByToken() - Atualização por Token

Implemente APENAS o método updateStatusByToken() no ServiceService:

TAREFA ESPECÍFICA:

-  Token: Validar token e expiração
-  Status: Apenas status permitidos para cliente
-  Auditoria: Registrar mudança por cliente
-  Segurança: Log de mudanças

IMPLEMENTAÇÃO:

```php
public function updateStatusByToken(
    string $serviceCode,
    string $token,
    string $newStatus,
    ?string $reason = null
): ServiceResult {
    try {
        return DB::transaction(function () use ($serviceCode, $token, $newStatus, $reason) {
            // Buscar serviço com token
            $service = Service::where('code', $serviceCode)
                ->whereHas('userConfirmationToken', function ($query) use ($token) {
                    $query->where('token', $token)
                          ->where('expires_at', '>', now());
                })
                ->first();

            if (!$service) {
                return $this->error(
                    OperationStatus::NOT_FOUND,
                    'Serviço ou token não encontrado/expirado'
                );
            }

            // Validar que é status permitido para cliente
            $allowedStatuses = [
                ServiceStatusEnum::APPROVED->value,
                ServiceStatusEnum::REJECTED->value,
                ServiceStatusEnum::CANCELLED->value
            ];

            if (!in_array($newStatus, $allowedStatuses)) {
                return $this->error(
                    OperationStatus::VALIDATION_ERROR,
                    'Status não permitido para alteração por cliente'
                );
            }

            // Validar transições permitidas
            $allowedTransitions = ServiceStatusEnum::getAllowedTransitions($service->status->value);
            if (!in_array($newStatus, $allowedTransitions)) {
                return $this->error(
                    OperationStatus::VALIDATION_ERROR,
                    "Transição de {$service->status->value} para {$newStatus} não permitida"
                );
            }

            // Atualizar status
            $service->update([
                'status' => $newStatus,
                'reason' => $reason
            ]);

            // Log da alteração
            Log::info('Service status updated via public token', [
                'service_id' => $service->id,
                'service_code' => $service->code,
                'old_status' => $service->status->value,
                'new_status' => $newStatus,
                'reason' => $reason,
                'ip' => request()->ip()
            ]);

            return $this->success($service, 'Status atualizado com sucesso');

        });

    } catch (Exception $e) {
        return $this->error(
            OperationStatus::ERROR,
            'Erro ao atualizar status via token',
            null,
            $e
        );
    }
}
```

ARQUIVOS:

-  app/Services/Domain/ServiceService.php (método updateStatusByToken)
-  app/Models/Service.php (campo reason)

CRITÉRIO DE SUCESSO: Status atualizado por cliente com validação robusta

---

# 🎯 GRUPO 4: CONTROLLERS (Interface HTTP) - **QUARTO**

## 🎯 PROMPT 4.1: Implementar index() - Lista de Serviços

Implemente APENAS o método index() no ServiceController:

TAREFA ESPECÍFICA:

-  Método: public function index(Request $request): View
-  Filtros: Status, categoria, período, busca por código
-  Paginação: 15 registros por página
-  Eager loading: `category`, `budget.customer`, `serviceStatus`

IMPLEMENTAÇÃO:

```php
public function index(Request $request): View
{
    try {
        $filters = $request->only(['status', 'category_id', 'date_from', 'date_to', 'search']);

        $result = $this->serviceService->getFilteredServices($filters, [
            'category:id,name',
            'budget.customer.commonData',
            'serviceStatus'
        ]);

        if (!$result->isSuccess()) {
            abort(500, 'Erro ao carregar lista de serviços');
        }

        $services = $result->getData();

        return view('services.index', [
            'services' => $services,
            'filters' => $filters,
            'statusOptions' => ServiceStatusEnum::cases(),
            'categories' => $this->categoryService->getActive()
        ]);

    } catch (Exception $e) {
        abort(500, 'Erro ao carregar serviços');
    }
}
```

ARQUIVOS:

-  app/Http/Controllers/ServiceController.php (método index)
-  app/Services/Domain/ServiceService.php (método getFilteredServices)
-  resources/views/services/index.blade.php (criar)

CRITÉRIO DE SUCESSO: Lista de serviços com filtros funcionais e paginação

---

## 🎯 PROMPT 4.2: Implementar create() - Formulário de Criação

Implemente APENAS o método create() no ServiceController:

TAREFA ESPECÍFICA:

-  Método: public function create(?string $budgetCode = null): View
-  Pré-seleção: Orçamento por código (opcional)
-  Dados: Categorias, produtos, unidades, orçamentos disponíveis
-  Geração: Código de serviço automático (futuro)

IMPLEMENTAÇÃO:

```php
public function create(?string $budgetCode = null): View
{
    try {
        $budget = null;

        if ($budgetCode) {
            $budgetResult = $this->budgetService->findByCode($budgetCode);
            if ($budgetResult->isSuccess()) {
                $budget = $budgetResult->getData();
            }
        }

        return view('services.create', [
            'budget' => $budget,
            'categories' => $this->categoryService->getActive(),
            'products' => $this->productService->getActive(),
            'budgets' => $this->budgetService->getNotCompleted(),
            'statusOptions' => ServiceStatusEnum::cases()
        ]);

    } catch (Exception $e) {
        abort(500, 'Erro ao carregar formulário de criação');
    }
}
```

ARQUIVOS:

-  app/Http/Controllers/ServiceController.php (método create)
-  resources/views/services/create.blade.php (criar)

CRITÉRIO DE SUCESSO: Formulário de criação carregado com dados necessários

---

## 🎯 PROMPT 4.3: Implementar store() - Criar Serviço

Implemente APENAS o método store() no ServiceController:

TAREFA ESPECÍFICA:

-  Método: public function store(ServiceStoreRequest $request): RedirectResponse
-  Validação: ServiceStoreRequest
-  Geração: Código único {BUDGET_CODE}-S{SEQUENCIAL}
-  Transaction: DB::transaction para atomicidade

IMPLEMENTAÇÃO:

```php
public function store(ServiceStoreRequest $request): RedirectResponse
{
    try {
        $result = $this->serviceService->createService($request->validated());

        if (!$result->isSuccess()) {
            return redirect()->back()
                ->withInput()
                ->with('error', $result->getMessage());
        }

        $service = $result->getData();

        return redirect()->route('services.show', $service->code)
            ->with('success', 'Serviço criado com sucesso!');

    } catch (Exception $e) {
        return redirect()->back()
            ->withInput()
            ->with('error', 'Erro ao criar serviço: ' . $e->getMessage());
    }
}
```

ARQUIVOS:

-  app/Http/Controllers/ServiceController.php (método store)
-  app/Services/Domain/ServiceService.php (método createService)

CRITÉRIO DE SUCESSO: Serviço criado com código único e itens relacionados

---

## 🎯 PROMPT 4.4: Implementar show() - Detalhes do Serviço

Implemente APENAS o método show() no ServiceController:

TAREFA ESPECÍFICA:

-  Método: public function show(string $code): View
-  Busca: Por código com relacionamentos completos
-  Eager loading: `budget.customer`, `category`, `serviceItems.product`
-  Agendamentos: Último agendamento
-  Faturas: Fatura vinculada (se existir)

IMPLEMENTAÇÃO:

```php
public function show(string $code): View
{
    try {
        $result = $this->serviceService->findByCode($code, [
            'budget.customer.commonData',
            'budget.customer.contacts',
            'category',
            'serviceItems.product',
            'serviceStatus',
            'schedules' => function($q) {
                $q->latest()->limit(1);
            }
        ]);

        if (!$result->isSuccess()) {
            abort(404, 'Serviço não encontrado');
        }

        $service = $result->getData();

        return view('services.show', [
            'service' => $service
        ]);

    } catch (Exception $e) {
        abort(500, 'Erro ao carregar serviço');
    }
}
```

ARQUIVOS:

-  app/Http/Controllers/ServiceController.php (método show)
-  resources/views/services/show.blade.php (criar)

CRITÉRIO DE SUCESSO: Detalhes completos do serviço com todos os relacionamentos

---

## 🎯 PROMPT 4.5: Implementar edit() - Formulário de Edição

Implemente APENAS o método edit() no ServiceController:

TAREFA ESPECÍFICA:

-  Método: public function edit(string $code): View
-  Busca: Por código com itens relacionados
-  Validação: Status editável
-  Dados: Mesmos dados do create()

IMPLEMENTAÇÃO:

```php
public function edit(string $code): View
{
    try {
        $result = $this->serviceService->findByCode($code, [
            'serviceItems.product',
            'budget'
        ]);

        if (!$result->isSuccess()) {
            abort(404, 'Serviço não encontrado');
        }

        $service = $result->getData();

        // Verificar se pode editar
        if (!$service->status->canEdit()) {
            abort(403, 'Serviço não pode ser editado no status atual');
        }

        return view('services.edit', [
            'service' => $service,
            'categories' => $this->categoryService->getActive(),
            'products' => $this->productService->getActive(),
            'budgets' => $this->budgetService->getNotCompleted()
        ]);

    } catch (Exception $e) {
        abort(500, 'Erro ao carregar formulário de edição');
    }
}
```

ARQUIVOS:

-  app/Http/Controllers/ServiceController.php (método edit)
-  app/Enums/ServiceStatusEnum.php (método canEdit)
-  resources/views/services/edit.blade.php (criar)

CRITÉRIO DE SUCESSO: Formulário de edição carregado apenas para status editáveis

---

## 🎯 PROMPT 4.6: Implementar update() - Atualizar Serviço

Implemente APENAS o método update() no ServiceController:

TAREFA ESPECÍFICA:

-  Método: public function update(string $code, ServiceUpdateRequest $request): RedirectResponse
-  Validação: ServiceUpdateRequest
-  Transaction: DB::transaction para atomicidade
-  Itens: Gerenciar itens adicionados/removidos/modificados

IMPLEMENTAÇÃO:

```php
public function update(string $code, ServiceUpdateRequest $request): RedirectResponse
{
    try {
        $result = $this->serviceService->updateServiceByCode($code, $request->validated());

        if (!$result->isSuccess()) {
            return redirect()->back()
                ->withInput()
                ->with('error', $result->getMessage());
        }

        $service = $result->getData();

        return redirect()->route('services.show', $service->code)
            ->with('success', 'Serviço atualizado com sucesso!');

    } catch (Exception $e) {
        return redirect()->back()
            ->withInput()
            ->with('error', 'Erro ao atualizar serviço: ' . $e->getMessage());
    }
}
```

ARQUIVOS:

-  app/Http/Controllers/ServiceController.php (método update)
-  app/Services/Domain/ServiceService.php (método updateServiceByCode)

CRITÉRIO DE SUCESSO: Serviço atualizado com gerenciamento de itens

---

## 🎯 PROMPT 4.7: Implementar change_status() - Mudança de Status (Provider)

Implemente APENAS o método change_status() no ServiceController:

TAREFA ESPECÍFICA:

-  Método: public function change_status(string $code, Request $request): RedirectResponse
-  Validação: Transição de status válida
-  Cascata: Pode alterar status do orçamento vinculado
-  Auditoria: Registrar mudança de status

IMPLEMENTAÇÃO:

```php
public function change_status(string $code, Request $request): RedirectResponse
{
    $request->validate([
        'status' => ['required', 'string', 'in:' . implode(',', ServiceStatusEnum::values())]
    ]);

    try {
        $result = $this->serviceService->changeStatus($code, $request->status);

        if (!$result->isSuccess()) {
            return redirect()->back()
                ->with('error', $result->getMessage());
        }

        return redirect()->route('services.show', $code)
            ->with('success', 'Status alterado com sucesso!');

    } catch (Exception $e) {
        return redirect()->back()
            ->with('error', 'Erro ao alterar status: ' . $e->getMessage());
    }
}
```

ARQUIVOS:

-  app/Http/Controllers/ServiceController.php (método change_status)
-  app/Services/Domain/ServiceService.php (método changeStatus)

CRITÉRIO DE SUCESSO: Status alterado com validação de transições

---

## 🎯 PROMPT 4.8: Implementar delete_store() - Deletar Serviço

Implemente APENAS o método delete_store() no ServiceController:

TAREFA ESPECÍFICA:

-  Método: public function delete_store(string $code): RedirectResponse
-  Verificação: Relacionamentos que impedem exclusão (agendamentos, faturas)
-  Cascata: Deletar itens do serviço primeiro
-  Auditoria: Registrar exclusão

IMPLEMENTAÇÃO:

```php
public function delete_store(string $code): RedirectResponse
{
    try {
        $result = $this->serviceService->deleteByCode($code);

        if (!$result->isSuccess()) {
            return redirect()->back()
                ->with('error', $result->getMessage());
        }

        return redirect()->route('services.index')
            ->with('success', 'Serviço excluído com sucesso!');

    } catch (Exception $e) {
        return redirect()->back()
            ->with('error', 'Erro ao excluir serviço: ' . $e->getMessage());
    }
}
```

ARQUIVOS:

-  app/Http/Controllers/ServiceController.php (método delete_store)
-  app/Services/Domain/ServiceService.php (método deleteByCode)

CRITÉRIO DE SUCESSO: Serviço deletado apenas se não tiver dependências bloqueantes

---

## 🎯 PROMPT 4.9: Implementar cancel() - Cancelar Serviço

Implemente APENAS o método cancel() no ServiceController:

TAREFA ESPECÍFICA:

-  Método: public function cancel(string $code): RedirectResponse
-  Status: Atualizar para CANCELLED diretamente
-  Motivo: Opcional (campo nullable)
-  Auditoria: Registrar cancelamento

IMPLEMENTAÇÃO:

```php
public function cancel(string $code): RedirectResponse
{
    try {
        $result = $this->serviceService->cancelService($code);

        if (!$result->isSuccess()) {
            return redirect()->back()
                ->with('error', $result->getMessage());
        }

        $service = $result->getData();

        return redirect()->route('services.show', $service->code)
            ->with('success', 'Serviço cancelado com sucesso!');

    } catch (Exception $e) {
        return redirect()->back()
            ->with('error', 'Erro ao cancelar serviço: ' . $e->getMessage());
    }
}
```

ARQUIVOS:

-  app/Http/Controllers/ServiceController.php (método cancel)
-  app/Models/Service.php (campo reason para cancelamento)

CRITÉRIO DE SUCESSO: Serviço cancelado com status CANCELLED

---

## 🎯 PROMPT 4.10: Atualizar chooseServiceStatus() - Cliente (Melhorar)

Atualize o método chooseServiceStatus() no ServiceController:

TAREFA ESPECÍFICA:

-  Melhorar: Validação de tokens mais robusta
-  Status: Apenas status permitidos para cliente (APPROVED, REJECTED, CANCELLED)
-  Auditoria: Registrar mudança por cliente
-  Expiração: Validar expiração de token

IMPLEMENTAÇÃO ATUALIZADA:

```php
public function chooseServiceStatus(Request $request): RedirectResponse
{
    try {
        $validated = $request->validate([
            'service_code' => 'required|string',
            'token' => 'required|string|size:43',
            'service_status_id' => [
                'required',
                'string',
                'in:' . implode(',', [
                    ServiceStatusEnum::APPROVED->value,
                    ServiceStatusEnum::REJECTED->value,
                    ServiceStatusEnum::CANCELLED->value
                ])
            ],
            'reason' => 'nullable|string|max:500'
        ]);

        $result = $this->serviceService->updateStatusByToken(
            $validated['service_code'],
            $validated['token'],
            $validated['service_status_id'],
            $validated['reason'] ?? null
        );

        if (!$result->isSuccess()) {
            return redirect()->back()
                ->with('error', $result->getMessage());
        }

        return redirect()->route('services.public.view-status', [
            'code' => $validated['service_code'],
            'token' => $validated['token']
        ])->with('success', 'Status do serviço atualizado com sucesso!');

    } catch (Exception $e) {
        Log::error('Error in chooseServiceStatus', [
            'error' => $e->getMessage(),
            'request' => $request->all()
        ]);
        return redirect()->route('error.internal');
    }
}
```

ARQUIVOS:

-  app/Http/Controllers/ServiceController.php (método chooseServiceStatus)
-  app/Services/Domain/ServiceService.php (método updateStatusByToken)

CRITÉRIO DE SUCESSO: Status atualizado por cliente com validação robusta

---

# 📈 **ESTATÍSTICAS**

**Total de Prompts:** 23 prompts
**Ordem Correta:** Repository → FormRequests → Services → Controllers
**Status Atual:** 3/13 métodos implementados (23%)
**Prioridade:** GRUPO 1 (Repository) - **PRIMEIRO**

### **Fase 1: Repository (1 dia)**

-  PROMPTS 1.1 a 1.3: getFiltered, findByCode, countByStatus

### **Fase 2: Form Requests (1 dia)**

-  PROMPTS 2.1 a 2.2: ServiceStoreRequest, ServiceUpdateRequest

### **Fase 3: Services Críticos (5 dias)**

-  PROMPTS 3.1 a 3.4: findByCode, getFilteredServices, createService, createServiceItems

### **Fase 4: Services Avançados (3 dias)**

-  PROMPTS 3.5 a 3.8: updateServiceByCode, changeStatus, deleteByCode, updateStatusByToken

### **Fase 5: Controllers CRUD (4 dias)**

-  PROMPTS 4.1 a 4.4: index, create, store, show

### **Fase 6: Controllers Avançados (3 dias)**

-  PROMPTS 4.5 a 4.10: edit, update, change_status, delete_store, cancel, update chooseServiceStatus

## ✅ **CRITÉRIOS DE SUCESSO POR PROMPT**

-  **Repository:** Queries otimizadas com eager loading
-  **FormRequest:** Validação robusta com mensagens em português
-  **Service:** Lógica de negócio completa com transação e auditoria
-  **Controller:** Método funcionando com validação e error handling

## 🚀 **BENEFÍCIOS DA ORDEM CORRETA**

-  **Dependências respeitadas:** Repository → Services → Controllers
-  **Validação primeiro:** Form Requests antes dos Controllers
-  **Base sólida:** Repository implementado antes dos Services
-  **Testabilidade:** Cada grupo pode ser testado independentemente
-  **Zero dependências circulares:** Arquitetura clara e desacoplada

**Total:** 23 prompts na ordem técnica correta para completar a migração do ServiceController.
