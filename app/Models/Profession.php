<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Model para representar profissões do sistema.
 */
class Profession extends Model
{
    use HasFactory;

    protected $table = 'professions';

    protected $fillable = [
        'slug',
        'name',
        'is_active',
    ];

    protected $attributes = [
        'is_active' => 1, // Valor padrão conforme definido no SQL
    ];

    protected $casts = [
        'slug'       => 'string',
        'name'       => 'string',
        'is_active'  => 'boolean',
        'created_at' => 'immutable_datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Define as chaves únicas da tabela.
     */
    protected array $uniqueKeys = [
        'slug' => [ 'slug' ],
    ];

    /**
     * Regras de validação para o modelo Profession.
     */
    public static function businessRules(): array
    {
        return [
            'slug'      => [
                'required',
                'string',
                'max:50',
                'unique:professions,slug',
                'regex:/^[a-z0-9-]+$/'
            ],
            'name'      => [
                'required',
                'string',
                'max:100'
            ],
            'is_active' => [
                'boolean'
            ]
        ];
    }

    /**
     * Relacionamento com CommonData.
     */
    public function commonData()
    {
        return $this->hasMany( CommonData::class, 'profession_id' );
    }

}
