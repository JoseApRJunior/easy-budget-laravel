<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Model para representar profissões, global (sem scoping por tenant).
 */
class Profession extends Model
{
    use HasFactory;

    protected $table = 'professions';

    protected $fillable = [
        'slug',
        'name',
        'description',
        'order_index',
        'is_active',
    ];

    protected $casts = [
        'description' => 'string|null',
        'order_index' => 'integer|null',
        'is_active' => 'boolean',
    ];

    /**
     * Relacionamento com CommonData.
     */
    public function commonData()
    {
        return $this->hasMany(CommonData::class, 'profession_id');
    }
}
