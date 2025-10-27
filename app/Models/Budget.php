<?php
declare(strict_types=1);

namespace App\Models;

use App\Enums\BudgetStatusEnum;
use App\Models\Customer;
use App\Models\Tenant;
use App\Models\Traits\TenantScoped;
use App\Models\UserConfirmationToken;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Budget extends Model
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
     *
     * @var string
     */
    protected $table = 'budgets';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'customer_id',
        'budget_statuses_id',
        'user_confirmation_token_id',
        'code',
        'due_date',
        'discount',
        'total',
        'description',
        'payment_terms',
        'attachment',
        'history',
        'pdf_verification_hash',
        'public_token',
        'public_expires_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'tenant_id'                  => 'integer',
        'customer_id'                => 'integer',
        'budget_statuses_id'         => BudgetStatusEnum::class,
        'user_confirmation_token_id' => 'integer',
        'code'                       => 'string',
        'discount'                   => 'decimal:2',
        'total'                      => 'decimal:2',
        'due_date'                   => 'date',
        'description'                => 'string',
        'payment_terms'              => 'string',
        'attachment'                 => 'string',
        'history'                    => 'string',
        'pdf_verification_hash'      => 'string',
        'public_token'               => 'string',
        'public_expires_at'          => 'datetime',
        'created_at'                 => 'immutable_datetime',
        'updated_at'                 => 'datetime',
    ];

    /**
     * Campos que devem ser tratados como datas imutáveis.
     */
    protected $dates = [
        'due_date',
        'created_at',
        'updated_at',
    ];

    /**
     * Regras de validação para o modelo Budget.
     */
    public static function businessRules(): array
    {
        return [
            'tenant_id'                  => 'required|integer|exists:tenants,id',
            'customer_id'                => 'required|integer|exists:customers,id',
            'budget_statuses_id'         => 'required|string|in:' . implode( ',', array_column( BudgetStatusEnum::cases(), 'value' ) ),
            'user_confirmation_token_id' => 'nullable|integer|exists:user_confirmation_tokens,id',
            'code'                       => 'required|string|max:50|unique:budgets,code',
            'due_date'                   => 'nullable|date|after:today',
            'discount'                   => 'required|numeric|min:0|max:999999.99',
            'total'                      => 'required|numeric|min:0|max:999999.99',
            'description'                => 'nullable|string|max:65535',
            'payment_terms'              => 'nullable|string|max:65535',
            'attachment'                 => 'nullable|string|max:255',
            'history'                    => 'nullable|string|max:65535',
            'pdf_verification_hash'      => 'nullable|string|max:64|unique:budgets,pdf_verification_hash', // SHA256 hash, not a confirmation token
        ];
    }

    /**
     * Regras de validação para criação de orçamento.
     */
    public static function createRules(): array
    {
        $rules            = self::businessRules();
        $rules[ 'code' ]  = 'required|string|max:50|unique:budgets,code';
        $rules[ 'total' ] = 'required|numeric|min:0.01|max:999999.99';

        return $rules;
    }

    /**
     * Regras de validação para atualização de orçamento.
     */
    public static function updateRules( int $budgetId ): array
    {
        $rules                            = self::businessRules();
        $rules[ 'code' ]                  = 'required|string|max:50|unique:budgets,code,' . $budgetId;
        $rules[ 'pdf_verification_hash' ] = 'nullable|string|max:64|unique:budgets,pdf_verification_hash,' . $budgetId;

        return $rules;
    }

    /**
     * Validação customizada para verificar se o código é único no tenant.
     */
    public static function validateUniqueCodeInTenant( string $code, int $tenantId, ?int $excludeBudgetId = null ): bool
    {
        $query = static::where( 'code', $code )->where( 'tenant_id', $tenantId );

        if ( $excludeBudgetId ) {
            $query->where( 'id', '!=', $excludeBudgetId );
        }

        return !$query->exists();
    }

    /**
     * Validação customizada para verificar se o total é maior que o desconto.
     */
    public static function validateTotalGreaterThanDiscount( float $total, float $discount ): bool
    {
        return $total >= $discount;
    }

    /**
     * Get the tenant that owns the Budget.
     */
    public function tenant()
    {
        return $this->belongsTo( Tenant::class);
    }

    /**
     * Get the customer that owns the Budget.
     */
    public function customer()
    {
        return $this->belongsTo( Customer::class);
    }

    /**
     * Get the budget status enum.
     */
    public function getBudgetStatusAttribute(): ?BudgetStatusEnum
    {
        return BudgetStatusEnum::tryFrom( $this->budget_statuses_id );
    }

    /**
     * Get the budget status enum for backward compatibility with views.
     */
    public function budgetStatus(): ?BudgetStatusEnum
    {
        return $this->budget_status;
    }

    public function userConfirmationToken()
    {
        return $this->belongsTo( UserConfirmationToken::class);
    }

    /**
     * Get the services for the Budget.
     */
    public function services()
    {
        return $this->hasMany( Service::class);
    }

    /**
     * Get the budget items for the Budget.
     */
    public function items()
    {
        return $this->hasMany( BudgetItem::class);
    }

    /**
     * Get the budget versions for the Budget.
     */
    public function versions()
    {
        return $this->hasMany( BudgetVersion::class);
    }

    /**
     * Get the current budget version.
     */
    public function currentVersion()
    {
        return $this->belongsTo( BudgetVersion::class, 'current_version_id' );
    }

    /**
     * Get the budget attachments.
     */
    public function attachments()
    {
        return $this->hasMany( BudgetAttachment::class);
    }

    /**
     * Get the budget shares.
     */
    public function shares()
    {
        return $this->hasMany( BudgetShare::class);
    }

    /**
     * Get the budget action history.
     */
    public function actionHistory()
    {
        return $this->hasMany( BudgetActionHistory::class);
    }

    /**
     * Get the budget notifications.
     */
    public function notifications()
    {
        return $this->hasMany( BudgetNotification::class);
    }

    /**
     * Scope para orçamentos ativos.
     */
    public function scopeActive( $query )
    {
        $activeStatuses = array_filter(
            array_column( BudgetStatusEnum::cases(), 'value' ),
            fn( $status ) => BudgetStatusEnum::tryFrom( $status )?->isActive() ?? false
        );

        return $query->whereIn( 'budget_statuses_id', $activeStatuses );
    }

    /**
     * Scope para orçamentos por status.
     */
    public function scopeByStatus( $query, $statusSlug )
    {
        return $query->where( 'budget_statuses_id', $statusSlug );
    }

    /**
     * Scope para orçamentos válidos (não expirados).
     */
    public function scopeValid( $query )
    {
        return $query->where( function ( $q ) {
            $q->whereNull( 'valid_until' )
                ->orWhere( 'valid_until', '>', now() );
        } );
    }

    /**
     * Scope para orçamentos expirados.
     */
    public function scopeExpired( $query )
    {
        return $query->where( 'valid_until', '<=', now() );
    }

    /**
     * Scope para orçamentos enviados.
     */
    public function scopeSent( $query )
    {
        return $query->byStatus( 'enviado' );
    }

    /**
     * Scope para orçamentos aprovados.
     */
    public function scopeApproved( $query )
    {
        return $query->byStatus( 'aprovado' );
    }

    /**
     * Scope para orçamentos rejeitados.
     */
    public function scopeRejected( $query )
    {
        return $query->byStatus( 'rejeitado' );
    }

    /**
     * Scope para orçamentos em rascunho.
     */
    public function scopeDraft( $query )
    {
        return $query->byStatus( 'rascunho' );
    }

    /**
     * Verifica se o orçamento está expirado.
     */
    public function isExpired(): bool
    {
        return $this->valid_until && $this->valid_until->isPast();
    }

    /**
     * Verifica se o orçamento está válido.
     */
    public function isValid(): bool
    {
        return !$this->isExpired();
    }

    /**
     * Verifica se o orçamento pode ser editado.
     */
    public function canBeEdited(): bool
    {
        return in_array( $this->budget_status?->value, [ BudgetStatusEnum::DRAFT->value, BudgetStatusEnum::REJECTED->value ] );
    }

    /**
     * Verifica se o orçamento pode ser enviado.
     */
    public function canBeSent(): bool
    {
        return $this->budget_status === BudgetStatusEnum::DRAFT && $this->isValid();
    }

    /**
     * Verifica se o orçamento pode ser aprovado.
     */
    public function canBeApproved(): bool
    {
        return $this->budget_status === BudgetStatusEnum::SENT && $this->isValid();
    }

    /**
     * Verifica se o orçamento pode ser rejeitado.
     */
    public function canBeRejected(): bool
    {
        return $this->budget_status === BudgetStatusEnum::SENT;
    }

    /**
     * Calcula o total do orçamento baseado nos itens.
     */
    public function calculateTotals(): array
    {
        $subtotal = $this->items->sum( function ( $item ) {
            return $item->quantity * $item->unit_price;
        } );

        $discountTotal = $this->items->sum( function ( $item ) {
            $itemTotal = $item->quantity * $item->unit_price;
            return $itemTotal * ( $item->discount_percentage / 100 );
        } );

        // Aplicar desconto global se houver
        if ( $this->global_discount_percentage > 0 ) {
            $globalDiscount = $subtotal * ( $this->global_discount_percentage / 100 );
            $discountTotal += $globalDiscount;
        }

        $taxesTotal = $this->items->sum( function ( $item ) {
            $itemTotal    = $item->quantity * $item->unit_price;
            $itemDiscount = $itemTotal * ( $item->discount_percentage / 100 );
            $itemSubtotal = $itemTotal - $itemDiscount;
            return $itemSubtotal * ( $item->tax_percentage / 100 );
        } );

        $grandTotal = ( $subtotal - $discountTotal ) + $taxesTotal;

        return [
            'subtotal'       => $subtotal,
            'discount_total' => $discountTotal,
            'taxes_total'    => $taxesTotal,
            'grand_total'    => $grandTotal,
            'items_count'    => $this->items->count(),
        ];
    }

    /**
     * Atualiza os totais calculados do orçamento.
     */
    public function updateCalculatedTotals(): void
    {
        $totals = $this->calculateTotals();

        $this->update( [
            'subtotal'   => $totals[ 'subtotal' ],
            'total'      => $totals[ 'grand_total' ],
            'updated_at' => now(),
        ] );
    }

    /**
     * Cria uma nova versão do orçamento.
     */
    public function createVersion( string $changeDescription, int $userId ): BudgetVersion
    {
        $totals = $this->calculateTotals();

        $version = $this->versions()->create( [
            'tenant_id'           => $this->tenant_id,
            'user_id'             => $userId,
            'version_number'      => $this->getNextVersionNumber(),
            'changes_description' => $changeDescription,
            'budget_data'         => $this->toArray(),
            'items_data'          => $this->items->toArray(),
            'version_total'       => $totals[ 'grand_total' ],
            'is_current'          => true,
            'version_date'        => now(),
        ] );

        // Marcar versão atual
        $this->update( [ 'current_version_id' => $version->id ] );

        // Desmarcar outras versões como não atuais
        $this->versions()->where( 'id', '!=', $version->id )->update( [ 'is_current' => false ] );

        return $version;
    }

    /**
     * Obtém o próximo número de versão.
     */
    private function getNextVersionNumber(): string
    {
        $lastVersion = $this->versions()->max( 'version_number' );

        if ( !$lastVersion ) {
            return '1.0';
        }

        $parts = explode( '.', $lastVersion );
        $major = (int) $parts[ 0 ];
        $minor = (int) $parts[ 1 ];

        return ( $minor + 1 ) >= 10 ? ( $major + 1 ) . '.0' : $major . '.' . ( $minor + 1 );
    }

    /**
     * Restaura uma versão específica.
     */
    public function restoreVersion( BudgetVersion $version, int $userId ): bool
    {
        if ( $version->budget_id !== $this->id ) {
            return false;
        }

        // Criar nova versão com dados restaurados
        $this->createVersion( "Restauração da versão {$version->version_number}", $userId );

        // Atualizar dados do orçamento com os dados da versão
        $budgetData = $version->budget_data;
        $this->update( [
            'customer_id'           => $budgetData[ 'customer_id' ],
            'budget_statuses_id'    => $budgetData[ 'budget_statuses_id' ],
            'code'                  => $budgetData[ 'code' ],
            'due_date'              => $budgetData[ 'due_date' ],
            'discount'              => $budgetData[ 'discount' ],
            'total'                 => $budgetData[ 'total' ],
            'description'           => $budgetData[ 'description' ],
            'payment_terms'         => $budgetData[ 'payment_terms' ],
            'attachment'            => $budgetData[ 'attachment' ],
            'history'               => $budgetData[ 'history' ],
            'pdf_verification_hash' => $budgetData[ 'pdf_verification_hash' ],
        ] );

        // Recriar itens se necessário
        if ( isset( $version->items_data ) && is_array( $version->items_data ) ) {
            $this->items()->delete();
            foreach ( $version->items_data as $itemData ) {
                $this->items()->create( $itemData );
            }
        }

        return true;
    }

    /**
     * Adiciona um item ao orçamento.
     */
    public function addItem( array $itemData ): BudgetItem
    {
        $itemData[ 'tenant_id' ]   = $this->tenant_id;
        $itemData[ 'budget_id' ]   = $this->id;
        $itemData[ 'order_index' ] = $this->items()->max( 'order_index' ) + 1;

        return $this->items()->create( $itemData );
    }

    /**
     * Remove um item do orçamento.
     */
    public function removeItem( BudgetItem $item ): bool
    {
        return $item->delete();
    }

    /**
     * Duplica o orçamento.
     */
    public function duplicate( int $userId ): Budget
    {
        $newBudget                     = $this->replicate();
        $newBudget->code               = $this->generateDuplicateCode();
        $newBudget->budget_statuses_id = BudgetStatusEnum::DRAFT->value;
        $newBudget->current_version_id = null;
        $newBudget->save();

        // Duplicar itens
        foreach ( $this->items as $item ) {
            $newItem            = $item->replicate();
            $newItem->budget_id = $newBudget->id;
            $newItem->save();
        }

        // Criar versão inicial
        $newBudget->createVersion( 'Orçamento criado por duplicação', $userId );

        return $newBudget;
    }

    /**
     * Gera código para orçamento duplicado.
     */
    private function generateDuplicateCode(): string
    {
        $baseCode = $this->code . '-COPY';
        $counter  = 1;

        while ( static::where( 'code', $baseCode . '-' . $counter )->exists() ) {
            $counter++;
        }

        return $baseCode . '-' . $counter;
    }

}
