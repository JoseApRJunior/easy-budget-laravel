<?php
declare(strict_types=1);

namespace App\Models;

use App\Models\BudgetStatus;
use App\Models\Customer;
use App\Models\Tenant;
use App\Models\Traits\TenantScoped;
use App\Models\UserConfirmationToken;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'tenant_id'                  => 'integer',
        'customer_id'                => 'integer',
        'budget_statuses_id'         => 'integer',
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
            'budget_statuses_id'         => 'required|integer|exists:budget_statuses,id',
            'user_confirmation_token_id' => 'nullable|integer|exists:user_confirmation_tokens,id',
            'code'                       => 'required|string|max:50|unique:budgets,code',
            'due_date'                   => 'nullable|date|after:today',
            'discount'                   => 'required|numeric|min:0|max:999999.99',
            'total'                      => 'required|numeric|min:0|max:999999.99',
            'description'                => 'nullable|string|max:65535',
            'payment_terms'              => 'nullable|string|max:65535',
            'attachment'                 => 'nullable|string|max:255',
            'history'                    => 'nullable|string|max:65535',
            'pdf_verification_hash'      => 'nullable|string|max:64|unique:budgets,pdf_verification_hash',
        ];
    }

    /**
     * Regras de validação para criação de orçamento.
     */
    public static function createRules(): array
    {
        $rules          = self::businessRules();
        $rules[ 'code' ]  = 'required|string|max:50|unique:budgets,code';
        $rules[ 'total' ] = 'required|numeric|min:0.01|max:999999.99';

        return $rules;
    }

    /**
     * Regras de validação para atualização de orçamento.
     */
    public static function updateRules( int $budgetId ): array
    {
        $rules                          = self::businessRules();
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
    public function tenant(): BelongsTo
    {
        return $this->belongsTo( Tenant::class);
    }

    /**
     * Get the customer that owns the Budget.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo( Customer::class);
    }

    /**
     * Get the budget status that owns the Budget.
     */
    public function budgetStatus(): BelongsTo
    {
        return $this->belongsTo( BudgetStatus::class, 'budget_statuses_id' );
    }

    public function userConfirmationToken(): BelongsTo
    {
        return $this->belongsTo( UserConfirmationToken::class);
    }

    /**
     * Get the services for the Budget.
     */
    public function services(): HasMany
    {
        return $this->hasMany( Service::class);
    }

}
