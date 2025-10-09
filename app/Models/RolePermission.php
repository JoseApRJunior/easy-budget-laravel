<?php
declare(strict_types=1);

namespace App\Models;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class RolePermission extends Pivot
{
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
        'role_id',
        'permission_id',
    ];

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'role_id'       => 'integer',
        'permission_id' => 'integer',
        'created_at'    => 'immutable_datetime',
        'updated_at'    => 'datetime',
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
     * Accessor para tratar valores zero-date no updated_at.
     */
    public function getUpdatedAtAttribute( $value )
    {
        return ( $value === '0000-00-00 00:00:00' || empty( $value ) ) ? null : new \DateTime( $value );
    }

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

}
