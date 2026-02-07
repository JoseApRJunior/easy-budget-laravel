<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
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
    protected $table = 'email_templates';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'category',
        'subject',
        'html_content',
        'text_content',
        'variables',
        'is_active',
        'is_system',
        'sort_order',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'tenant_id' => 'integer',
        'variables' => 'array',
        'is_active' => 'boolean',
        'is_system' => 'boolean',
        'sort_order' => 'integer',
        'metadata' => 'array',
        'created_at' => 'immutable_datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Campos que devem ser tratados como datas imutáveis.
     */
    protected $dates = [
        'created_at',
        'updated_at',
    ];

    /**
     * Regras de validação para o modelo EmailTemplate.
     */
    public static function businessRules(): array
    {
        return [
            'tenant_id' => 'required|integer|exists:tenants,id',
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:100|unique:email_templates,slug',
            'category' => 'required|in:transactional,promotional,notification,system',
            'subject' => 'required|string|max:500',
            'html_content' => 'required|string',
            'text_content' => 'nullable|string',
            'variables' => 'nullable|array',
            'is_active' => 'boolean',
            'is_system' => 'boolean',
            'sort_order' => 'integer|min:0',
            'metadata' => 'nullable|array',
        ];
    }

    /**
     * Regras de validação para criação de template.
     */
    public static function createRules(): array
    {
        $rules = self::businessRules();
        $rules['name'] = 'required|string|max:255|unique:email_templates,name';
        $rules['slug'] = 'required|string|max:100|unique:email_templates,slug';

        return $rules;
    }

    /**
     * Regras de validação para atualização de template.
     */
    public static function updateRules(int $templateId): array
    {
        $rules = self::businessRules();
        $rules['name'] = 'required|string|max:255|unique:email_templates,name,'.$templateId;
        $rules['slug'] = 'required|string|max:100|unique:email_templates,slug,'.$templateId;

        return $rules;
    }

    /**
     * Get the tenant that owns the EmailTemplate.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the email logs for the EmailTemplate.
     */
    public function logs(): HasMany
    {
        return $this->hasMany(EmailLog::class);
    }

    /**
     * Scope para templates ativos.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope para templates por categoria.
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope para templates do sistema.
     */
    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }

    /**
     * Scope para templates customizados.
     */
    public function scopeCustom($query)
    {
        return $query->where('is_system', false);
    }

    /**
     * Scope para ordenação padrão.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Verifica se o template pode ser editado.
     */
    public function canBeEdited(): bool
    {
        return ! $this->is_system;
    }

    /**
     * Verifica se o template pode ser excluído.
     */
    public function canBeDeleted(): bool
    {
        return ! $this->is_system && $this->logs()->count() === 0;
    }

    /**
     * Obtém as variáveis utilizadas no template.
     */
    public function getUsedVariables(): array
    {
        $variables = [];

        // Extrair variáveis do conteúdo HTML
        if (preg_match_all('/\{\{(\w+)\}\}/', $this->html_content, $matches)) {
            $variables = array_merge($variables, $matches[1]);
        }

        // Extrair variáveis do conteúdo texto
        if ($this->text_content && preg_match_all('/\{\{(\w+)\}\}/', $this->text_content, $matches)) {
            $variables = array_merge($variables, $matches[1]);
        }

        return array_unique($variables);
    }

    /**
     * Valida se todas as variáveis utilizadas estão disponíveis.
     */
    public function validateVariables(array $availableVariables): array
    {
        $usedVariables = $this->getUsedVariables();
        $invalidVariables = array_diff($usedVariables, $availableVariables);

        return [
            'valid' => empty($invalidVariables),
            'used' => $usedVariables,
            'invalid' => array_values($invalidVariables),
        ];
    }

    /**
     * Cria uma cópia do template.
     */
    public function duplicate(): EmailTemplate
    {
        $newTemplate = $this->replicate();
        $newTemplate->name = 'Cópia de '.$this->name;
        $newTemplate->slug = $this->slug.'-copy-'.time();
        $newTemplate->is_system = false;
        $newTemplate->save();

        return $newTemplate;
    }

    /**
     * Obtém estatísticas de uso do template.
     */
    public function getUsageStats(): array
    {
        return [
            'total_sent' => $this->logs()->count(),
            'total_opened' => $this->logs()->whereNotNull('opened_at')->count(),
            'total_clicked' => $this->logs()->whereNotNull('clicked_at')->count(),
            'total_bounced' => $this->logs()->where('status', 'bounced')->count(),
            'open_rate' => $this->calculateOpenRate(),
            'click_rate' => $this->calculateClickRate(),
        ];
    }

    /**
     * Calcula taxa de abertura.
     */
    private function calculateOpenRate(): float
    {
        $totalSent = $this->logs()->count();
        if ($totalSent === 0) {
            return 0.0;
        }

        $totalOpened = $this->logs()->whereNotNull('opened_at')->count();

        return round(($totalOpened / $totalSent) * 100, 2);
    }

    /**
     * Calcula taxa de clique.
     */
    private function calculateClickRate(): float
    {
        $totalSent = $this->logs()->count();
        if ($totalSent === 0) {
            return 0.0;
        }

        $totalClicked = $this->logs()->whereNotNull('clicked_at')->count();

        return round(($totalClicked / $totalSent) * 100, 2);
    }
}
