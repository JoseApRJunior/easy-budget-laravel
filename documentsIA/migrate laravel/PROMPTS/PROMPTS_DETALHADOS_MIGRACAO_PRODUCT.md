# 🎯 Prompts Detalhados - Migração Módulo de Produtos (Ordem Correta)

## 📋 CONTEXTO

**Base:** Análise completa em `RELATORIO_ANALISE_PRODUCT_CONTROLLER.md`
**Status:** 0% implementado
**Objetivo:** Implementar o módulo de produtos completo, seguindo a arquitetura moderna do novo sistema, com base na análise do `ProductController` do sistema legado.
**Ordem:** Sequência lógica seguindo dependências técnicas (Database → Repository → Form Requests → Service → Controller).

---

# 🎯 GRUPO 1: DATABASE & REPOSITORY (Base de Dados) - **PRIMEIRO**

## 🎯 PROMPT 1.1: Atualizar Migration, Model e Factory

Implemente APENAS a atualização da Migration, Model e Factory para o módulo de produtos:

TAREFA ESPECÍFICA:

-  **Migration:** **Atualizar** o schema inicial (`..._create_initial_schema.php`) para adicionar os campos `category_id`, `sku`, `unit` e `softDeletes` à tabela `products`. O campo `code` será substituído por `sku`.
-  **Model:** **Atualizar** `Product.php` para incluir os novos campos, relacionamentos e casts.
-  **Factory:** **Atualizar** `ProductFactory.php` para gerar dados para os novos campos.

IMPLEMENTAÇÃO:

```php
1. Migration (Alterar em `..._create_initial_schema.php`):

   // Dentro da migration inicial, na criação da tabela 'products'
   Schema::create('products', function (Blueprint $table) {
       $table->id();
       $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
       $table->foreignId('category_id')->nullable()->constrained('categories')->onDelete('set null'); // ADICIONAR
       $table->string('name');
       $table->text('description')->nullable();
       $table->string('sku')->nullable(); // SUBSTITUIR 'code' por 'sku'
       $table->decimal('price', 10, 2)->default(0);
       $table->string('unit', 20)->nullable()->comment('Ex: un, m², h'); // ADICIONAR
       $table->boolean('active')->default(true);
       $table->string('image')->nullable();
       $table->timestamps();
       $table->softDeletes(); // ADICIONAR

       $table->unique(['tenant_id', 'sku']); // ATUALIZAR para 'sku'
   });

2. Model (`app/Models/Product.php`):

   class Product extends Model
   {
       use HasFactory, SoftDeletes, TenantScoped;

       protected $fillable = [
           'tenant_id', 'category_id', 'name', 'description', 'sku', 'price', 'unit', 'active', 'image'
       ];

       protected $casts = [
           'price' => 'decimal:2',
           'active' => 'boolean',
       ];

       public function category(): BelongsTo
       {
           return $this->belongsTo(Category::class);
       }

       public function serviceItems(): HasMany
       {
           return $this->hasMany(ServiceItem::class);
       }

       public function scopeActive(Builder $query): Builder
       {
           return $query->where('active', true);
       }
   }

3. Factory (`database/factories/ProductFactory.php`):
   public function definition(): array
   {
       return [
           'tenant_id' => Tenant::factory(),
           'category_id' => null, // Pode ser definido com um state
           'name' => $this->faker->commerce->productName(),
           'description' => $this->faker->sentence,
           'sku' => $this->faker->unique()->ean8,
           'price' => $this->faker->randomFloat(2, 10, 500),
           'unit' => $this->faker->randomElement(['un', 'h', 'm²']),
           'active' => true,
           'image' => null,
       ];
   }
```

ARQUIVOS:

-  `database/migrations/..._create_initial_schema.php` (**alterar**)
-  `app/Models/Product.php` (**alterar**)
-  `database/factories/ProductFactory.php` (**alterar**)

CRITÉRIO DE SUCESSO: Estrutura de banco de dados e modelo Eloquent atualizados e funcionais.

---

## 🎯 PROMPT 1.2: Implementar ProductRepository - getPaginated()

Implemente APENAS o método `getPaginated()` no `ProductRepository`:

TAREFA ESPECÍFICA:

-  **Abstração:** Isolar as queries do banco de dados.
-  **Filtragem:** Implementar `getPaginated()` com filtros avançados e paginação.
-  **Tenant Scoping:** Garantir isolamento automático de dados via `AbstractTenantRepository`.
-  **Eager Loading:** Carregar relacionamento `category` para otimização.

