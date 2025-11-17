<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Budget;
use App\Models\Tenant;
use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BudgetShare extends Model
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
    protected $table = 'budget_shares';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'tenant_id',
        'budget_id',
        'share_token',
        'recipient_email',
        'recipient_name',
        'message',
        'permissions',
        'expires_at',
        'is_active',
        'access_count',
        'last_accessed_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'tenant_id'        => 'integer',
        'budget_id'        => 'integer',
        'share_token'      => 'string',
        'permissions'      => 'array',
        'expires_at'       => 'datetime',
        'is_active'        => 'boolean',
        'access_count'     => 'integer',
        'last_accessed_at' => 'datetime',
        'created_at'       => 'immutable_datetime',
        'updated_at'       => 'datetime',
    ];

    /**
     * Regras de validação para o modelo BudgetShare.
     */
    public static function businessRules(): array
    {
        return [
            'tenant_id'       => 'required|integer|exists:tenants,id',
            'budget_id'       => 'required|integer|exists:budgets,id',
            'share_token'     => 'required|string|size:43|unique:budget_shares,share_token', // base64url format: 32 bytes = 43 caracteres
            'recipient_email' => 'nullable|email|max:255',
            'recipient_name'  => 'nullable|string|max:255',
            'message'         => 'nullable|string|max:1000',
            'permissions'     => 'nullable|array',
            'expires_at'      => 'nullable|date|after:now',
            'is_active'       => 'required|boolean',
            'access_count'    => 'required|integer|min:0',
        ];
    }

    /**
     * Get the tenant that owns the BudgetShare.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo( Tenant::class);
    }

    /**
     * Get the budget that owns the BudgetShare.
     */
    public function budget(): BelongsTo
    {
        return $this->belongsTo( Budget::class);
    }

    /**
     * Scope para compartilhamentos ativos.
     */
    public function scopeActive( $query )
    {
        return $query->where( 'is_active', true );
    }

    /**
     * Scope para compartilhamentos expirados.
     */
    public function scopeExpired( $query )
    {
        return $query->where( 'expires_at', '<=', now() );
    }

    /**
     * Scope para compartilhamentos válidos (ativos e não expirados).
     */
    public function scopeValid( $query )
    {
        return $query->where( 'is_active', true )
            ->where( function ( $q ) {
                $q->whereNull( 'expires_at' )
                    ->orWhere( 'expires_at', '>', now() );
            } );
    }

    /**
     * Incrementa o contador de acessos.
     */
    public function incrementAccessCount(): bool
    {
        $this->increment( 'access_count' );
        $this->last_accessed_at = now();
        return $this->save();
    }

    /**
     * Verifica se o compartilhamento está válido.
     */
    public function isValid(): bool
    {
        return $this->is_active &&
            ( $this->expires_at === null || $this->expires_at->isFuture() );
    }

    /**
     * Verifica se o usuário tem permissão específica.
     */
    public function hasPermission( string $permission ): bool
    {
        $permissions = $this->permissions ?? [];

        // Se não há permissões definidas, permitir apenas visualização
        if ( empty( $permissions ) ) {
            return $permission === 'view';
        }

        return in_array( $permission, $permissions );
    }

    /**
     * Desativa o compartilhamento.
     */
    public function deactivate(): bool
    {
        $this->is_active = false;
        return $this->save();
    }

    /**
     * Estende a validade do compartilhamento.
     */
    public function extendValidity( int $days ): bool
    {
        $this->expires_at = now()->addDays( $days );
        return $this->save();
    }

    /**
     * Obtém permissões padrão para visualização.
     */
    public static function getDefaultViewPermissions(): array
    {
        return [ 'view', 'download' ];
    }

    /**
     * Obtém permissões completas para edição.
     */
    public static function getFullPermissions(): array
    {
        return [ 'view', 'download', 'approve', 'reject' ];
    }

}
