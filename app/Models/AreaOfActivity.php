<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Model para representar áreas de atividade.
 */
class AreaOfActivity extends Model
{
    use HasFactory;

    protected $table = 'areas_of_activity';

    protected $fillable = [
        'slug',
        'name',
        'is_active',
    ];

    protected $casts = [
        'slug' => 'string',
        'name' => 'string',
        'is_active' => 'boolean',
        'created_at' => 'immutable_datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Regras de validação para o modelo Plan.
     */
    public static function businessRules(): array
    {
        return [

        ];
    }

    /**
     * Índices para otimização de consultas
     */
    protected $indexes = [
        'slug' => 'unique',
        'is_active' => 'index',
    ];

    /**
     * Relacionamento com CommonData.
     */
    public function commonData()
    {
        return $this->hasMany(CommonData::class, 'area_of_activity_id');
    }

    /**
     * Scope para buscar apenas áreas ativas.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
