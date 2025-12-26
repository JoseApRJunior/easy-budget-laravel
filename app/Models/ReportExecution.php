<?php

namespace App\Models;

use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para controle de execuções de relatórios
 * Registra cada execução com seus parâmetros e resultados
 */
class ReportExecution extends Model
{
    use TenantScoped;

    protected $table = 'report_executions';

    protected $fillable = [
        'tenant_id',
        'definition_id',
        'user_id',
        'execution_id',
        'status',
        'parameters',
        'filters_applied',
        'data_count',
        'execution_time',
        'memory_used',
        'error_message',
        'file_path',
        'file_size',
        'expires_at',
        'executed_at',
        'completed_at',
    ];

    protected $casts = [
        'parameters' => 'array',
        'filters_applied' => 'array',
        'data_count' => 'integer',
        'execution_time' => 'float',
        'memory_used' => 'integer',
        'expires_at' => 'datetime',
        'executed_at' => 'datetime',
        'completed_at' => 'datetime',
        'created_at' => 'immutable_datetime',
    ];

    /**
     * Status possíveis
     */
    public const STATUS = [
        'pending' => 'Pendente',
        'running' => 'Executando',
        'completed' => 'Concluído',
        'failed' => 'Falhou',
        'cancelled' => 'Cancelado',
        'expired' => 'Expirado',
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
            'execution_id' => 'required|string|unique:report_executions',
            'status' => 'required|string|in:'.implode(',', array_keys(self::STATUS)),
            'parameters' => 'nullable|array',
            'filters_applied' => 'nullable|array',
            'data_count' => 'nullable|integer|min:0',
            'execution_time' => 'nullable|numeric|min:0',
            'memory_used' => 'nullable|integer|min:0',
            'error_message' => 'nullable|string',
            'file_path' => 'nullable|string|max:500',
            'file_size' => 'nullable|integer|min:0',
            'expires_at' => 'nullable|date|after:now',
            'executed_at' => 'nullable|date',
            'completed_at' => 'nullable|date',
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
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeRunning($query)
    {
        return $query->where('status', 'running');
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now());
    }

    public function scopeByDefinition($query, int $definitionId)
    {
        return $query->where('definition_id', $definitionId);
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Métodos auxiliares
     */
    public function getStatusLabel(): string
    {
        return self::STATUS[$this->status] ?? 'Desconhecido';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isRunning(): bool
    {
        return $this->status === 'running';
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function markAsCompleted(?string $filePath = null, ?int $fileSize = null): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'file_path' => $filePath,
            'file_size' => $fileSize,
        ]);
    }

    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'completed_at' => now(),
            'error_message' => $errorMessage,
        ]);
    }

    public function getExecutionDuration(): ?float
    {
        if (! $this->executed_at || ! $this->completed_at) {
            return null;
        }

        return $this->executed_at->diffInSeconds($this->completed_at, false);
    }

    public function getFileSizeFormatted(): string
    {
        if (! $this->file_size) {
            return 'N/A';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = $this->file_size;
        $unitIndex = 0;

        while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
            $bytes /= 1024;
            $unitIndex++;
        }

        return round($bytes, 2).' '.$units[$unitIndex];
    }

    /**
     * Boot method
     */
    protected static function boot()
    {
        parent::boot();
        static::bootTenantScoped();

        static::creating(function ($model) {
            if (empty($model->execution_id)) {
                $model->execution_id = 'exec_'.uniqid().'_'.time();
            }
            if (empty($model->executed_at)) {
                $model->executed_at = now();
            }
        });
    }
}