IMPLEMENTAÇÃO:

```php
namespace App\Repositories;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

class ProductRepository extends AbstractTenantRepository
{
    public function __construct(Product $model)
    {
        parent::__construct($model);
    }

    public function getPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->newQuery()->with('category');

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('sku', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('description', 'like', '%' . $filters['search'] . '%'); // Adicionado description
            });
        }

        if (isset($filters['active']) && $filters['active'] !== '') {
            $query->where('active', (bool)$filters['active']);
        }

        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (!empty($filters['min_price'])) {
            $query->where('price', '>=', $filters['min_price']);
        }

        if (!empty($filters['max_price'])) {
            $query->where('price', '<=', $filters['max_price']);
        }

        return $query->orderBy('name', 'asc')->paginate($perPage);
    }
}
```

ARQUIVOS:

-  `app/Repositories/ProductRepository.php` (método `getPaginated`)

CRITÉRIO DE SUCESSO: Repository com paginação e filtros funcionais.

---

## 🎯 PROMPT 1.3: Implementar ProductRepository - findBySku()

Implemente APENAS o método `findBySku()` no `ProductRepository`:

TAREFA ESPECÍFICA:

-  Busca: Por SKU (string)
-  Eager loading: Relacionamentos opcionais
-  Tenant scoping: Automático via `AbstractTenantRepository`

IMPLEMENTAÇÃO:

```php
// Dentro de app/Repositories/ProductRepository.php

public function findBySku(string $sku, array $with = []): ?Model
{
    $query = $this->model->where('sku', $sku);

    if (!empty($with)) {
        $query->with($with);
    }

    return $query->first();
}
```

ARQUIVOS:

-  `app/Repositories/ProductRepository.php` (método `findBySku`)

CRITÉRIO DE SUCESSO: Repository com busca por SKU.

---

## 🎯 PROMPT 1.4: Implementar ProductRepository - countActive()

Implemente APENAS o método `countActive()` no `ProductRepository`:

TAREFA ESPECÍFICA:

-  Contagem: Produtos ativos dentro do tenant
-  Return: Inteiro com a contagem
-  Performance: Query otimizada

IMPLEMENTAÇÃO:

```php
// Dentro de app/Repositories/ProductRepository.php

public function countActive(): int
{
    return $this->model->where('active', true)->count();
}
```

ARQUIVOS:

-  `app/Repositories/ProductRepository.php` (método `countActive`)

CRITÉRIO DE SUCESSO: Repository com métrica de produtos ativos.

---

## 🎯 PROMPT 1.5: Implementar ProductRepository - canBeDeactivatedOrDeleted()

Implemente APENAS o método `canBeDeactivatedOrDeleted()` no `ProductRepository`:

TAREFA ESPECÍFICA:

-  Verificação: Se o produto pode ser desativado ou deletado
-  Regra: Não pode ser desativado/deletado se estiver em `service_items`
-  Return: Booleano

IMPLEMENTAÇÃO:

```php
// Dentro de app/Repositories/ProductRepository.php

public function canBeDeactivatedOrDeleted(int $productId): bool
{
    // Verifica se o produto está associado a algum service_item
    return !$this->model->where('id', $productId)->has('serviceItems')->exists();
}
```

ARQUIVOS:

-  `app/Repositories/ProductRepository.php` (método `canBeDeactivatedOrDeleted`)

CRITÉRIO DE SUCESSO: Validação de desativação/exclusão de produto.

---

# 🎯 GRUPO 2: FORM REQUESTS (Validação) - **SEGUNDO**

## 🎯 PROMPT 2.1: Criar ProductStoreRequest - Validação de Criação

Crie APENAS o `ProductStoreRequest`:

TAREFA ESPECÍFICA:

-  Campos: `name`, `sku`, `price`, `category_id`, `unit`, `active`, `image`
-  Validação: `sku` único por tenant, `category_id` existe, `price` mínimo 0, `active` booleano.
-  Mensagens: Em português.

IMPLEMENTAÇÃO:

