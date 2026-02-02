<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceShare extends Model
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
     */
    protected $table = 'invoice_shares';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'tenant_id',
        'invoice_id',
        'share_token',
        'recipient_email',
        'recipient_name',
        'message',
        'permissions',
        'expires_at',
        'is_active',
        'status',
        'access_count',
        'last_accessed_at',
        'rejected_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'tenant_id' => 'integer',
        'invoice_id' => 'integer',
        'share_token' => 'string',
        'permissions' => 'array',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
        'status' => \App\Enums\InvoiceShareStatus::class,
        'access_count' => 'integer',
        'last_accessed_at' => 'datetime',
        'rejected_at' => 'datetime',
        'created_at' => 'immutable_datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Regras de validação para o modelo InvoiceShare.
     */
    public static function businessRules(): array
    {
        return [
            'tenant_id' => 'required|integer|exists:tenants,id',
            'invoice_id' => 'required|integer|exists:invoices,id',
            'share_token' => 'required|string|size:43|unique:invoice_shares,share_token',
            'recipient_email' => 'nullable|email|max:255',
            'recipient_name' => 'nullable|string|max:255',
            'message' => 'nullable|string|max:1000',
            'permissions' => 'nullable|array',
            'expires_at' => 'nullable|date|after:now',
            'is_active' => 'required|boolean',
            'status' => 'required|string|in:active,rejected,expired',
            'access_count' => 'required|integer|min:0',
        ];
    }

    /**
     * Get the tenant that owns the InvoiceShare.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the invoice that owns the InvoiceShare.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
