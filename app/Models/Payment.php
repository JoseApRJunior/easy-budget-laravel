<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PaymentStatus;
use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int $invoice_id
 * @property int $customer_id
 * @property PaymentStatus $status
 * @property string $method
 * @property float $amount
 * @property string|null $gateway_transaction_id
 * @property string|null $gateway_response
 * @property \Illuminate\Support\Carbon|null $processed_at
 * @property \Illuminate\Support\Carbon|null $confirmed_at
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class Payment extends Model
{
    use HasFactory, TenantScoped, SoftDeletes;

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
     */
    protected $table = 'payments';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'tenant_id',
        'invoice_id',
        'customer_id',
        'status',
        'method',
        'amount',
        'gateway_transaction_id',
        'gateway_response',
        'processed_at',
        'confirmed_at',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'tenant_id' => 'integer',
        'invoice_id' => 'integer',
        'customer_id' => 'integer',
        'status' => PaymentStatus::class,
        'method' => 'string',
        'amount' => 'decimal:2',
        'gateway_transaction_id' => 'string',
        'gateway_response' => 'json',
        'processed_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'notes' => 'string',
        'created_at' => 'immutable_datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Payment methods constants.
     */
    public const METHOD_PIX = 'pix';
    public const METHOD_BOLETO = 'boleto';
    public const METHOD_CREDIT_CARD = 'credit_card';
    public const METHOD_DEBIT_CARD = 'debit_card';
    public const METHOD_CASH = 'cash';
    public const METHOD_BANK_TRANSFER = 'bank_transfer';

    /**
     * Get available payment methods.
     */
    public static function getPaymentMethods(): array
    {
        return [
            self::METHOD_PIX => 'PIX',
            self::METHOD_BOLETO => 'Boleto Bancário',
            self::METHOD_CREDIT_CARD => 'Cartão de Crédito',
            self::METHOD_DEBIT_CARD => 'Cartão de Débito',
            self::METHOD_CASH => 'Dinheiro',
            self::METHOD_BANK_TRANSFER => 'Transferência Bancária',
        ];
    }

    /**
     * Get the tenant that owns the Payment.
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the invoice that owns the Payment.
     */
    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Get the customer that owns the Payment.
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Check if payment is pending.
     */
    public function isPending(): bool
    {
        return $this->status === PaymentStatus::PENDING;
    }

    /**
     * Check if payment is processing.
     */
    public function isProcessing(): bool
    {
        return $this->status === PaymentStatus::PROCESSING;
    }

    /**
     * Check if payment is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === PaymentStatus::COMPLETED;
    }

    /**
     * Check if payment failed.
     */
    public function isFailed(): bool
    {
        return $this->status === PaymentStatus::FAILED;
    }

    /**
     * Check if payment was refunded.
     */
    public function isRefunded(): bool
    {
        return $this->status === PaymentStatus::REFUNDED;
    }

    /**
     * Get payment method label.
     */
    public function getMethodLabel(): string
    {
        return self::getPaymentMethods()[$this->method] ?? $this->method;
    }

    /**
     * Scope for completed payments.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', PaymentStatus::COMPLETED);
    }

    /**
     * Scope for pending payments.
     */
    public function scopePending($query)
    {
        return $query->where('status', PaymentStatus::PENDING);
    }

    /**
     * Scope for failed payments.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', PaymentStatus::FAILED);
    }

    /**
     * Scope for payments by method.
     */
    public function scopeByMethod($query, string $method)
    {
        return $query->where('method', $method);
    }
}