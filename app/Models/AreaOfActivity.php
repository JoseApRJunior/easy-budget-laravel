<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Model para representar áreas de atividade, global (sem scoping por tenant).
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
        'is_active' => 'boolean',
    ];

    /**
     * Relacionamento com CommonData.
     */
    public function commonData()
    {
        return $this->hasMany( CommonData::class, 'area_of_activity_id' );
    }

}
