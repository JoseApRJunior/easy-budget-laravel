# Easy Budget Laravel - Diretrizes de Desenvolvimento

## Padrões de Qualidade de Código

### Declarações de Tipo Estritas
**Frequência: 100% dos arquivos analisados**

Todos os arquivos PHP DEVEM começar com declaração de tipo estrita:
```php
<?php

declare(strict_types=1);

namespace App\Services\Domain;
```

### Organização de Namespace
**Frequência: 100% dos arquivos analisados**

Seguir padrões PSR-4 de autoloading com hierarquia clara de namespace:
```php
// Serviços de domínio
namespace App\Services\Domain;

// Serviços de aplicação
namespace App\Services\Application;

// Serviços de infraestrutura
namespace App\Services\Infrastructure;

// Models
namespace App\Models;

// Padrões de design
namespace App\DesignPatterns\Models;
```

### Convenções de Formatação de Código

#### Espaçamento e Alinhamento
**Frequência: 95% dos arquivos analisados**

Use espaçamento consistente para declarações de array e parâmetros:
```php
// ✅ Correto - Chaves de array alinhadas
$invoice = Invoice::create( [
    'tenant_id'   => $service->tenant_id,
    'service_id'  => $service->id,
    'customer_id' => $customerId,
    'code'        => $invoiceCode,
    'issue_date'  => $additionalData[ 'issue_date' ] ?? now(),
] );

// ✅ Correto - Parâmetros de função espaçados
public function calculateDistance(
    float $lat1,
    float $lng1,
    float $lat2,
    float $lng2,
    string $unit = 'km',
): float {
    // Implementação
}
```

#### Espaçamento de Acesso a Array
**Frequência: 100% dos arquivos analisados**

Sempre use espaços ao redor dos colchetes de acesso a array:
```php
// ✅ Correto
$data[ 'customer_id' ]
$filters[ 'status' ]
$result[ 'geometry' ][ 'location' ][ 'lat' ]

// ❌ Incorreto
$data['customer_id']
$filters['status']
```

### Padrões de Documentação

#### Documentação em Nível de Classe
**Frequência: 90% dos arquivos analisados**

Toda classe DEVE ter um docblock abrangente:
```php
/**
 * Serviço de Geolocalização - Integração com Google Maps
 *
 * Fornece funcionalidades de geocodificação, cálculo de distância
 * e validação de endereços usando a API do Google Maps.
 */
class GeolocationService
{
    // Implementação
}
```

#### Documentação de Métodos
**Frequência: 85% dos arquivos analisados**

Métodos públicos devem ter docblocks claros:
```php
/**
 * Cria uma nova interação com cliente.
 */
public function createInteraction( Customer $customer, array $data, User $user ): CustomerInteraction
{
    // Implementação
}

/**
 * Calcula distância entre dois pontos usando fórmula de Haversine.
 */
public function calculateDistance(
    float $lat1,
    float $lng1,
    float $lat2,
    float $lng2,
    string $unit = 'km',
): float {
    // Implementação
}
```

## Padrões Arquiteturais

### Padrão de Camada de Serviço
**Frequência: 100% dos arquivos de serviço**

Toda lógica de negócio DEVE estar em classes de serviço:

#### Estrutura de Serviço
```php
class InvoiceService extends AbstractBaseService
{
    private InvoiceRepository   $invoiceRepository;
    private NotificationService $notificationService;

    public function __construct( 
        InvoiceRepository $invoiceRepository, 
        NotificationService $notificationService 
    ) {
        $this->invoiceRepository   = $invoiceRepository;
        $this->notificationService = $notificationService;
    }

    public function createInvoice( array $data ): ServiceResult
    {
        try {
            return DB::transaction( function () use ($data) {
                // Lógica de negócio aqui
                return $this->success( $invoice, 'Fatura criada com sucesso' );
            } );
        } catch ( Exception $e ) {
            return $this->error(
                OperationStatus::ERROR,
                'Erro ao criar fatura',
                null,
                $e,
            );
        }
    }
}
```

#### Padrão ServiceResult
**Frequência: 100% dos métodos de serviço**

Todos os métodos de serviço DEVEM retornar ServiceResult:
```php
// ✅ Correto - Resposta de sucesso
return $this->success( $invoice, 'Fatura criada com sucesso' );

// ✅ Correto - Resposta de erro
return $this->error(
    OperationStatus::NOT_FOUND,
    'Fatura não encontrada',
);

// ✅ Correto - Erro com exceção
return $this->error(
    OperationStatus::ERROR,
    'Erro ao criar fatura',
    null,
    $e,
);
```

### Gestão de Transações
**Frequência: 90% das operações de escrita**

