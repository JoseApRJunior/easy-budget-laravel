<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Model para representar categorias.
 *
 * Categorias são compartilhadas entre todos os tenants.
 */
class Category extends Model
{
    use Auditable, HasFactory, SoftDeletes;

    protected $table = 'categories';

    protected $fillable = [
        'slug',
        'name',
        'parent_id',
        'is_active',
        'is_custom',
        'tenant_id',
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
        'is_custom'  => 'boolean',
        'tenant_id'  => 'integer',
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
            'slug' => 'required|string|max:255',
        ];
    }

    /**
     * Validação customizada para verificar se o slug é único.
     */
    public static function validateUniqueSlug( string $slug, ?int $tenantId = null, ?int $excludeCategoryId = null ): bool
    {
        $query = static::where( 'slug', $slug );

        // Se tenantId for fornecido, verificar apenas no contexto do tenant
        if ( $tenantId !== null ) {
            $query->where( 'tenant_id', $tenantId );
        } else {
            // Para categorias globais, verificar apenas categorias sem tenant_id
            $query->whereNull( 'tenant_id' );
        }

        // Se excludeCategoryId for fornecido, ignorar a categoria com esse ID
        if ( $excludeCategoryId !== null ) {
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

    public function parent(): BelongsTo
    {
        return $this->belongsTo( Category::class, 'parent_id' );
    }

    public function children(): HasMany
    {
        return $this->hasMany( Category::class, 'parent_id' );
    }

    /**
     * Relacionamento com tenants.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function tenants()
    {
        return $this->belongsToMany( Tenant::class, 'category_tenant', 'category_id', 'tenant_id' )
            ->withTimestamps();
    }

    public function hasChildren(): bool
    {
        return $this->children()->exists();
    }

    public function getActiveChildrenCountAttribute(): int
    {
        return $this->children()->where( 'is_active', true )->count();
    }

    /**
     * Verifica se a categoria é uma categoria global do sistema.
     *
     * @return bool True se for uma categoria global, false caso contrário
     */
    public function isGlobal(): bool
    {
        // Categorias globais não têm tenant_id ou têm is_custom = false
        return $this->tenant_id === null || !$this->is_custom;
    }

    /**
     * Verifica se a categoria é custom para um tenant específico.
     *
     * @param int $tenantId ID do tenant
     * @return bool True se for uma categoria custom para o tenant
     */
    public function isCustomFor( int $tenantId ): bool
    {
        // Categorias custom têm tenant_id e is_custom = true
        return $this->tenant_id === $tenantId && $this->is_custom;
    }

    /**
     * Verifica se a categoria está disponível para um tenant específico.
     *
     * @param int $tenantId ID do tenant
     * @return bool True se a categoria for global ou custom para o tenant
     */
    public function isAvailableFor( int $tenantId ): bool
    {
        // Categorias globais estão sempre disponíveis
        if ( $this->isGlobal() ) {
            return true;
        }

        // Categorias custom do tenant estão disponíveis
        return $this->isCustomFor( $tenantId );
    }

    /**
     * Escopo para filtrar apenas categorias globais.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeGlobalOnly( $query )
    {
        return $query->whereNull( 'tenant_id' );
    }

    /**
     * Verifica se definir um parent_id criaria uma referência circular.
     *
     * @param int $proposedParentId ID do parent que se deseja definir
     * @return bool True se criar loop, false caso contrário
     */
    public function wouldCreateCircularReference( int $proposedParentId ): bool
    {
        // Se não tem parent proposto, não há loop
        if ( !$proposedParentId ) {
            return false;
        }

        // Se o parent proposto é a própria categoria, é loop direto
        if ( $proposedParentId === $this->id ) {
            return true;
        }

        // Percorrer ancestrais do parent proposto
        $visited   = [ $this->id ]; // Evitar loops infinitos
        $currentId = $proposedParentId;
        $maxDepth  = 20; // Limite de segurança
        $depth     = 0;

        while ( $currentId && $depth < $maxDepth ) {
            // Se encontramos a categoria atual na cadeia de ancestrais, é loop
            if ( in_array( $currentId, $visited ) ) {
                return true;
            }

            $visited[] = $currentId;

            // Buscar próximo ancestral (incluindo deletados)
            $parent = Category::withTrashed()->find( $currentId );
            if ( !$parent ) {
                break; // Parent não existe, não há loop
            }

            $currentId = $parent->parent_id;
            $depth++;
        }

        return false;
    }

}
