# 🎯 Prompts Detalhados - Migração Plan Controller (Tarefas Menores)

## 📋 CONTEXTO

**Base:** Análise completa em `RELATORIO_ANALISE_PLAN_CONTROLLER.md`
**Status:** 0% implementado
**Objetivo:** Implementar o módulo de plan completo, seguindo a arquitetura moderna do novo sistema, com base na análise do `PlanController` do sistema legado.
**Ordem:** Sequência lógica seguindo dependências técnicas (Database → Repository → Form Requests → Service → Controller).

-  **Tokens globais:**
   -  **plan:** nome no singular (ex: plan)
   -  **plans:** nome no plural (ex: plans)
   -  **Plan:** classe do modelo (ex: Plan)
   -  **PlanController:** controller (ex: PlanController)
   -  **PlanRepository:** repositório (ex: PlanRepository)
   -  **PlanService:** serviço (ex: PlanService)
   -  **plans:** nome da tabela (ex: plans)
   -  **id:** chave primária (ex: id)
   -  **slug:** campo único (ex: slug)
   -  **[]:** lista de FKs relevantes (ex: [])
   -  **[]:** lista de relações a carregar (ex: [])
   -  \***\*:** trait de tenant (ex: )

---

# 🎯 Grupo 1: Database & Repository (base de dados) — primeiro

## 🎯 Prompt 1.1: Atualizar migration, model e factory

Implemente APENAS a atualização da Migration, Model e Factory para o módulo de plans:

-  **Tarefa específica:**

   -  **Migration:** Atualizar o schema inicial (`..._create_initial_schema.php`) para adicionar os campos necessários em `plans`:
      -  FKs: []
      -  Campo único: `slug` (substituir se houver legado como `code`)
      -  Campos de domínio (ex: `name`, `description`, `price`, `status`) conforme o módulo
      -  `softDeletes`
   -  **Model:** Atualizar `Plan.php` para incluir fillable, casts e relacionamentos.
   -  **Factory:** Atualizar `PlanFactory.php` para gerar dados dos novos campos.

-  **Implementação (exemplo base):**

```php
// Migration
Schema::create('plans', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('slug')->unique();
    $table->text('description')->nullable();
    $table->decimal('price', 10, 2)->default(0);
    $table->boolean('status')->default(true);
    $table->json('features')->nullable();
    $table->integer('max_budgets')->default(0);
    $table->integer('max_clients')->default(0);
    $table->timestamps();
    $table->softDeletes();
});

// Model
class Plan extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'slug', 'description', 'price', 'status', 'features', 'max_budgets', 'max_clients'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'status' => 'boolean',
        'features' => 'array',
    ];

    // Relações — exemplo:
    // public function subscriptions(): HasMany { return $this->hasMany(PlanSubscription::class); }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', true);
    }
}

// Factory
public function definition(): array
{
    return [
        'name' => $this->faker->word(),
        'slug' => $this->faker->unique()->slug(),
        'description' => $this->faker->sentence(),
        'price' => $this->faker->randomFloat(2, 0, 100),
        'status' => true,
        'features' => json_encode(['feature1', 'feature2']),
        'max_budgets' => $this->faker->numberBetween(10, 1000),
        'max_clients' => $this->faker->numberBetween(10, 1000),
    ];
}
```

-  **Arquivos:**

   -  `database/migrations/..._create_initial_schema.php` (alterar)
   -  `app/Models/Plan.php` (alterar)
   -  `database/factories/PlanFactory.php` (alterar)

-  **Critério de sucesso:** Estrutura de banco e Eloquent atualizados e funcionais.

---

## 🎯 Prompt 1.2: Implementar PlanRepository — getPaginated()

-  **Tarefa específica:**

   -  Abstrair queries.
   -  Filtros avançados: `search`, `status`, FKs, range numérico.
   -  Tenant scoping automático.
   -  Eager loading de `[]`.

-  **Implementação:**

```php
class PlanRepository extends AbstractGlobalRepository
{
    public function __construct(Plan $model) { parent::__construct($model); }

    public function getPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->newQuery()->with([]);

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('slug', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('description', 'like', '%' . $filters['search'] . '%');
            });
        }

        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('status', (bool)$filters['status']);
        }

        // Exemplo FKs e ranges
        foreach ([]) as $fk) {
            if (!empty($filters[$fk])) $query->where($fk, $filters[$fk]);
        }
        if (!empty($filters['min_price'])) $query->where('price', '>=', $filters['min_price']);
        if (!empty($filters['max_price'])) $query->where('price', '<=', $filters['max_price']);

        return $query->orderBy('name', 'asc')->paginate($perPage);
    }
}
```

-  **Arquivo:** `app/Repositories/PlanRepository.php`
-  **Critério de sucesso:** Paginação com filtros funcionais.

