<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $request_id
 * @property string $type
 * @property string $payload_hash
 * @property string $status
 * @property \Illuminate\Support\Carbon $received_at
 * @property \Illuminate\Support\Carbon|null $processed_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class WebhookRequest extends Model
{
    /** @use HasFactory<\Database\Factories\WebhookRequestFactory> */
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

    /** @var array<string, string> */
    protected $casts = [
        'received_at' => 'datetime',
        'processed_at' => 'datetime',
    ];
}
