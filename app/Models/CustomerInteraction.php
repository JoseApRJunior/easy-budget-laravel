<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo CustomerInteraction - Interações com clientes
 *
 * Gerencia o histórico completo de interações com clientes,
 * incluindo ligações, emails, reuniões, visitas e propostas.
 */
class CustomerInteraction extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'customer_interactions';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'customer_id',
        'user_id',
        'type',
        'title',
        'description',
        'direction',
        'interaction_date',
        'duration_minutes',
        'outcome',
        'next_action',
        'next_action_date',
        'attachments',
        'metadata',
        'notify_customer',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'customer_id'      => 'integer',
        'user_id'          => 'integer',
        'type'             => 'string',
        'title'            => 'string',
        'description'      => 'string',
        'direction'        => 'string',
        'interaction_date' => 'datetime',
        'duration_minutes' => 'integer',
        'outcome'          => 'string',
        'next_action'      => 'string',
        'next_action_date' => 'datetime',
        'attachments'      => 'array',
        'metadata'         => 'array',
        'notify_customer'  => 'boolean',
        'is_active'        => 'boolean',
        'created_at'       => 'immutable_datetime',
        'updated_at'       => 'datetime',
    ];

    /**
     * Regras de validação para o modelo CustomerInteraction.
     */
    public static function businessRules(): array
    {
        return [
            'customer_id'      => 'required|integer|exists:customers,id',
            'user_id'          => 'required|integer|exists:users,id',
            'type'             => 'required|string|in:call,email,meeting,visit,proposal,note,task',
            'title'            => 'required|string|max:255',
            'description'      => 'nullable|string|max:2000',
            'direction'        => 'required|string|in:inbound,outbound',
            'interaction_date' => 'required|date',
            'duration_minutes' => 'nullable|integer|min:1|max:1440',
            'outcome'          => 'nullable|string|in:completed,pending,cancelled,rescheduled',
            'next_action'      => 'nullable|string|max:255',
            'next_action_date' => 'nullable|date|after:interaction_date',
            'attachments'      => 'nullable|array',
            'metadata'         => 'nullable|array',
            'notify_customer'  => 'boolean',
            'is_active'        => 'boolean',
        ];
    }

    /**
     * Get the customer that owns the interaction.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo( Customer::class);
    }

    /**
     * Get the user that owns the interaction.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo( User::class);
    }

    /**
     * Scope para buscar apenas interações ativas.
     */
    public function scopeActive( $query )
    {
        return $query->where( 'is_active', true );
    }

    /**
     * Scope para buscar interações por tipo.
     */
    public function scopeOfType( $query, string $type )
    {
        return $query->where( 'type', $type );
    }

    /**
     * Scope para buscar interações por direção.
     */
    public function scopeOfDirection( $query, string $direction )
    {
        return $query->where( 'direction', $direction );
    }

    /**
     * Scope para buscar interações por período.
     */
    public function scopeInDateRange( $query, $startDate, $endDate )
    {
        return $query->whereBetween( 'interaction_date', [ $startDate, $endDate ] );
    }

    /**
     * Scope para buscar próximas ações pendentes.
     */
    public function scopePendingActions( $query )
    {
        return $query->whereNotNull( 'next_action' )
            ->whereNotNull( 'next_action_date' )
            ->where( 'next_action_date', '>=', now() )
            ->where( function ( $q ) {
                $q->whereNull( 'outcome' )->orWhere( 'outcome', '!=', 'completed' );
            } );
    }

    /**
     * Get the interaction type label.
     */
    public function getTypeLabelAttribute(): string
    {
        return match ( $this->type ) {
            'call'     => 'Ligação',
            'email'    => 'Email',
            'meeting'  => 'Reunião',
            'visit'    => 'Visita',
            'proposal' => 'Proposta',
            'note'     => 'Nota',
            'task'     => 'Tarefa',
            default    => ucfirst( $this->type ),
        };
    }

    /**
     * Get the interaction direction label.
     */
    public function getDirectionLabelAttribute(): string
    {
        return match ( $this->direction ) {
            'inbound'  => 'Entrada',
            'outbound' => 'Saída',
            default    => ucfirst( $this->direction ),
        };
    }

    /**
     * Get the interaction outcome label.
     */
    public function getOutcomeLabelAttribute(): string
    {
        return match ( $this->outcome ) {
            'completed'   => 'Concluída',
            'pending'     => 'Pendente',
            'cancelled'   => 'Cancelada',
            'rescheduled' => 'Reagendada',
            default       => ucfirst( $this->outcome ?? 'Não definida' ),
        };
    }

    /**
     * Get the formatted duration.
     */
    public function getFormattedDurationAttribute(): string
    {
        if ( !$this->duration_minutes ) {
            return '';
        }

        $hours   = intdiv( $this->duration_minutes, 60 );
        $minutes = $this->duration_minutes % 60;

        if ( $hours > 0 ) {
            return sprintf( '%dh %dmin', $hours, $minutes );
        }

        return sprintf( '%dmin', $minutes );
    }

    /**
     * Check if the interaction is overdue for next action.
     */
    public function isOverdue(): bool
    {
        return $this->next_action_date &&
            $this->next_action_date->isPast() &&
            ( !$this->outcome || $this->outcome !== 'completed' );
    }

    /**
     * Check if the interaction has attachments.
     */
    public function hasAttachments(): bool
    {
        return !empty( $this->attachments );
    }

    /**
     * Get the number of attachments.
     */
    public function getAttachmentCountAttribute(): int
    {
        return is_array( $this->attachments ) ? count( $this->attachments ) : 0;
    }

    /**
     * Mark interaction as completed.
     */
    public function markAsCompleted(): void
    {
        $this->update( [ 'outcome' => 'completed' ] );
    }

    /**
     * Mark interaction as cancelled.
     */
    public function markAsCancelled(): void
    {
        $this->update( [ 'outcome' => 'cancelled' ] );
    }

    /**
     * Reschedule the next action.
     */
    public function rescheduleNextAction( string $nextAction, $nextActionDate ): void
    {
        $this->update( [
            'next_action'      => $nextAction,
            'next_action_date' => $nextActionDate,
            'outcome'          => 'rescheduled',
        ] );
    }

    /**
     * Get the interaction's priority level based on next action date.
     */
    public function getPriorityLevelAttribute(): string
    {
        if ( !$this->next_action_date ) {
            return 'normal';
        }

        $daysUntilDue = now()->diffInDays( $this->next_action_date, false );

        return match ( true ) {
            $daysUntilDue < 0  => 'overdue',
            $daysUntilDue <= 1 => 'urgent',
            $daysUntilDue <= 3 => 'high',
            default            => 'normal',
        };
    }

    /**
     * Get the interaction's priority color for UI.
     */
    public function getPriorityColorAttribute(): string
    {
        return match ( $this->priority_level ) {
            'overdue' => 'red',
            'urgent'  => 'orange',
            'high'    => 'yellow',
            default   => 'green',
        };
    }

    /**
     * Check if interaction requires follow-up.
     */
    public function requiresFollowUp(): bool
    {
        return $this->next_action &&
            ( !$this->outcome || !in_array( $this->outcome, [ 'completed', 'cancelled' ] ) );
    }

    /**
     * Get the time until next action.
     */
    public function getTimeUntilNextActionAttribute(): ?string
    {
        if ( !$this->next_action_date ) {
            return null;
        }

        $diff = now()->diff( $this->next_action_date );

        if ( $diff->invert ) {
            return 'Vencida há ' . $diff->format( '%d dias' );
        }

        if ( $diff->days === 0 ) {
            return 'Hoje';
        }

        return 'Em ' . $diff->format( '%d dias' );
    }

}
