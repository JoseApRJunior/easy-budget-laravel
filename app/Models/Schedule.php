<?php

namespace App\Models;

use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Schedule extends Model
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
    protected $table = 'schedules';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'service_id',
        'user_confirmation_token_id',
        'start_date_time',
        'location',
        'end_date_time',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'tenant_id'                  => 'integer',
        'service_id'                 => 'integer',
        'user_confirmation_token_id' => 'integer',
        'start_date_time'            => 'immutable_datetime',
        'end_date_time'              => 'immutable_datetime',
        'created_at'                 => 'immutable_datetime',
        'updated_at'                 => 'immutable_datetime',
    ];

    /**
     * Get the tenant that owns the Schedule.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo( Tenant::class);
    }

    /**
     * Get the service that owns the Schedule.
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo( Service::class);
    }

    /**
     * Get the user confirmation token that owns the Schedule.
     */
    public function userConfirmationToken(): BelongsTo
    {
        return $this->belongsTo( UserConfirmationToken::class);
    }

}