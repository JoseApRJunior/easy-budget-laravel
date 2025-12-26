<?php

namespace App\Models;

use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Modelo para agendamento de relatórios
 * Gerencia quando e como os relatórios devem ser executados automaticamente
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $definition_id
 * @property int $user_id
 * @property string $name
 * @property string|null $description
 * @property bool $is_active
 * @property string $frequency_type
 * @property int|null $frequency_value
 * @property int|null $day_of_week
 * @property int|null $day_of_month
 * @property string $time_to_run
 * @property string $timezone
 * @property Carbon|null $last_run_at
 * @property Carbon|null $next_run_at
 * @property array $recipients
 * @property string $email_subject
 * @property string|null $email_body
 * @property string $format
 * @property array|null $parameters
 * @property array|null $filters
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class ReportSchedule extends Model
{
    use TenantScoped;

    protected $table = 'report_schedules';

    protected $fillable = [
        'tenant_id',
        'definition_id',
        'user_id',
        'name',
        'description',
        'is_active',
        'frequency_type',
        'frequency_value',
        'day_of_week',
        'day_of_month',
        'time_to_run',
        'timezone',
        'last_run_at',
        'next_run_at',
        'recipients',
        'email_subject',
        'email_body',
        'format',
        'parameters',
        'filters',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'frequency_value' => 'integer',
        'day_of_week' => 'integer',
        'day_of_month' => 'integer',
        'last_run_at' => 'datetime',
        'next_run_at' => 'datetime',
        'recipients' => 'array',
        'parameters' => 'array',
        'filters' => 'array',
        'created_at' => 'immutable_datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Tipos de frequência
     */
    public const FREQUENCY_TYPES = [
        'daily' => 'Diário',
        'weekly' => 'Semanal',
        'monthly' => 'Mensal',
        'quarterly' => 'Trimestral',
        'yearly' => 'Anual',
        'custom' => 'Personalizado',
    ];

    /**
     * Dias da semana
     */
    public const DAYS_OF_WEEK = [
        1 => 'Segunda-feira',
        2 => 'Terça-feira',
        3 => 'Quarta-feira',
        4 => 'Quinta-feira',
        5 => 'Sexta-feira',
        6 => 'Sábado',
        7 => 'Domingo',
    ];

    /**
     * Formatos de exportação
     */
    public const FORMATS = [
        'pdf' => 'PDF',
        'excel' => 'Excel',
        'csv' => 'CSV',
        'json' => 'JSON',
    ];

    /**
     * Regras de validação
     */
    public static function rules(): array
    {
        return [
            'tenant_id' => 'required|exists:tenants,id',
            'definition_id' => 'required|exists:report_definitions,id',
            'user_id' => 'required|exists:users,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'frequency_type' => 'required|string|in:'.implode(',', array_keys(self::FREQUENCY_TYPES)),
            'frequency_value' => 'nullable|integer|min:1',
            'day_of_week' => 'nullable|integer|min:1|max:7',
            'day_of_month' => 'nullable|integer|min:1|max:31',
            'time_to_run' => 'required|string|regex:/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/',
            'timezone' => 'required|string|max:50',
            'recipients' => 'required|array|min:1',
            'recipients.*' => 'email',
            'email_subject' => 'required|string|max:255',
            'email_body' => 'nullable|string',
            'format' => 'required|string|in:'.implode(',', array_keys(self::FORMATS)),
            'parameters' => 'nullable|array',
            'filters' => 'nullable|array',
        ];
    }

    /**
     * Relacionamentos
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function definition(): BelongsTo
    {
        return $this->belongsTo(ReportDefinition::class, 'definition_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByFrequency($query, string $frequency)
    {
        return $query->where('frequency_type', $frequency);
    }

    public function scopeDue($query)
    {
        return $query->where('next_run_at', '<=', now());
    }

    public function scopeByDefinition($query, int $definitionId)
    {
        return $query->where('definition_id', $definitionId);
    }

    /**
     * Métodos auxiliares
     */
    public function getFrequencyLabel(): string
    {
        return self::FREQUENCY_TYPES[$this->frequency_type] ?? 'Desconhecido';
    }

    public function getFormatLabel(): string
    {
        return self::FORMATS[$this->format] ?? 'Desconhecido';
    }

    public function getDayOfWeekLabel(): string
    {
        return self::DAYS_OF_WEEK[$this->day_of_week] ?? 'N/A';
    }

    public function isDue(): bool
    {
        return $this->next_run_at && $this->next_run_at->isPast();
    }

    public function calculateNextRun(): Carbon
    {
        $now = now($this->timezone);
        $time = $this->time_to_run;

        switch ($this->frequency_type) {
            case 'daily':
                $nextRun = $now->setTimeFromTimeString($time);
                if ($nextRun->isPast()) {
                    $nextRun->addDay();
                }
                break;

            case 'weekly':
                $nextRun = $now->next((int) $this->day_of_week)->setTimeFromTimeString($time);
                break;

            case 'monthly':
                $nextRun = $now->setDay((int) $this->day_of_month)->setTimeFromTimeString($time);
                if ($nextRun->isPast()) {
                    $nextRun->addMonth();
                }
                break;

            case 'quarterly':
                $nextRun = $now->firstOfQuarter()->setTimeFromTimeString($time);
                if ($nextRun->isPast()) {
                    $nextRun->addQuarter();
                }
                break;

            case 'yearly':
                $nextRun = $now->setMonth(1)->setDay(1)->setTimeFromTimeString($time);
                if ($nextRun->isPast()) {
                    $nextRun->addYear();
                }
                break;

            default:
                $nextRun = $now->addHour();
        }

        return $nextRun;
    }

    public function updateNextRun(): void
    {
        $this->next_run_at = $this->calculateNextRun();
        $this->save();
    }

    public function markAsRun(): void
    {
        $this->last_run_at = now();
        $this->updateNextRun();
    }

    public function shouldRunToday(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $today = now($this->timezone)->toDateString();
        $nextRun = $this->next_run_at?->toDateString();

        return $nextRun === $today;
    }

    public function getRecipientsAsString(): string
    {
        return implode(', ', $this->recipients);
    }

    /**
     * Boot method
     */
    protected static function boot()
    {
        parent::boot();
        static::bootTenantScoped();

        static::creating(function ($model) {
            if (empty($model->timezone)) {
                $model->timezone = config('app.timezone');
            }
            if (empty($model->next_run_at)) {
                $model->next_run_at = $model->calculateNextRun();
            }
        });

        static::updating(function ($model) {
            if (
                $model->isDirty('frequency_type') ||
                $model->isDirty('frequency_value') ||
                $model->isDirty('day_of_week') ||
                $model->isDirty('day_of_month') ||
                $model->isDirty('time_to_run') ||
                $model->isDirty('timezone')
            ) {
                $model->next_run_at = $model->calculateNextRun();
            }
        });
    }
}
