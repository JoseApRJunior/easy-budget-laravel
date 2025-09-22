<?php
declare(strict_types=1);

namespace App\Models;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class RolePermission extends Pivot
{
    use BelongsToTenant;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'role_permissions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [ 
        'tenant_id',
        'role_id',
        'permission_id',
    ];

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [ 
        'tenant_id'     => 'integer',
        'role_id'       => 'integer',
        'permission_id' => 'integer',
        'created_at'    => 'datetime_immutable',
        'updated_at'    => 'datetime_immutable',
    ];

    /**
     * Get the role that owns the RolePermission.
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo( Role::class);
    }

    /**
     * Get the permission that owns the RolePermission.
     */
    public function permission(): BelongsTo
    {
        return $this->belongsTo( Permission::class);
    }

    /**
     * Get the tenant that owns the RolePermission.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo( Tenant::class);
    }

}