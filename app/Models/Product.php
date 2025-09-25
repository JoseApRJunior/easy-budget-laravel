<?php

namespace App\Models;

use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Product extends Model
{
    use HasFactory;
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
    protected $table = 'products';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'price',
        'active',
        'code',
        'image',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'tenant_id'   => 'integer',
        'name'        => 'string',
        'description' => 'string',
        'price'       => 'decimal:2',
        'active'      => 'boolean',
        'code'        => 'string',
        'image'       => 'string',
        'created_at'  => 'immutable_datetime',
        'updated_at'  => 'datetime',
    ];

    /**
     * Get the tenant that owns the Product.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo( Tenant::class);
    }

    /**
     * Movimentações de inventário deste tenant.
     */
    public function inventoryMovements(): HasMany
    {
        return $this->hasMany( InventoryMovement::class);
    }

}