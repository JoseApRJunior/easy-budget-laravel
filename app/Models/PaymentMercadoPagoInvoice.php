<?php

namespace App\Models;

use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class PaymentMercadoPagoInvoice extends Model
{
    use TenantScoped;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'payment_mercado_pago_invoices';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [ 
        'tenant_id',
        'invoice_id',
        'payment_id',
        'status',
        'payment_method',
        'transaction_amount',
        'transaction_date',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [ 
        'tenant_id'          => 'integer',
        'invoice_id'         => 'integer',
        'transaction_amount' => 'decimal:2',
        'transaction_date'   => 'datetime',
        'created_at'         => 'immutable_datetime',
        'updated_at'         => 'immutable_datetime',
    ];

    /**
     * Get the tenant that owns the PaymentMercadoPagoInvoice.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo( Tenant::class);
    }

    /**
     * Get the invoice that owns the PaymentMercadoPagoInvoice.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo( Invoice::class);
    }

}
