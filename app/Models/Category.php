<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model para representar categorias, com tenant_id opcional para compatibilidade com sistema legado.
 */
class Category extends Model
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

    protected $table = 'categories';

    protected $fillable = [
        'slug',
        'name',
        'tenant_id', // Adicionado para compatibilidade com CategoryEntity legada
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'tenant_id'  => 'integer',
        'slug'       => 'string',
        'name'       => 'string',
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
            'slug'      => 'required|string|max:255|unique:categories,slug',
            'name'      => 'required|string|max:255',
            'tenant_id' => 'nullable|integer|exists:tenants,id',
        ];
    }

    /**
     * Regras de validação para criação de categoria.
     */
    public static function createRules(): array
    {
        return [
            'slug'      => 'required|string|max:255|unique:categories,slug',
            'name'      => 'required|string|max:255',
            'tenant_id' => 'nullable|integer|exists:tenants,id',
        ];
    }

    /**
     * Regras de validação para atualização de categoria.
     */
    public static function updateRules( int $categoryId ): array
    {
        return [
            'slug'      => 'required|string|max:255|unique:categories,slug,' . $categoryId,
            'name'      => 'required|string|max:255',
            'tenant_id' => 'nullable|integer|exists:tenants,id',
        ];
    }

    /**
     * Validação customizada para verificar se o slug é único no tenant.
     */
    public static function validateUniqueSlugInTenant( string $slug, ?int $tenantId = null, ?int $excludeCategoryId = null ): bool
    {
        $query = static::where( 'slug', $slug );

        if ( $tenantId ) {
            $query->where( 'tenant_id', $tenantId );
        }

        if ( $excludeCategoryId ) {
            $query->where( 'id', '!=', $excludeCategoryId );
        }

        return !$query->exists();
    }

    /**
     * Validação customizada para verificar se o slug tem formato válido.
     */
    public static function validateSlugFormat( string $slug ): bool
    {
        return preg_match( '/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug );
    }

    /**
     * Get the services for the Category.
     */
    public function services(): HasMany
    {
        return $this->hasMany( Service::class);
    }

    /**
     * Relacionamentos podem ser adicionados aqui se aplicável, ex: products, services.
     */
}