Use transações de banco de dados para integridade de dados:
```php
public function createInvoice( array $data ): ServiceResult
{
    try {
        return DB::transaction( function () use ($data) {
            // Criar fatura
            $invoice = Invoice::create( $invoiceData );
            
            // Criar itens da fatura
            $this->createInvoiceItems( $invoice, $data[ 'items' ] );
            
            // Atualizar registros relacionados
            $service->update( [ 'has_invoice' => true ] );
            
            return $this->success( $invoice, 'Fatura criada com sucesso' );
        } );
    } catch ( Exception $e ) {
        return $this->error( OperationStatus::ERROR, 'Erro ao criar fatura', null, $e );
    }
}
```

### Padrão de Tratamento de Erros
**Frequência: 100% dos métodos de serviço**

Tratamento de erros abrangente com logging:
```php
try {
    // Lógica de negócio
    return $this->success( $data, 'Operação realizada' );
    
} catch ( Exception $e ) {
    Log::error( 'Erro na operação', [
        'error'   => $e->getMessage(),
        'file'    => $e->getFile(),
        'line'    => $e->getLine(),
        'context' => $contextData,
    ] );
    
    return $this->error(
        OperationStatus::ERROR,
        'Erro ao processar operação',
        null,
        $e,
    );
}
```

## Padrões de Model

### Estrutura de Model - Três Níveis
**Frequência: Definido em ModelPattern.php**

#### Nível 1 - Model Básico
Para entidades simples sem relacionamentos complexos:
```php
class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'active',
    ];

    protected $casts = [
        'active'     => 'boolean',
        'created_at' => 'immutable_datetime',
        'updated_at' => 'datetime',
    ];

    // Constantes
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    // Regras de negócio
    public static function businessRules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:categories,slug',
        ];
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}
```

#### Nível 2 - Model Intermediário
Para entidades com relacionamentos importantes:
```php
class Product extends Model
{
    use HasFactory, TenantScoped;

    protected static function boot()
    {
        parent::boot();
        static::bootTenantScoped();
    }

    protected $fillable = [
        'tenant_id',
        'name',
        'sku',
        'price',
        'category_id',
        'active',
    ];

    protected $casts = [
        'tenant_id'   => 'integer',
        'category_id' => 'integer',
        'price'       => 'decimal:2',
        'active'      => 'boolean',
        'created_at'  => 'immutable_datetime',
        'updated_at'  => 'datetime',
    ];

    // Relacionamentos
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    // Accessors
    public function getFormattedPriceAttribute(): string
    {
        return 'R$ ' . number_format($this->price, 2, ',', '.');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeInStock($query)
    {
        return $query->whereHas('inventory', function ($q) {
            $q->where('quantity', '>', 0);
        });
    }
}
```

#### Nível 3 - Model Avançado
Para entidades complexas com autorização e lógica de negócio:
```php
class User extends Model
{
    use HasFactory, TenantScoped, Auditable;

    protected static function boot()
    {
        parent::boot();
        static::bootTenantScoped();
        static::bootAuditable();
    }

    protected $fillable = [
        'tenant_id',
        'email',
        'password',
        'first_name',
        'last_name',
        'is_active',
        'settings',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'tenant_id'         => 'integer',
        'settings'          => 'array',
        'is_active'         => 'boolean',
        'email_verified_at' => 'datetime',
        'created_at'        => 'immutable_datetime',
        'updated_at'        => 'datetime',
    ];

    // Relacionamentos complexos
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles')
            ->withPivot(['tenant_id'])
            ->withTimestamps();
    }

    // Accessors avançados
    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    // Métodos de autorização
    public function hasRole(string $role): bool
    {
        return $this->roles()
            ->wherePivot('tenant_id', $this->tenant_id)
            ->where('name', $role)
            ->exists();
    }

    // Métodos de negócio
    public function getStats(): array
    {
        return [
            'total_budgets'  => $this->budgets()->count(),
            'total_invoices' => $this->invoices()->count(),
            'role_count'     => $this->roles()->count(),
        ];
    }
}
```

### Convenções de Model

#### Fillable e Casts
**Frequência: 100% dos models**

Sempre defina campos fillable e casts:
```php
protected $fillable = [
    'tenant_id',
    'name',
    'email',
    'status',
];

protected $casts = [
    'tenant_id'  => 'integer',
    'active'     => 'boolean',
    'settings'   => 'array',
    'created_at' => 'immutable_datetime',
    'updated_at' => 'datetime',
];
```

#### Constantes para Valores de Status
**Frequência: 90% dos models com status**

Defina constantes de status:
```php
public const STATUS_ACTIVE = 'active';
public const STATUS_INACTIVE = 'inactive';
public const STATUS_PENDING = 'pending';

public const STATUSES = [
    self::STATUS_ACTIVE,
    self::STATUS_INACTIVE,
    self::STATUS_PENDING,
];
```

#### Método de Regras de Negócio
**Frequência: 80% dos models**

