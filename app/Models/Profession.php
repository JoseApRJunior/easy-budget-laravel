<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Model para representar profissões, com tenant_id opcional para compatibilidade com sistema legado.
 */
class Profession extends Model
{
    use HasFactory;

    protected $table = 'professions';

    protected $fillable = [
        'slug',
        'name',
        'is_active',
        'tenant_id', // Adicionado para compatibilidade com ProfessionEntity legada
    ];

    protected $casts = [
        'tenant_id'  => 'integer',
        'slug'       => 'string',
        'name'       => 'string',
        'is_active'  => 'boolean',
        'created_at' => 'immutable_datetime',
        'updated_at' => 'immutable_datetime',
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
     * Relacionamento com CommonData.
     */
    public function commonData()
    {
        return $this->hasMany( CommonData::class, 'profession_id' );
    }

}
