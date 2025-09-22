<?php

namespace App\Models;

use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Product extends Model
{
    use HasFactory;
    use TenantScoped;

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
        'category_id',
        'unit_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [ 
        'tenant_id'  => 'integer',
        'price'      => 'decimal:2',
        'active'     => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the tenant that owns the Product.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo( Tenant::class);
    }

    /**
     * Get the category that owns the Product.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo( Category::class);
    }

    /**
     * Get the unit that owns the Product.
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo( Unit::class);
    }

}
