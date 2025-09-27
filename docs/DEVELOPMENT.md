# Guia do Desenvolvedor

## Arquitetura do Sistema

### Estrutura de Diretórios

```
app/
├── Http/Controllers/     # Controllers da aplicação
├── Services/            # Camada de serviços
├── Models/              # Modelos Eloquent
├── Http/Requests/       # Form Requests
└── Providers/           # Service Providers

resources/
├── views/               # Templates Blade
├── css/                 # Arquivos CSS
└── js/                  # JavaScript

routes/
├── web.php              # Rotas web
├── api.php              # Rotas da API
└── tenant.php           # Rotas multi-tenant
```

## Padrões de Desenvolvimento

### Services

A camada de serviços implementa a lógica de negócio:

```php
<?php

namespace App\Services;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Collection;

class PlanService
{
    public function create(array $data): Plan
    {
        // Lógica de criação
    }

    public function update(Plan $plan, array $data): Plan
    {
        // Lógica de atualização
    }

    public function getActivePlans(): Collection
    {
        return Plan::active()->get();
    }
}
```

### Controllers

Controllers seguem o padrão RESTful:

```php
<?php

namespace App\Http\Controllers;

use App\Services\PlanService;
use App\Http\Requests\StorePlanRequest;
use Illuminate\Http\JsonResponse;

class PlanController extends Controller
{
    public function __construct(
        private PlanService $planService
    ) {}

    public function index(): JsonResponse
    {
        $plans = $this->planService->getActivePlans();
        return response()->json(['data' => $plans]);
    }

    public function store(StorePlanRequest $request): JsonResponse
    {
        $plan = $this->planService->create($request->validated());
        return response()->json(['data' => $plan], 201);
    }
}
```

### Models

Modelos Eloquent com relationships:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    public function budgets(): HasMany
    {
        return $this->hasMany(Budget::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
```

## Validação e Form Requests

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'is_active' => 'boolean'
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O nome do plano é obrigatório.',
            'description.required' => 'A descrição é obrigatória.'
        ];
    }
}
```

## Tratamento de Erros

### Exceptions Personalizadas

```php
<?php

namespace App\Exceptions;

use Exception;

class PlanNotFoundException extends Exception
{
    public function __construct()
    {
        parent::__construct('Plano não encontrado.');
    }
}
```

### Handler Global

```php
<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    public function render($request, Throwable $e)
    {
        if ($e instanceof PlanNotFoundException) {
            return response()->json([
                'message' => $e->getMessage()
            ], 404);
        }

        return parent::render($request, $e);
    }
}
```

## Banco de Dados

### Migrations

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('is_active');
        });
    }
};
```

### Seeders

```php
<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        Plan::create([
            'name' => 'Plano Básico',
            'description' => 'Plano para pequenas empresas',
            'is_active' => true
        ]);
    }
}
```

## Testes

### Testes de Feature

```php
<?php

namespace Tests\Feature;

use App\Models\Plan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_plan(): void
    {
        $planData = [
            'name' => 'Plano Teste',
            'description' => 'Descrição do plano teste',
            'is_active' => true
        ];

        $response = $this->postJson('/api/plans', $planData);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'name',
                        'description',
                        'is_active'
                    ]
                ]);
    }
}
```

## Configurações Importantes

### Configuração de Cache

```php
// config/cache.php
'default' => env('CACHE_DRIVER', 'file'),
'stores' => [
    'file' => [
        'driver' => 'file',
        'path' => storage_path('framework/cache/data'),
    ],
],
```

### Configuração de Queue

```php
// config/queue.php
'default' => env('QUEUE_CONNECTION', 'sync'),
'connections' => [
    'sync' => [
        'driver' => 'sync',
    ],
],
```

## Deployment

### Comandos de Deploy

```bash
# Instalar dependências
composer install --optimize-autoloader --no-dev

# Executar migrations
php artisan migrate --force

# Limpar caches
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Otimizar para produção
php artisan optimize
```

## Debugging e Logs

### Configuração de Log

```php
// config/logging.php
'channels' => [
    'stack' => [
        'driver' => 'stack',
        'channels' => ['single', 'daily'],
    ],
],
```

### Logs Personalizados

```php
use Illuminate\Support\Facades\Log;

Log::info('Plano criado', ['plan_id' => $plan->id]);
Log::error('Erro ao criar plano', ['error' => $e->getMessage()]);
```

## Performance

### Otimizações Implementadas

-  Eager loading de relationships
-  Cache de configurações
-  Índices de banco otimizados
-  Compressão de assets

### Monitoramento

-  Laravel Telescope para debugging
-  Clockwork para profiling
-  Logs estruturados
