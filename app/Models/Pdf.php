<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pdf extends Model
{
    use HasFactory;

    protected $fillable = [ 
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
        'created_at'   => 'datetime',
        'updated_at'   => 'datetime'
    ];
}