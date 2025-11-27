<?php

declare(strict_types=1);

namespace App\DesignPatterns\Models;

/**
 * Padrão Unificado para Models no Easy Budget Laravel
 *
 * Define convenções consistentes para desenvolvimento de models,
 * garantindo uniformidade, manutenibilidade e reutilização de código.
 */
class ModelPattern
{
    /**
     * PADRÃO UNIFICADO PARA MODELS
     *
     * Baseado na análise dos models existentes, definimos 3 níveis:
     */

    /**
     * NÍVEL 1 - Model Básico (Sem Relacionamentos)
     * Para models simples sem relacionamentos complexos
     *
     * @example Category, Unit, Profession
     */
    public function basicModel(): string
    {
        return '
class BasicModel extends Model
{
    use HasFactory;

    protected $fillable = [
        \'name\',
        \'slug\',
        \'active\',
    ];

    protected $casts = [
        \'active\' => \'boolean\',
        \'created_at\' => \'immutable_datetime\',
        \'updated_at\' => \'datetime\',
    ];

    // Regras de validação
    public static function businessRules(): array
    {
        return [
            \'name\' => \'required|string|max:255\',
            \'slug\' => \'required|string|max:255|unique:models,slug\',
            \'active\' => \'boolean\',
        ];
    }

    // Scopes básicos
    public function scopeActive($query)
    {
        return $query->where(\'active\', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy(\'name\');
    }
}';
    }

    /**
     * NÍVEL 2 - Model Intermediário (Com Relacionamentos)
     * Para models com relacionamentos importantes
     *
     * @example Customer, Product, Budget
     */
    public function intermediateModel(): string
    {
        return '
class IntermediateModel extends Model
{
    use HasFactory, TenantScoped;

    protected static function boot()
    {
        parent::boot();
        static::bootTenantScoped();
    }

    protected $fillable = [
        \'tenant_id\',
        \'name\',
        \'description\',
        \'status\',
        \'active\',
    ];

    protected $casts = [
        \'tenant_id\' => \'integer\',
        \'active\' => \'boolean\',
        \'created_at\' => \'immutable_datetime\',
        \'updated_at\' => \'datetime\',
    ];

    // Constantes de status
    public const STATUS_ACTIVE = \'active\';
    public const STATUS_INACTIVE = \'inactive\';

    public const STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_INACTIVE,
    ];

    // Regras de validação
    public static function businessRules(): array
    {
        return [
            \'tenant_id\' => \'required|integer|exists:tenants,id\',
            \'name\' => \'required|string|max:255\',
            \'description\' => \'nullable|string|max:1000\',
            \'status\' => \'required|in:\' . implode(\',\', self::STATUSES),
            \'active\' => \'boolean\',
        ];
    }

    // Relacionamentos
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    // Accessors
    public function getFormattedNameAttribute(): string
    {
        return ucfirst($this->name);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where(\'status\', self::STATUS_ACTIVE);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy(\'name\');
    }

    // Métodos auxiliares
    public function activate(): bool
    {
        return $this->update([\'status\' => self::STATUS_ACTIVE]);
    }

    public function deactivate(): bool
    {
        return $this->update([\'status\' => self::STATUS_INACTIVE]);
    }
}';
    }

    /**
     * NÍVEL 3 - Model Avançado (Com Relacionamentos Complexos)
     * Para models com relacionamentos complexos e lógica de negócio
     *
     * @example User, Budget, Invoice
     */
    public function advancedModel(): string
    {
        return '
class AdvancedModel extends Model
{
    use HasFactory, TenantScoped, Auditable;

    protected static function boot()
    {
        parent::boot();
        static::bootTenantScoped();
        static::bootAuditable();
    }

    protected $fillable = [
        \'tenant_id\',
        \'name\',
        \'email\',
        \'status\',
        \'settings\',
    ];

    protected $casts = [
        \'tenant_id\' => \'integer\',
        \'settings\' => \'array\',
        \'active\' => \'boolean\',
        \'created_at\' => \'immutable_datetime\',
        \'updated_at\' => \'datetime\',
    ];

    protected $hidden = [
        \'password\',
        \'remember_token\',
    ];

    // Constantes
    public const STATUS_ACTIVE = \'active\';
    public const STATUS_INACTIVE = \'inactive\';
    public const STATUS_PENDING = \'pending\';

    public const STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_INACTIVE,
        self::STATUS_PENDING,
    ];

    // Regras de validação
    public static function businessRules(): array
    {
        return [
            \'tenant_id\' => \'required|integer|exists:tenants,id\',
            \'name\' => \'required|string|max:255\',
            \'email\' => \'required|email|unique:models,email\',
            \'status\' => \'required|in:\' . implode(\',\', self::STATUSES),
        ];
    }

    // Relacionamentos complexos
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function relatedModels(): HasMany
    {
        return $this->hasMany(RelatedModel::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    // Accessors avançados
    public function getFullNameAttribute(): string
    {
        return $this->first_name . \' \' . $this->last_name;
    }

    public function getFormattedEmailAttribute(): string
    {
        return strtolower($this->email);
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    // Mutators
    public function setEmailAttribute(string $value): void
    {
        $this->attributes[\'email\'] = strtolower($value);
    }

    // Scopes avançados
    public function scopeActive($query)
    {
        return $query->where(\'status\', self::STATUS_ACTIVE);
    }

    public function scopeWithRelated($query)
    {
        return $query->with([\'relatedModels\', \'tags\']);
    }

    public function scopeRecent($query, int $days = 7)
    {
        return $query->where(\'created_at\', \'>=\', now()->subDays($days));
    }

    // Métodos de negócio
    public function activate(): bool
    {
        $this->status = self::STATUS_ACTIVE;
        return $this->save();
    }

    public function addTag(Tag $tag): void
    {
        if (!$this->tags()->where(\'tag_id\', $tag->id)->exists()) {
            $this->tags()->attach($tag->id);
        }
    }

    public function getStats(): array
    {
        return [
            \'total_related\' => $this->relatedModels()->count(),
            \'active_related\' => $this->relatedModels()->active()->count(),
            \'tag_count\' => $this->tags()->count(),
        ];
    }
}';
    }

    /**
     * CONVENÇÕES PARA RELACIONAMENTOS
     */

    /**
     * Relacionamentos Padronizados
     */
    public function relationshipConventions(): string
    {
        return '
// ✅ CORRETO - Relacionamentos padronizados

// 1. Relacionamentos básicos com convenções de nomenclatura
public function tenant(): BelongsTo
{
    return $this->belongsTo(Tenant::class);
}

public function creator(): BelongsTo
{
    return $this->belongsTo(User::class, \'created_by\');
}

public function updater(): BelongsTo
{
    return $this->belongsTo(User::class, \'updated_by\');
}

// 2. Relacionamentos com nomes descritivos
public function customer(): BelongsTo
{
    return $this->belongsTo(Customer::class);
}

public function budgetItems(): HasMany
{
    return $this->hasMany(BudgetItem::class);
}

public function serviceCategories(): BelongsToMany
{
    return $this->belongsToMany(Category::class, \'service_categories\');
}

// 3. Relacionamentos com accessors para dados derivados
public function getPrimaryEmailAttribute(): ?string
{
    $primaryContact = $this->contacts()
        ->where(\'type\', \'email\')
        ->where(\'is_primary\', true)
        ->first();

    return $primaryContact?->value;
}

public function getFullAddressAttribute(): string
{
    $address = $this->address;

    if (!$address) {
        return \'\';
    }

    $parts = array_filter([
        $address->address,
        $address->address_number,
        $address->neighborhood,
        $address->city . \' - \' . $address->state,
        $address->cep,
    ]);

    return implode(\', \', $parts);
}

// 4. Relacionamentos condicionais
public function activeBudgetItems(): HasMany
{
    return $this->hasMany(BudgetItem::class)
        ->where(\'active\', true);
}

public function pendingInvoices(): HasMany
{
    return $this->hasMany(Invoice::class)
        ->where(\'status\', \'pending\');
}

// ❌ INCORRETO - Não fazer isso

// 1. Não usar nomes genéricos para relacionamentos
public function data(): BelongsTo // ❌ Muito genérico
{
    return $this->belongsTo(CommonData::class);
}

// 2. Não fazer queries N+1 nos relacionamentos
public function getItemsAttribute(): Collection // ❌ Pode causar N+1
{
    return $this->budgetItems; // Sem eager loading
}

// 3. Não misturar responsabilidades
public function sendNotification(): bool // ❌ Model não deve enviar notificações
{
    // Lógica de notificação no model...
    return true;
}

// 4. Não usar relacionamentos desnecessariamente complexos
public function getAllRelatedData(): array // ❌ Muito complexo
{
    return [
        \'items\' => $this->budgetItems,
        \'invoices\' => $this->invoices,
        \'customer\' => $this->customer,
        \'customer_address\' => $this->customer->address,
        \'customer_contacts\' => $this->customer->contacts,
        // ... muitos outros relacionamentos
    ];
}';
    }

    /**
     * EXEMPLOS PRÁTICOS DE IMPLEMENTAÇÃO
     */

    /**
     * Exemplo de Model Nível 1 - Básico
     */
    public function basicModelExample(): string
    {
        return '<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Model básico para categorias - Sem relacionamentos complexos
 */
class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        \'name\',
        \'slug\',
        \'description\',
        \'active\',
    ];

    protected $casts = [
        \'active\' => \'boolean\',
        \'created_at\' => \'immutable_datetime\',
        \'updated_at\' => \'datetime\',
    ];

    // Constantes
    public const STATUS_ACTIVE = \'active\';
    public const STATUS_INACTIVE = \'inactive\';

    public const STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_INACTIVE,
    ];

    // Regras de validação
    public static function businessRules(): array
    {
        return [
            \'name\' => \'required|string|max:255\',
            \'slug\' => \'required|string|max:255|unique:categories,slug\',
            \'description\' => \'nullable|string|max:1000\',
            \'active\' => \'boolean\',
        ];
    }

    public static function createRules(): array
    {
        return [
            \'name\' => \'required|string|max:255\',
            \'slug\' => \'required|string|max:255|unique:categories,slug\',
            \'description\' => \'nullable|string|max:1000\',
        ];
    }

    public static function updateRules(int $id): array
    {
        return [
            \'name\' => \'required|string|max:255\',
            \'slug\' => \'required|string|max:255|unique:categories,slug,\' . $id,
            \'description\' => \'nullable|string|max:1000\',
        ];
    }

    // Relacionamentos básicos
    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    // Accessors
    public function getFormattedNameAttribute(): string
    {
        return ucfirst($this->name);
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->active;
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where(\'active\', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy(\'name\');
    }

    // Métodos auxiliares
    public function activate(): bool
    {
        return $this->update([\'active\' => true]);
    }

    public function deactivate(): bool
    {
        return $this->update([\'active\' => false]);
    }

    public function isInUse(): bool
    {
        return $this->services()->exists() || $this->products()->exists();
    }
}';
    }

    /**
     * Exemplo de Model Nível 2 - Intermediário
     */
    public function intermediateModelExample(): string
    {
        return '<?php

namespace App\Models;

use App\Models\Tenant;
use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model intermediário para produtos - Com relacionamentos importantes
 */
class Product extends Model
{
    use HasFactory, TenantScoped;

    protected static function boot()
    {
        parent::boot();
        static::bootTenantScoped();
    }

    protected $fillable = [
        \'tenant_id\',
        \'name\',
        \'sku\',
        \'description\',
        \'price\',
        \'category_id\',
        \'active\',
    ];

    protected $casts = [
        \'tenant_id\' => \'integer\',
        \'category_id\' => \'integer\',
        \'price\' => \'decimal:2\',
        \'active\' => \'boolean\',
        \'created_at\' => \'immutable_datetime\',
        \'updated_at\' => \'datetime\',
    ];

    // Constantes
    public const STATUS_ACTIVE = \'active\';
    public const STATUS_INACTIVE = \'inactive\';

    public const STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_INACTIVE,
    ];

    // Regras de validação
    public static function businessRules(): array
    {
        return [
            \'tenant_id\' => \'required|integer|exists:tenants,id\',
            \'name\' => \'required|string|max:255\',
            \'sku\' => \'required|string|max:100|unique:products,sku\',
            \'description\' => \'nullable|string|max:1000\',
            \'price\' => \'required|numeric|min:0\',
            \'category_id\' => \'required|integer|exists:categories,id\',
            \'active\' => \'boolean\',
        ];
    }

    // Relacionamentos
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function inventory(): HasOne
    {
        return $this->hasOne(ProductInventory::class);
    }

    public function budgetItems(): HasMany
    {
        return $this->hasMany(BudgetItem::class);
    }

    public function serviceItems(): HasMany
    {
        return $this->hasMany(ServiceItem::class);
    }

    // Accessors
    public function getFormattedPriceAttribute(): string
    {
        return \'R$ \' . number_format($this->price, 2, \',\', \'.\');
    }

    public function getStockQuantityAttribute(): int
    {
        return $this->inventory?->quantity ?? 0;
    }

    public function getIsLowStockAttribute(): bool
    {
        $minQuantity = $this->inventory?->min_quantity ?? 0;
        return $this->stock_quantity <= $minQuantity;
    }

    public function getUsageCountAttribute(): int
    {
        return $this->budgetItems()->count() + $this->serviceItems()->count();
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where(\'active\', true);
    }

    public function scopeInStock($query)
    {
        return $query->whereHas(\'inventory\', function ($q) {
            $q->where(\'quantity\', \'>\', 0);
        });
    }

    public function scopeLowStock($query)
    {
        return $query->whereHas(\'inventory\', function ($q) {
            $q->whereColumn(\'quantity\', \'<=\', \'min_quantity\');
        });
    }

    public function scopeByCategory($query, int $categoryId)
    {
        return $query->where(\'category_id\', $categoryId);
    }

    public function scopeByPriceRange($query, float $min, float $max)
    {
        return $query->whereBetween(\'price\', [$min, $max]);
    }

    // Métodos de negócio
    public function updateStock(int $quantity, string $reason = \'adjustment\'): bool
    {
        if (!$this->inventory) {
            return false;
        }

        $newQuantity = max(0, $this->inventory->quantity + $quantity);

        // Registra movimento de estoque
        InventoryMovement::create([
            \'tenant_id\' => $this->tenant_id,
            \'product_id\' => $this->id,
            \'type\' => $quantity > 0 ? \'in\' : \'out\',
            \'quantity\' => abs($quantity),
            \'reason\' => $reason,
        ]);

        return $this->inventory->update([\'quantity\' => $newQuantity]);
    }

    public function isAvailable(int $requestedQuantity = 1): bool
    {
        return $this->active && ($this->inventory?->quantity ?? 0) >= $requestedQuantity;
    }

    public function calculateTotalValue(int $quantity): float
    {
        return $this->price * $quantity;
    }
}';
    }

    /**
     * Exemplo de Model Nível 3 - Avançado
     */
    public function advancedModelExample(): string
    {
        return '<?php

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
 * Model avançado para usuários - Com relacionamentos complexos e lógica de negócio
 */
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
        \'tenant_id\',
        \'email\',
        \'password\',
        \'first_name\',
        \'last_name\',
        \'is_active\',
        \'settings\',
    ];

    protected $hidden = [
        \'password\',
        \'remember_token\',
    ];

    protected $casts = [
        \'tenant_id\' => \'integer\',
        \'settings\' => \'array\',
        \'is_active\' => \'boolean\',
        \'email_verified_at\' => \'datetime\',
        \'created_at\' => \'immutable_datetime\',
        \'updated_at\' => \'datetime\',
    ];

    // Constantes
    public const STATUS_ACTIVE = \'active\';
    public const STATUS_INACTIVE = \'inactive\';
    public const STATUS_PENDING = \'pending\';

    public const ROLE_ADMIN = \'admin\';
    public const ROLE_PROVIDER = \'provider\';
    public const ROLE_USER = \'user\';

    public const ROLES = [
        self::ROLE_ADMIN,
        self::ROLE_PROVIDER,
        self::ROLE_USER,
    ];

    // Regras de validação
    public static function businessRules(): array
    {
        return [
            \'tenant_id\' => \'required|integer|exists:tenants,id\',
            \'email\' => \'required|email|max:255|unique:users,email\',
            \'password\' => \'required|string|min:8|max:255\',
            \'first_name\' => \'required|string|max:100\',
            \'last_name\' => \'required|string|max:100\',
            \'is_active\' => \'boolean\',
        ];
    }

    // Relacionamentos complexos
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function provider(): HasOne
    {
        return $this->hasOne(Provider::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, \'user_roles\')
            ->withPivot([\'tenant_id\'])
            ->withTimestamps();
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, \'user_permissions\')
            ->withPivot([\'tenant_id\'])
            ->withTimestamps();
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }

    public function budgets(): HasMany
    {
        return $this->hasMany(Budget::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    // Accessors avançados
    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . \' \' . $this->last_name);
    }

    public function getInitialsAttribute(): string
    {
        return strtoupper(substr($this->first_name, 0, 1) . substr($this->last_name, 0, 1));
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->provider?->company_name ?? $this->full_name;
    }

    public function getIsAdminAttribute(): bool
    {
        return $this->hasRole(self::ROLE_ADMIN);
    }

    public function getIsProviderAttribute(): bool
    {
        return $this->hasRole(self::ROLE_PROVIDER);
    }

    public function getTenantRolesAttribute(): Collection
    {
        return $this->roles()->wherePivot(\'tenant_id\', $this->tenant_id)->get();
    }

    public function getTenantPermissionsAttribute(): Collection
    {
        return $this->permissions()->wherePivot(\'tenant_id\', $this->tenant_id)->get();
    }

    // Mutators
    public function setEmailAttribute(string $value): void
    {
        $this->attributes[\'email\'] = strtolower($value);
    }

    public function setPasswordAttribute(string $value): void
    {
        $this->attributes[\'password\'] = bcrypt($value);
    }

    // Scopes avançados
    public function scopeActive($query)
    {
        return $query->where(\'is_active\', true);
    }

    public function scopeProviders($query)
    {
        return $query->whereHas(\'roles\', function ($q) {
            $q->where(\'name\', self::ROLE_PROVIDER);
        });
    }

    public function scopeAdmins($query)
    {
        return $query->whereHas(\'roles\', function ($q) {
            $q->where(\'name\', self::ROLE_ADMIN);
        });
    }

    public function scopeWithProvider($query)
    {
        return $query->whereHas(\'provider\');
    }

    public function scopeRecent($query, int $days = 7)
    {
        return $query->where(\'created_at\', \'>=\', now()->subDays($days));
    }

    // Métodos de autorização
    public function hasRole(string $role): bool
    {
        return $this->roles()
            ->wherePivot(\'tenant_id\', $this->tenant_id)
            ->where(\'name\', $role)
            ->exists();
    }

    public function hasPermission(string $permission): bool
    {
        return $this->permissions()
            ->wherePivot(\'tenant_id\', $this->tenant_id)
            ->where(\'name\', $permission)
            ->exists();
    }

    public function hasAnyRole(array $roles): bool
    {
        return $this->roles()
            ->wherePivot(\'tenant_id\', $this->tenant_id)
            ->whereIn(\'name\', $roles)
            ->exists();
    }

    public function hasAnyPermission(array $permissions): bool
    {
        return $this->permissions()
            ->wherePivot(\'tenant_id\', $this->tenant_id)
            ->whereIn(\'name\', $permissions)
            ->exists();
    }

    // Métodos de gestão de roles
    public function assignRole(Role $role): void
    {
        if (!$this->hasRole($role->name)) {
            $this->roles()->attach($role->id, [
                \'tenant_id\' => $this->tenant_id
            ]);
        }
    }

    public function removeRole(Role $role): void
    {
        $this->roles()->detach($role->id);
    }

    public function syncRoles(array $roleIds): void
    {
        $this->roles()->sync($roleIds);
    }

    // Métodos de atividade
    public function logActivity(string $action, array $metadata = []): Activity
    {
        return $this->activities()->create([
            \'tenant_id\' => $this->tenant_id,
            \'action\' => $action,
            \'metadata\' => $metadata,
            \'ip_address\' => request()->ip(),
            \'user_agent\' => request()->userAgent(),
        ]);
    }

    // Métodos de estatísticas
    public function getStats(): array
    {
        return [
            \'total_budgets\' => $this->budgets()->count(),
            \'total_invoices\' => $this->invoices()->count(),
            \'active_budgets\' => $this->budgets()->active()->count(),
            \'pending_invoices\' => $this->invoices()->pending()->count(),
            \'role_count\' => $this->roles()->count(),
            \'permission_count\' => $this->permissions()->count(),
            \'activity_count\' => $this->activities()->count(),
        ];
    }

    // Métodos de validação customizada
    public function canAccessResource(string $resource): bool
    {
        // Lógica específica de autorização
        return $this->is_admin || $this->hasPermission(\"access_{$resource}\");
    }

    public function isTenantAdmin(): bool
    {
        return $this->hasRole(self::ROLE_ADMIN) &&
               $this->provider &&
               $this->provider->is_tenant_owner;
    }

    // Métodos de configuração
    public function updateSettings(array $settings): bool
    {
        $currentSettings = $this->settings ?? [];
        $newSettings = array_merge($currentSettings, $settings);

        return $this->update([\'settings\' => $newSettings]);
    }

    public function getSetting(string $key, $default = null)
    {
        return data_get($this->settings, $key, $default);
    }

    public function setSetting(string $key, $value): bool
    {
        return $this->updateSettings([$key => $value]);
    }
}';
    }

    /**
     * GUIA DE IMPLEMENTAÇÃO
     */
    public function getImplementationGuide(): string
    {
        return '
## Guia de Implementação - Escolhendo o Nível Correto

### NÍVEL 1 - Model Básico
✅ Quando usar:
- Entidades simples sem relacionamentos importantes
- Catálogos básicos (categorias, unidades, profissões)
- Tabelas de configuração global
- Não há necessidade de multi-tenant

❌ Não usar quando:
- Relacionamentos são importantes para o negócio
- Multi-tenant necessário
- Scopes avançados necessários
- Lógica de negócio complexa

### NÍVEL 2 - Model Intermediário
✅ Quando usar:
- Entidades com relacionamentos importantes
- Multi-tenant necessário
- Scopes específicos necessários
- Relacionamentos 1:N e N:1

❌ Não usar quando:
- Relacionamentos muitos-para-muitos complexos
- Lógica de negócio muito avançada
- Relacionamentos polimórficos necessários
- Métodos de autorização complexos

### NÍVEL 3 - Model Avançado
✅ Quando usar:
- Relacionamentos muitos-para-muitos
- Lógica de autorização complexa
- Métodos de negócio específicos
- Relacionamentos polimórficos
- Auditoria automática necessária

❌ Não usar quando:
- Modelo muito simples (use nível 1)
- Relacionamentos simples (use nível 2)
- Projeto inicial sem necessidade de complexidade

## Benefícios do Padrão

✅ **Consistência**: Todos os models seguem convenções unificadas
✅ **Manutenibilidade**: Relacionamentos padronizados e documentados
✅ **Performance**: Accessors e scopes otimizados
✅ **Flexibilidade**: Diferentes níveis para diferentes necessidades
✅ **Testabilidade**: Métodos de negócio bem definidos
✅ **Escalabilidade**: Preparado para crescimento

## Estrutura Recomendada

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

### **Regras de Validação:**
```php
public static function businessRules(): array
{
    return [
        \'name\' => \'required|string|max:255\',
        \'status\' => \'required|in:\' . implode(\',\', self::STATUSES),
    ];
}
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

// ✅ Correto - Relacionamentos condicionais
public function activeItems(): HasMany
{
    return $this->hasMany(BudgetItem::class)->where(\'active\', true);
}
```

### **Accessors e Mutators:**
```php
// ✅ Correto - Accessors para dados derivados
public function getFullNameAttribute(): string
{
    return $this->first_name . \' \' . $this->last_name;
}

public function getFormattedPriceAttribute(): string
{
    return \'R$ \' . number_format($this->price, 2, \',\', \'.\');
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

// ✅ Correto - Scopes avançados
public function scopeWithRelated($query)
{
    return $query->with([\'customer\', \'items\']);
}

public function scopeRecent($query, int $days = 7)
{
    return $query->where(\'created_at\', \'>=\', now()->subDays($days));
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
