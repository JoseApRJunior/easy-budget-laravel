<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
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
        'type',
        'email',
        'message',
        'subject',
        'sent_at',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'sent_at'    => 'datetime',
        'created_at' => 'immutable_datetime',
        'updated_at' => 'immutable_datetime'
    ];


        /**
     * Regras de validação para o modelo Plan.
     */
    public static function businessRules(): array
    {
        return [

        ];
    }

}
