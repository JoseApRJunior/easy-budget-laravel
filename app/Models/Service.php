<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ServiceStatus;
use App\Models\Traits\HasPublicToken;
use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory, HasPublicToken, TenantScoped;

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();
        static::bootTenantScoped();
        static::bootHasPublicToken();
    }

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'services';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'budget_id',
        'category_id',
        'status',
        'user_confirmation_token_id',
        'code',
        'description',
        'pdf_verification_hash',
        'public_token',
        'public_expires_at',
        'discount',
        'total',
        'due_date',
        'reason',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array<string, float>
     */
    protected $attributes = [
        'discount' => 0.0,
        'total' => 0.0,
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'tenant_id' => 'integer',
        'budget_id' => 'integer',
        'category_id' => 'integer',
        'status' => ServiceStatus::class,
        'user_confirmation_token_id' => 'integer',
        'code' => 'string',
        'description' => 'string',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
        'due_date' => 'date',
        'reason' => 'string',
        'pdf_verification_hash' => 'string',
        'public_token' => 'string',
        'public_expires_at' => 'datetime',
        'created_at' => 'immutable_datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Verifica se o serviço já foi finalizado (sucesso, falha ou cancelamento).
     */
    public function isFinished(): bool
    {
        return in_array($this->status, [
            ServiceStatus::COMPLETED,
            ServiceStatus::PARTIAL,
            ServiceStatus::CANCELLED,
            ServiceStatus::NOT_PERFORMED,
            ServiceStatus::EXPIRED,
        ], true);
    }

    /**
     * Regras de validação para o modelo Service.
     */
    public static function businessRules(): array
    {
        return [
            'tenant_id' => 'required|integer|exists:tenants,id',
            'budget_id' => 'required|integer|exists:budgets,id',
            'category_id' => 'required|integer|exists:categories,id',
            'status' => 'required|string|in:'.implode(',', array_column(ServiceStatus::cases(), 'value')),
            'user_confirmation_token_id' => 'nullable|integer|exists:user_confirmation_tokens,id',
            'code' => 'required|string|max:50|unique:services,code',
            'description' => 'nullable|string',
            'discount' => 'required|numeric|min:0|max:999999.99',
            'total' => 'required|numeric|min:0|max:999999.99',
            'due_date' => 'nullable|date',
            'reason' => 'nullable|string|max:500',
            'pdf_verification_hash' => 'nullable|string|max:64', // SHA256 hash, not a confirmation token
            'public_token' => 'nullable|string|size:43', // base64url format: 32 bytes = 43 caracteres
            'public_expires_at' => 'nullable|date',
        ];
    }

    /**
     * Get the tenant that owns the Service.
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the budget that owns the Service.
     */
    public function budget()
    {
        return $this->belongsTo(Budget::class);
    }

    /**
     * Get the customer through the budget relationship.
     */
    public function customer()
    {
        return $this->hasOneThrough(
            Customer::class,
            Budget::class,
            'id', // Local key on budgets (matches budget_id on services)
            'id', // Local key on customers (matches customer_id on budgets)
            'budget_id', // Local key on services
            'customer_id' // Local key on budgets
        );
    }

    /**
     * Get the category that owns the Service.
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the user confirmation token for the Service.
     */
    public function userConfirmationToken()
    {
        return $this->belongsTo(UserConfirmationToken::class);
    }

    /**
     * Get the service status enum.
     */
    public function getServiceStatusAttribute(): ?ServiceStatus
    {
        return $this->status;
    }

    /**
     * Get the name of the service status for backward compatibility with views.
     */
    public function getNameAttribute(): ?string
    {
        return $this->status?->getDescription();
    }

    /**
     * Get the color of the service status for backward compatibility with views.
     */
    public function getColorAttribute(): string
    {
        return $this->status?->getColor() ?? '#6c757d';
    }

    /**
     * Get the slug of the service status for backward compatibility with views.
     */
    public function getSlugAttribute(): string
    {
        return $this->status?->value ?? '';
    }

    /**
     * Get the description of the service status for backward compatibility with views.
     */
    public function getStatusDescriptionAttribute(): ?string
    {
        return $this->status?->getDescription();
    }

    /**
     * Get the service items for the Service.
     */
    public function serviceItems()
    {
        return $this->hasMany(ServiceItem::class);
    }

    /**
     * Get the invoices for the Service.
     */
    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Get the schedules for the Service.
     */
    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }

    public function canBeEdited(): bool
    {
        $status = $this->status instanceof \App\Enums\ServiceStatus
            ? $this->status
            : \App\Enums\ServiceStatus::fromString((string) $this->status);

        return $status?->canEdit() ?? false;
    }

    public function canEdit(): bool
    {
        return $this->canBeEdited();
    }

    /**
     * Accessor para tratar valores zero-date no updated_at.
     */
    public function getUpdatedAtAttribute($value)
    {
        return ($value === '0000-00-00 00:00:00' || empty($value)) ? null : \DateTime::createFromFormat('Y-m-d H:i:s', $value);
    }

    /**
     * Gera a URL pública para visualização/interação com o serviço.
     */
    public function getPublicUrl(): ?string
    {
        if (! $this->public_token || ! $this->code) {
            return null;
        }

        return route('services.public.view-status', [
            'code' => $this->code,
            'token' => $this->public_token,
        ], true);
    }

    /**
     * Gera a URL interna para visualização do serviço.
     */
    public function getUrl(): string
    {
        return route('provider.services.show', $this->id, true);
    }
}
