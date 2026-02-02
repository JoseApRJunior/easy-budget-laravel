<?php

namespace App\Models;

use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Modelo para relatórios gerados - Compatibilidade com sistema legado
 * Armazena arquivos de relatório gerados e metadados básicos
 */
class Report extends Model
{
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
    protected $table = 'reports';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'user_id',
        'hash',
        'type',
        'description',
        'file_name',
        'file_path',
        'status',
        'format',
        'size',
        'filters',
        'error_message',
        'generated_at',
        'definition_id',
        'execution_id',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'hash' => 'string',
        'type' => 'string',
        'description' => 'string',
        'file_name' => 'string',
        'file_path' => 'string',
        'status' => 'string',
        'format' => 'string',
        'size' => 'float',
        'filters' => 'array',
        'error_message' => 'string',
        'generated_at' => 'datetime',
        'metadata' => 'array',
        'created_at' => 'immutable_datetime',
    ];

    /**
     * Status possíveis para relatórios
     */
    public const STATUS = [
        'pending' => 'Pendente',
        'processing' => 'Processando',
        'completed' => 'Concluído',
        'failed' => 'Falhou',
        'expired' => 'Expirado',
    ];

    /**
     * Formatos suportados
     */
    public const FORMATS = [
        'pdf' => 'PDF',
        'xlsx' => 'Excel',
        'csv' => 'CSV',
        'json' => 'JSON',
    ];

    /**
     * Tipos de relatório
     */
    public const TYPES = [
        'financial' => 'Financeiro',
        'customer' => 'Clientes',
        'budget' => 'Orçamentos',
        'executive' => 'Executivo',
        'custom' => 'Personalizado',
    ];

    /**
     * Regras de validação para o modelo Report.
     */
    public static function businessRules(): array
    {
        return [
            'tenant_id' => 'required|exists:tenants,id',
            'user_id' => 'required|exists:users,id',
            'hash' => 'nullable|string|max:64',
            'type' => 'required|string|max:50|in:'.implode(',', array_keys(self::TYPES)),
            'description' => 'nullable|string',
            'file_name' => 'required|string|max:255',
            'status' => 'required|string|max:20|in:'.implode(',', array_keys(self::STATUS)),
            'format' => 'required|string|max:10|in:'.implode(',', array_keys(self::FORMATS)),
            'size' => 'nullable|numeric|min:0',
            'definition_id' => 'nullable|exists:report_definitions,id',
            'execution_id' => 'nullable|exists:report_executions,execution_id',
            'metadata' => 'nullable|array',
        ];
    }

    /**
     * Validação personalizada para hash único por tenant.
     * Esta validação deve ser usada no contexto de um request onde o tenant_id está disponível.
     */
    public static function validateUniqueHashRule(?string $hash, ?int $excludeId = null): string
    {
        if (empty($hash)) {
            return 'nullable|string|max:64';
        }

        $rule = 'unique:reports,hash';

        if ($excludeId) {
            $rule .= ','.$excludeId.',id';
        }

        return $rule.',tenant_id,'.request()->user()->tenant_id;
    }

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public const UPDATED_AT = null;

    /**
     * Relacionamentos
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function definition(): BelongsTo
    {
        return $this->belongsTo(ReportDefinition::class, 'definition_id');
    }

    public function executions(): HasMany
    {
        return $this->hasMany(ReportExecution::class, 'execution_id', 'execution_id');
    }

    /**
     * Scopes
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByFormat($query, string $format)
    {
        return $query->where('format', $format);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', Carbon::now()->subDays($days));
    }

    /**
     * Métodos auxiliares
     */
    public function getStatusLabel(): string
    {
        return self::STATUS[$this->status] ?? 'Desconhecido';
    }

    public function getTypeLabel(): string
    {
        return self::TYPES[$this->type] ?? 'Desconhecido';
    }

    public function getFormatLabel(): string
    {
        return self::FORMATS[$this->format] ?? 'Desconhecido';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function getFileSizeFormatted(): string
    {
        if (! $this->size) {
            return 'N/A';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = $this->size;
        $unitIndex = 0;

        while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
            $bytes /= 1024;
            $unitIndex++;
        }

        return round($bytes, 2).' '.$units[$unitIndex];
    }

    public function getFilePath(): ?string
    {
        if (! $this->file_name) {
            return null;
        }

        return "reports/{$this->tenant_id}/{$this->file_name}";
    }

    public function getDownloadUrl(): ?string
    {
        if (! $this->isCompleted()) {
            return null;
        }

        return route('reports.download', $this->hash);
    }
}
