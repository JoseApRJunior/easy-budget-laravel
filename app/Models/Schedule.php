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
     * Flag para suprimir notificações de status (usado em processos em lote ou transições automáticas)
     */
    public bool $suppressStatusNotification = false;

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();
        static::bootTenantScoped();

        static::creating(function ($schedule) {
            if (empty($schedule->code)) {
                $year = date('Y');
                $month = date('m');

                // Busca o último agendamento deste mês para gerar sequencial
                $lastSchedule = static::whereYear('created_at', $year)
                    ->whereMonth('created_at', $month)
                    ->where('code', 'LIKE', "AGD-{$year}-{$month}-%")
                    ->latest('id')
                    ->first();

                $sequence = 1;
                if ($lastSchedule && preg_match('/AGD-\d{4}-\d{2}-(\d+)/', $lastSchedule->code, $matches)) {
                    $sequence = (int) $matches[1] + 1;
                }

                $schedule->code = sprintf('AGD-%s-%s-%06d', $year, $month, $sequence);

                // Garante unicidade em caso de concorrência
                while (static::where('code', $schedule->code)->exists()) {
                    $sequence++;
                    $schedule->code = sprintf('AGD-%s-%s-%06d', $year, $month, $sequence);
                }
            }
        });
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
        'code',
        'service_id',
        'user_confirmation_token_id',
        'start_date_time',
        'location',
        'notes',
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
        'notes' => 'string',
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
            'code' => 'required|string|max:20|unique:schedules,code',
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
        return route('provider.schedules.show', $this->id, true);
    }

    /**
     * Retorna a URL de confirmação pública para o cliente
     */
    public function getConfirmationUrl(): ?string
    {
        if (! $this->userConfirmationToken) {
            return null;
        }

        return route('services.public.schedules.confirm', [
            'token' => $this->userConfirmationToken->token,
        ], true);
    }

    /**
     * Retorna a URL pública (usa o padrão do serviço para visualização do cliente)
     */
    /**
     * Retorna a URL pública para visualização do status do agendamento.
     * Prioriza a URL do serviço relacionado, pois ela contém o status completo.
     */
    public function getPublicUrl(): ?string
    {
        // Se tiver token de confirmação de usuário, usamos a rota de confirmação
        if ($this->userConfirmationToken) {
            return $this->getConfirmationUrl();
        }

        if (! $this->service) {
            return null;
        }

        // Tenta obter a URL pública do serviço
        $url = $this->service->getPublicUrl();

        // Se a URL do serviço for nula (raro agora com o trait), retornamos a rota administrativa
        // Mas o listener deve tratar isso para não enviar para o cliente
        return $url;
    }
}
