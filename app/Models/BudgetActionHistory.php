<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Budget;
use App\Models\Tenant;
use App\Models\Traits\TenantScoped;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BudgetActionHistory extends Model
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
    protected $table = 'budget_action_history';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'tenant_id',
        'budget_id',
        'user_id',
        'action',
        'old_status',
        'new_status',
        'description',
        'changes',
        'metadata',
        'ip_address',
        'user_agent',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'tenant_id'  => 'integer',
        'budget_id'  => 'integer',
        'user_id'    => 'integer',
        'changes'    => 'array',
        'metadata'   => 'array',
        'created_at' => 'immutable_datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the tenant that owns the BudgetActionHistory.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo( Tenant::class);
    }

    /**
     * Get the budget that owns the BudgetActionHistory.
     */
    public function budget(): BelongsTo
    {
        return $this->belongsTo( Budget::class);
    }

    /**
     * Get the user that owns the BudgetActionHistory.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo( User::class);
    }

    /**
     * Scope para ações de um orçamento específico.
     */
    public function scopeForBudget( $query, int $budgetId )
    {
        return $query->where( 'budget_id', $budgetId );
    }

    /**
     * Scope para ações por tipo.
     */
    public function scopeByAction( $query, string $action )
    {
        return $query->where( 'action', $action );
    }

    /**
     * Scope para ações recentes.
     */
    public function scopeRecent( $query, int $days = 30 )
    {
        return $query->where( 'created_at', '>=', now()->subDays( $days ) );
    }

    /**
     * Obtém ações disponíveis.
     */
    public static function getAvailableActions(): array
    {
        return [
            'created'         => 'Criado',
            'updated'         => 'Atualizado',
            'sent'            => 'Enviado',
            'approved'        => 'Aprovado',
            'rejected'        => 'Rejeitado',
            'expired'         => 'Expirado',
            'viewed'          => 'Visualizado',
            'downloaded'      => 'Baixado',
            'shared'          => 'Compartilhado',
            'duplicated'      => 'Duplicado',
            'version_created' => 'Versão Criada',
            'restored'        => 'Restaurado',
        ];
    }

    /**
     * Cria um registro de ação.
     */
    public static function logAction(
        int $budgetId,
        int $userId,
        string $action,
        ?string $oldStatus = null,
        ?string $newStatus = null,
        ?string $description = null,
        array $changes = [],
        array $metadata = [],
    ): self {
        return static::create( [
            'budget_id'   => $budgetId,
            'user_id'     => $userId,
            'action'      => $action,
            'old_status'  => $oldStatus,
            'new_status'  => $newStatus,
            'description' => $description,
            'changes'     => $changes,
            'metadata'    => $metadata,
            'ip_address'  => request()->ip(),
            'user_agent'  => request()->userAgent(),
        ] );
    }

    /**
     * Obtém informações formatadas da ação.
     */
    public function getFormattedInfoAttribute(): array
    {
        $actions = self::getAvailableActions();

        return [
            'action'       => $this->action,
            'action_label' => $actions[ $this->action ] ?? ucfirst( $this->action ),
            'description'  => $this->description,
            'old_status'   => $this->old_status,
            'new_status'   => $this->new_status,
            'user_name'    => $this->user->name ?? 'Sistema',
            'date'         => $this->created_at->format( 'd/m/Y H:i:s' ),
            'changes'      => $this->changes,
        ];
    }

}
