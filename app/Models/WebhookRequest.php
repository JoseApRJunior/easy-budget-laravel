<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property int $id
 * @property string $request_id
 * @property string $type
 * @property array<string, mixed> $payload
 * @property bool $processed
 * @property array<string, mixed>|null $response
 * @property string|null $error_message
 * @property int $attempts
 * @property \Illuminate\Support\Carbon|null $last_attempt_at
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
        'payload',
        'processed',
        'response',
        'error_message',
        'attempts',
        'last_attempt_at',
        'processed_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'payload'         => 'array',
        'response'        => 'array',
        'processed'       => 'boolean',
        'last_attempt_at' => 'datetime',
        'processed_at'    => 'datetime',
    ];
}