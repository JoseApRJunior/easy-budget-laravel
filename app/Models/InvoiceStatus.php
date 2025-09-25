<?php

namespace App\Models;

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
        'color',
        'icon',
        'description'
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
        'created_at'  => 'immutable_datetime',
        'updated_at'  => 'datetime',
    ];

    /**
     * Get the invoices for the InvoiceStatus.
     */
    public function invoices(): HasMany
    {
        return $this->hasMany( Invoice::class, 'invoice_statuses_id' );
    }

}