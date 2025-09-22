<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [ 
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
}