---

## 🎯 Prompt 1.3: Implementar PlanRepository — findBySlug()

-  **Tarefa específica:** Buscar por `slug` com eager loading opcional.

-  **Implementação:**

```php
public function findBySlug(string $slug, array $with = []): ?Model
{
    $query = $this->model->where('slug', $slug);
    if (!empty($with)) $query->with($with);
    return $query->first();
}
```

-  **Arquivo:** `app/Repositories/PlanRepository.php`
-  **Critério de sucesso:** Busca por slug único do módulo.

---

## 🎯 Prompt 1.4: Implementar PlanRepository — countActive()

-  **Implementação:**

```php
public function countActive(): int
{
    return $this->model->where('status', true)->count();
}
```

-  **Arquivo:** `app/Repositories/PlanRepository.php`
-  **Critério de sucesso:** Métrica de ativos.

---

## 🎯 Prompt 1.5: Implementar PlanRepository — canBeDeactivatedOrDeleted()

-  **Regra:** Não pode desativar/deletar se houver dependências (ex: `subscriptions`).

-  **Implementação (exemplo):**

```php
public function canBeDeactivatedOrDeleted(int $id): bool
{
    return !$this->model->where('id', $id)->has('subscriptions')->exists();
}
```

-  **Arquivo:** `app/Repositories/PlanRepository.php`
-  **Critério de sucesso:** Validação de integridade referencial.

---

# 🎯 Grupo 2: Form requests (validação) — segundo

## 🎯 Prompt 2.1: Criar PlanStoreRequest

-  **Campos:** defina conforme domínio: `name`, `slug`, `description`, `price`, `status`, `features`, `max_budgets`, `max_clients`.
-  **Validações:** unicidade global, required, numéricos, booleanos.

-  **Implementação:**

```php
class PlanStoreRequest extends FormRequest
{
    public function authorize(): bool { return auth()->check(); }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'slug' => [
                'required','string','max:50',
                Rule::unique('plans')
            ],
            'description' => 'nullable|string|max:500',
            'price' => 'required|numeric|min:0',
            'status' => 'boolean',
            'features' => 'nullable|array',
            'max_budgets' => 'required|integer|min:0',
            'max_clients' => 'required|integer|min:0'
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O nome é obrigatório.',
            'slug.unique' => 'O slug informado já está em uso.',
            'price.required' => 'O preço é obrigatório.',
            'price.numeric' => 'O preço deve ser numérico.',
            'price.min' => 'O preço deve ser no mínimo 0.',
            'max_budgets.required' => 'O máximo de orçamentos é obrigatório.',
            'max_clients.required' => 'O máximo de clientes é obrigatório.'
        ];
    }
}
```

-  **Arquivo:** `app/Http/Requests/PlanStoreRequest.php`
-  **Critério de sucesso:** Validação robusta com mensagens em português.

---

## 🎯 Prompt 2.2: Criar PlanUpdateRequest

-  **Campos:** todos opcionais para atualização parcial.
-  **Regra:** unicidade global ignorando o próprio registro.

-  **Implementação:**

```php
class PlanUpdateRequest extends FormRequest
{
    public function authorize(): bool { return auth()->check(); }

    public function rules(): array
    {
        $entityId = $this->route('plan'); // parâmetro de rota com ID

        return [
            'name' => 'sometimes|required|string|max:255',
            'slug' => [
                'sometimes','required','string','max:50',
                Rule::unique('plans')->ignore($entityId)
            ],
            'description' => 'sometimes|nullable|string|max:500',
            'price' => 'sometimes|required|numeric|min:0',
            'status' => 'sometimes|boolean',
            'features' => 'sometimes|nullable|array',
            'max_budgets' => 'sometimes|required|integer|min:0',
            'max_clients' => 'sometimes|required|integer|min:0'
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O nome é obrigatório.',
            'slug.unique' => 'O slug informado já está em uso.',
            'price.required' => 'O preço é obrigatório.',
            'price.numeric' => 'O preço deve ser numérico.',
            'price.min' => 'O preço deve ser no mínimo 0.',
            'max_budgets.required' => 'O máximo de orçamentos é obrigatório.',
            'max_clients.required' => 'O máximo de clientes é obrigatório.'
        ];
    }
}
```

-  **Arquivo:** `app/Http/Requests/PlanUpdateRequest.php`
-  **Critério de sucesso:** Validação robusta para edição.

---

# 🎯 Grupo 3: Services (lógica de negócio) — terceiro

## 🎯 Prompt 3.1: Implementar PlanService — findBySlug()

-  **Implementação:**

