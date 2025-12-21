# Arquitetura Refinada - Easy Budget Laravel

## 🎯 Visão Geral da Estrutura Refinada

Este documento apresenta a proposta de refinamento da arquitetura do Easy Budget Laravel, abrangendo todo o fluxo desde a interface até o controller, com foco em consistência, manutenibilidade e escalabilidade.

## 🏗️ Estrutura de Camadas

### **1. Interface de Contrato (TenantRepositoryInterface)**

```php
interface TenantRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Busca registros com filtros avançados específicos do tenant.
     */
    public function getAllByTenant(
        array $criteria = [],
        ?array $orderBy = null,
        ?int $limit = null,
        ?int $offset = null,
    ): Collection;

    /**
     * Retorna registros paginados do tenant atual.
     */
    public function paginate(
        int $perPage = 10,
        array $filters = [],
        ?array $orderBy = null,
    ): LengthAwarePaginator;

    /**
     * Conta registros do tenant atual com filtros opcionais.
     */
    public function countByTenant(array $filters = []): int;

    /**
     * Busca registros por slug único dentro do tenant atual.
     */
    public function findByTenantAndSlug(string $slug): ?Model;

    /**
     * Busca registros por código único dentro do tenant atual.
     */
    public function findByTenantAndCode(string $code): ?Model;

    /**
     * Verifica se um valor de campo único já existe dentro do tenant atual.
     */
    public function isUniqueInTenant(string $field, mixed $value, ?int $excludeId = null): bool;

    /**
     * Busca registros por múltiplos IDs dentro do tenant atual.
     */
    public function findManyByTenant(array $ids): Collection;

    /**
     * Remove múltiplos registros por IDs dentro do tenant atual.
     */
    public function deleteManyByTenant(array $ids): int;
}
```

**Benefícios:**

-  **Padronização** de métodos para todos os repositórios tenant-scoped
-  **Documentação clara** das responsabilidades de cada método
-  **Tipagem forte** para melhor desenvolvimento e manutenção

### **2. Implementação Abstrata (AbstractTenantRepository)**

```php
abstract class AbstractTenantRepository implements BaseRepositoryInterface, TenantRepositoryInterface
{
    use RepositoryFiltersTrait;

    protected Model $model;

    public function __construct()
    {
        $this->model = $this->makeModel();
    }

    abstract protected function makeModel(): Model;

    // Métodos básicos do BaseRepositoryInterface
    public function find(int $id): ?Model
    {
        try {
            return $this->model->findOrFail($id);
        } catch (ModelNotFoundException $e) {
            return null;
        }
    }

    public function getAll(): Collection
    {
        return $this->model->all();
    }

    public function create(array $data): Model
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): ?Model
    {
        $model = $this->find($id);
        if (!$model) {
            return null;
        }
        $model->update($data);
        return $model->fresh();
    }

    public function delete(int $id): bool
    {
        $model = $this->find($id);
        if (!$model) {
            return false;
        }
        return $model->delete();
    }

    // Métodos específicos do TenantRepositoryInterface
    public function getAllByTenant(
        array $criteria = [],
        ?array $orderBy = null,
        ?int $limit = null,
        ?int $offset = null,
    ): Collection {
        $query = $this->model->newQuery();

        // Aplica filtros de tenant automaticamente via Global Scope
        $this->applyFilters($query, $criteria);

        // Aplica ordenação usando trait
        $this->applyOrderBy($query, $orderBy);

        // Aplica limite e offset
        if ($offset !== null) {
            $query->offset($offset);
        }
        if ($limit !== null) {
            $query->limit($limit);
        }

        return $query->get();
    }

    public function paginate(
        int $perPage = 10,
        array $filters = [],
        ?array $orderBy = null,
    ): LengthAwarePaginator {
        return $this->getPaginated($filters, $perPage, [], $orderBy);
    }

    public function countByTenant(array $filters = []): int
    {
        $query = $this->model->newQuery();
        $this->applyFilters($query, $filters);
        return $query->count();
    }

    public function findByTenantAndSlug(string $slug): ?Model
    {
        return $this->model->where('slug', $slug)->first();
    }

    public function findByTenantAndCode(string $code): ?Model
    {
        return $this->model->where('code', $code)->first();
    }

    public function isUniqueInTenant(string $field, mixed $value, ?int $excludeId = null): bool
    {
        $query = $this->model->where($field, $value);
        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }
        return $query->exists();
    }

    public function findManyByTenant(array $ids): Collection
    {
        return $this->model->whereIn('id', $ids)->get();
    }

    public function deleteManyByTenant(array $ids): int
    {
        return $this->model->whereIn('id', $ids)->delete();
    }

    // Método padrão de paginação avançada
    public function getPaginated(
        array $filters = [],
        int $perPage = 10,
        array $with = [],
        ?array $orderBy = null,
    ): LengthAwarePaginator {
        $query = $this->model->newQuery();

        // Eager loading paramétrico
        if (!empty($with)) {
            $query->with($with);
        }

        // Aplicar filtros avançados
        $this->applyFilters($query, $filters);

        // Aplicar filtro de soft delete se necessário
        $this->applySoftDeleteFilter($query, $filters);

        // Aplicar ordenação
        $this->applyOrderBy($query, $orderBy);

        // Per page dinâmico
        $effectivePerPage = $this->getEffectivePerPage($filters, $perPage);

        return $query->paginate($effectivePerPage);
    }
}
```

