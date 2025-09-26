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
     * Regras de validação para o modelo Product.
     */
    public static function businessRules(): array
    {
        return [
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'price'       => 'required|numeric|min:0',
            'code'        => 'nullable|string|max:50',
            'active'      => 'boolean',
            'image'       => 'nullable|string|max:255',
            'tenant_id'   => 'required|exists:tenants,id',
        ];
    }

    /**
     * Validação personalizada para code único por tenant.
     * Esta validação deve ser usada no contexto de um request onde o tenant_id está disponível.
     */
    public static function validateUniqueCodeRule( ?string $code, ?int $excludeId = null ): string
    {
        if ( empty( $code ) ) {
            return 'nullable|string|max:50';
        }

        $rule = 'unique:products,code';

        if ( $excludeId ) {
            $rule .= ',' . $excludeId . ',id';
        }

        return $rule . ',tenant_id,' . request()->user()->tenant_id;
    }

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

    /**
     * Controle de inventário do produto.
     */
    public function productInventory(): HasMany
    {
        return $this->hasMany( ProductInventory::class);
    }

}
