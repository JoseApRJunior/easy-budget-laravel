<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Budget;
use App\Models\Tenant;
use App\Models\Traits\TenantScoped;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BudgetNotification extends Model
{
    use HasFactory;
    use TenantScoped;

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();
        static::bootTenantScoped();
    }

    /**
     * The table associated with the model.
     */
    protected $table = 'budget_notifications';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'tenant_id',
        'budget_id',
        'user_id',
        'type',
        'channel',
        'recipient_email',
        'message',
        'subject',
        'data',
        'sent',
        'sent_at',
        'read',
        'read_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'tenant_id'  => 'integer',
        'budget_id'  => 'integer',
        'user_id'    => 'integer',
        'data'       => 'array',
        'sent'       => 'boolean',
        'read'       => 'boolean',
        'sent_at'    => 'datetime',
        'read_at'    => 'datetime',
        'created_at' => 'immutable_datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the tenant that owns the BudgetNotification.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo( Tenant::class);
    }

    /**
     * Get the budget that owns the BudgetNotification.
     */
    public function budget(): BelongsTo
    {
        return $this->belongsTo( Budget::class);
    }

    /**
     * Get the user that owns the BudgetNotification.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo( User::class);
    }

    /**
     * Scope para notificações não lidas.
     */
    public function scopeUnread( $query )
    {
        return $query->where( 'read', false );
    }

    /**
     * Scope para notificações enviadas.
     */
    public function scopeSent( $query )
    {
        return $query->where( 'sent', true );
    }

    /**
     * Scope para notificações não enviadas.
     */
    public function scopePending( $query )
    {
        return $query->where( 'sent', false );
    }

    /**
     * Marca a notificação como lida.
     */
    public function markAsRead(): bool
    {
        if ( !$this->read ) {
            $this->read    = true;
            $this->read_at = now();
            return $this->save();
        }
        return true;
    }

    /**
     * Marca a notificação como enviada.
     */
    public function markAsSent(): bool
    {
        if ( !$this->sent ) {
            $this->sent    = true;
            $this->sent_at = now();
            return $this->save();
        }
        return true;
    }

    /**
     * Obtém tipos de notificação disponíveis.
     */
    public static function getAvailableTypes(): array
    {
        return [
            'created'  => 'Orçamento Criado',
            'updated'  => 'Orçamento Atualizado',
            'sent'     => 'Orçamento Enviado',
            'approved' => 'Orçamento Aprovado',
            'rejected' => 'Orçamento Rejeitado',
            'expired'  => 'Orçamento Expirado',
            'reminder' => 'Lembrete de Orçamento',
            'viewed'   => 'Orçamento Visualizado',
            'shared'   => 'Orçamento Compartilhado',
        ];
    }

    /**
     * Obtém canais disponíveis.
     */
    public static function getAvailableChannels(): array
    {
        return [
            'email'    => 'E-mail',
            'sms'      => 'SMS',
            'push'     => 'Push Notification',
            'database' => 'Notificação Interna',
        ];
    }

}