**Benefícios:**

-  **Implementação única** de funcionalidades comuns
-  **Trait de filtros** para reutilização de lógica
-  **Método getPaginated** padrão com funcionalidades avançadas
-  **Isolamento automático** por tenant via Global Scope

### **3. Repositório Especializado (CategoryRepository)**

```php
class CategoryRepository extends AbstractTenantRepository
{
    protected function makeModel(): Model
    {
        return new Category();
    }

    /**
     * Busca categoria por slug dentro do tenant.
     */
    public function findBySlugAndTenantId(string $slug, int $tenantId): ?Model
    {
        return $this->model
            ->where('slug', $slug)
            ->where('tenant_id', $tenantId)
            ->first();
    }

    /**
     * Verifica se slug existe dentro do tenant.
     */
    public function existsBySlugAndTenantId(string $slug, int $tenantId, ?int $excludeId = null): bool
    {
        $query = $this->model
            ->where('slug', $slug)
            ->where('tenant_id', $tenantId);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Lista categorias ativas do tenant.
     */
    public function listActiveByTenantId(int $tenantId, ?array $orderBy = null): Collection
    {
        $query = $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->where(function ($q) {
                // Incluir categorias sem parent OU com parent não deletado
                $q->whereNull('parent_id')
                    ->orWhereHas('parent', function ($parentQuery) {
                    $parentQuery->withoutTrashed();
                });
            });

        $this->applyOrderBy($query, $orderBy);
        return $query->get();
    }

    /**
     * Busca categorias ordenadas por nome dentro do tenant.
     */
    public function findOrderedByNameAndTenantId(int $tenantId, string $direction = 'asc'): Collection
    {
        return $this->getAllByTenant([], ['name' => $direction]);
    }

    /**
     * Conta categorias do tenant.
     */
    public function countByTenantId(int $tenantId): int
    {
        return $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->count();
    }

    /**
     * Conta categorias ativas do tenant.
     */
    public function countActiveByTenantId(int $tenantId): int
    {
        return $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->count();
    }

    /**
     * Obtém categorias recentes do tenant.
     */
    public function getRecentByTenantId(int $tenantId, int $limit = 10): Collection
    {
        return $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Verifica se slug existe (método requerido pelos testes).
     */
    public function existsBySlug(string $slug, ?int $tenantId = null, ?int $excludeId = null): bool
    {
        if ($tenantId === null) {
            return false;
        }
        return $this->existsBySlugAndTenantId($slug, $tenantId, $excludeId);
    }

    /**
     * {@inheritdoc}
     */
    public function getPaginated(
        array $filters = [],
        int $perPage = 15,
        array $with = [],
        ?array $orderBy = null,
    ): LengthAwarePaginator {
        $query = $this->model->query();
        $query->with($with);

        $this->applyAllCategoryFilters($query, $filters);

        // Ordenação hierárquica simplificada
        if (!$orderBy) {
            $query->orderByRaw('COALESCE((SELECT name FROM categories AS parent WHERE parent.id = categories.parent_id LIMIT 1), name), parent_id IS NULL DESC, name');
        } else {
            $this->applyOrderBy($query, $orderBy);
        }

        $effectivePerPage = $this->getEffectivePerPage($filters, $perPage);
        return $query->paginate($effectivePerPage);
    }

    /**
     * Aplica todos os filtros de categoria.
     */
    protected function applyAllCategoryFilters($query, array $filters): void
    {
        $this->applySearchFilter($query, $filters, 'name', 'slug');
        $this->applyOperatorFilter($query, $filters, 'name', 'name');
        $this->applyBooleanFilter($query, $filters, 'is_active', 'is_active');
        $this->applySoftDeleteFilter($query, $filters);
    }
}
```

