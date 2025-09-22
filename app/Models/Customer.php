<?php
declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Customer extends Model
{
    use TenantScoped;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'customers';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [ 
        'tenant_id',
        'common_data_id',
        'contact_id',
        'address_id',
        'status',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array<string, string>
     */
    protected $attributes = [ 
        'status' => 'active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [ 
        'tenant_id'      => 'integer',
        'common_data_id' => 'integer',
        'contact_id'     => 'integer',
        'address_id'     => 'integer',
        'status'         => 'string',
        'created_at'     => 'datetime',
        'updated_at'     => 'datetime',
    ];

    /**
     * Get the tenant that owns the Customer.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo( Tenant::class);
    }

    /**
     * Get the common data associated with the Customer.
     */
    public function commonData(): BelongsTo
    {
        return $this->belongsTo( CommonData::class);
    }

    /**
     * Get the contact associated with the Customer.
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo( Contact::class);
    }

    /**
     * Get the address associated with the Customer.
     */
    public function address(): BelongsTo
    {
        return $this->belongsTo( Address::class);
    }

    /**
     * Get the budgets for the Customer.
     */
    public function budgets(): HasMany
    {
        return $this->hasMany( Budget::class);
    }

}