```php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'sku' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('products')->where(fn ($query) => $query->where('tenant_id', tenant()->id))
            ],
            'price' => 'required|numeric|min:0',
            'category_id' => 'nullable|integer|exists:categories,id',
            'unit' => 'nullable|string|max:20',
            'active' => 'boolean',
            'image' => 'nullable|image|max:2048' // 2MB max
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O nome do produto é obrigatório.',
            'sku.unique' => 'O SKU informado já está em uso por outro produto.',
            'price.required' => 'O preço é obrigatório.',
            'price.numeric' => 'O preço deve ser um valor numérico.',
            'price.min' => 'O preço deve ser no mínimo 0.',
            'category_id.exists' => 'A categoria selecionada é inválida.',
            'image.image' => 'O arquivo deve ser uma imagem.',
            'image.max' => 'A imagem não pode ter mais de 2MB.'
        ];
    }
}
```

ARQUIVOS:

-  `app/Http/Requests/ProductStoreRequest.php` (criar)

CRITÉRIO DE SUCESSO: Validação robusta para criação de produto com mensagens em português.

---

## 🎯 PROMPT 2.2: Criar ProductUpdateRequest - Validação de Edição

Crie APENAS o `ProductUpdateRequest`:

TAREFA ESPECÍFICA:

-  Campos: `name`, `sku`, `price`, `category_id`, `unit`, `active`, `image` (todos opcionais para atualização parcial)
-  Validação: `sku` único por tenant (ignorando o produto atual), `category_id` existe, `price` mínimo 0, `active` booleano.
-  Mensagens: Em português.

IMPLEMENTAÇÃO:

```php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $productId = $this->route('product'); // Assume que a rota tem um parâmetro 'product' com o ID do produto

        return [
            'name' => 'sometimes|required|string|max:255',
            'sku' => [
                'sometimes',
                'nullable',
                'string',
                'max:50',
                Rule::unique('products')->ignore($productId)->where(fn ($query) => $query->where('tenant_id', tenant()->id))
            ],
            'price' => 'sometimes|required|numeric|min:0',
            'category_id' => 'sometimes|nullable|integer|exists:categories,id',
            'unit' => 'sometimes|nullable|string|max:20',
            'active' => 'sometimes|boolean',
            'image' => 'nullable|image|max:2048', // 2MB max
            'remove_image' => 'boolean' // Campo para indicar remoção de imagem existente
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O nome do produto é obrigatório.',
            'sku.unique' => 'O SKU informado já está em uso por outro produto.',
            'price.required' => 'O preço é obrigatório.',
            'price.numeric' => 'O preço deve ser um valor numérico.',
            'price.min' => 'O preço deve ser no mínimo 0.',
            'category_id.exists' => 'A categoria selecionada é inválida.',
            'image.image' => 'O arquivo deve ser uma imagem.',
            'image.max' => 'A imagem não pode ter mais de 2MB.'
        ];
    }
}
```

ARQUIVOS:

-  `app/Http/Requests/ProductUpdateRequest.php` (criar)

CRITÉRIO DE SUCESSO: Validação robusta para edição de produto com mensagens em português.

---

# 🎯 GRUPO 3: SERVICES (Lógica de Negócio) - **TERCEIRO**

## 🎯 PROMPT 3.1: Implementar ProductService - findBySku()

Implemente APENAS o método `findBySku()` no `ProductService`:

TAREFA ESPECÍFICA:

-  Busca: Por SKU (string)
-  Tenant scoping: Automático via `TenantScoped` (no Model)
-  Eager loading: Relacionamentos opcionais
-  Error handling: Produto não encontrado

IMPLEMENTAÇÃO:

```php
namespace App\Services\Domain;

use App\Enums\OperationStatus;
use App\Models\Product;
use App\Repositories\ProductRepository;
use App\Services\AbstractService;
use App\Services\ServiceResult;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductService extends AbstractService
{
    private ProductRepository $productRepository;

    public function __construct(ProductRepository $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    public function findBySku(string $sku, array $with = []): ServiceResult
    {
        try {
            $product = $this->productRepository->findBySku($sku, $with);

            if (!$product) {
                return $this->error(
                    OperationStatus::NOT_FOUND,
                    "Produto com SKU {$sku} não encontrado"
                );
            }

            return $this->success($product, 'Produto encontrado');

        } catch (Exception $e) {
            return $this->error(
                OperationStatus::ERROR,
                'Erro ao buscar produto',
                null,
                $e
            );
        }
    }
}
```

ARQUIVOS:

-  `app/Services/Domain/ProductService.php` (método `findBySku`)

CRITÉRIO DE SUCESSO: Busca por SKU funcionando com eager loading opcional.

---

## 🎯 PROMPT 3.2: Implementar ProductService - getFilteredProducts()