**Benefícios:**

-  **Especialização** para necessidades específicas de categorias
-  **Ordenação hierárquica** para estrutura de categorias
-  **Filtros avançados** específicos para o domínio
-  **Métodos auxiliares** para operações comuns

### **4. Camada de Serviço (CategoryService)**

```php
class CategoryService extends AbstractBaseService
{
    private CategoryRepository $categoryRepository;

    public function __construct(CategoryRepository $repository)
    {
        parent::__construct($repository);
        $this->categoryRepository = $repository;
    }

    protected function getSupportedFilters(): array
    {
        return ['id', 'name', 'slug', 'is_active', 'parent_id', 'created_at', 'updated_at'];
    }

    /**
     * Gera slug único para o tenant.
     */
    public function generateUniqueSlug(string $name, int $tenantId, ?int $excludeId = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i = 1;

        while ($this->categoryRepository->existsBySlugAndTenantId($slug, $tenantId, $excludeId)) {
            $slug = $base . '-' . $i;
            $i++;
        }

        return $slug;
    }

    /**
     * Valida dados da categoria.
     */
    public function validate(array $data, bool $isUpdate = false): ServiceResult
    {
        $rules = Category::businessRules();
        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            $messages = implode(', ', $validator->errors()->all());
            return $this->error(OperationStatus::INVALID_DATA, $messages);
        }

        return $this->success($data);
    }

    /**
     * Lista categorias do tenant com filtros e paginação.
     */
    public function getCategories(array $filters = [], int $perPage = 10): ServiceResult
    {
        try {
            $tenantId = auth()->user()->tenant_id ?? null;

            if (!$tenantId) {
                return $this->error(OperationStatus::ERROR, 'Tenant não identificado');
            }

            // Normalizar filtros para formato aceito pelo repository
            $normalized = $this->normalizeFilters($filters);

            // Usar o método específico do CategoryRepository
            $paginator = $this->categoryRepository->getPaginated(
                $normalized,
                $perPage,
                ['parent'], // Carregar relacionamento `parent` para exibição
                null // Permitir que o repositório aplique a ordenação hierárquica padrão
            );

            return $this->success($paginator, 'Categorias carregadas com sucesso.');
        } catch (Exception $e) {
            return $this->error(OperationStatus::ERROR, 'Erro ao carregar categorias: ' . $e->getMessage(), null, $e);
        }
    }

    /**
     * Normaliza filtros do request para formato aceito pelo repository.
     */
    private function normalizeFilters(array $filters): array
    {
        $normalized = [];

        // Verificar se o parâmetro 'all' está presente
        if (isset($filters['all'])) {
            $normalized['all'] = (bool) $filters['all'];
        }

        // Filtro por status ativo
        if (array_key_exists('active', $filters)) {
            if ($filters['active'] === null || $filters['active'] === '') {
                // Não filtra por ativo/inativo
            } elseif ((string) $filters['active'] === '0' || $filters['active'] === 0) {
                $normalized['is_active'] = false;
            } else {
                $normalized['is_active'] = (string) $filters['active'] === '1' || $filters['active'] === 1;
            }
        }

        // Filtro por nome
        if (array_key_exists('name', $filters) && $filters['name'] !== null && $filters['name'] !== '') {
            $normalized['name'] = ['operator' => 'like', 'value' => '%' . $filters['name'] . '%'];
        }

        // Filtro por slug
        if (array_key_exists('slug', $filters) && $filters['slug'] !== null && $filters['slug'] !== '') {
            $normalized['slug'] = ['operator' => 'like', 'value' => '%' . $filters['slug'] . '%'];
        }

        // Filtro de busca geral
        if (array_key_exists('search', $filters) && $filters['search'] !== null && $filters['search'] !== '') {
            $normalized['search'] = '%' . $filters['search'] . '%';
        }

        // Filtro de deletados
        if (array_key_exists('deleted', $filters)) {
            if ($filters['deleted'] === 'only' || $filters['deleted'] === '1') {
                $normalized['deleted'] = 'only';
            } elseif ($filters['deleted'] === 'current' || $filters['deleted'] === '0') {
                $normalized['deleted'] = 'current';
            } else {
                // null, vazio ou qualquer outro valor: default (todos)
                $normalized['deleted'] = '';
            }
        }

        return $normalized;
    }

    /**
     * Cria nova categoria para o tenant.
     */
    public function createCategory(array $data): ServiceResult
    {
        try {
            $tenantId = auth()->user()->tenant_id ?? null;

            if (!$tenantId) {
                return $this->error(OperationStatus::ERROR, 'Tenant não identificado');
            }

            return DB::transaction(function () use ($data, $tenantId) {
                // Gerar slug único se não fornecido
                if (!isset($data['slug']) || empty($data['slug'])) {
                    $data['slug'] = $this->generateUniqueSlug($data['name'], $tenantId);
                }

                // Validar slug único
                if (!Category::validateUniqueSlug($data['slug'], $tenantId)) {
                    return ServiceResult::error(
                        OperationStatus::INVALID_DATA,
                        'Slug já existe neste tenant',
                        null,
                        new Exception('Slug duplicado'),
                    );
                }

                // Validar parent_id se fornecido
                if (isset($data['parent_id']) && $data['parent_id']) {
                    $parentCategory = Category::find($data['parent_id']);
                    if (!$parentCategory || $parentCategory->tenant_id !== $tenantId) {
                        return $this->error(OperationStatus::INVALID_DATA, 'Categoria pai inválida');
                    }

                    // Verificar referência circular
                    $tempCategory = new Category([
                        'tenant_id' => $tenantId,
                        'parent_id' => $data['parent_id']
                    ]);

                    if ($tempCategory->wouldCreateCircularReference((int) $data['parent_id'])) {
                        return $this->error(OperationStatus::INVALID_DATA, 'Não é possível criar referência circular');
                    }
                }

                // Criar categoria
                $category = Category::create([
                    'tenant_id' => $tenantId,
                    'slug' => $data['slug'],
                    'name' => $data['name'],
                    'parent_id' => $data['parent_id'] ?? null,
                    'is_active' => $data['is_active'] ?? true,
                ]);

                return ServiceResult::success($category, 'Categoria criada com sucesso');
            });
        } catch (Exception $e) {
            return ServiceResult::error(OperationStatus::ERROR, 'Erro ao criar categoria: ' . $e->getMessage(), null, $e);
        }
    }

    /**
     * Atualiza categoria.
     */
    public function updateCategory(int $id, array $data): ServiceResult
    {
        try {
            $categoryResult = $this->findById($id);
            if ($categoryResult->isError()) {
                return $categoryResult;
            }

            $category = $categoryResult->getData();
            $tenantId = auth()->user()->tenant_id ?? null;

            // Verificar se categoria pertence ao tenant atual
            if ($category->tenant_id !== $tenantId) {
                return $this->error(OperationStatus::UNAUTHORIZED, 'Categoria não pertence ao tenant atual');
            }

            // Se o nome foi alterado e slug não foi fornecido, gerar novo slug
            if (isset($data['name']) && empty($data['slug'])) {
                $data['slug'] = $this->generateUniqueSlug($data['name'], $tenantId, $id);
            }

            // Validar slug único
            if (isset($data['slug']) && !Category::validateUniqueSlug($data['slug'], $tenantId, $id)) {
                return ServiceResult::error(
                    OperationStatus::INVALID_DATA,
                    'Slug já existe neste tenant',
                    null,
                    new Exception('Slug duplicado'),
                );
            }

            // Validar parent_id se fornecido
            if (isset($data['parent_id']) && $data['parent_id']) {
                if ($data['parent_id'] == $id) {
                    return $this->error(OperationStatus::INVALID_DATA, 'Categoria não pode ser pai de si mesma');
                }

                $parentCategory = Category::find($data['parent_id']);
                if (!$parentCategory || $parentCategory->tenant_id !== $tenantId) {
                    return $this->error(OperationStatus::INVALID_DATA, 'Categoria pai inválida');
                }

                // Verificar referência circular
                if ($category->wouldCreateCircularReference((int) $data['parent_id'])) {
                    return $this->error(OperationStatus::INVALID_DATA, 'Não é possível criar referência circular');
                }
            }

            return $this->update($id, $data);
        } catch (Exception $e) {
            return ServiceResult::error(OperationStatus::ERROR, 'Erro ao atualizar categoria: ' . $e->getMessage(), null, $e);
        }
    }

    /**
     * Remove categoria.
     */
    public function deleteCategory(int $id): ServiceResult
    {
        $categoryResult = $this->findById($id);
        if ($categoryResult->isError()) {
            return $categoryResult;
        }

        /** @var Category $category */
        $category = $categoryResult->getData();
        $tenantId = auth()->user()->tenant_id ?? null;

        // Verificar se categoria pertence ao tenant atual
        if ($category->tenant_id !== $tenantId) {
            return $this->error(OperationStatus::UNAUTHORIZED, 'Categoria não pertence ao tenant atual');
        }

        // Verificar se categoria tem filhos
        if ($category->hasChildren()) {
            return $this->error(OperationStatus::INVALID_DATA, 'Não é possível excluir categoria que possui subcategorias');
        }

        return $this->delete($id);
    }
}
```

