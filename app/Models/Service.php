<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Service extends Model
{
    use TenantScoped;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'services';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'budget_id',
        'category_id',
        'service_statuses_id',
        'code',
        'description',
        'pdf_verification_hash',
        'discount',
        'total',
        'due_date',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array<string, float>
     */
    protected $attributes = [
        'discount' => 0.0,
        'total'    => 0.0,
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'tenant_id'             => 'integer',
        'budget_id'             => 'integer',
        'category_id'           => 'integer',
        'service_statuses_id'   => 'integer',
        'discount'              => 'decimal:2',
        'total'                 => 'decimal:2',
        'due_date'              => 'datetime',
        'pdf_verification_hash' => 'string',
        'created_at'            => 'datetime',
        'updated_at'            => 'datetime',
    ];

    /**
     * Get the tenant that owns the Service.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo( Tenant::class);
    }

    /**
     * Get the budget that owns the Service.
     */
    public function budget(): BelongsTo
    {
        return $this->belongsTo( Budget::class);
    }

    /**
     * Get the category that owns the Service.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo( Category::class);
    }

    /**
     * Get the service status that owns the Service.
     */
    public function serviceStatus(): BelongsTo
    {
        return $this->belongsTo( ServiceStatus::class, 'service_statuses_id' );
    }

    /**
     * Get the service items for the Service.
     */
    public function serviceItems(): HasMany
    {
        return $this->hasMany( ServiceItem::class);
    }

}