Implemente APENAS o método `getFilteredProducts()` no `ProductService`:

TAREFA ESPECÍFICA:

-  Filtros: `search` (nome, SKU, descrição), `active`, `category_id`, `min_price`, `max_price`
-  Paginação: 15 registros por página
-  Ordenação: Por nome (asc)
-  Eager loading: Relacionamento `category`

IMPLEMENTAÇÃO:

```php
// Dentro de app/Services/Domain/ProductService.php

public function getFilteredProducts(array $filters = [], array $with = []): ServiceResult
{
    try {
        $products = $this->productRepository->getPaginated($filters, 15);

        return $this->success($products, 'Produtos filtrados');

    } catch (Exception $e) {
        return $this->error(
            OperationStatus::ERROR,
            'Erro ao filtrar produtos',
            null,
            $e
        );
    }
}
```

ARQUIVOS:

-  `app/Services/Domain/ProductService.php` (método `getFilteredProducts`)

CRITÉRIO DE SUCESSO: Filtros funcionais com paginação.

---

## 🎯 PROMPT 3.3: Implementar ProductService - createProduct()

Implemente APENAS o método `createProduct()` no `ProductService`:

TAREFA ESPECÍFICA:

-  Geração: SKU único (se não fornecido)
-  Transaction: `DB::transaction` para atomicidade
-  Imagem: Upload e armazenamento (redimensionamento para 200px de largura)
-  Auditoria: Registrar criação

IMPLEMENTAÇÃO:

```php
// Dentro de app/Services/Domain/ProductService.php

public function createProduct(array $data): ServiceResult
{
    try {
        return DB::transaction(function () use ($data) {
            // Gerar SKU se não fornecido
            if (empty($data['sku'])) {
                $data['sku'] = $this->generateUniqueSku();
            }

            // Processar imagem
            if (isset($data['image'])) {
                $data['image'] = $this->uploadProductImage($data['image']);
            }

            $product = $this->productRepository->create($data);

            return $this->success($product, 'Produto criado com sucesso');
        });
    } catch (Exception $e) {
        return $this->error(
            OperationStatus::ERROR,
            'Erro ao criar produto',
            null,
            $e
        );
    }
}

private function generateUniqueSku(): string
{
    do {
        $sku = 'PROD' . str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
    } while ($this->productRepository->findBySku($sku));

    return $sku;
}

private function uploadProductImage($imageFile): ?string
{
    if (!$imageFile) {
        return null;
    }

    $path = 'products/' . tenant()->id;
    $filename = Str::random(40) . '.' . $imageFile->getClientOriginalExtension();

    // Redimensionar e salvar imagem
    // Usar uma biblioteca de imagem como Intervention Image ou similar
    // Por simplicidade, aqui apenas salva o arquivo original
    $imageFile->storePubliclyAs($path, $filename, 'public');

    return Storage::url($path . '/' . $filename);
}
```

ARQUIVOS:

-  `app/Services/Domain/ProductService.php` (métodos `createProduct`, `generateUniqueSku`, `uploadProductImage`)
-  `app/Repositories/ProductRepository.php` (método `create`)

CRITÉRIO DE SUCESSO: Produto criado com SKU único e imagem processada.

---

## 🎯 PROMPT 3.4: Implementar ProductService - updateProductBySku()

Implemente APENAS o método `updateProductBySku()` no `ProductService`:

TAREFA ESPECÍFICA:

-  Busca: Por SKU + validação de existência
-  Transaction: Atomicidade completa
-  Imagem: Gerenciar imagem (upload nova, remover existente)
-  Auditoria: Registrar atualização

IMPLEMENTAÇÃO:

