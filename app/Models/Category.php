<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Pivots\CategoryTenant;
use App\Models\Traits\Auditable;

/**
 * Model para representar categorias, com tenant_id opcional para compatibilidade com sistema legado.
 */
class Category extends Model
{
    use HasFactory, Auditable;

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();
    }

    protected $table = 'categories';

    protected $fillable = [
        'slug',
        'name',
        'parent_id',
        'tenant_id',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'slug'       => 'string',
        'name'       => 'string',
        'parent_id'  => 'integer',
        'is_active'  => 'boolean',
        'created_at' => 'immutable_datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Campos que devem ser tratados como datas imutáveis.
     */
    protected $dates = [
        'created_at',
        'updated_at',
    ];

    /**
     * Regras de validação para o modelo Category.
     */
    public static function businessRules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:categories,slug',
        ];
    }

    /**
     * Validação customizada para verificar se o slug .
     */
    public static function validateUniqueSlug(string $slug, ?int $excludeCategoryId = null): bool
    {
        $query = static::where('slug', $slug);

        if ($excludeCategoryId) {
            $query->where('id', '!=', $excludeCategoryId);
        }

        return !$query->exists();
    }

    /**
     * Validação customizada para verificar se o slug tem formato válido.
     */
    public static function validateSlugFormat(string $slug): bool
    {
        return preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug);
    }

    /**
     * Get the services for the Category.
     */
    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    public function tenants(): BelongsToMany
    {
        return $this->belongsToMany(Tenant::class, 'category_tenant')
            ->using(CategoryTenant::class)
            ->withPivot(['is_default', 'is_custom'])
            ->withTimestamps();
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function hasChildren(): bool
    {
        return $this->children()->exists();
    }

    public function getActiveChildrenCountAttribute(): int
    {
        return $this->children()->where('is_active', true)->count();
    }

    public function scopeOwnedByTenant(Builder $query, int $tenantId): Builder
    {
        return $query
            ->whereHas('tenants', function ($t) use ($tenantId) {
                $t->where('tenant_id', $tenantId);
            })
            ->where(function ($q) use ($tenantId) {
                $q->whereNull('categories.tenant_id')
                  ->orWhere('categories.tenant_id', $tenantId);
            });
    }

    public function scopeForTenantWithGlobals(Builder $query, ?int $tenantId): Builder
    {
        return $query->where(function ($q) use ($tenantId) {
            if ($tenantId !== null) {
                $q->whereHas('tenants', function ($t) use ($tenantId) {
                    $t->where('tenant_id', $tenantId);
                });
            }
            $q->orDoesntHave('tenants');
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class, 'tenant_id');
    }
}