Defina regras de validação no model:
```php
public static function businessRules(): array
{
    return [
        'tenant_id'   => 'required|integer|exists:tenants,id',
        'name'        => 'required|string|max:255',
        'email'       => 'required|email|unique:users,email',
        'status'      => 'required|in:' . implode(',', self::STATUSES),
    ];
}
```

## Padrões de Banco de Dados

### Padrões de Configuração
**Frequência: 100% da configuração de banco de dados**

Use variáveis de ambiente com fallbacks:
```php
'mysql' => [
    'driver'         => 'mysql',
    'host'           => env( 'DB_HOST', '127.0.0.1' ),
    'port'           => env( 'DB_PORT', '3306' ),
    'database'       => env( 'DB_DATABASE', env( 'DB_NAME', 'laravel' ) ),
    'username'       => env( 'DB_USERNAME', env( 'DB_USER', 'root' ) ),
    'password'       => env( 'DB_PASSWORD', env( 'DB_PASSWORD', '' ) ),
    'charset'        => env( 'DB_CHARSET', 'utf8mb4' ),
    'collation'      => env( 'DB_COLLATION', 'utf8mb4_unicode_ci' ),
    'prefix'         => '',
    'prefix_indexes' => true,
    'strict'         => true,
    'engine'         => null,
],
```

### Padrões de Construção de Consultas
**Frequência: 95% dos métodos de repositório**

Construa consultas progressivamente com condições claras:
```php
public function getFilteredInvoices( array $filters = [] ): Collection
{
    $query = Invoice::query();

    // Aplicar filtros progressivamente
    if ( !empty( $filters[ 'status' ] ) ) {
        $query->where( 'status', $filters[ 'status' ] );
    }

    if ( !empty( $filters[ 'customer_id' ] ) ) {
        $query->where( 'customer_id', $filters[ 'customer_id' ] );
    }

    if ( !empty( $filters[ 'date_from' ] ) ) {
        $query->whereDate( 'issue_date', '>=', $filters[ 'date_from' ] );
    }

    // Ordenação
    $sortBy        = $filters[ 'sort_by' ] ?? 'issue_date';
    $sortDirection = $filters[ 'sort_direction' ] ?? 'desc';
    $query->orderBy( $sortBy, $sortDirection );

    return $query->get();
}
```

## Padrões de Logging

### Logging Abrangente
**Frequência: 90% das operações críticas**

Registre todas as operações importantes com contexto:
```php
// Logging de sucesso
Log::info( 'Interação criada', [
    'interaction_id' => $interaction->id,
    'customer_id'    => $customer->id,
    'user_id'        => $user->id,
    'type'           => $interaction->type,
] );

// Logging de erro
Log::error( 'Erro ao criar fatura', [
    'error'   => $e->getMessage(),
    'file'    => $e->getFile(),
    'line'    => $e->getLine(),
    'context' => $contextData,
] );

// Logging de aviso
Log::warning( 'Cliente sem email para notificação', [
    'interaction_id' => $interaction->id,
    'customer_id'    => $customer->id,
] );
```

### Mensagens de Log Estruturadas
**Frequência: 100% das chamadas de log**

Use mensagens descritivas com dados estruturados:
```php
// ✅ Correto - Descritivo com contexto
Log::info( 'Starting createInvoiceFromService', [
    'service_code'    => $serviceCode,
    'additional_data' => $additionalData
] );

// ✅ Correto - Erro com contexto completo
Log::error( 'Exception in createInvoiceFromService', [
    'error' => $e->getMessage(),
    'file'  => $e->getFile(),
    'line'  => $e->getLine(),
    'trace' => $e->getTraceAsString()
] );
```

## Padrões de Integração de API

### Integração de Serviço Externo
**Frequência: 100% dos serviços de infraestrutura**

Use timeout e tratamento de erros para APIs externas:
```php
public function geocodeAddress( array $address ): array
{
    if ( !$this->apiKey ) {
        return $this->getDefaultGeolocation( $address );
    }

    try {
        $response = Http::timeout( 10 )->get( "{$this->baseUrl}/geocode/json", [
            'address'  => $fullAddress,
            'key'      => $this->apiKey,
            'language' => 'pt-BR',
            'region'   => 'br',
        ] );

        if ( $response->successful() ) {
            return $this->parseGeocodeResponse( $response );
        }

        Log::error( 'Google Maps Geocoding API error', [
            'status' => $response->status(),
            'body'   => $response->body(),
        ] );

    } catch ( \Exception $e ) {
        Log::error( 'Geolocation service error', [
            'message' => $e->getMessage(),
            'address' => $address,
        ] );
    }

    return $this->getDefaultGeolocation( $address );
}
```

### Mecanismos de Fallback
**Frequência: 80% das integrações externas**