**Benefícios:**

-  **Validação centralizada** de regras de negócio
-  **Transações** para operações complexas
-  **ServiceResult padronizado** para tratamento consistente
-  **Validação de pertencimento** ao tenant em todas as operações

### **5. Controller (CategoryController)**

```php
class CategoryController extends Controller
{
    public function __construct(
        private CategoryRepository $repository,
        private CategoryService $categoryService,
    ) {}

    /**
     * Dashboard de categorias com estatísticas.
     */
    public function dashboard(): View
    {
        $result = $this->categoryService->getDashboardData();

        if (!$result->isSuccess()) {
            return view('pages.category.dashboard', [
                'stats' => [],
                'error' => $result->getMessage(),
            ]);
        }

        return view('pages.category.dashboard', [
            'stats' => $result->getData(),
        ]);
    }

    /**
     * Lista categorias com filtros e paginação.
     */
    public function index(Request $request): View
    {
        if (!$request->hasAny(['search', 'active', 'per_page', 'deleted', 'all'])) {
            return view('pages.category.index', [
                'categories' => collect(),
                'filters' => [],
                'parent_categories' => collect(),
            ]);
        }

        $filters = $request->only(['search', 'active', 'per_page', 'deleted']);
        $perPage = (int) ($filters['per_page'] ?? 10);
        $allowedPerPage = [10, 20, 50];
        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 10;
        }
        $filters['per_page'] = $perPage;

        try {
            $result = $this->categoryService->getCategories($filters, $perPage);
            $categories = $result->isSuccess() ? $result->getData() : collect();

            if (method_exists($categories, 'appends')) {
                $categories = $categories->appends($request->query());
            }

            // Carregar categorias pai para filtros na view
            $parentResult = $this->categoryService->getParentCategories();
            $parentCategories = $parentResult->isSuccess() ? $parentResult->getData() : collect();

            return view('pages.category.index', [
                'categories' => $categories,
                'filters' => $filters,
                'parent_categories' => $parentCategories,
            ]);
        } catch (\Exception) {
            abort(500, 'Erro ao carregar categorias');
        }
    }

    /**
     * Form para criar categoria.
     */
    public function create(): View
    {
        /** @var User $user */
        $user = auth()->user();
        $tenantId = $user->tenant_id ?? null;

        if (!$tenantId) {
            return redirect()->route('categories.index')->with('error', 'Tenant não identificado');
        }

        $parents = Category::query()
            ->where('tenant_id', $tenantId)
            ->whereNull('parent_id')
            ->whereNull('deleted_at')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $defaults = ['is_active' => true];

        return view('pages.category.create', compact('parents', 'defaults'));
    }

    /**
     * Persiste nova categoria.
     */
    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $data = $request->validated();
        if (isset($data['name'])) {
            $data['name'] = mb_convert_case($data['name'], MB_CASE_TITLE, 'UTF-8');
        }

        $result = $this->categoryService->createCategory($data);

        if ($result->isError()) {
            // Converter ServiceResult errors em validation errors para campos específicos
            $message = $result->getMessage();

            // Se for erro de slug duplicado, adicionar erro de validação específico
            if (strpos($message, 'Slug já existe neste tenant') !== false) {
                return back()
                    ->withErrors(['slug' => 'Este slug já está em uso nesta empresa. Escolha outro slug.'])
                    ->withInput();
            }

            return back()->with('error', $message)->withInput();
        }

        $category = $result->getData();
        $this->logOperation('categories_store', ['id' => $category->id, 'name' => $category->name]);

        return redirect()
            ->route('categories.create')
            ->with('success', 'Categoria criada com sucesso! Você pode cadastrar outra categoria agora.');
    }

    /**
     * Mostra detalhes da categoria por slug.
     */
    public function show(string $slug): View
    {
        $result = $this->categoryService->findBySlug($slug);
        if ($result->isError()) {
            abort(404);
        }

        $category = $result->getData();
        $category->load('parent');

        return view('pages.category.show', compact('category'));
    }

    /**
     * Form para editar categoria.
     */
    public function edit(string $slug): View
    {
        $result = $this->categoryService->findBySlug($slug);
        if ($result->isError()) {
            abort(404);
        }

        $category = $result->getData();
        /** @var User $user */
        $user = auth()->user();
        $tenantId = $user->tenant_id ?? null;

        if (!$tenantId || $category->tenant_id !== $tenantId) {
            return redirect()->route('categories.index')->with('error', 'Categoria não encontrada');
        }

        $parents = Category::query()
            ->where('tenant_id', $tenantId)
            ->whereNull('parent_id')
            ->whereNull('deleted_at')
            ->where('id', '!=', $category->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $canDeactivate = !($category->hasChildren());

        return view('pages.category.edit', compact('category', 'parents', 'canDeactivate'));
    }

    /**
     * Atualiza categoria.
     */
    public function update(UpdateCategoryRequest $request, string $slug): RedirectResponse
    {
        $result = $this->categoryService->findBySlug($slug);
        if ($result->isError()) {
            abort(404);
        }

        $category = $result->getData();
        $data = $request->validated();
        if (isset($data['name'])) {
            $data['name'] = mb_convert_case($data['name'], MB_CASE_TITLE, 'UTF-8');
        }

        $result = $this->categoryService->updateCategory($category->id, $data);

        if ($result->isError()) {
            $message = $result->getMessage();

            // Se for erro de referência circular ou validação específica de campo, usar withErrors
            if (
                strpos($message, 'referência circular') !== false ||
                strpos($message, 'Categoria não pode ser pai de si mesma') !== false ||
                strpos($message, 'Categoria pai inválida') !== false
            ) {
                return back()
                    ->withErrors(['parent_id' => $message])
                    ->withInput();
            }

            return redirect()->back()->with('error', $message)->withInput();
        }

        $this->logOperation('categories_update', ['id' => $category->id, 'name' => $category->name]);

        return $this->redirectSuccess('categories.index', 'Categoria atualizada com sucesso.');
    }

    /**
     * Exclui categoria.
     */
    public function destroy(string $slug): RedirectResponse
    {
        $result = $this->categoryService->findBySlug($slug);
        if ($result->isError()) {
            abort(404);
        }

        $category = $result->getData();

        $result = $this->categoryService->deleteCategory($category->id);

        if ($result->isError()) {
            return $this->redirectError('categories.index', $result->getMessage());
        }

        $this->logOperation('categories_destroy', ['id' => $category->id, 'slug' => $slug]);

        return $this->redirectSuccess('categories.index', 'Categoria excluída com sucesso.');
    }

    /**
     * Alterna status ativo/inativo da categoria.
     */
    public function toggle_status(string $slug): RedirectResponse
    {
        $result = $this->categoryService->findBySlug($slug);
        if ($result->isError()) {
            abort(404);
        }

        $category = $result->getData();
        /** @var User $user */
        $user = auth()->user();
        $tenantId = $user->tenant_id ?? null;

        // Verificar se categoria pertence ao tenant atual
        if (!$tenantId || $category->tenant_id !== $tenantId) {
            return $this->redirectError('categories.index', 'Categoria não encontrada');
        }

        // Alternar status
        $category->is_active = !$category->is_active;
        $category->save();

        $statusText = $category->is_active ? 'ativada' : 'desativada';
        $this->logOperation('categories_toggle_status', [
            'id' => $category->id,
            'name' => $category->name,
            'new_status' => $category->is_active ? 'active' : 'inactive'
        ]);

        return $this->redirectSuccess('categories.index', "Categoria {$statusText} com sucesso.");
    }

    /**
     * Restaura categoria deletada (soft delete).
     */
    public function restore(string $slug): RedirectResponse
    {
        /** @var User $user */
        $user = auth()->user();
        $tenantId = $user->tenant_id ?? null;

        if (!$tenantId) {
            return $this->redirectError('categories.index', 'Tenant não identificado');
        }

        $category = Category::onlyTrashed()
            ->where('tenant_id', $tenantId)
            ->where('slug', $slug)
            ->firstOrFail();

        $category->restore();

        $this->logOperation('categories_restore', ['slug' => $slug, 'name' => $category->name]);

        return $this->redirectSuccess('categories.index', 'Categoria restaurada com sucesso!');
    }

    /**
     * Exporta categorias em xlsx, csv ou pdf.
     */
    public function export(Request $request): BinaryFileResponse
    {
        $format = $request->get('format', 'xlsx');

        $fileName = match ($format) {
            'csv' => 'categories.csv',
            'xlsx' => 'categories.xlsx',
            'pdf' => 'categories.pdf',
            default => 'categories.xlsx',
        };

        /** @var User $user */
        $user = auth()->user();
        $tenantId = $user->tenant_id ?? null;

        if (!$tenantId) {
            return redirect()->route('categories.index')->with('error', 'Tenant não identificado');
        }

        $search = trim((string) $request->get('search', ''));
        $active = $request->get('active');

        $query = Category::query()
            ->where('tenant_id', $tenantId)
            ->with('parent');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhereHas('parent', function ($p) use ($search) {
                        $p->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if (in_array($active, ['0', '1'], true)) {
            $query->where('is_active', $active === '1');
        }

        $categories = $query->orderBy('name')->get();

        $collator = class_exists(Collator::class) ? new Collator('pt_BR') : null;
        $categories = $categories->sort(function ($a, $b) use ($collator) {
            if ($collator) {
                return $collator->compare($a->name, $b->name);
            }
            return strcasecmp($a->name, $b->name);
        })->values();

        if ($format === 'pdf') {
            $rows = '';
            foreach ($categories as $category) {
                $createdAt = $category->created_at instanceof \DateTimeInterface ? $category->created_at->format('d/m/Y H:i:s') : '';
                $updatedAt = $category->updated_at instanceof \DateTimeInterface ? $category->updated_at->format('d/m/Y H:i:s') : '';
                $slugVal = $category->slug ?: Str::slug($category->name);
                $childrenCount = $category->children()->where('is_active', true)->count();
                $categoryName = $category->parent_id ? $category->parent->name : $category->name;
                $subcategoryName = $category->parent_id ? $category->name : '—';
                $rows .= '<tr>'
                    . '<td>' . e($categoryName) . '</td>'
                    . '<td>' . e($subcategoryName) . '</td>'
                    . '<td>' . e($slugVal) . '</td>'
                    . '<td>' . ($category->is_active ? 'Sim' : 'Não') . '</td>'
                    . '<td class="text-center">' . $childrenCount . '</td>'
                    . '<td>' . e($createdAt) . '</td>'
                    . '<td>' . e($updatedAt) . '</td>'
                    . '</tr>';
            }

            $thead = '<thead><tr><th>Categoria</th><th>Subcategoria</th><th>Slug</th><th>Ativo</th><th style="text-align:center">Subcategorias Ativas</th><th>Data Criação</th><th>Data Atualização</th></tr></thead>';
            $html = '<html><head><meta charset="utf-8"><style>table{border-collapse:collapse;width:100%;font-size:12px}th,td{border:1px solid #ddd;padding:6px;text-align:left}th{background:#f5f5f5}.text-center{text-align:center}</style></head><body>'
                . '<h3>Categorias</h3>'
                . '<table>'
                . $thead
                . '<tbody>' . $rows . '</tbody>'
                . '</table>'
                . '</body></html>';

            return response()->streamDownload(function () use ($html) {
                $mpdf = new Mpdf();
                $mpdf->WriteHTML($html);
                echo $mpdf->Output('', 'S');
            }, $fileName, [
                'Content-Type' => 'application/pdf',
            ]);
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $headers = ['Categoria', 'Subcategoria', 'Slug', 'Ativo', 'Subcategorias Ativas', 'Data Criação', 'Data Atualização'];
        $sheet->fromArray([$headers]);

        // Centralizar coluna "Subcategorias Ativas"
        $subCatCol = 'E';
        $sheet->getStyle($subCatCol . '1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $row = 2;
        foreach ($categories as $category) {
            $createdAt = $category->created_at instanceof \DateTimeInterface ? $category->created_at->format('d/m/Y H:i:s') : '';
            $updatedAt = $category->updated_at instanceof \DateTimeInterface ? $category->updated_at->format('d/m/Y H:i:s') : '';
            $childrenCount = $category->children()->where('is_active', true)->count();
            $categoryName = $category->parent_id ? $category->parent->name : $category->name;
            $subcategoryName = $category->parent_id ? $category->name : '—';
            $dataRow = [
                $categoryName,
                $subcategoryName,
                ($category->slug ?: Str::slug($category->name)),
                $category->is_active ? 'Sim' : 'Não',
                $childrenCount,
                $createdAt,
                $updatedAt,
            ];
            $sheet->fromArray([$dataRow], null, 'A' . $row);

            // Centralizar valor da coluna "Subcategorias Ativas"
            $subCatCol = 'E';
            $sheet->getStyle($subCatCol . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

            $row++;
        }

        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $contentType = $format === 'csv' ? 'text/csv' : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
        return response()->streamDownload(function () use ($spreadsheet, $format) {
            if ($format === 'csv') {
                $writer = new Csv($spreadsheet);
            } else {
                $writer = new Xlsx($spreadsheet);
            }
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => $contentType,
        ]);
    }
}
```

