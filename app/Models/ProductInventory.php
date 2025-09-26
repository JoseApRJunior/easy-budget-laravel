<?php

namespace App\Models;

use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductInventory extends Model
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
    protected $table = 'product_inventory';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'product_id',
        'quantity',
        'min_quantity',
        'max_quantity',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'tenant_id'    => 'integer',
        'product_id'   => 'integer',
        'quantity'     => 'integer',
        'min_quantity' => 'integer',
        'max_quantity' => 'integer',
        'created_at'   => 'immutable_datetime',
        'updated_at'   => 'datetime',
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
     * Get the tenant that owns the ProductInventory.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo( Tenant::class);
    }

    /**
     * Get the product that owns the ProductInventory.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo( Product::class);
    }

}
