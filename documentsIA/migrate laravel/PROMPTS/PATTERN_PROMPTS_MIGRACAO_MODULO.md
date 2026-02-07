**Criando modelo de prompts reutilizável**

Eu preciso gerar um modelo de prompt que seja reutilizável, usando o arquivo do usuário como exemplo. Vou criar um template com espaços para preencher e instruções. A estrutura deve ser concisa, mas completa, mantendo markdown e cabeçalhos organizados. Usarei tokens variáveis como {{NOME_MODULO}} e indicarei grupos e estruturas de prompt, sem necessidade de citações. Vou sugerir um nome adequado para o arquivo de padrão e garantir que tudo esteja claro e funcional.
# Pattern de prompts técnicos para migração de módulo

Este é um modelo reutilizável, baseado no seu arquivo, para você passar à IA e ela gerar tarefas no mesmo formato. Use os placeholders para adaptar a qualquer módulo.

---

## 📋 Contexto do módulo

- **Base:** Análise completa em `RELATORIO_ANALISE_{{MODULE_UPPER}}_CONTROLLER.md`
- **Status:** 0% implementado
- **Objetivo:** Implementar o módulo de {{MODULE_NAME}} completo, seguindo a arquitetura moderna do novo sistema, com base na análise do `{{ModuleController}}` do sistema legado.
- **Ordem:** Sequência lógica seguindo dependências técnicas (Database → Repository → Form Requests → Service → Controller).

- **Tokens globais:**
  - **{{MODULE_NAME}}:** nome no singular (ex: produto)
  - **{{MODULE_PLURAL}}:** nome no plural (ex: produtos)
  - **{{Module}}:** classe do modelo (ex: Product)
  - **{{ModuleController}}:** controller (ex: ProductController)
  - **{{Repository}}:** repositório (ex: ProductRepository)
  - **{{Service}}:** serviço (ex: ProductService)
  - **{{TABLE_NAME}}:** nome da tabela (ex: products)
  - **{{PRIMARY_KEY}}:** chave primária (ex: id)
  - **{{UNIQUE_CODE_FIELD}}:** campo único (ex: sku)
  - **{{FOREIGN_KEYS}}:** lista de FKs relevantes (ex: category_id)
  - **{{RELATIONS}}:** lista de relações a carregar (ex: category)
  - **{{TENANT_SCOPED_TRAIT}}:** trait de tenant (ex: TenantScoped)

---

# 🎯 Grupo 1: Database & Repository (base de dados) — primeiro

## 🎯 Prompt 1.1: Atualizar migration, model e factory

Implemente APENAS a atualização da Migration, Model e Factory para o módulo de {{MODULE_PLURAL}}:

- **Tarefa específica:**
  - **Migration:** Atualizar o schema inicial (`..._create_initial_schema.php`) para adicionar os campos necessários em `{{TABLE_NAME}}`:
    - FKs: {{FOREIGN_KEYS}}
    - Campo único: `{{UNIQUE_CODE_FIELD}}` (substituir se houver legado como `code`)
    - Campos de domínio (ex: `unit`, `active`, `image`) conforme o módulo
    - `softDeletes`
  - **Model:** Atualizar `{{Module}}.php` para incluir fillable, casts e relacionamentos.
  - **Factory:** Atualizar `{{Module}}Factory.php` para gerar dados dos novos campos.

- **Implementação (exemplo base):**
```php
// Migration
Schema::create('{{TABLE_NAME}}', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
    // {{FOREIGN_KEYS}} — exemplo:
    // $table->foreignId('category_id')->nullable()->constrained('categories')->onDelete('set null');

    $table->string('name');
    $table->text('description')->nullable();
    $table->string('{{UNIQUE_CODE_FIELD}}')->nullable();
    $table->decimal('price', 10, 2)->default(0);
    $table->string('unit', 20)->nullable()->comment('Ex: un, m², h');
    $table->boolean('active')->default(true);
    $table->string('image')->nullable();
    $table->timestamps();
    $table->softDeletes();

    $table->unique(['tenant_id', '{{UNIQUE_CODE_FIELD}}']);
});

// Model
class {{Module}} extends Model
{
    use HasFactory, SoftDeletes, {{TENANT_SCOPED_TRAIT}};

    protected $fillable = [
        'tenant_id', /* {{FOREIGN_KEYS}} */, 'name', 'description', '{{UNIQUE_CODE_FIELD}}', 'price', 'unit', 'active', 'image'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'active' => 'boolean',
    ];

    // Relações — exemplo:
    // public function category(): BelongsTo { return $this->belongsTo(Category::class); }
    // public function serviceItems(): HasMany { return $this->hasMany(ServiceItem::class); }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }
}

// Factory
public function definition(): array
{
    return [
        'tenant_id' => Tenant::factory(),
        // '{{FOREIGN_KEYS}}' com states, se necessário
        'name' => $this->faker->word(),
        'description' => $this->faker->sentence(),
        '{{UNIQUE_CODE_FIELD}}' => $this->faker->unique()->ean8(),
        'price' => $this->faker->randomFloat(2, 10, 500),
        'unit' => $this->faker->randomElement(['un', 'h', 'm²']),
        'active' => true,
        'image' => null,
    ];
}
```

