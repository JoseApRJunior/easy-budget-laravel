<?php
declare(strict_types=1);

namespace App\Models;

use App\Models\Tenant;
use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

class Contact extends Model
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
    protected $table = 'contacts';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'email',
        'phone',
        'email_business',
        'phone_business',
        'website',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'tenant_id'      => 'integer',
        'email'          => 'string',
        'phone'          => 'string',
        'email_business' => 'string',
        'phone_business' => 'string',
        'website'        => 'string',
        'created_at'     => 'immutable_datetime',
        'updated_at'     => 'datetime',
    ];

    /**
     * Get the tenant that owns the Contact.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo( Tenant::class);
    }

    /**
     * Get the customer associated with the Contact.
     */
    public function customer(): HasOne
    {
        return $this->hasOne( Customer::class);
    }

}