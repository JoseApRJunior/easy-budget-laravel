<?php

declare(strict_types=1);

namespace App\DesignPatterns\Models;

/**
 * Templates Práticos para Models
 *
 * Fornece templates prontos para uso imediato no desenvolvimento,
 * seguindo o padrão unificado definido em ModelPattern.
 *
 * @package App\DesignPatterns
 */
class ModelTemplates
{
    /**
     * TEMPLATE COMPLETO - Model Nível 1 (Básico)
     */
    public function basicModelTemplate(): string
    {
        return '<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Model básico para {Module} - Sem relacionamentos complexos
 *
 * Implementa funcionalidades básicas sem lógica complexa ou relacionamentos.
 */
class {Module} extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = \'{module}\';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        \'name\',
        \'slug\',
        \'description\',
        \'active\',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        \'active\' => \'boolean\',
        \'created_at\' => \'immutable_datetime\',
        \'updated_at\' => \'datetime\',
    ];

    // --------------------------------------------------------------------------
    // CONSTANTES
    // --------------------------------------------------------------------------

    public const STATUS_ACTIVE = \'active\';
    public const STATUS_INACTIVE = \'inactive\';

    public const STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_INACTIVE,
    ];

    // --------------------------------------------------------------------------
    // VALIDAÇÃO
    // --------------------------------------------------------------------------

    /**
     * Regras de validação para o modelo.
     */
    public static function businessRules(): array
    {
        return [
            \'name\' => \'required|string|max:255\',
            \'slug\' => \'required|string|max:255|unique:{module},slug\',
            \'description\' => \'nullable|string|max:1000\',
            \'active\' => \'boolean\',
        ];
    }

    /**
     * Regras de validação para criação.
     */
    public static function createRules(): array
    {
        return [
            \'name\' => \'required|string|max:255\',
            \'slug\' => \'required|string|max:255|unique:{module},slug\',
            \'description\' => \'nullable|string|max:1000\',
        ];
    }

    /**
     * Regras de validação para atualização.
     */
    public static function updateRules(int $id): array
    {
        return [
            \'name\' => \'required|string|max:255\',
            \'slug\' => \'required|string|max:255|unique:{module},slug,\' . $id,
            \'description\' => \'nullable|string|max:1000\',
        ];
    }

    // --------------------------------------------------------------------------
    // RELACIONAMENTOS
    // --------------------------------------------------------------------------

    /**
     * Relacionamentos podem ser adicionados aqui se necessário.
     */
    // public function relatedModels(): HasMany
    // {
    //     return $this->hasMany(RelatedModel::class);
    // }

    // --------------------------------------------------------------------------
    // ACCESSORS
    // --------------------------------------------------------------------------

    /**
     * Accessor para nome formatado.
     */
    public function getFormattedNameAttribute(): string
    {
        return ucfirst($this->name);
    }

    /**
     * Accessor para verificar se está ativo.
     */
    public function getIsActiveAttribute(): bool
    {
        return $this->active;
    }

    /**
     * Accessor para status legível.
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->active) {
            true => \'Ativo\',
            false => \'Inativo\',
        };
    }

    // --------------------------------------------------------------------------
    // SCOPES
    // --------------------------------------------------------------------------

    /**
     * Scope para registros ativos.
     */
    public function scopeActive($query)
    {
        return $query->where(\'active\', true);
    }

    /**
     * Scope para ordenação por nome.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy(\'name\');
    }

    /**
     * Scope para busca por nome.
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(\'name\', \'ILIKE\', "%{$search}%");
    }

    // --------------------------------------------------------------------------
    // MÉTODOS AUXILIARES
    // --------------------------------------------------------------------------

    /**
     * Ativa o registro.
     */
    public function activate(): bool
    {
        return $this->update([\'active\' => true]);
    }

    /**
     * Desativa o registro.
     */
    public function deactivate(): bool
    {
        return $this->update([\'active\' => false]);
    }

    /**
     * Verifica se está em uso.
     */
    public function isInUse(): bool
    {
        // Implementar verificação específica se necessário
        return false;
    }

    /**
     * Gera slug único a partir do nome.
     */
    public function generateUniqueSlug(string $name): string
    {
        $slug = Str::slug($name);
        $count = 0;
        $originalSlug = $slug;

        while (static::where(\'slug\', $slug)->when($this->exists, fn($q) => $q->where(\'id\', \'!=\', $this->id))->exists()) {
            $slug = $originalSlug . \'-\' . ++$count;
        }

        return $slug;
    }
}';
    }

    /**
     * TEMPLATE COMPLETO - Model Nível 2 (Intermediário)
     */
    public function intermediateModelTemplate(): string
    {
        return '<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Tenant;
use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Model intermediário para {Module} - Com relacionamentos importantes
 *
 * Implementa relacionamentos essenciais e funcionalidades específicas.
 */
class {Module} extends Model
{
    use HasFactory, TenantScoped;

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();
        static::bootTenantScoped();
    }

    /**
     * The table associated with the model.
     */
    protected $table = \'{module}\';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        \'tenant_id\',
        \'name\',
        \'slug\',
        \'description\',
        \'status\',
        \'active\',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        \'tenant_id\' => \'integer\',
        \'active\' => \'boolean\',
        \'created_at\' => \'immutable_datetime\',
        \'updated_at\' => \'datetime\',
    ];

    // --------------------------------------------------------------------------
    // CONSTANTES
    // --------------------------------------------------------------------------

    public const STATUS_ACTIVE = \'active\';
    public const STATUS_INACTIVE = \'inactive\';

    public const STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_INACTIVE,
    ];

    // --------------------------------------------------------------------------
    // VALIDAÇÃO
    // --------------------------------------------------------------------------

    /**
     * Regras de validação para o modelo.
     */
    public static function businessRules(): array
    {
        return [
            \'tenant_id\' => \'required|integer|exists:tenants,id\',
            \'name\' => \'required|string|max:255\',
            \'slug\' => \'required|string|max:255|unique:{module},slug\',
            \'description\' => \'nullable|string|max:1000\',
            \'status\' => \'required|in:\' . implode(\',\', self::STATUSES),
            \'active\' => \'boolean\',
        ];
    }

    // --------------------------------------------------------------------------
    // RELACIONAMENTOS
    // --------------------------------------------------------------------------

    /**
     * Relacionamento com tenant.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Relacionamentos específicos podem ser adicionados aqui.
     */
    // public function category(): BelongsTo
    // {
    //     return $this->belongsTo(Category::class);
    // }

    // public function items(): HasMany
    // {
    //     return $this->hasMany({Module}Item::class);
    // }

    // --------------------------------------------------------------------------
    // ACCESSORS
    // --------------------------------------------------------------------------

    /**
     * Accessor para nome formatado.
     */
    public function getFormattedNameAttribute(): string
    {
        return ucfirst($this->name);
    }

    /**
     * Accessor para status legível.
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_ACTIVE => \'Ativo\',
            self::STATUS_INACTIVE => \'Inativo\',
            default => ucfirst($this->status),
        };
    }

    /**
     * Accessor para verificar se está ativo.
     */
    public function getIsActiveAttribute(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    // --------------------------------------------------------------------------
    // SCOPES
    // --------------------------------------------------------------------------

    /**
     * Scope para registros ativos.
     */
    public function scopeActive($query)
    {
        return $query->where(\'status\', self::STATUS_ACTIVE);
    }

    /**
     * Scope para ordenação por nome.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy(\'name\');
    }

    /**
     * Scope para busca por nome.
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(\'name\', \'ILIKE\', "%{$search}%");
    }

    /**
     * Scope para registros por tenant.
     */
    public function scopeByTenant($query, int $tenantId)
    {
        return $query->where(\'tenant_id\', $tenantId);
    }

    // --------------------------------------------------------------------------
    // MÉTODOS DE NEGÓCIO
    // --------------------------------------------------------------------------

    /**
     * Ativa o registro.
     */
    public function activate(): bool
    {
        return $this->update([\'status\' => self::STATUS_ACTIVE]);
    }

    /**
     * Desativa o registro.
     */
    public function deactivate(): bool
    {
        return $this->update([\'status\' => self::STATUS_INACTIVE]);
    }

    /**
     * Verifica se pode ser excluído.
     */
    public function canBeDeleted(): bool
    {
        // Implementar verificação específica se necessário
        return true;
    }

    /**
     * Obtém estatísticas básicas.
     */
    public function getBasicStats(): array
    {
        return [
            \'is_active\' => $this->is_active,
            \'days_since_created\' => $this->created_at->diffInDays(now()),
        ];
    }
}';
    }

    /**
     * TEMPLATE COMPLETO - Model Nível 3 (Avançado)
     */
    public function advancedModelTemplate(): string
    {
        return '<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Tenant;
use App\Models\Traits\TenantScoped;
use App\Models\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Model avançado para {Module} - Com relacionamentos complexos e lógica de negócio
 *
 * Implementa relacionamentos avançados, autorização e métodos de negócio específicos.
 */
class {Module} extends Model
{
    use HasFactory, TenantScoped, Auditable;

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();
        static::bootTenantScoped();
        static::bootAuditable();
    }

    /**
     * The table associated with the model.
     */
    protected $table = \'{module}\';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        \'tenant_id\',
        \'name\',
        \'email\',
        \'status\',
        \'settings\',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        \'password\',
        \'remember_token\',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        \'tenant_id\' => \'integer\',
        \'settings\' => \'array\',
        \'active\' => \'boolean\',
        \'email_verified_at\' => \'datetime\',
        \'created_at\' => \'immutable_datetime\',
        \'updated_at\' => \'datetime\',
    ];

    // --------------------------------------------------------------------------
    // CONSTANTES
    // --------------------------------------------------------------------------

    public const STATUS_ACTIVE = \'active\';
    public const STATUS_INACTIVE = \'inactive\';
    public const STATUS_PENDING = \'pending\';

    public const STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_INACTIVE,
        self::STATUS_PENDING,
    ];

    // --------------------------------------------------------------------------
    // VALIDAÇÃO
    // --------------------------------------------------------------------------

    /**
     * Regras de validação para o modelo.
     */
    public static function businessRules(): array
    {
        return [
            \'tenant_id\' => \'required|integer|exists:tenants,id\',
            \'name\' => \'required|string|max:255\',
            \'email\' => \'required|email|max:255|unique:{module},email\',
            \'status\' => \'required|in:\' . implode(\',\', self::STATUSES),
        ];
    }

    // --------------------------------------------------------------------------
    // RELACIONAMENTOS COMPLEXOS
    // --------------------------------------------------------------------------

    /**
     * Relacionamento com tenant.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Relacionamentos específicos podem ser adicionados aqui.
     */
    // public function profile(): HasOne
    // {
    //     return $this->hasOne(Profile::class);
    // }

    // public function roles(): BelongsToMany
    // {
    //     return $this->belongsToMany(Role::class);
    // }

    // public function activities(): HasMany
    // {
    //     return $this->hasMany(Activity::class);
    // }

    // --------------------------------------------------------------------------
    // ACCESSORS AVANÇADOS
    // --------------------------------------------------------------------------

    /**
     * Accessor para nome completo.
     */
    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . \' \' . $this->last_name);
    }

    /**
     * Accessor para iniciais.
     */
    public function getInitialsAttribute(): string
    {
        return strtoupper(
            substr($this->first_name, 0, 1) .
            substr($this->last_name, 0, 1)
        );
    }

    /**
     * Accessor para nome de exibição.
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->profile?->company_name ?? $this->full_name;
    }

    /**
     * Accessor para verificar se é admin.
     */
    public function getIsAdminAttribute(): bool
    {
        return $this->hasRole(\'admin\');
    }

    /**
     * Accessor para configurações específicas.
     */
    public function getThemeAttribute(): string
    {
        return $this->settings[\'theme\'] ?? \'light\';
    }

    // --------------------------------------------------------------------------
    // MUTATORS
    // --------------------------------------------------------------------------

    /**
     * Mutator para email em minúsculo.
     */
    public function setEmailAttribute(string $value): void
    {
        $this->attributes[\'email\'] = strtolower($value);
    }

    /**
     * Mutator para configurações.
     */
    public function setSettingsAttribute(array $value): void
    {
        $this->attributes[\'settings\'] = array_merge(
            $this->settings ?? [],
            $value
        );
    }

    // --------------------------------------------------------------------------
    // SCOPES AVANÇADOS
    // --------------------------------------------------------------------------

    /**
     * Scope para registros ativos.
     */
    public function scopeActive($query)
    {
        return $query->where(\'status\', self::STATUS_ACTIVE);
    }

    /**
     * Scope para registros recentes.
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where(\'created_at\', \'>=\', now()->subDays($days));
    }

    /**
     * Scope com relacionamentos carregados.
     */
    public function scopeWithRelated($query)
    {
        return $query->with([\'profile\', \'roles\', \'permissions\']);
    }

    /**
     * Scope para busca avançada.
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where(\'name\', \'ILIKE\', "%{$search}%")
              ->orWhere(\'email\', \'ILIKE\', "%{$search}%");
        });
    }

    // --------------------------------------------------------------------------
    // MÉTODOS DE AUTORIZAÇÃO
    // --------------------------------------------------------------------------

    /**
     * Verifica se tem uma role específica.
     */
    public function hasRole(string $role): bool
    {
        return $this->roles()
            ->wherePivot(\'tenant_id\', $this->tenant_id)
            ->where(\'name\', $role)
            ->exists();
    }

    /**
     * Verifica se tem uma permissão específica.
     */
    public function hasPermission(string $permission): bool
    {
        return $this->permissions()
            ->wherePivot(\'tenant_id\', $this->tenant_id)
            ->where(\'name\', $permission)
            ->exists();
    }

    /**
     * Verifica se tem qualquer uma das roles.
     */
    public function hasAnyRole(array $roles): bool
    {
        return $this->roles()
            ->wherePivot(\'tenant_id\', $this->tenant_id)
            ->whereIn(\'name\', $roles)
            ->exists();
    }

    /**
     * Verifica se pode acessar um recurso.
     */
    public function canAccessResource(string $resource): bool
    {
        return $this->is_admin || $this->hasPermission("access_{$resource}");
    }

    // --------------------------------------------------------------------------
    // MÉTODOS DE GESTÃO
    // --------------------------------------------------------------------------

    /**
     * Ativa o usuário.
     */
    public function activate(): bool
    {
        $this->status = self::STATUS_ACTIVE;
        return $this->save();
    }

    /**
     * Desativa o usuário.
     */
    public function deactivate(): bool
    {
        $this->status = self::STATUS_INACTIVE;
        return $this->save();
    }

    /**
     * Atualiza configurações.
     */
    public function updateSettings(array $settings): bool
    {
        $currentSettings = $this->settings ?? [];
        $newSettings = array_merge($currentSettings, $settings);

        return $this->update([\'settings\' => $newSettings]);
    }

    /**
     * Obtém configuração específica.
     */
    public function getSetting(string $key, $default = null)
    {
        return data_get($this->settings, $key, $default);
    }

    /**
     * Define configuração específica.
     */
    public function setSetting(string $key, $value): bool
    {
        return $this->updateSettings([$key => $value]);
    }

    // --------------------------------------------------------------------------
    // MÉTODOS DE ESTATÍSTICAS
    // --------------------------------------------------------------------------

    /**
     * Obtém estatísticas do usuário.
     */
    public function getStats(): array
    {
        return [
            \'total_budgets\' => $this->budgets()->count(),
            \'total_invoices\' => $this->invoices()->count(),
            \'active_budgets\' => $this->budgets()->active()->count(),
            \'pending_invoices\' => $this->invoices()->pending()->count(),
            \'role_count\' => $this->roles()->count(),
            \'activity_count\' => $this->activities()->count(),
            \'days_since_created\' => $this->created_at->diffInDays(now()),
        ];
    }

    /**
     * Obtém atividades recentes.
     */
    public function getRecentActivities(int $limit = 10): Collection
    {
        return $this->activities()
            ->orderBy(\'created_at\', \'desc\')
            ->limit($limit)
            ->get();
    }

    // --------------------------------------------------------------------------
    // MÉTODOS DE VALIDAÇÃO CUSTOMIZADA
    // --------------------------------------------------------------------------

    /**
     * Verifica se é administrador do tenant.
     */
    public function isTenantAdmin(): bool
    {
        return $this->hasRole(\'admin\') &&
               $this->profile &&
               $this->profile->is_tenant_owner;
    }

    /**
     * Verifica se pode gerenciar outros usuários.
     */
    public function canManageUsers(): bool
    {
        return $this->is_admin || $this->hasPermission(\'manage_users\');
    }

    /**
     * Verifica se pode acessar configurações avançadas.
     */
    public function canAccessAdvancedSettings(): bool
    {
        return $this->is_admin || $this->hasPermission(\'access_advanced_settings\');
    }
}';
    }

    /**
     * GUIA DE UTILIZAÇÃO DOS TEMPLATES
     */
    public function getUsageGuide(): string
    {
        return '
## Como Usar os Templates de Models

### 1. Escolha o Nível Correto

**Nível 1 (Básico):**
- Para entidades simples sem relacionamentos
- Catálogos básicos (categorias, unidades, profissões)
- Tabelas de configuração global
- Não há necessidade de multi-tenant

**Nível 2 (Intermediário):**
- Para entidades com relacionamentos importantes
- Multi-tenant necessário
- Scopes específicos necessários
- Relacionamentos 1:N e N:1

**Nível 3 (Avançado):**
- Para entidades com relacionamentos muitos-para-muitos
- Lógica de autorização complexa
- Métodos de negócio específicos
- Relacionamentos polimórficos

### 2. Substitua os Placeholders

No template, substitua:
- `{Module}` → Nome do módulo (ex: Category, Product, User)
- `{module}` → Nome da tabela (ex: categories, products, users)

### 3. Personalize conforme Necessário

**Para Nível 1:**
```php
protected $fillable = [
    \'name\',
    \'slug\',
    \'description\',
    \'active\',
    \'priority\', // Campo específico
];

public const STATUS_ACTIVE = \'active\';
public const STATUS_INACTIVE = \'inactive\';
public const STATUS_SUSPENDED = \'suspended\'; // Status específico

public const STATUSES = [
    self::STATUS_ACTIVE,
    self::STATUS_INACTIVE,
    self::STATUS_SUSPENDED,
];
```

**Para Nível 2:**
```php
// Adicione relacionamentos específicos
public function category(): BelongsTo
{
    return $this->belongsTo(Category::class);
}

public function items(): HasMany
{
    return $this->hasMany(ProductItem::class);
}

// Adicione scopes específicos
public function scopeInStock($query)
{
    return $query->where(\'quantity\', \'>\', 0);
}

public function scopeByPriceRange($query, float $min, float $max)
{
    return $query->whereBetween(\'price\', [$min, $max]);
}
```

**Para Nível 3:**
```php
// Adicione relacionamentos complexos
public function roles(): BelongsToMany
{
    return $this->belongsToMany(Role::class, \'user_roles\')
        ->withPivot([\'tenant_id\'])
        ->withTimestamps();
}

// Adicione métodos de autorização
public function canManageBudgets(): bool
{
    return $this->is_admin || $this->hasPermission(\'manage_budgets\');
}
```

### 4. Implemente Relacionamentos Específicos

**Para relacionamentos importantes:**
```php
public function customer(): BelongsTo
{
    return $this->belongsTo(Customer::class);
}

public function budgetItems(): HasMany
{
    return $this->hasMany(BudgetItem::class);
}

public function tags(): BelongsToMany
{
    return $this->belongsToMany(Tag::class, \'budget_tags\');
}
```

### 5. Configure Índices de Performance

**Para campos frequentemente consultados:**
```php
// Em migrations
Schema::table(\'{module}\', function (Blueprint $table) {
    $table->index([\'tenant_id\', \'status\']);
    $table->index([\'tenant_id\', \'created_at\']);
    $table->index([\'tenant_id\', \'customer_id\']);
    $table->index([\'slug\']); // Para busca por slug
});
```

## Benefícios dos Templates

✅ **Rapidez**: Criação rápida de models padronizados
✅ **Consistência**: Todos seguem convenções unificadas
✅ **Funcionalidade**: Relacionamentos e métodos inclusos
✅ **Flexibilidade**: Diferentes níveis para diferentes necessidades
✅ **Manutenibilidade**: Estrutura clara e fácil de entender

## Estrutura de Arquivos Recomendada

```
app/Models/
├── Basic/                              # Models básicos
│   ├── Category.php                   # Nível 1 - Básico
│   ├── Unit.php
│   └── Profession.php
├── Intermediate/                      # Models intermediários
│   ├── Product.php                    # Nível 2 - Intermediário
│   ├── Customer.php
│   └── Service.php
└── Advanced/                          # Models avançados
    ├── User.php                       # Nível 3 - Avançado
    ├── Budget.php
    └── Invoice.php
```

## Convenções de Desenvolvimento

### **Propriedades Obrigatórias:**
```php
protected $fillable = [/* campos */];
protected $casts = [/* tipos */];
protected $hidden = [/* campos sensíveis */];
```

### **Constantes de Status:**
```php
public const STATUS_ACTIVE = \'active\';
public const STATUS_INACTIVE = \'inactive\';

public const STATUSES = [
    self::STATUS_ACTIVE,
    self::STATUS_INACTIVE,
];
```

### **Relacionamentos Padronizados:**
```php
// ✅ Correto - Relacionamentos com nomes descritivos
public function customer(): BelongsTo
{
    return $this->belongsTo(Customer::class);
}

public function budgetItems(): HasMany
{
    return $this->hasMany(BudgetItem::class);
}
```

### **Accessors e Mutators:**
```php
// ✅ Correto - Accessors para dados derivados
public function getFullNameAttribute(): string
{
    return $this->first_name . \' \' . $this->last_name;
}

// ✅ Correto - Mutators para transformação
public function setEmailAttribute(string $value): void
{
    $this->attributes[\'email\'] = strtolower($value);
}
```

### **Scopes Úteis:**
```php
// ✅ Correto - Scopes básicos
public function scopeActive($query)
{
    return $query->where(\'active\', true);
}

public function scopeOrdered($query)
{
    return $query->orderBy(\'name\');
}
```

## Boas Práticas

### **1. Relacionamentos**
- Use nomes descritivos para relacionamentos
- Implemente relacionamentos condicionais quando necessário
- Use eager loading para evitar N+1 queries
- Documente relacionamentos importantes

### **2. Accessors/Mutators**
- Use accessors para dados derivados
- Use mutators para transformação de dados
- Mantenha lógica simples e clara
- Documente transformações complexas

### **3. Validação**
- Implemente regras de negócio no model
- Use constantes para valores fixos
- Valide relacionamentos obrigatórios
- Documente regras específicas

### **4. Scopes**
- Crie scopes para consultas frequentes
- Use scopes para filtros comuns
- Mantenha scopes simples e reutilizáveis
- Documente parâmetros de scopes

### **5. Métodos de Negócio**
- Implemente lógica específica no model apropriado
- Mantenha métodos coesos e com responsabilidade única
- Use nomes descritivos e claros
- Documente métodos públicos importantes';
    }

}
