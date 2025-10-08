<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Tenant;
use App\Models\Traits\TenantScoped;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Activity extends Model
{
    use TenantScoped;

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();
        static::bootTenantScoped();
    }

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public const UPDATED_AT = null;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'activities';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'user_id',
        'action_type',
        'entity_type',
        'entity_id',
        'description',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'tenant_id'   => 'integer',
        'user_id'     => 'integer',
        'action_type' => 'string',
        'entity_type' => 'string',
        'entity_id'   => 'integer',
        'description' => 'string',
        'metadata'    => 'string',
        'created_at'  => 'immutable_datetime',
    ];

    /**
     * Regras de validação para o modelo Activity.
     */
    public static function businessRules(): array
    {
        return [
            'tenant_id'   => 'required|integer|exists:tenants,id',
            'user_id'     => 'required|integer|exists:users,id',
            'action_type' => 'required|string|max:50',
            'entity_type' => 'required|string|max:50',
            'entity_id'   => 'required|integer',
            'description' => 'required|string|max:65535',
            'metadata'    => 'nullable|string|max:65535',
        ];
    }

    /**
     * Get the tenant that owns the Activity.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo( Tenant::class);
    }

    /**
     * Get the user that owns the Activity.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo( User::class);
    }

}
