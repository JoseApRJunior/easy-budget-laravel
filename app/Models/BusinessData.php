<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Dados empresariais para Customer (PJ) e Provider
 */
class BusinessData extends Model
{
    use HasFactory, TenantScoped;

    protected static function boot()
    {
        parent::boot();
        static::bootTenantScoped();
    }

    protected $table = 'business_datas';

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'provider_id',
        'fantasy_name',
        'state_registration',
        'municipal_registration',
        'founding_date',
        'industry',
        'company_size',
        'notes',
    ];

    protected $casts = [
        'founding_date' => 'date',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
