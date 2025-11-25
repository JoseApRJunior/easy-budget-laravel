<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model para representar categorias, com tenant_id opcional para compatibilidade com sistema legado.
 */
class Category extends Model
{
    use HasFactory, \App\Models\Traits\TenantScoped, \App\Models\Traits\Auditable;

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();
        static::bootTenantScoped();
        static::bootAuditable();
    }

    protected $table = 'categories';

    protected $fillable = [
        'tenant_id',
        'slug',
        'name',
        'description',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'tenant_id'   => 'integer',
        'slug'        => 'string',
        'name'        => 'string',
        'description' => 'string',
        'is_active'   => 'boolean',
        'created_at'  => 'immutable_datetime',
        'updated_at'  => 'datetime',
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
            'tenant_id'  => 'required|integer|exists:tenants,id',
            'name'       => 'required|string|max:255',
            'slug'       => 'required|string|max:255|unique:categories,slug,NULL,id,tenant_id,' . (auth()->user()->tenant_id ?? 'NULL'),
            'description' => 'nullable|string',
            'is_active'  => 'boolean',
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

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Relação com a categoria pai (para hierarquia)
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /**
     * Relação com as categorias filhas (para hierarquia)
     */
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    /**
     * Verifica se esta categoria tem filhas
     */
    public function hasChildren(): bool
    {
        return $this->children()->exists();
    }

    /**
     * Contagem de categorias filhas ativas
     */
    public function getActiveChildrenCountAttribute(): int
    {
        return $this->children()->where('is_active', true)->count();
    }
}