```php
// Dentro de app/Services/Domain/ProductService.php

public function updateProductBySku(string $sku, array $data): ServiceResult
{
    try {
        return DB::transaction(function () use ($sku, $data) {
            $product = $this->productRepository->findBySku($sku);

            if (!$product) {
                return $this->error(
                    OperationStatus::NOT_FOUND,
                    "Produto com SKU {$sku} não encontrado"
                );
            }

            // Remover imagem existente se solicitado
            if (isset($data['remove_image']) && $data['remove_image'] && $product->image) {
                Storage::disk('public')->delete(Str::after($product->image, '/storage/'));
                $data['image'] = null;
            }

            // Processar nova imagem se fornecida
            if (isset($data['image']) && is_a($data['image'], 'Illuminate\Http\UploadedFile')) {
                // Deletar imagem antiga se existir
                if ($product->image) {
                    Storage::disk('public')->delete(Str::after($product->image, '/storage/'));
                }
                $data['image'] = $this->uploadProductImage($data['image']);
            } else if (isset($data['image']) && $data['image'] === null) {
                // Se a imagem foi explicitamente definida como null (e não foi removida pelo remove_image)
                // Isso pode acontecer se o campo de upload for limpo sem o checkbox de remover
                if ($product->image) {
                    Storage::disk('public')->delete(Str::after($product->image, '/storage/'));
                }
                $data['image'] = null;
            } else {
                // Manter imagem existente se não houver nova imagem e nem remoção solicitada
                unset($data['image']);
            }

            $product = $this->productRepository->update($product->id, $data);

            return $this->success($product, 'Produto atualizado com sucesso');
        });
    } catch (Exception $e) {
        return $this->error(
            OperationStatus::ERROR,
            'Erro ao atualizar produto',
            null,
            $e
        );
    }
}
```

ARQUIVOS:

-  `app/Services/Domain/ProductService.php` (método `updateProductBySku`)
-  `app/Repositories/ProductRepository.php` (método `update`)

CRITÉRIO DE SUCESSO: Produto atualizado com gerenciamento de imagem.

---

## 🎯 PROMPT 3.5: Implementar ProductService - toggleProductStatus()

Implemente APENAS o método `toggleProductStatus()` no `ProductService`:

TAREFA ESPECÍFICA:

-  Busca: Por SKU + validação de existência
-  Validação: Se o produto pode ter o status alterado (não pode se estiver em `service_items`)
-  Ação: Alternar o status `active` (true/false)
-  Auditoria: Registrar mudança

IMPLEMENTAÇÃO:

```php
// Dentro de app/Services/Domain/ProductService.php

public function toggleProductStatus(string $sku): ServiceResult
{
    try {
        return DB::transaction(function () use ($sku) {
            $product = $this->productRepository->findBySku($sku);

            if (!$product) {
                return $this->error(
                    OperationStatus::NOT_FOUND,
                    "Produto com SKU {$sku} não encontrado"
                );
            }

            if (!$this->productRepository->canBeDeactivatedOrDeleted($product->id)) {
                return $this->error(
                    OperationStatus::VALIDATION_ERROR,
                    'Produto não pode ser desativado/ativado pois está em uso em serviços.'
                );
            }

            $newStatus = !$product->active;
            $product = $this->productRepository->update($product->id, ['active' => $newStatus]);

            $message = $newStatus ? 'Produto ativado com sucesso' : 'Produto desativado com sucesso';
            return $this->success($product, $message);
        });
    } catch (Exception $e) {
        return $this->error(
            OperationStatus::ERROR,
            'Erro ao alterar status do produto',
            null,
            $e
        );
    }
}
```

ARQUIVOS:

-  `app/Services/Domain/ProductService.php` (método `toggleProductStatus`)
-  `app/Repositories/ProductRepository.php` (método `canBeDeactivatedOrDeleted`)

CRITÉRIO DE SUCESSO: Status do produto alternado com validação.

---

## 🎯 PROMPT 3.6: Implementar ProductService - deleteProductBySku()

Implemente APENAS o método `deleteProductBySku()` no `ProductService`:

TAREFA ESPECÍFICA:

-  Busca: Por SKU + validação de deletabilidade
-  Verificação: Relacionamentos que impedem exclusão (`service_items`)
-  Cascata: Deletar imagem física
-  Transaction: Atomicidade

IMPLEMENTAÇÃO:

```php
// Dentro de app/Services/Domain/ProductService.php

public function deleteProductBySku(string $sku): ServiceResult
{
    try {
        return DB::transaction(function () use ($sku) {
            $product = $this->productRepository->findBySku($sku);

            if (!$product) {
                return $this->error(
                    OperationStatus::NOT_FOUND,
                    "Produto com SKU {$sku} não encontrado"
                );
            }

            if (!$this->productRepository->canBeDeactivatedOrDeleted($product->id)) {
                return $this->error(
                    OperationStatus::VALIDATION_ERROR,
                    'Produto não pode ser excluído pois está em uso em serviços.'
                );
            }

            // Deletar imagem física se existir
            if ($product->image) {
                Storage::disk('public')->delete(Str::after($product->image, '/storage/'));
            }

            $this->productRepository->delete($product->id);

            return $this->success(null, 'Produto excluído com sucesso');
        });
    } catch (Exception $e) {
        return $this->error(
            OperationStatus::ERROR,
            'Erro ao excluir produto',
            null,
            $e
        );
    }
}
```

