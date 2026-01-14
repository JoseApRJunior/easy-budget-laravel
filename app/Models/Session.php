<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo Session usando estrutura padrão do Laravel
 * Compatível com Laravel 12 e funcionalidades nativas de sessão
 */
class Session extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'sessions';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'id',
        'user_id',
        'ip_address',
        'user_agent',
        'payload',
        'last_activity',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'user_id' => 'integer',
        'ip_address' => 'string',
        'user_agent' => 'string',
        'payload' => 'string',
        'last_activity' => 'integer',
    ];

    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = false;

    /**
     * Indicates if the IDs are auto-incrementing.
     */
    public $incrementing = false;

    /**
     * The primary key type.
     */
    protected $keyType = 'string';

    /**
     * Get the user that owns the Session.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
