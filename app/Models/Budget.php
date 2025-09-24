<?php
declare(strict_types=1);

namespace App\Models;

use App\Models\BudgetStatus;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\Traits\TenantScoped;
use App\Models\UserConfirmationToken;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

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
     * Get the tenant that owns the Budget.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo( Tenant::class);
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
        'code',
        'budget_statuses_id',
        'user_confirmation_token_id',
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
        'discount'                   => 'decimal:2',
        'total'                      => 'decimal:2',
        'due_date'                   => 'datetime',
        'attachment'                 => 'array',
        'history'                    => 'array',
        'pdf_verification_hash'      => 'string',
        'created_at'                 => 'immutable_datetime',
        'updated_at'                 => 'immutable_datetime',
    ];

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

    /**
     * Accessor para tratar valores zero-date no updated_at.
     */
    public function getUpdatedAtAttribute( $value )
    {
        return ( $value === '0000-00-00 00:00:00' || empty( $value ) ) ? null : \DateTime::createFromFormat( 'Y-m-d H:i:s', $value );
    }

}