- **Arquivos:**
  - `database/migrations/..._create_initial_schema.php` (alterar)
  - `app/Models/{{Module}}.php` (alterar)
  - `database/factories/{{Module}}Factory.php` (alterar)

- **Critério de sucesso:** Estrutura de banco e Eloquent atualizados e funcionais.

---

## 🎯 Prompt 1.2: Implementar {{Repository}} — getPaginated()

- **Tarefa específica:**
  - Abstrair queries.
  - Filtros avançados: `search`, `active`, FKs, range numérico.
  - Tenant scoping automático.
  - Eager loading de `{{RELATIONS}}`.

- **Implementação:**
```php
class {{Repository}} extends AbstractTenantRepository
{
    public function __construct({{Module}} $model) { parent::__construct($model); }

    public function getPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->newQuery()->with({{ json_encode((array) '{{RELATIONS}}') }});

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('{{UNIQUE_CODE_FIELD}}', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('description', 'like', '%' . $filters['search'] . '%');
            });
        }

        if (isset($filters['active']) && $filters['active'] !== '') {
            $query->where('active', (bool)$filters['active']);
        }

        // Exemplo FKs e ranges
        foreach (['{{FOREIGN_KEYS}}'] as $fk) {
            if (!empty($filters[$fk])) $query->where($fk, $filters[$fk]);
        }
        if (!empty($filters['min_price'])) $query->where('price', '>=', $filters['min_price']);
        if (!empty($filters['max_price'])) $query->where('price', '<=', $filters['max_price']);

        return $query->orderBy('name', 'asc')->paginate($perPage);
    }
}
```

- **Arquivo:** `app/Repositories/{{Repository}}.php`
- **Critério de sucesso:** Paginação com filtros funcionais.

---

## 🎯 Prompt 1.3: Implementar {{Repository}} — findByCode()

- **Tarefa específica:** Buscar por `{{UNIQUE_CODE_FIELD}}` com eager loading opcional.

- **Implementação:**
```php
public function findByCode(string $code, array $with = []): ?Model
{
    $query = $this->model->where('{{UNIQUE_CODE_FIELD}}', $code);
    if (!empty($with)) $query->with($with);
    return $query->first();
}
```

- **Arquivo:** `app/Repositories/{{Repository}}.php`
- **Critério de sucesso:** Busca por código único do módulo.

---

## 🎯 Prompt 1.4: Implementar {{Repository}} — countActive()

- **Implementação:**
```php
public function countActive(): int
{
    return $this->model->where('active', true)->count();
}
```

- **Arquivo:** `app/Repositories/{{Repository}}.php`
- **Critério de sucesso:** Métrica de ativos por tenant.

---

## 🎯 Prompt 1.5: Implementar {{Repository}} — canBeDeactivatedOrDeleted()

- **Regra:** Não pode desativar/deletar se houver dependências (ex: `serviceItems`).

- **Implementação (exemplo):**
```php
public function canBeDeactivatedOrDeleted(int $id): bool
{
    return !$this->model->where('{{PRIMARY_KEY}}', $id)->has('serviceItems')->exists();
}
```

- **Arquivo:** `app/Repositories/{{Repository}}.php`
- **Critério de sucesso:** Validação de integridade referencial.

---

# 🎯 Grupo 2: Form requests (validação) — segundo

## 🎯 Prompt 2.1: Criar {{Module}}StoreRequest

- **Campos:** defina conforme domínio: `name`, `{{UNIQUE_CODE_FIELD}}`, `price`, `{{FOREIGN_KEYS}}`, `unit`, `active`, `image`.
- **Validações:** unicidade por tenant, FKs exist, numéricos, booleanos, imagem.