ARQUIVOS:

-  `app/Services/Domain/ProductService.php` (método `deleteProductBySku`)
-  `app/Repositories/ProductRepository.php` (método `delete`, `canBeDeactivatedOrDeleted`)

CRITÉRIO DE SUCESSO: Produto deletado com validação de dependências e imagem física.

---

# 🎯 GRUPO 4: CONTROLLERS (Interface HTTP) - **QUARTO**

## 🎯 PROMPT 4.1: Implementar index() - Lista de Produtos

Implemente APENAS o método `index()` no `ProductController`:

TAREFA ESPECÍFICA:

-  Método: `public function index(Request $request): View`
-  Filtros: `search`, `category_id`, `active`, `min_price`, `max_price`
-  Paginação: 15 registros por página
-  Eager loading: `category`

IMPLEMENTAÇÃO:

```php
namespace App\Http\Controllers;

use App\Http\Requests\ProductStoreRequest;
use App\Http\Requests\ProductUpdateRequest;
use App\Services\Domain\CategoryService;
use App\Services\Domain\ProductService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Exception;

class ProductController extends Controller
{
    private ProductService $productService;
    private CategoryService $categoryService;

    public function __construct(ProductService $productService, CategoryService $categoryService)
    {
        $this->productService = $productService;
        $this->categoryService = $categoryService;
    }

    public function index(Request $request): View
    {
        try {
            $filters = $request->only(['search', 'category_id', 'active', 'min_price', 'max_price']);

            $result = $this->productService->getFilteredProducts($filters, ['category']);

            if (!$result->isSuccess()) {
                abort(500, 'Erro ao carregar lista de produtos');
            }

            $products = $result->getData();

            return view('products.index', [
                'products' => $products,
                'filters' => $filters,
                'categories' => $this->categoryService->getActive()
            ]);

        } catch (Exception $e) {
            abort(500, 'Erro ao carregar produtos');
        }
    }
}
```

ARQUIVOS:

-  `app/Http/Controllers/ProductController.php` (método `index`)
-  `app/Services/Domain/ProductService.php` (método `getFilteredProducts`)
-  `resources/views/products/index.blade.php` (criar)

CRITÉRIO DE SUCESSO: Lista de produtos com filtros funcionais e paginação.

---

## 🎯 PROMPT 4.2: Implementar create() - Formulário de Criação

Implemente APENAS o método `create()` no `ProductController`:

TAREFA ESPECÍFICA:

-  Método: `public function create(): View`
-  Dados: Categorias ativas
-  View: `products.create`

IMPLEMENTAÇÃO:

```php
// Dentro de app/Http/Controllers/ProductController.php

public function create(): View
{
    try {
        return view('products.create', [
            'categories' => $this->categoryService->getActive()
        ]);
    } catch (Exception $e) {
        abort(500, 'Erro ao carregar formulário de criação de produto');
    }
}
```

ARQUIVOS:

-  `app/Http/Controllers/ProductController.php` (método `create`)
-  `resources/views/products/create.blade.php` (criar)

CRITÉRIO DE SUCESSO: Formulário de criação carregado com dados necessários.

---

## 🎯 PROMPT 4.3: Implementar store() - Criar Produto

Implemente APENAS o método `store()` no `ProductController`:

TAREFA ESPECÍFICA:

-  Método: `public function store(ProductStoreRequest $request): RedirectResponse`
-  Validação: `ProductStoreRequest`
-  Lógica: Chamar `ProductService::createProduct()`
-  Redirecionamento: Para `products.show` em caso de sucesso, `back` em caso de erro.

IMPLEMENTAÇÃO:

```php
// Dentro de app/Http/Controllers/ProductController.php

public function store(ProductStoreRequest $request): RedirectResponse
{
    try {
        $result = $this->productService->createProduct($request->validated());

        if (!$result->isSuccess()) {
            return redirect()->back()
                ->withInput()
                ->with('error', $result->getMessage());
        }

        $product = $result->getData();

        return redirect()->route('products.show', $product->sku)
            ->with('success', 'Produto criado com sucesso!');

    } catch (Exception $e) {
        return redirect()->back()
            ->withInput()
            ->with('error', 'Erro ao criar produto: ' . $e->getMessage());
    }
}
```