Sempre forneça fallback para serviços externos:
```php
private function getDefaultGeolocation( array $address ): array
{
    // Fallback para coordenadas aproximadas
    return $this->getApproximateCoordinates( 
        $address[ 'state' ] ?? '', 
        $address[ 'city' ] ?? '' 
    );
}
```

## Padrões de Validação

### Validação de Entrada
**Frequência: 85% dos métodos de serviço**

Valide dados de entrada antes do processamento:
```php
public function validateInteractionData( array $data ): array
{
    $errors = [];

    if ( empty( $data[ 'type' ] ) ) {
        $errors[] = 'Tipo de interação é obrigatório.';
    }

    if ( empty( $data[ 'title' ] ) ) {
        $errors[] = 'Título da interação é obrigatório.';
    }

    if ( !empty( $data[ 'next_action_date' ] ) && !empty( $data[ 'interaction_date' ] ) ) {
        $interactionDate = strtotime( $data[ 'interaction_date' ] );
        $nextActionDate  = strtotime( $data[ 'next_action_date' ] );

        if ( $nextActionDate <= $interactionDate ) {
            $errors[] = 'Data da próxima ação deve ser posterior à data da interação.';
        }
    }

    return $errors;
}
```

### Métodos de Validação de Dados
**Frequência: 90% dos serviços de infraestrutura**

Crie métodos de validação específicos:
```php
public function isValidCoordinates( ?float $latitude, ?float $longitude ): bool
{
    if ( $latitude === null || $longitude === null ) {
        return false;
    }

    return $latitude >= -90 && $latitude <= 90 &&
        $longitude >= -180 && $longitude <= 180;
}

public function isValidCep( string $cep ): bool
{
    return preg_match( '/^\d{5}-?\d{3}$/', $cep ) === 1;
}
```

## Padrões de Geração de Código

### Geração de Código Único
**Frequência: 100% das entidades com códigos**

Gere códigos sequenciais únicos:
```php
private function generateUniqueInvoiceCode( string $serviceCode ): string
{
    $lastInvoice = Invoice::whereHas( 'service', function ( $query ) use ( $serviceCode ) {
        $query->where( 'code', $serviceCode );
    } )
        ->orderBy( 'code', 'desc' )
        ->first();

    $sequential = 1;
    if ( $lastInvoice && preg_match( '/-INV(\d{3})$/', $lastInvoice->code, $matches ) ) {
        $sequential = (int) $matches[ 1 ] + 1;
    }

    return "{$serviceCode}-INV" . str_pad( $sequential, 3, '0', STR_PAD_LEFT );
}
```

## Resumo de Melhores Práticas

### FAZER ✅

1. **Sempre use tipos estritos** - `declare(strict_types=1);`
2. **Use padrão ServiceResult** - Todos os métodos de serviço retornam ServiceResult
3. **Envolva escritas em transações** - Use `DB::transaction()` para integridade de dados
4. **Registre de forma abrangente** - Registre todas as operações críticas com contexto
5. **Trate erros graciosamente** - Try-catch com respostas de erro adequadas
6. **Valide entradas** - Valide antes de processar
7. **Use type hints** - Tipifique todos os parâmetros e tipos de retorno
8. **Documente classes e métodos** - Docblocks claros em português
9. **Use constantes para status** - Defina constantes de status em models
10. **Forneça fallbacks** - Sempre tenha fallback para serviços externos

### NÃO FAZER ❌

1. **Não pule declarações de tipo** - Sempre use tipos estritos
2. **Não retorne dados brutos** - Sempre envolva em ServiceResult
3. **Não pule transações** - Use transações para operações de escrita
4. **Não ignore erros** - Sempre registre e trate exceções
5. **Não pule validação** - Valide todas as entradas
6. **Não use valores mágicos** - Use constantes em vez disso
7. **Não pule logging** - Registre todas as operações importantes
8. **Não exponha exceções** - Capture e envolva em ServiceResult
9. **Não pule documentação** - Documente todos os métodos públicos
10. **Não hardcode valores** - Use configuração e variáveis de ambiente

## Checklist de Estilo de Código

Antes de fazer commit do código, garanta:

- [ ] Declaração de tipo estrita no início do arquivo
- [ ] Declaração adequada de namespace
- [ ] Docblock de classe com descrição
- [ ] Docblocks de método para métodos públicos
- [ ] Type hints em todos os parâmetros
- [ ] Declarações de tipo de retorno
- [ ] Espaços ao redor de colchetes de array
- [ ] Chaves de array alinhadas
- [ ] Constantes para valores de status
- [ ] Padrão de retorno ServiceResult
- [ ] Transações de banco de dados para escritas
- [ ] Tratamento de erros abrangente
- [ ] Logging para operações críticas
- [ ] Validação de entrada
- [ ] Mecanismos de fallback para serviços externos
