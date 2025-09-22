<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Model para representar categorias, scoped por tenant.
 */
class Category extends Model
{
    use HasFactory, TenantScoped;

    protected $table = 'categories';

    protected $fillable = [ 
        'slug',
        'name',
        'description',
        'is_active',
        'tenant_id',
    ];

    protected $casts = [ 
        'description' => 'string|null',
        'is_active'   => 'boolean',
    ];

    /**
     * Relacionamentos podem ser adicionados aqui se aplicável, ex: products, services.
     */
}