**Benefícios:**

-  **Controller enxuto** com foco na camada de apresentação
-  **ServiceResult tratado** consistentemente
-  **Validação de autorização** em todas as operações
-  **Exportação avançada** com múltiplos formatos

## 🎯 Benefícios da Arquitetura Refinada

### **1. Consistência e Padrões**

-  **Interface única** para todos os repositórios tenant-scoped
-  **Implementação padrão** no AbstractTenantRepository
-  **ServiceResult padronizado** em toda a camada de serviço

### **2. Performance Otimizada**

-  **Eager loading paramétrico** no método getPaginated
-  **Filtros avançados** com suporte a múltiplos operadores
-  **Ordenação hierárquica** para categorias

### **3. Segurança e Isolamento**

-  **TenantScoped trait** garantindo isolamento automático
-  **Validação de pertencimento** ao tenant em todas as operações
-  **Tratamento de erros** consistente e informativo

### **4. Manutenibilidade**

-  **Código reutilizável** através de traits e classes abstratas
-  **Documentação clara** com exemplos e casos de uso
-  **Testabilidade** facilitada com interfaces bem definidas

### **5. Escalabilidade**

-  **Arquitetura preparada** para novos módulos
-  **Padrões consistentes** para fácil adoção por novos desenvolvedores
-  **Extensibilidade** através de herança e composição

