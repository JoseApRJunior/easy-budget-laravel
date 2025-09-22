<?php

namespace App\Models;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\Traits\TenantScoped;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Carbon;

class UserRole extends Pivot
{
    use TenantScoped;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'user_roles';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [ 
        'user_id',
        'role_id',
        'tenant_id',
    ];

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * Get the user that owns the UserRole.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo( User::class);
    }

    /**
     * Get the role that owns the UserRole.
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo( Role::class);
    }

    /**
     * Get the tenant that owns the UserRole.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo( Tenant::class);
    }

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [ 
        'user_id'    => 'integer',
        'role_id'    => 'integer',
        'tenant_id'  => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