- **Implementação:**
```php
class {{Module}}StoreRequest extends FormRequest
{
    public function authorize(): bool { return auth()->check(); }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            '{{UNIQUE_CODE_FIELD}}' => [
                'nullable','string','max:50',
                Rule::unique('{{TABLE_NAME}}')->where(fn($q)=>$q->where('tenant_id', tenant()->id))
            ],
            'price' => 'required|numeric|min:0',
            // {{FOREIGN_KEYS}}: 'nullable|integer|exists:{{target_table}},id'
            'unit' => 'nullable|string|max:20',
            'active' => 'boolean',
            'image' => 'nullable|image|max:2048'
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O nome é obrigatório.',
            '{{UNIQUE_CODE_FIELD}}.unique' => 'O código informado já está em uso.',
            'price.required' => 'O preço é obrigatório.',
            'price.numeric' => 'O preço deve ser numérico.',
            'price.min' => 'O preço deve ser no mínimo 0.',
            'image.image' => 'O arquivo deve ser uma imagem.',
            'image.max' => 'A imagem não pode ter mais de 2MB.'
        ];
    }
}
```

- **Arquivo:** `app/Http/Requests/{{Module}}StoreRequest.php`
- **Critério de sucesso:** Validação robusta com mensagens em português.

---

## 🎯 Prompt 2.2: Criar {{Module}}UpdateRequest

- **Campos:** todos opcionais para atualização parcial.
- **Regra:** unicidade por tenant ignorando o próprio registro.

- **Implementação:**
```php
class {{Module}}UpdateRequest extends FormRequest
{
    public function authorize(): bool { return auth()->check(); }

    public function rules(): array
    {
        $entityId = $this->route('{{MODULE_NAME}}'); // parâmetro de rota com ID

        return [
            'name' => 'sometimes|required|string|max:255',
            '{{UNIQUE_CODE_FIELD}}' => [
                'sometimes','nullable','string','max:50',
                Rule::unique('{{TABLE_NAME}}')->ignore($entityId)->where(fn($q)=>$q->where('tenant_id', tenant()->id))
            ],
            'price' => 'sometimes|required|numeric|min:0',
            // {{FOREIGN_KEYS}}: 'sometimes|nullable|integer|exists:{{target_table}},id'
            'unit' => 'sometimes|nullable|string|max:20',
            'active' => 'sometimes|boolean',
            'image' => 'nullable|image|max:2048',
            'remove_image' => 'boolean'
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O nome é obrigatório.',
            '{{UNIQUE_CODE_FIELD}}.unique' => 'O código informado já está em uso.',
            'price.required' => 'O preço é obrigatório.',
            'price.numeric' => 'O preço deve ser numérico.',
            'price.min' => 'O preço deve ser no mínimo 0.',
            'image.image' => 'O arquivo deve ser uma imagem.',
            'image.max' => 'A imagem não pode ter mais de 2MB.'
        ];
    }
}
```

- **Arquivo:** `app/Http/Requests/{{Module}}UpdateRequest.php`
- **Critério de sucesso:** Validação robusta para edição.

---

# 🎯 Grupo 3: Services (lógica de negócio) — terceiro

## 🎯 Prompt 3.1: Implementar {{Service}} — findByCode()

- **Implementação:**
```php
public function findByCode(string $code, array $with = []): ServiceResult
{
    try {
        $entity = $this->repository->findByCode($code, $with);
        if (!$entity) return $this->error(OperationStatus::NOT_FOUND, "Registro com código {$code} não encontrado");
        return $this->success($entity, 'Encontrado');
    } catch (Exception $e) {
        return $this->error(OperationStatus::ERROR, 'Erro ao buscar', null, $e);
    }
}
```

---

## 🎯 Prompt 3.2: Implementar {{Service}} — getFiltered{{Module}}s()

- **Implementação:**
```php
public function getFiltered{{Module}}s(array $filters = [], array $with = []): ServiceResult
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

## 🎯 Prompt 3.3: Implementar {{Service}} — create{{Module}}()

- **Implementação:**
```php
public function create{{Module}}(array $data): ServiceResult
{
    try {
        return DB::transaction(function () use ($data) {
            if (empty($data['{{UNIQUE_CODE_FIELD}}'])) {
                $data['{{UNIQUE_CODE_FIELD}}'] = $this->generateUniqueCode();
            }
            if (isset($data['image'])) {
                $data['image'] = $this->uploadImage($data['image']);
            }
            $entity = $this->repository->create($data);
            return $this->success($entity, '{{Module}} criado com sucesso');
        });
    } catch (Exception $e) {
        return $this->error(OperationStatus::ERROR, 'Erro ao criar {{MODULE_NAME}}', null, $e);
    }
}

private function generateUniqueCode(): string
{
    do {
        $code = '{{CODE_PREFIX}}' . str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
    } while ($this->repository->findByCode($code));
    return $code;
}