ARQUIVOS:

-  `app/Http/Controllers/ProductController.php` (método `store`)
-  `app/Services/Domain/ProductService.php` (método `createProduct`)

CRITÉRIO DE SUCESSO: Produto criado com sucesso e redirecionamento correto.

---

## 🎯 PROMPT 4.4: Implementar show() - Detalhes do Produto

Implemente APENAS o método `show()` no `ProductController`:

TAREFA ESPECÍFICA:

-  Método: `public function show(string $sku): View`
-  Busca: Por SKU com relacionamento `category`
-  View: `products.show`

IMPLEMENTAÇÃO:

```php
// Dentro de app/Http/Controllers/ProductController.php

public function show(string $sku): View
{
    try {
        $result = $this->productService->findBySku($sku, ['category']);

        if (!$result->isSuccess()) {
            abort(404, 'Produto não encontrado');
        }

        $product = $result->getData();

        return view('products.show', [
            'product' => $product
        ]);

    } catch (Exception $e) {
        abort(500, 'Erro ao carregar detalhes do produto');
    }
}
```

ARQUIVOS:

-  `app/Http/Controllers/ProductController.php` (método `show`)
-  `resources/views/products/show.blade.php` (criar)

CRITÉRIO DE SUCESSO: Detalhes completos do produto com relacionamentos.

---

## 🎯 PROMPT 4.5: Implementar edit() - Formulário de Edição

Implemente APENAS o método `edit()` no `ProductController`:

TAREFA ESPECÍFICA:

-  Método: `public function edit(string $sku): View`
-  Busca: Por SKU com relacionamento `category`
-  Dados: Categorias ativas
-  View: `products.edit`

IMPLEMENTAÇÃO:

```php
// Dentro de app/Http/Controllers/ProductController.php

public function edit(string $sku): View
{
    try {
        $result = $this->productService->findBySku($sku, ['category']);

        if (!$result->isSuccess()) {
            abort(404, 'Produto não encontrado');
        }

        $product = $result->getData();

        return view('products.edit', [
            'product' => $product,
            'categories' => $this->categoryService->getActive()
        ]);

    } catch (Exception $e) {
        abort(500, 'Erro ao carregar formulário de edição de produto');
    }
}
```

ARQUIVOS:

-  `app/Http/Controllers/ProductController.php` (método `edit`)
-  `resources/views/products/edit.blade.php` (criar)

CRITÉRIO DE SUCESSO: Formulário de edição carregado com dados do produto.

---

## 🎯 PROMPT 4.6: Implementar update() - Atualizar Produto

Implemente APENAS o método `update()` no `ProductController`:

TAREFA ESPECÍFICA:

-  Método: `public function update(string $sku, ProductUpdateRequest $request): RedirectResponse`
-  Validação: `ProductUpdateRequest`
-  Lógica: Chamar `ProductService::updateProductBySku()`
-  Redirecionamento: Para `products.show` em caso de sucesso, `back` em caso de erro.

IMPLEMENTAÇÃO:

```php
// Dentro de app/Http/Controllers/ProductController.php

public function update(string $sku, ProductUpdateRequest $request): RedirectResponse
{
    try {
        $result = $this->productService->updateProductBySku($sku, $request->validated());

        if (!$result->isSuccess()) {
            return redirect()->back()
                ->withInput()
                ->with('error', $result->getMessage());
        }

        $product = $result->getData();

        return redirect()->route('products.show', $product->sku)
            ->with('success', 'Produto atualizado com sucesso!');

    } catch (Exception $e) {
        return redirect()->back()
            ->withInput()
            ->with('error', 'Erro ao atualizar produto: ' . $e->getMessage());
    }
}
```

ARQUIVOS:

-  `app/Http/Controllers/ProductController.php` (método `update`)
-  `app/Services/Domain/ProductService.php` (método `updateProductBySku`)

CRITÉRIO DE SUCESSO: Produto atualizado com sucesso e redirecionamento correto.

---

## 🎯 PROMPT 4.7: Implementar toggle_status() - Ativar/Desativar Produto (AJAX)

Implemente APENAS o método `toggle_status()` no `ProductController`:

TAREFA ESPECÍFICA:

-  Método: `public function toggle_status(string $sku): JsonResponse`
-  Lógica: Chamar `ProductService::toggleProductStatus()`
-  Retorno: JSON com sucesso/erro

