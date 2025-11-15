<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebhookRequest extends Model
{
    use HasFactory;

    protected $table = 'webhook_requests';

    protected $fillable = [
        'request_id',
        'type',
        'payload_hash',
        'status',
        'received_at',
        'processed_at',
    ];

    protected $casts = [
        'request_id'   => 'string',
        'type'         => 'string',
        'payload_hash' => 'string',
        'status'       => 'string',
        'received_at'  => 'immutable_datetime',
        'processed_at' => 'datetime',
        'created_at'   => 'immutable_datetime',
        'updated_at'   => 'datetime',
    ];
}