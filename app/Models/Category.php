<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model para representar categorias, com tenant_id opcional para compatibilidade com sistema legado.
 */
class Category extends Model
{
    use HasFactory;

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
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
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
            'slug' => 'required|string|max:255|unique:categories,slug',
            'name' => 'required|string|max:255',
        ];
    }

    /**
     * Validação customizada para verificar se o slug .
     */
    public static function validateUniqueSlug( string $slug, ?int $excludeCategoryId = null ): bool
    {
        $query = static::where( 'slug', $slug );

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
