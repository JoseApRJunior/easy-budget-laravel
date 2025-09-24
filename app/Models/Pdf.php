<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pdf extends Model
{
    use HasFactory, TenantScoped;

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();
        static::bootTenantScoped();
    }

    protected $fillable = [
        'tenant_id',
        'path',
        'type',
        'data',
        'generated_at',
        'budget_id',
        'customer_id',
        'invoice_id',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'data'         => 'array',
        'generated_at' => 'datetime',
        'created_at'   => 'immutable_datetime',
        'updated_at'   => 'immutable_datetime'
    ];
}