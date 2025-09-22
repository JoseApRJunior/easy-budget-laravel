<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        'color',
        'icon',
        'description',
        'order_index',
        'is_active',
    ];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [ 
        'order_index' => 'integer',
        'is_active'   => 'boolean',
        'created_at'  => 'datetime_immutable',
        'updated_at'  => 'datetime_immutable',
    ];

    /**
     * Get the invoices for the InvoiceStatus.
     */
    public function invoices(): HasMany
    {
        return $this->hasMany( Invoice::class, 'invoice_statuses_id' );
    }

}