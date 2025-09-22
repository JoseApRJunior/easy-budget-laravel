<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Model para representar unidades de medida, global (sem scoping por tenant).
 */
class Unit extends Model
{
    use HasFactory;

    protected $table = 'units';

    protected $fillable = [ 
        'slug',
        'name',
        'is_active',
    ];

    protected $casts = [ 
        'is_active' => 'boolean',
    ];

    /**
     * Relacionamentos podem ser adicionados aqui se necessário.
     */
}