```php
public function findBySlug(string $slug, array $with = []): ServiceResult
{
    try {
        $entity = $this->repository->findBySlug($slug, $with);
        if (!$entity) return $this->error(OperationStatus::NOT_FOUND, "Registro com slug {$slug} não encontrado");
        return $this->success($entity, 'Encontrado');
    } catch (Exception $e) {
        return $this->error(OperationStatus::ERROR, 'Erro ao buscar', null, $e);
    }
}
```

---

## 🎯 Prompt 3.2: Implementar PlanService — getFilteredPlans()

-  **Implementação:**

```php
public function getFilteredPlans(array $filters = [], array $with = []): ServiceResult
{
    try {
        $entities = $this->repository->getPaginated($filters, 15);
        return $this->success($entities, 'Filtrados');
    } catch (Exception $e) {
        return $this->error(OperationStatus::ERROR, 'Erro ao filtrar', null, $e);
    }
}
```

---

## 🎯 Prompt 3.3: Implementar PlanService — createPlan()

-  **Implementação:**

```php
public function createPlan(array $data): ServiceResult
{
    try {
        return DB::transaction(function () use ($data) {
            $entity = $this->repository->create($data);
            return $this->success($entity, 'Plan criado com sucesso');
        });
    } catch (Exception $e) {
        return $this->error(OperationStatus::ERROR, 'Erro ao criar plan', null, $e);
    }
}
```

---

## 🎯 Prompt 3.4: Implementar PlanService — updateBySlug()

-  **Implementação:**

```php
public function updateBySlug(string $slug, array $data): ServiceResult
{
    try {
        return DB::transaction(function () use ($slug, $data) {
            $entity = $this->repository->findBySlug($slug);
            if (!$entity) return $this->error(OperationStatus::NOT_FOUND, "Registro com slug {$slug} não encontrado");

            $entity = $this->repository->update($entity->id, $data);
            return $this->success($entity, 'Atualizado com sucesso');
        });
    } catch (Exception $e) {
        return $this->error(OperationStatus::ERROR, 'Erro ao atualizar', null, $e);
    }
}
```

---

## 🎯 Prompt 3.5: Implementar PlanService — toggleStatus()

-  **Implementação:**

```php
public function toggleStatus(string $slug): ServiceResult
{
    try {
        return DB::transaction(function () use ($slug) {
            $entity = $this->repository->findBySlug($slug);
            if (!$entity) return $this->error(OperationStatus::NOT_FOUND, "Registro com slug {$slug} não encontrado");

            if (!$this->repository->canBeDeactivatedOrDeleted($entity->id)) {
                return $this->error(OperationStatus::VALIDATION_ERROR, 'Não pode alterar status: em uso.');
            }

            $new = !$entity->status;
            $entity = $this->repository->update($entity->id, ['status' => $new]);
            return $this->success($entity, $new ? 'Ativado com sucesso' : 'Desativado com sucesso');
        });
    } catch (Exception $e) {
        return $this->error(OperationStatus::ERROR, 'Erro ao alterar status', null, $e);
    }
}
```

---

## 🎯 Prompt 3.6: Implementar PlanService — deleteBySlug()

-  **Implementação:**

```php
public function deleteBySlug(string $slug): ServiceResult
{
    try {
        return DB::transaction(function () use ($slug) {
            $entity = $this->repository->findBySlug($slug);
            if (!$entity) return $this->error(OperationStatus::NOT_FOUND, "Registro com slug {$slug} não encontrado");

            if (!$this->repository->canBeDeactivatedOrDeleted($entity->id)) {
                return $this->error(OperationStatus::VALIDATION_ERROR, 'Não pode excluir: em uso.');
            }

            $this->repository->delete($entity->id);

            return $this->success(null, 'Excluído com sucesso');
        });
    } catch (Exception $e) {
        return $this->error(OperationStatus::ERROR, 'Erro ao excluir', null, $e);
    }
}
```

---

# 🎯 Grupo 4: Controllers (interface HTTP) — quarto

## 🎯 Prompt 4.1: Implementar index() — lista

-  **Implementação:**

```php
public function index(Request $request): View
{
    try {
        $filters = $request->only(['search', 'status', 'min_price', 'max_price']);
        $result = $this->service->getFilteredPlans($filters, []);
        if (!$result->isSuccess()) abort(500, 'Erro ao carregar lista');

        return view('plans.index', [
            'plans' => $result->getData(),
            'filters' => $filters,
        ]);
    } catch (Exception $e) {
        Log::error('Erro no PlanController@index', ['error' => $e->getMessage()]);
        abort(500, 'Erro interno do servidor');
    }
}
```

-  **Arquivo:** `app/Http/Controllers/PlanController.php`
-  **Critério de sucesso:** Lista de plans com filtros funcionando.

---

## 🎯 Prompt 4.2: Implementar show() — visualizar

-  **Implementação:**

