<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Model para representar áreas de atividade, com tenant_id opcional para compatibilidade com sistema legado.
 */
class AreaOfActivity extends Model
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

    protected $table = 'areas_of_activity';

    protected $fillable = [
        'slug',
        'name',
        'is_active',
        'tenant_id', // Adicionado para compatibilidade com AreaOfActivityEntity legada
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
     * Relacionamento com CommonData.
     */
    public function commonData()
    {
        return $this->hasMany( CommonData::class, 'area_of_activity_id' );
    }

}
