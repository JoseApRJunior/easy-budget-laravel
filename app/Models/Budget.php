<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BudgetStatus;
use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int $customer_id
 * @property BudgetStatus $status
 * @property int|null $user_confirmation_token_id
 * @property string $code
 * @property \Illuminate\Support\Carbon $due_date
 * @property float $discount
 * @property float $total
 * @property string|null $description
 * @property string|null $payment_terms
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class Budget extends Model
{
    use HasFactory;
    use TenantScoped;

    /**
     * Propriedade temporária para armazenar comentário do cliente durante o ciclo de vida do request.
     * Não é persistida no banco de dados.
     */
    public ?string $transient_customer_comment = null;

    /**
     * Propriedade temporária para suprimir notificações de status durante o ciclo de vida do request.
     * Não é persistida no banco de dados.
     */
    public bool $suppressStatusNotification = false;

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
    protected $table = 'budgets';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'customer_id',
        'status',
        'user_confirmation_token_id',
        'code',
        'due_date',
        'discount',
        'total',
        'description',
        'payment_terms',
        'attachment',
        'history',
        'pdf_verification_hash',
        'status_updated_at',
        'status_updated_by',
    ];

    /**
     * Get the customer comment (from history or temporary attribute).
     */
    public function getCustomerCommentAttribute(?string $value): ?string
    {
        // Se o valor foi definido manualmente na propriedade transiente, retorna ele.
        if (! empty($this->transient_customer_comment)) {
            return $this->transient_customer_comment;
        }

        // Se o valor veio do banco (caso existisse a coluna, ou cache), usa ele.
        if (! empty($value)) {
            return $value;
        }

        // Caso contrário, busca do histórico (última ação relevante)
        $lastAction = $this->actionHistory()
            ->whereNotNull('metadata->customer_comment')
            ->latest()
            ->first();

        return $lastAction?->metadata['customer_comment'] ?? null;
    }

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'tenant_id' => 'integer',
        'customer_id' => 'integer',
        'status' => BudgetStatus::class,
        'user_confirmation_token_id' => 'integer',
        'code' => 'string',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
        'due_date' => 'date',
        'description' => 'string',
        'payment_terms' => 'string',
        'attachment' => 'string',
        'history' => 'string',
        'pdf_verification_hash' => 'string',
        'public_token' => 'string',
        'public_expires_at' => 'datetime',
        'created_at' => 'immutable_datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Campos que devem ser tratados como datas imutáveis.
     */
    protected $dates = [
        'due_date',
        'created_at',
        'updated_at',
    ];

    /**
     * Regras de validação para o modelo Budget.
     */
    public static function businessRules(): array
    {
        return [
            'tenant_id' => 'required|integer|exists:tenants,id',
            'customer_id' => 'required|integer|exists:customers,id',
            'status' => 'required|string|in:'.implode(',', array_column(\App\Enums\BudgetStatus::cases(), 'value')),
            'user_confirmation_token_id' => 'nullable|integer|exists:user_confirmation_tokens,id',
            'code' => 'required|string|max:50|unique:budgets,code',
            'due_date' => 'nullable|date|after:today',
            'discount' => 'required|numeric|min:0|max:999999.99',
            'total' => 'required|numeric|min:0|max:999999.99',
            'description' => 'nullable|string|max:65535',
            'payment_terms' => 'nullable|string|max:65535',
            'attachment' => 'nullable|string|max:255',
            'history' => 'nullable|string|max:65535',
            'pdf_verification_hash' => 'nullable|string|max:64|unique:budgets,pdf_verification_hash', // SHA256 hash, not a confirmation token
        ];
    }

    /**
     * Regras de validação para criação de orçamento.
     */
    public static function createRules(): array
    {
        $rules = self::businessRules();
        $rules['code'] = 'required|string|max:50|unique:budgets,code';
        $rules['total'] = 'required|numeric|min:0.01|max:999999.99';

        return $rules;
    }

    /**
     * Regras de validação para atualização de orçamento.
     */
    public static function updateRules(int $budgetId): array
    {
        $rules = self::businessRules();
        $rules['code'] = 'required|string|max:50|unique:budgets,code,'.$budgetId;
        $rules['pdf_verification_hash'] = 'nullable|string|max:64|unique:budgets,pdf_verification_hash,'.$budgetId;

        return $rules;
    }

    /**
     * Validação customizada para verificar se o código é único no tenant.
     */
    public static function validateUniqueCodeInTenant(string $code, int $tenantId, ?int $excludeBudgetId = null): bool
    {
        $query = static::where('code', $code)->where('tenant_id', $tenantId);

        if ($excludeBudgetId) {
            $query->where('id', '!=', $excludeBudgetId);
        }

        return ! $query->exists();
    }

    /**
     * Validação customizada para verificar se o total é maior que o desconto.
     */
    public static function validateTotalGreaterThanDiscount(float $total, float $discount): bool
    {
        return $total >= $discount;
    }

    /**
     * Get the tenant that owns the Budget.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the provider (business owner) for the Budget.
     */
    public function provider(): \Illuminate\Database\Eloquent\Relations\HasOneThrough
    {
        return $this->hasOneThrough(
            Provider::class,
            Tenant::class,
            'id', // Foreign key on tenants table...
            'tenant_id', // Foreign key on providers table...
            'tenant_id', // Local key on budgets table...
            'id' // Local key on tenants table...
        );
    }

    /**
     * Verifica se o orçamento já foi finalizado (aprovado, rejeitado, cancelado ou concluído).
     */
    public function isFinished(): bool
    {
        return in_array($this->status, [
            BudgetStatus::COMPLETED,
            BudgetStatus::CANCELLED,
            BudgetStatus::REJECTED,
        ], true);
    }

    /**
     * Get the customer that owns the Budget.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the budget status enum.
     */
    public function getBudgetStatusAttribute(): ?BudgetStatus
    {
        return $this->status;
    }

    /**
     * Get the budget status enum for backward compatibility with views.
     */
    public function budgetStatus(): ?BudgetStatus
    {
        return $this->status;
    }

    public function userConfirmationToken(): BelongsTo
    {
        return $this->belongsTo(UserConfirmationToken::class);
    }

    /**
     * Get the services for the Budget.
     */
    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    /**
     * Get the budget versions for the Budget.
     */
    public function versions(): HasMany
    {
        return $this->hasMany(BudgetVersion::class);
    }

    /**
     * Get the current budget version.
     */
    public function currentVersion(): BelongsTo
    {
        return $this->belongsTo(BudgetVersion::class, 'current_version_id');
    }

    /**
     * Get the budget action history.
     */
    public function actionHistory(): HasMany
    {
        return $this->hasMany(BudgetActionHistory::class);
    }

    /**
     * Get the budget notifications.
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(BudgetNotification::class);
    }

    public function getRouteKeyName(): string
    {
        return 'code';
    }

    /**
     * Scope para orçamentos ativos.
     */
    public function scopeActive($query)
    {
        $activeStatuses = array_filter(
            array_column(\App\Enums\BudgetStatus::cases(), 'value'),
            fn ($status) => \App\Enums\BudgetStatus::tryFrom($status)?->isActive() ?? false
        );

        return $query->whereIn('status', $activeStatuses);
    }

    /**
     * Scope para orçamentos por status.
     */
    public function scopeByStatus($query, $statusSlug)
    {
        return $query->where('status', $statusSlug);
    }

    /**
     * Scope para orçamentos válidos (não expirados).
     */
    public function scopeValid($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('valid_until')
                ->orWhere('valid_until', '>', Carbon::now());
        });
    }

    /**
     * Scope para orçamentos expirados.
     */
    public function scopeExpired($query)
    {
        return $query->where('valid_until', '<=', Carbon::now());
    }

    /**
     * Scope para orçamentos enviados.
     */
    public function scopeSent($query)
    {
        return $query->byStatus('enviado');
    }

    /**
     * Scope para orçamentos aprovados.
     */
    public function scopeApproved($query)
    {
        return $query->byStatus('aprovado');
    }

    /**
     * Scope para orçamentos rejeitados.
     */
    public function scopeRejected($query)
    {
        return $query->byStatus('rejeitado');
    }

    /**
     * Scope para orçamentos em rascunho.
     */
    public function scopeDraft($query)
    {
        return $query->byStatus('rascunho');
    }

    /**
     * Verifica se o orçamento está expirado.
     */
    public function isExpired(): bool
    {
        return $this->valid_until && $this->valid_until->isPast();
    }

    /**
     * Verifica se o orçamento está válido.
     */
    public function isValid(): bool
    {
        return ! $this->isExpired();
    }

    /**
     * Verifica se o orçamento pode ser editado.
     */
    public function canBeEdited(): bool
    {
        return in_array($this->status?->value, [\App\Enums\BudgetStatus::DRAFT->value, \App\Enums\BudgetStatus::REJECTED->value]);
    }

    /**
     * Verifica se o orçamento pode ser enviado.
     */
    public function canBeSent(): bool
    {
        return $this->status === BudgetStatus::DRAFT && $this->isValid();
    }

    /**
     * Verifica se o orçamento pode ser aprovado.
     */
    public function canBeApproved(): bool
    {
        return $this->status === \App\Enums\BudgetStatus::PENDING && $this->isValid();
    }

    /**
     * Verifica se o orçamento pode ser rejeitado.
     */
    public function canBeRejected(): bool
    {
        return $this->status === \App\Enums\BudgetStatus::PENDING;
    }

    /**
     * Calcula o total do orçamento baseado nos serviços e seus itens.
     */
    public function calculateTotals(): array
    {
        $subtotal = $this->services()
            ->whereNotIn('status', [
                \App\Enums\ServiceStatus::CANCELLED->value,
                \App\Enums\ServiceStatus::NOT_PERFORMED->value,
                \App\Enums\ServiceStatus::EXPIRED->value,
            ])
            ->get()
            ->sum('total');

        // No novo modelo, descontos e impostos são tratados dentro de cada Serviço/Item
        // Mas se houver um desconto global no orçamento, aplicamos aqui
        $discountTotal = (float) ($this->discount ?? 0.0);

        $grandTotal = $subtotal - $discountTotal;

        return [
            'subtotal' => $subtotal,
            'discount_total' => $discountTotal,
            'taxes_total' => 0, // Impostos agora estão embutidos no total do serviço
            'grand_total' => $grandTotal,
            'services_count' => $this->services->count(),
        ];
    }

    /**
     * Atualiza os totais calculados do orçamento.
     */
    public function updateCalculatedTotals(): void
    {
        $totals = $this->calculateTotals();

        $this->update([
            'subtotal' => $totals['subtotal'],
            'total' => $totals['grand_total'],
            'updated_at' => Carbon::now(),
        ]);
    }

    /**
     * Cria uma nova versão do orçamento.
     */
    public function createVersion(string $changeDescription, int $userId): BudgetVersion
    {
        $totals = $this->calculateTotals();

        $version = $this->versions()->create([
            'tenant_id' => $this->tenant_id,
            'user_id' => $userId,
            'version_number' => $this->getNextVersionNumber(),
            'changes_description' => $changeDescription,
            'budget_data' => $this->toArray(),
            'services_data' => $this->services->map(fn ($s) => [
                'id' => $s->id,
                'category_id' => $s->category_id,
                'description' => $s->description,
                'total' => $s->total,
                'items' => $s->serviceItems->toArray(),
            ])->toArray(),
            'version_total' => $totals['grand_total'],
            'is_current' => true,
            'version_date' => Carbon::now(),
        ]);

        // Marcar versão atual
        $this->update(['current_version_id' => $version->id]);

        // Desmarcar outras versões como não atuais
        $this->versions()->where('id', '!=', $version->id)->update(['is_current' => false]);

        return $version;
    }

    /**
     * Obtém o próximo número de versão.
     */
    private function getNextVersionNumber(): string
    {
        $lastVersion = $this->versions()->max('version_number');

        if (! $lastVersion) {
            return '1.0';
        }

        $parts = explode('.', $lastVersion);
        $major = (int) $parts[0];
        $minor = (int) $parts[1];

        return ($minor + 1) >= 10 ? ($major + 1).'.0' : $major.'.'.($minor + 1);
    }

    /**
     * Restaura uma versão específica.
     */
    public function restoreVersion(BudgetVersion $version, int $userId): bool
    {
        if ($version->budget_id !== $this->id) {
            return false;
        }

        // Criar nova versão com dados restaurados
        $this->createVersion("Restauração da versão {$version->version_number}", $userId);

        // Atualizar dados do orçamento com os dados da versão
        $budgetData = $version->budget_data;
        $this->update([
            'customer_id' => $budgetData['customer_id'],
            'status' => $budgetData['status'],
            'code' => $budgetData['code'],
            'due_date' => $budgetData['due_date'],
            'discount' => $budgetData['discount'],
            'total' => $budgetData['total'],
            'description' => $budgetData['description'],
            'payment_terms' => $budgetData['payment_terms'],
            'attachment' => $budgetData['attachment'],
            'history' => $budgetData['history'],
            'pdf_verification_hash' => $budgetData['pdf_verification_hash'],
        ]);

        // Recriar serviços e itens se necessário
        if (isset($version->services_data) && is_array($version->services_data)) {
            $this->services()->delete();
            foreach ($version->services_data as $serviceData) {
                $service = $this->services()->create([
                    'tenant_id' => $this->tenant_id,
                    'category_id' => $serviceData['category_id'],
                    'description' => $serviceData['description'],
                    'status' => $this->status,
                    'code' => $serviceData['code'] ?? 'SRV-'.uniqid(),
                    'total' => $serviceData['total'],
                ]);

                if (isset($serviceData['items']) && is_array($serviceData['items'])) {
                    foreach ($serviceData['items'] as $itemData) {
                        $service->serviceItems()->create($itemData);
                    }
                }
            }
        }

        return true;
    }

    /**
     * Duplica o orçamento.
     */
    public function duplicate(int $userId): Budget
    {
        $newBudget = $this->replicate();
        $newBudget->code = $this->generateDuplicateCode();
        $newBudget->status = \App\Enums\BudgetStatus::DRAFT->value;
        $newBudget->current_version_id = null;
        $newBudget->save();

        // Duplicar serviços e seus itens
        foreach ($this->services as $service) {
            $newService = $service->replicate();
            $newService->budget_id = $newBudget->id;
            $newService->save();

            foreach ($service->serviceItems as $item) {
                $newItem = $item->replicate();
                $newItem->service_id = $newService->id;
                $newItem->save();
            }
        }

        // Criar versão inicial
        $newBudget->createVersion('Orçamento criado por duplicação', $userId);

        return $newBudget;
    }

    /**
     * Gera a URL pública para visualização/interação com o orçamento.
     */
    public function getPublicUrl(): ?string
    {
        // Obter o compartilhamento mais recente ativo ou criar um novo
        $share = \App\Models\BudgetShare::where('budget_id', $this->id)
            ->where('is_active', true)
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if ($share) {
            return route('budgets.public.shared.view', ['token' => $share->share_token]);
        }

        return null;
    }

    /**
     * Gera a URL para impressão do orçamento.
     */
    public function getPrintUrl(): ?string
    {
        $share = \App\Models\BudgetShare::where('budget_id', $this->id)
            ->where('is_active', true)
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (! $share || ! $this->code) {
            return null;
        }

        return route('budgets.public.print', [
            'code' => $this->code,
            'token' => $share->share_token,
        ], true);
    }

    /**
     * Gera a URL interna para visualização do orçamento.
     */
    public function getUrl(): string
    {
        return route('provider.budgets.show', $this->id, true);
    }
}
