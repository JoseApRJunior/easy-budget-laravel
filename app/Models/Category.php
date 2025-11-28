<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Pivots\CategoryTenant;
use App\Models\Traits\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Model para representar categorias em sistema multitenancy via pivot table.
 *
 * Categorias podem ser:
 * - Globais: Sem vínculo em category_tenant (disponíveis para todos)
 * - Custom: Com vínculo em category_tenant onde is_custom = true
 */
class Category extends Model
{
    use Auditable, HasFactory, SoftDeletes;

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
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'slug' => 'string',
        'name' => 'string',
        'parent_id' => 'integer',
        'is_active' => 'boolean',
        'created_at' => 'immutable_datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Campos que devem ser tratados como datas imutáveis.
     */
    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
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

        return ! $query->exists();
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

    public function scopeForTenant(Builder $query, ?int $tenantId): Builder
    {
        if ($tenantId === null) {
            return $query;
        }

        return $query->where(function ($q) use ($tenantId) {
            $q->whereHas('tenants', function ($t) use ($tenantId) {
                $t->where('tenant_id', $tenantId);
            })
            ->orWhereHas('tenants', function ($t) {
                $t->where('is_custom', false);
            })
            ->orWhereDoesntHave('tenants');
        });
    }

    /**
     * Scope para apenas categorias globais (não vinculadas a nenhum tenant)
     */
    public function scopeGlobalOnly(Builder $query): Builder
    {
        return $query->whereDoesntHave('tenants', function ($t) {
            $t->where('is_custom', true);
        });
    }

    /**
     * Scope para apenas categorias custom de um tenant
     */
    public function scopeCustomOnly(Builder $query, int $tenantId): Builder
    {
        return $query->whereHas('tenants', function ($t) use ($tenantId) {
            $t->where('tenant_id', $tenantId)
              ->where('is_custom', true);
        });
    }

    /**
     * Verifica se é categoria global (sem vínculo com tenants)
     */
    public function isGlobal(): bool
    {
        return !$this->tenants()->wherePivot('is_custom', true)->exists();
    }

    /**
     * Verifica se é custom de um tenant específico
     */
    public function isCustomFor(int $tenantId): bool
    {
        return $this->tenants()
            ->where('tenant_id', $tenantId)
            ->wherePivot('is_custom', true)
            ->exists();
    }

    /**
     * Verifica se categoria está disponível para um tenant
     */
    public function isAvailableFor(int $tenantId): bool
    {
        return $this->isGlobal()
            || $this->tenants()->where('tenant_id', $tenantId)->exists();
    }

    /**
     * Relacionamento com Tenant (legado - mantido para compatibilidade)
     *
     * @deprecated Use tenants() many-to-many ao invés deste belongsTo
     */
    public function tenant(): BelongsTo
    {
        // Retorna relacionamento vazio para evitar quebrar código legado
        return $this->belongsTo(\App\Models\Tenant::class, 'id', 'id')
            ->whereRaw('1 = 0'); // Nunca retorna resultados
    }
}
