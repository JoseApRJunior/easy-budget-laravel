<?php

namespace App\Models;

use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Report extends Model
{
    use TenantScoped;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'reports';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'user_id',
        'hash',
        'type',
        'description',
        'file_name',
        'status',
        'format',
        'size',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'tenant_id'   => 'integer',
        'user_id'     => 'integer',
        'hash'        => 'string',
        'type'        => 'string',
        'description' => 'string',
        'file_name'   => 'string',
        'status'      => 'string',
        'format'      => 'string',
        'size'        => 'float',
        'created_at'  => 'immutable_datetime',
    ];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public const UPDATED_AT = null;

    /**
     * Get the tenant that owns the Report.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo( Tenant::class);
    }

    /**
     * Get the user that owns the Report.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo( User::class);
    }

}