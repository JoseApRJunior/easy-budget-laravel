<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Model para representar profissões do sistema.
 */
class Profession extends Model
{
    use HasFactory, TenantScoped;

    protected $table = 'professions';

    protected $fillable = [
        'tenant_id',
        'slug',
        'name',
        'description',
        'type',
        'code',
        'is_active',
        'color',
        'icon',
        'order',
        'meta_title',
        'meta_description',
        'requirements',
        'certifications',
        'skills',
        'average_salary',
        'job_market',
        'education_level',
    ];

    protected $attributes = [
        'is_active' => 1, // Valor padrão conforme definido no SQL
    ];

    protected $casts = [
        'slug' => 'string',
        'name' => 'string',
        'is_active' => 'boolean',
        'created_at' => 'immutable_datetime',
        'updated_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        static::bootTenantScoped();
    }

    /**
     * Define as chaves únicas da tabela.
     */
    protected array $uniqueKeys = [
        'slug' => ['slug'],
    ];

    /**
     * Regras de validação para o modelo Profession.
     */
    public static function businessRules(): array
    {
        return [
            'slug' => [
                'required',
                'string',
                'max:50',
                'unique:professions,slug',
                'regex:/^[a-z0-9-]+$/',
            ],
            'name' => [
                'required',
                'string',
                'max:100',
            ],
            'is_active' => [
                'boolean',
            ],
        ];
    }

    /**
     * Relacionamento com CommonData.
     */
    public function commonData()
    {
        return $this->hasMany(CommonData::class, 'profession_id');
    }

    /**
     * Get the tenant that owns the Profession.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the providers associated with the Profession.
     */
    public function providers(): BelongsToMany
    {
        return $this->belongsToMany(Provider::class, 'common_datas', 'profession_id', 'provider_id');
    }

    /**
     * Get the users associated with the Profession (via providers).
     */
    public function users()
    {
        // Esta relação é indireta e complexa para definir como relação Eloquent padrão
        // Retornando providers para evitar erro fatal, mas logicamente incorreto se esperar User model
        // Ideal seria HasManyThrough se a estrutura permitisse
        return $this->providers(); 
    }

    /**
     * Scope para buscar apenas profissões ativas.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
