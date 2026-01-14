<?php

namespace App\Models;

use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para definições de relatórios avançados
 * Gerencia configurações, filtros e metadados dos relatórios
 */
class ReportDefinition extends Model
{
    use TenantScoped;

    protected $table = 'report_definitions';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'name',
        'description',
        'category',
        'type',
        'config',
        'query_builder',
        'filters',
        'visualization',
        'schedule_config',
        'is_active',
        'is_system',
        'tags',
        'version',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'config' => 'array',
        'query_builder' => 'array',
        'filters' => 'array',
        'visualization' => 'array',
        'schedule_config' => 'array',
        'is_active' => 'boolean',
        'is_system' => 'boolean',
        'tags' => 'array',
        'created_at' => 'immutable_datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Categorias disponíveis para relatórios
     */
    public const CATEGORIES = [
        'financial' => 'Financeiro',
        'customer' => 'Clientes',
        'budget' => 'Orçamentos',
        'executive' => 'Executivo',
        'custom' => 'Personalizado',
    ];

    /**
     * Tipos de relatório
     */
    public const TYPES = [
        'table' => 'Tabela',
        'chart' => 'Gráfico',
        'mixed' => 'Misto',
        'kpi' => 'KPI',
        'pivot' => 'Tabela Dinâmica',
    ];

    /**
     * Regras de validação
     */
    public static function rules(): array
    {
        return [
            'tenant_id' => 'required|exists:tenants,id',
            'user_id' => 'required|exists:users,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'required|string|in:'.implode(',', array_keys(self::CATEGORIES)),
            'type' => 'required|string|in:'.implode(',', array_keys(self::TYPES)),
            'config' => 'required|array',
            'query_builder' => 'required|array',
            'filters' => 'nullable|array',
            'visualization' => 'nullable|array',
            'schedule_config' => 'nullable|array',
            'is_active' => 'boolean',
            'is_system' => 'boolean',
            'tags' => 'nullable|array',
            'version' => 'integer|min:1',
        ];
    }

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

    public function executions(): HasMany
    {
        return $this->hasMany(ReportExecution::class, 'definition_id');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(ReportSchedule::class, 'definition_id');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeSystemReports($query)
    {
        return $query->where('is_system', true);
    }

    public function scopeUserReports($query)
    {
        return $query->where('is_system', false);
    }

    /**
     * Métodos auxiliares
     */
    public function getCategoryLabel(): string
    {
        return self::CATEGORIES[$this->category] ?? 'Desconhecido';
    }

    public function getTypeLabel(): string
    {
        return self::TYPES[$this->type] ?? 'Desconhecido';
    }

    public function isScheduled(): bool
    {
        return $this->schedules()->active()->exists();
    }

    public function hasExecutions(): bool
    {
        return $this->executions()->exists();
    }

    public function getLastExecution()
    {
        return $this->executions()->latest()->first();
    }

    /**
     * Boot method
     */
    protected static function boot()
    {
        parent::boot();
        static::bootTenantScoped();

        static::creating(function ($model) {
            if (empty($model->version)) {
                $model->version = 1;
            }
        });
    }
}
