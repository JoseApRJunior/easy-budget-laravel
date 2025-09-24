<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        'updated_at' => 'immutable_datetime',
    ];

    /**
     * Relacionamentos podem ser adicionados aqui se aplicável, ex: products, services.
     */
}