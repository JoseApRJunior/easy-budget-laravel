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
        'slug'       => 'string',
        'name'       => 'string',
        'is_active'  => 'boolean',
        'created_at' => 'immutable_datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Regras de validação para o modelo Unit.
     */
    public static function businessRules(): array
    {
        return [
            'slug'      => 'required|string|max:50|unique:units,slug',
            'name'      => 'required|string|max:100|unique:units,name',
            'is_active' => 'boolean',
        ];
    }

}
