<?php

namespace App\Models;

use App\Models\Invoice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InvoiceStatus extends Model
{

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'invoice_statuses';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'color',
        'icon',
        'order_index',
        'is_active'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'name'        => 'string',
        'slug'        => 'string',
        'description' => 'string',
        'color'       => 'string',
        'icon'        => 'string',
        'order_index' => 'integer',
        'is_active'   => 'boolean',
        'created_at'  => 'immutable_datetime',
        'updated_at'  => 'datetime',
    ];

    /**
     * Regras de validação para o modelo Plan.
     */
    public static function businessRules(): array
    {
        return [
            'name'        => 'required|string|max:100|unique:invoice_statuses,name',
            'slug'        => 'required|string|max:50|unique:invoice_statuses,slug',
            'description' => 'nullable|string|max:500',
            'color'       => 'nullable|string|max:7|regex:/^#[0-9A-F]{6}$/i',
            'icon'        => 'nullable|string|max:50',
            'order_index' => 'nullable|integer|min:0',
            'is_active'   => 'required|boolean',
        ];
    }

    /**
     * Get the invoices for the InvoiceStatus.
     */
    public function invoices(): HasMany
    {
        return $this->hasMany( Invoice::class, 'invoice_statuses_id' );
    }

}
