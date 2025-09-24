<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Model para representar categorias, global (sem scoping por tenant).
 */
class Category extends Model
{
    use HasFactory;

    protected $table = 'categories';

    protected $fillable = [
        'slug',
        'name',
    ];

    /**
     * Relacionamentos podem ser adicionados aqui se aplicável, ex: products, services.
     */
}