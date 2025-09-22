<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Tenant;
use App\Models\Traits\TenantScoped;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Activity extends Model
{
    use TenantScoped;

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
        'tenant_id'  => 'integer',
        'user_id'    => 'integer',
        'entity_id'  => 'integer',
        'metadata'   => 'array',
        'created_at' => 'immutable_datetime',
    ];

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
