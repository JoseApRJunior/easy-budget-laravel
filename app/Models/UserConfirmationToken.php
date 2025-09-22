<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class UserConfirmationToken extends Model
{
    use BelongsToTenant;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'user_confirmation_tokens';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [ 
        'user_id',
        'tenant_id',
        'token',
        'expires_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [ 
        'tenant_id'  => 'integer',
        'expires_at' => 'datetime_immutable',
        'created_at' => 'datetime_immutable',
        'updated_at' => 'datetime_immutable',
    ];

    /**
     * Get the user that owns the UserConfirmationToken.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo( User::class);
    }

    /**
     * Get the tenant that owns the UserConfirmationToken.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo( Tenant::class);
    }

}