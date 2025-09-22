<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Activity extends Model
{
    use TenantScoped;

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
        'budget_id',
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
        'metadata'   => 'array',
        'created_at' => 'datetime_immutable',
        'updated_at' => 'datetime_immutable',
    ];

    /**
     * Get the tenant that owns the Activity.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo( Tenant::class);
    }

    /**
     * Get the budget that owns the Activity.
     */
    public function budget(): BelongsTo
    {
        return $this->belongsTo( Budget::class);
    }

    /**
     * Get the user that owns the Activity.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo( User::class);
    }

}
