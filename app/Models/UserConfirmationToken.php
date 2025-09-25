<?php

namespace App\Models;

use App\Models\Budget;
use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

class UserConfirmationToken extends Model
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
        'token'      => 'string',
        'expires_at' => 'datetime',
        'created_at' => 'immutable_datetime',
        'updated_at' => 'immutable_datetime',
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

    /**
     * Get the budgets for the UserConfirmationToken.
     */
    public function budgets(): HasMany
    {
        return $this->hasMany( Budget::class);
    }

}