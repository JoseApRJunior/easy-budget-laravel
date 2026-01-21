<?php

namespace App\Models;

use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Schedule extends Model
{
    use HasFactory, TenantScoped;

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
    protected $table = 'schedules';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'service_id',
        'user_confirmation_token_id',
        'start_date_time',
        'location',
        'end_date_time',
        'status',
        'confirmed_at',
        'completed_at',
        'no_show_at',
        'cancelled_at',
        'cancellation_reason',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'tenant_id' => 'integer',
        'service_id' => 'integer',
        'user_confirmation_token_id' => 'integer',
        'start_date_time' => 'immutable_datetime',
        'end_date_time' => 'immutable_datetime',
        'status' => \App\Enums\ScheduleStatus::class,
        'confirmed_at' => 'datetime',
        'completed_at' => 'datetime',
        'no_show_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'cancellation_reason' => 'string',
        'created_at' => 'immutable_datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Regras de validação para o modelo Schedule.
     * Implementa validações específicas para agendamentos considerando multi-tenancy.
     */
    public static function businessRules(): array
    {
        return [
            'tenant_id' => 'required|integer|exists:tenants,id',
            'service_id' => 'required|integer|exists:services,id',
            'user_confirmation_token_id' => 'nullable|integer|exists:user_confirmation_tokens,id',
            'start_date_time' => 'required|date|after:now|date_format:Y-m-d H:i:s',
            'end_date_time' => 'required|date|after:start_date_time|date_format:Y-m-d H:i:s',
            'location' => 'nullable|string|max:500',
        ];
    }

    /**
     * Get the tenant that owns the Schedule.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the service that owns the Schedule.
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Get the customer through the service relationship.
     */
    public function getCustomerAttribute(): ?Customer
    {
        return $this->service?->customer;
    }

    /**
     * Alias para compatibilidade com o MailerService que verifica method_exists('customer')
     */
    public function customer()
    {
        return $this->service?->customer();
    }

    /**
     * Get the user confirmation token that owns the Schedule.
     */
    public function userConfirmationToken(): BelongsTo
    {
        return $this->belongsTo(UserConfirmationToken::class);
    }

    /**
     * Retorna a URL para visualização do agendamento
     */
    public function getUrl(): string
    {
        return route('schedules.show', $this->id, true);
    }

    /**
     * Retorna a URL de confirmação pública para o cliente
     */
    public function getConfirmationUrl(): ?string
    {
        if (!$this->userConfirmationToken) {
            return null;
        }

        return route('public.schedule.confirm', [
            'token' => $this->userConfirmationToken->token
        ], true);
    }
}