private function uploadImage($file): ?string
{
    if (!$file) return null;
    $path = '{{TABLE_NAME}}/' . tenant()->id;
    $filename = Str::random(40) . '.' . $file->getClientOriginalExtension();
    $file->storePubliclyAs($path, $filename, 'public');
    return Storage::url($path . '/' . $filename);
}
```

---

## 🎯 Prompt 3.4: Implementar {{Service}} — updateByCode()

- **Implementação:**
```php
public function updateByCode(string $code, array $data): ServiceResult
{
    try {
        return DB::transaction(function () use ($code, $data) {
            $entity = $this->repository->findByCode($code);
            if (!$entity) return $this->error(OperationStatus::NOT_FOUND, "Registro com código {$code} não encontrado");

            if (!empty($data['remove_image']) && $entity->image) {
                Storage::disk('public')->delete(Str::after($entity->image, '/storage/'));
                $data['image'] = null;
            }

            if (isset($data['image']) && is_a($data['image'], 'Illuminate\Http\UploadedFile')) {
                if ($entity->image) Storage::disk('public')->delete(Str::after($entity->image, '/storage/'));
                $data['image'] = $this->uploadImage($data['image']);
            } else {
                unset($data['image']);
            }

            $entity = $this->repository->update($entity->id, $data);
            return $this->success($entity, 'Atualizado com sucesso');
        });
    } catch (Exception $e) {
        return $this->error(OperationStatus::ERROR, 'Erro ao atualizar', null, $e);
    }
}
```

---

## 🎯 Prompt 3.5: Implementar {{Service}} — toggleStatus()

- **Implementação:**
```php
public function toggleStatus(string $code): ServiceResult
{
    try {
        return DB::transaction(function () use ($code) {
            $entity = $this->repository->findByCode($code);
            if (!$entity) return $this->error(OperationStatus::NOT_FOUND, "Registro com código {$code} não encontrado");

            if (!$this->repository->canBeDeactivatedOrDeleted($entity->id)) {
                return $this->error(OperationStatus::VALIDATION_ERROR, 'Não pode alterar status: em uso.');
            }

            $new = !$entity->active;
            $entity = $this->repository->update($entity->id, ['active' => $new]);
            return $this->success($entity, $new ? 'Ativado com sucesso' : 'Desativado com sucesso');
        });
    } catch (Exception $e) {
        return $this->error(OperationStatus::ERROR, 'Erro ao alterar status', null, $e);
    }
}
```

---

## 🎯 Prompt 3.6: Implementar {{Service}} — deleteByCode()

- **Implementação:**
```php
public function deleteByCode(string $code): ServiceResult
{
    try {
        return DB::transaction(function () use ($code) {
            $entity = $this->repository->findByCode($code);
            if (!$entity) return $this->error(OperationStatus::NOT_FOUND, "Registro com código {$code} não encontrado");

            if (!$this->repository->canBeDeactivatedOrDeleted($entity->id)) {
                return $this->error(OperationStatus::VALIDATION_ERROR, 'Não pode excluir: em uso.');
            }

            if ($entity->image) Storage::disk('public')->delete(Str::after($entity->image, '/storage/'));
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

- **Implementação:**
```php
public function index(Request $request): View
{
    try {
        $filters = $request->only(['search', '{{FOREIGN_KEYS}}', 'active', 'min_price', 'max_price']);
        $result = $this->service->getFiltered{{Module}}s($filters, ['{{RELATIONS}}']);
        if (!$result->isSuccess()) abort(500, 'Erro ao carregar lista');

        return view('{{MODULE_PLURAL}}.index', [
            '{{MODULE_PLURAL}}' => $result->getData(),
            'filters' => $filters,
            'categories' => $this->categoryService->getActive() // ajuste conforme domínio
        ]);
    } catch (Exception $e) {
        abort(500, 'Erro ao carregar {{MODULE_PLURAL}}');
    }
}
```

---

## 🎯 Prompt 4.2: Implementar create() — formulário de criação
```php
public function create(): View
{
    try {
        return view('{{MODULE_PLURAL}}.create', [
            'categories' => $this->categoryService->getActive() // ajuste conforme domínio
        ]);
    } catch (Exception $e) {
        abort(500, 'Erro ao carregar formulário');
    }
}
```

---

## 🎯 Prompt 4.3: Implementar store() — criar
```php
public function store({{Module}}StoreRequest $request): RedirectResponse
{
    try {
        $result = $this->service->create{{Module}}($request->validated());
        if (!$result->isSuccess()) return back()->withInput()->with('error', $result->getMessage());

        $entity = $result->getData();
        return redirect()->route('{{MODULE_PLURAL}}.show', $entity->{{UNIQUE_CODE_FIELD}})
            ->with('success', '{{Module}} criado com sucesso!');
    } catch (Exception $e) {
        return back()->withInput()->with('error', 'Erro ao criar: ' . $e->getMessage());
    }
}
```

---

## 🎯 Prompt 4.4: Implementar show() — detalhes
```php
public function show(string $code): View
{
    try {
        $result = $this->service->findByCode($code, ['{{RELATIONS}}']);
        if (!$result->isSuccess()) abort(404, '{{Module}} não encontrado');

        return view('{{MODULE_PLURAL}}.show', ['{{MODULE_NAME}}' => $result->getData()]);
    } catch (Exception $e) {
        abort(500, 'Erro ao carregar detalhes');
    }
}
```

---

## 🎯 Prompt 4.5: Implementar edit() — formulário de edição
```php
public function edit(string $code): View
{
    try {
        $result = $this->service->findByCode($code, ['{{RELATIONS}}']);
        if (!$result->isSuccess()) abort(404, '{{Module}} não encontrado');

        return view('{{MODULE_PLURAL}}.edit', [
            '{{MODULE_NAME}}' => $result->getData(),
            'categories' => $this->categoryService->getActive() // ajuste conforme domínio
        ]);
    } catch (Exception $e) {
        abort(500, 'Erro ao carregar formulário de edição');
    }
}
```

---

## 🎯 Prompt 4.6: Implementar update() — atualizar
```php
public function update(string $code, {{Module}}UpdateRequest $request): RedirectResponse
{
    try {
        $result = $this->service->updateByCode($code, $request->validated());
        if (!$result->isSuccess()) return back()->withInput()->with('error', $result->getMessage());

        $entity = $result->getData();
        return redirect()->route('{{MODULE_PLURAL}}.show', $entity->{{UNIQUE_CODE_FIELD}})
            ->with('success', '{{Module}} atualizado com sucesso!');
    } catch (Exception $e) {
        return back()->withInput()->with('error', 'Erro ao atualizar: ' . $e->getMessage());
    }
}
```

---

## 🎯 Prompt 4.7: Implementar toggle_status() — ativar/desativar (AJAX)
```php
public function toggle_status(string $code): JsonResponse
{
    try {
        $result = $this->service->toggleStatus($code);
        if (!$result->isSuccess()) return response()->json(['success' => false, 'message' => $result->getMessage()], 400);

        return response()->json(['success' => true, 'message' => $result->getMessage()]);
    } catch (Exception $e) {
        return response()->json(['success' => false, 'message' => 'Erro: ' . $e->getMessage()], 500);
    }
}
```

---

## 🎯 Prompt 4.8: Implementar delete_store() — deletar
```php
public function delete_store(string $code): RedirectResponse
{
    try {
        $result = $this->service->deleteByCode($code);
        if (!$result->isSuccess()) return back()->with('error', $result->getMessage());

        return redirect()->route('{{MODULE_PLURAL}}.index')->with('success', 'Excluído com sucesso!');
    } catch (Exception $e) {
        return back()->with('error', 'Erro ao excluir: ' . $e->getMessage());
    }
}
```

---

# 📈 Estatísticas

- **Total de prompts:** 17 prompts
- **Ordem correta:** Database & Repository → Form Requests → Services → Controllers
- **Status atual:** 0% implementado
- **Prioridade:** Grupo 1 (Database & Repository) — primeiro

- **Fase 1:** Prompts 1.1 a 1.5
- **Fase 2:** Prompts 2.1 a 2.2
- **Fase 3:** Prompts 3.1 a 3.6
- **Fase 4:** Prompts 4.1 a 4.8

- **Critérios de sucesso por grupo:**
  - **Database & Repository:** estrutura, queries, eager loading e validações de dependência.
  - **Form Requests:** validação robusta com mensagens em português.
  - **Service:** transações, auditoria e imagens.
  - **Controller:** respostas corretas, validação e UX.

---

## 💾 Nome e uso

- Nome sugerido: `PATTERN_PROMPTS_MIGRACAO_MODULO.md`
- Como usar:
  - Substitua os placeholders.
  - Remova/adicione campos conforme o domínio.
  - Entregue este arquivo para a IA e peça: “Gere os prompts para o módulo X preenchendo este pattern.”

Quer que eu já gere uma versão preenchida para “Product” com seus campos exatos (sku, category_id, unit, etc.) pronta para salvar?