```php
public function show(string $slug): View
{
    try {
        $result = $this->service->findBySlug($slug, []);
        if (!$result->isSuccess()) abort(404, $result->getMessage());

        return view('plans.show', [
            'plan' => $result->getData(),
        ]);
    } catch (Exception $e) {
        Log::error('Erro no PlanController@show', ['slug' => $slug, 'error' => $e->getMessage()]);
        abort(500, 'Erro interno do servidor');
    }
}
```

-  **Arquivo:** `app/Http/Controllers/PlanController.php`
-  **Critério de sucesso:** Visualização de plan individual funcionando.

---

## 🎯 Prompt 4.3: Implementar create() — formulário criação

-  **Implementação:**

```php
public function create(): View
{
    return view('plans.create');
}
```

-  **Arquivo:** `app/Http/Controllers/PlanController.php`
-  **Critério de sucesso:** Formulário de criação acessível.

---

## 🎯 Prompt 4.4: Implementar store() — salvar criação

-  **Implementação:**

```php
public function store(PlanStoreRequest $request): RedirectResponse
{
    try {
        $result = $this->service->createPlan($request->validated());
        if (!$result->isSuccess()) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => $result->getMessage()]);
        }

        return redirect()->route('plans.show', $result->getData()->slug)
            ->with('success', $result->getMessage());
    } catch (Exception $e) {
        Log::error('Erro no PlanController@store', ['error' => $e->getMessage()]);
        return redirect()->back()
            ->withInput()
            ->withErrors(['error' => 'Erro interno do servidor']);
    }
}
```

-  **Arquivo:** `app/Http/Controllers/PlanController.php`
-  **Critério de sucesso:** Criação de plan funcionando com validação.

---

## 🎯 Prompt 4.5: Implementar edit() — formulário edição

-  **Implementação:**

```php
public function edit(string $slug): View
{
    try {
        $result = $this->service->findBySlug($slug, []);
        if (!$result->isSuccess()) abort(404, $result->getMessage());

        return view('plans.edit', [
            'plan' => $result->getData(),
        ]);
    } catch (Exception $e) {
        Log::error('Erro no PlanController@edit', ['slug' => $slug, 'error' => $e->getMessage()]);
        abort(500, 'Erro interno do servidor');
    }
}
```

-  **Arquivo:** `app/Http/Controllers/PlanController.php`
-  **Critério de sucesso:** Formulário de edição funcionando.

---

## 🎯 Prompt 4.6: Implementar update() — salvar edição

-  **Implementação:**

```php
public function update(PlanUpdateRequest $request, string $slug): RedirectResponse
{
    try {
        $result = $this->service->updateBySlug($slug, $request->validated());
        if (!$result->isSuccess()) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => $result->getMessage()]);
        }

        return redirect()->route('plans.show', $slug)
            ->with('success', $result->getMessage());
    } catch (Exception $e) {
        Log::error('Erro no PlanController@update', ['slug' => $slug, 'error' => $e->getMessage()]);
        return redirect()->back()
            ->withInput()
            ->withErrors(['error' => 'Erro interno do servidor']);
    }
}
```

-  **Arquivo:** `app/Http/Controllers/PlanController.php`
-  **Critério de sucesso:** Edição de plan funcionando.

---

## 🎯 Prompt 4.7: Implementar destroy() — excluir

-  **Implementação:**

```php
public function destroy(string $slug): RedirectResponse
{
    try {
        $result = $this->service->deleteBySlug($slug);
        if (!$result->isSuccess()) {
            return redirect()->back()
                ->withErrors(['error' => $result->getMessage()]);
        }

        return redirect()->route('plans.index')
            ->with('success', $result->getMessage());
    } catch (Exception $e) {
        Log::error('Erro no PlanController@destroy', ['slug' => $slug, 'error' => $e->getMessage()]);
        return redirect()->back()
            ->withErrors(['error' => 'Erro interno do servidor']);
    }
}
```

-  **Arquivo:** `app/Http/Controllers/PlanController.php`
-  **Critério de sucesso:** Exclusão de plan funcionando com validação.

---

## 🎯 Prompt 4.8: Implementar métodos específicos do PlanController

Baseado na análise do `RELATORIO_ANALISE_PLAN_CONTROLLER.md`, implementar os métodos específicos:

-  **redirectToPayment()** - Processa seleção de plano e redireciona para pagamento
-  **cancelPendingSubscription()** - Cancela assinatura pendente
-  **status()** - Verifica status de assinatura pendente
-  **paymentStatus()** - Página de retorno após pagamento

**Implementação:** Seguir a lógica descrita no relatório, adaptando para a arquitetura Laravel moderna com Services e Events.

-  **Arquivo:** `app/Http/Controllers/PlanController.php`
-  **Critério de sucesso:** Funcionalidades específicas do módulo de planos implementadas.