IMPLEMENTAÇÃO:

```php
namespace App\Http\Controllers;

use App\Http\Requests\ProductStoreRequest;
use App\Http\Requests\ProductUpdateRequest;
use App\Services\Domain\CategoryService;
use App\Services\Domain\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Exception;

// ... (restante do controller)

public function toggle_status(string $sku): JsonResponse
{
    try {
        $result = $this->productService->toggleProductStatus($sku);

        if (!$result->isSuccess()) {
            return response()->json(['success' => false, 'message' => $result->getMessage()], 400);
        }

        return response()->json(['success' => true, 'message' => $result->getMessage()]);

    } catch (Exception $e) {
        return response()->json(['success' => false, 'message' => 'Erro ao alterar status do produto: ' . $e->getMessage()], 500);
    }
}
```

ARQUIVOS:

-  `app/Http/Controllers/ProductController.php` (método `toggle_status`)
-  `app/Services/Domain/ProductService.php` (método `toggleProductStatus`)

CRITÉRIO DE SUCESSO: Status do produto alternado via AJAX.

---

## 🎯 PROMPT 4.8: Implementar delete_store() - Deletar Produto

Implemente APENAS o método `delete_store()` no `ProductController`:

TAREFA ESPECÍFICA:

-  Método: `public function delete_store(string $sku): RedirectResponse`
-  Lógica: Chamar `ProductService::deleteProductBySku()`
-  Redirecionamento: Para `products.index` em caso de sucesso, `back` em caso de erro.

IMPLEMENTAÇÃO:

```php
// Dentro de app/Http/Controllers/ProductController.php

public function delete_store(string $sku): RedirectResponse
{
    try {
        $result = $this->productService->deleteProductBySku($sku);

        if (!$result->isSuccess()) {
            return redirect()->back()
                ->with('error', $result->getMessage());
        }

        return redirect()->route('provider.products.index')
            ->with('success', 'Produto excluído com sucesso!');

    } catch (Exception $e) {
        return redirect()->back()
            ->with('error', 'Erro ao excluir produto: ' . $e->getMessage());
    }
}
```

ARQUIVOS:

-  `app/Http/Controllers/ProductController.php` (método `delete_store`)
-  `app/Services/Domain/ProductService.php` (método `deleteProductBySku`)

CRITÉRIO DE SUCESSO: Produto deletado com sucesso e redirecionamento correto.

---

# 📈 **ESTATÍSTICAS**

**Total de Prompts:** 17 prompts
**Ordem Correta:** Database & Repository → FormRequests → Services → Controllers
**Status Atual:** 0% implementado
**Prioridade:** GRUPO 1 (Database & Repository) - **PRIMEIRO**

### **Fase 1: Database & Repository (1.5 dias)**

-  PROMPTS 1.1 a 1.5: Atualizar Migration, Model e Factory, getPaginated, findBySku, countActive, canBeDeactivatedOrDeleted

### **Fase 2: Form Requests (1 dia)**

-  PROMPTS 2.1 a 2.2: ProductStoreRequest, ProductUpdateRequest

### **Fase 3: Services (4 dias)**

-  PROMPTS 3.1 a 3.6: findBySku, getFilteredProducts, createProduct, updateProductBySku, toggleProductStatus, deleteProductBySku

### **Fase 4: Controllers (4 dias)**

-  PROMPTS 4.1 a 4.8: index, create, store, show, edit, update, toggle_status, delete_store

## ✅ **CRITÉRIOS DE SUCESSO POR PROMPT**

-  **Database & Repository:** Estrutura de banco de dados e modelo Eloquent atualizados, queries otimizadas com eager loading e validações de dependência.
-  **FormRequest:** Validação robusta com mensagens em português.
-  **Service:** Lógica de negócio completa com transação, auditoria e gerenciamento de imagens.
-  **Controller:** Método funcionando com validação, error handling e redirecionamento/resposta JSON.

## 🚀 **BENEFÍCIOS DA ORDEM CORRETA**

-  **Dependências respeitadas:** Database & Repository → Form Requests → Services → Controllers
-  **Validação primeiro:** Form Requests antes dos Controllers
-  **Base sólida:** Repository implementado antes dos Services
-  **Testabilidade:** Cada grupo pode ser testado independentemente
-  **Zero dependências circulares:** Arquitetura clara e desacoplada

**Total:** 17 prompts na ordem técnica correta para completar a migração do Módulo de Produtos.
