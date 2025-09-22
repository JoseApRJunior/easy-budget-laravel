<?php
declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\TenantScoped;
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
     * Get the history attribute as array.
     */
    public function getHistoryAttribute( $value ): mixed
    {
        if ( empty( $value ) ) {
            return [];
        }

        $decoded = json_decode( $value, true );
        return json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
    }

    /**
     * Get the attachment attribute as array.
     */
    public function getAttachmentAttribute( $value ): mixed
    {
        if ( empty( $value ) ) {
            return [];
        }

        $decoded = json_decode( $value, true );
        return json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
    }

    /**
     * Set the attachment attribute as JSON.
     */
    public function setAttachmentAttribute( $value ): void
    {
        $this->attributes[ 'attachment' ] = is_array( $value ) ? json_encode( $value ) : $value;
    }

    /**
     * Set the history attribute as JSON.
     */
    public function setHistoryAttribute( $value ): void
    {
        $this->attributes[ 'history' ] = is_array( $value ) ? json_encode( $value ) : $value;
    }

}