## 🔄 Fluxo de Operações

```
Interface (TenantRepositoryInterface)
    ↓
Abstract (AbstractTenantRepository)
    ↓
Repository (CategoryRepository)
    ↓
Service (CategoryService)
    ↓
Controller (CategoryController)
    ↓
View (Blade Templates)
```

## 📊 Comparação Antes vs Depois

| Aspecto              | Antes                            | Depois                                          |
| -------------------- | -------------------------------- | ----------------------------------------------- |
| **Consistência**     | Inconsistente entre módulos      | Padrão único em toda a aplicação                |
| **Performance**      | Consultas N+1, sem eager loading | Eager loading paramétrico, consultas otimizadas |
| **Segurança**        | Validações espalhadas            | Isolamento automático por tenant                |
| **Manutenibilidade** | Código duplicado                 | Reutilização através de traits e herança        |
| **Testabilidade**    | Difícil de testar                | Interfaces bem definidas, fácil de mockar       |
| **Escalabilidade**   | Arquitetura rígida               | Extensível e flexível                           |

## 🚀 Próximos Passos

1. **Implementar** a estrutura refinada em outros módulos
2. **Criar** testes unitários e de integração
3. **Documentar** padrões de desenvolvimento
4. **Treinar** equipe na nova arquitetura
5. **Monitorar** performance e métricas de código

Esta arquitetura refinada cria um sistema robusto, consistente e fácil de manter, onde cada camada tem responsabilidades bem definidas e todas as operações respeitam o contexto multi-tenant do sistema.
