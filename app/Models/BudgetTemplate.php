<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Tenant;
use App\Models\Traits\TenantScoped;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BudgetTemplate extends Model
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

        // Atualizar contador de uso quando usado
        static::saving( function ( $template ) {
            if ( $template->isDirty( 'usage_count' ) ) {
                $template->last_used_at = now();
            }
        } );
    }

    /**
     * The table associated with the model.
     */
    protected $table = 'budget_templates';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'tenant_id',
        'user_id',
        'parent_template_id',
        'name',
        'slug',
        'description',
        'category',
        'template_data',
        'default_items',
        'variables',
        'estimated_hours',
        'is_public',
        'is_active',
        'usage_count',
        'last_used_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'tenant_id'          => 'integer',
        'user_id'            => 'integer',
        'parent_template_id' => 'integer',
        'template_data'      => 'array',
        'default_items'      => 'array',
        'variables'          => 'array',
        'estimated_hours'    => 'decimal:2',
        'is_public'          => 'boolean',
        'is_active'          => 'boolean',
        'usage_count'        => 'integer',
        'last_used_at'       => 'datetime',
        'created_at'         => 'immutable_datetime',
        'updated_at'         => 'datetime',
    ];

    /**
     * Regras de validação para o modelo BudgetTemplate.
     */
    public static function businessRules(): array
    {
        return [
            'tenant_id'          => 'required|integer|exists:tenants,id',
            'user_id'            => 'required|integer|exists:users,id',
            'parent_template_id' => 'nullable|integer|exists:budget_templates,id',
            'name'               => 'required|string|max:255',
            'slug'               => 'required|string|max:100|alpha_dash',
            'description'        => 'nullable|string|max:1000',
            'category'           => 'required|string|max:50',
            'template_data'      => 'required|array',
            'default_items'      => 'required|array',
            'variables'          => 'nullable|array',
            'estimated_hours'    => 'nullable|numeric|min:0|max:9999.99',
            'is_public'          => 'required|boolean',
            'is_active'          => 'required|boolean',
            'usage_count'        => 'required|integer|min:0',
        ];
    }

    /**
     * Regras de validação para criação.
     */
    public static function createRules(): array
    {
        $rules         = self::businessRules();
        $rules[ 'slug' ] = 'required|string|max:100|alpha_dash|unique:budget_templates,slug';

        return $rules;
    }

    /**
     * Regras de validação para atualização.
     */
    public static function updateRules( int $templateId ): array
    {
        $rules         = self::businessRules();
        $rules[ 'slug' ] = 'required|string|max:100|alpha_dash|unique:budget_templates,slug,' . $templateId;

        return $rules;
    }

    /**
     * Get the tenant that owns the BudgetTemplate.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo( Tenant::class);
    }

    /**
     * Get the user that owns the BudgetTemplate.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo( User::class);
    }

    /**
     * Get the parent template.
     */
    public function parentTemplate(): BelongsTo
    {
        return $this->belongsTo( BudgetTemplate::class, 'parent_template_id' );
    }

    /**
     * Get the child templates.
     */
    public function childTemplates(): HasMany
    {
        return $this->hasMany( BudgetTemplate::class, 'parent_template_id' );
    }

    /**
     * Scope para templates ativos.
     */
    public function scopeActive( $query )
    {
        return $query->where( 'is_active', true );
    }

    /**
     * Scope para templates públicos.
     */
    public function scopePublic( $query )
    {
        return $query->where( 'is_public', true );
    }

    /**
     * Scope para templates privados.
     */
    public function scopePrivate( $query )
    {
        return $query->where( 'is_public', false );
    }

    /**
     * Scope para templates por categoria.
     */
    public function scopeByCategory( $query, string $category )
    {
        return $query->where( 'category', $category );
    }

    /**
     * Scope para templates ordenados por uso.
     */
    public function scopeMostUsed( $query )
    {
        return $query->orderBy( 'usage_count', 'desc' );
    }

    /**
     * Scope para templates ordenados por data de criação.
     */
    public function scopeRecent( $query )
    {
        return $query->orderBy( 'created_at', 'desc' );
    }

    /**
     * Incrementa o contador de uso.
     */
    public function incrementUsage(): bool
    {
        $this->increment( 'usage_count' );
        $this->last_used_at = now();
        return $this->save();
    }

    /**
     * Cria um orçamento baseado neste template.
     */
    public function createBudgetFromTemplate( array $overrides = [] ): Budget
    {
        // Incrementar contador de uso
        $this->incrementUsage();

        // Dados base do orçamento
        $budgetData = array_merge( [
            'customer_id'        => $overrides[ 'customer_id' ] ?? null,
            'budget_statuses_id' => BudgetStatus::where( 'slug', 'rascunho' )->first()->id,
            'description'        => $this->description,
            'valid_until'        => now()->addDays( 30 ), // padrão 30 dias
        ], $overrides );

        // Criar orçamento
        $budget = Budget::create( $budgetData );

        // Adicionar itens do template
        if ( !empty( $this->default_items ) ) {
            foreach ( $this->default_items as $itemData ) {
                // Aplicar variáveis se houver
                $processedItemData = $this->processItemVariables( $itemData, $overrides[ 'variables' ] ?? [] );

                $budget->addItem( $processedItemData );
            }
        }

        // Criar versão inicial
        $budget->createVersion( 'Orçamento criado a partir do template: ' . $this->name, $overrides[ 'user_id' ] ?? $this->user_id );

        return $budget;
    }

    /**
     * Processa variáveis nos itens do template.
     */
    private function processItemVariables( array $itemData, array $variables ): array
    {
        foreach ( $itemData as $key => $value ) {
            if ( is_string( $value ) ) {
                // Substituir variáveis no formato {{variable_name}}
                foreach ( $variables as $varName => $varValue ) {
                    $itemData[ $key ] = str_replace( "{{{$varName}}}", $varValue, $value );
                }
            }
        }

        return $itemData;
    }

    /**
     * Obtém categorias disponíveis para templates.
     */
    public static function getAvailableCategories(): array
    {
        return [
            'produto'     => 'Produto',
            'servico'     => 'Serviço',
            'projeto'     => 'Projeto',
            'consultoria' => 'Consultoria',
            'manutencao'  => 'Manutenção',
            'treinamento' => 'Treinamento',
            'geral'       => 'Geral',
        ];
    }

    /**
     * Obtém templates padrão do sistema.
     */
    public static function getDefaultTemplates(): array
    {
        return [
            [
                'name'            => 'Template Básico de Produto',
                'slug'            => 'template-basico-produto',
                'description'     => 'Template básico para venda de produtos',
                'category'        => 'produto',
                'template_data'   => [
                    'payment_terms' => 'À vista ou em até 3x sem juros',
                    'validity_days' => 15,
                ],
                'default_items'   => [
                    [
                        'title'               => 'Produto Principal',
                        'description'         => 'Produto ou serviço principal',
                        'quantity'            => 1,
                        'unit'                => 'un',
                        'unit_price'          => 0,
                        'discount_percentage' => 0,
                        'tax_percentage'      => 0,
                        'order_index'         => 1,
                    ],
                ],
                'variables'       => [
                    'produto_nome'      => 'Nome do Produto',
                    'produto_descricao' => 'Descrição do Produto',
                    'produto_preco'     => 'Preço do Produto',
                ],
                'estimated_hours' => null,
                'is_public'       => true,
            ],
            [
                'name'            => 'Template Básico de Serviço',
                'slug'            => 'template-basico-servico',
                'description'     => 'Template básico para prestação de serviços',
                'category'        => 'servico',
                'template_data'   => [
                    'payment_terms' => '50% de entrada + saldo em 30 dias',
                    'validity_days' => 30,
                ],
                'default_items'   => [
                    [
                        'title'               => 'Serviço Profissional',
                        'description'         => 'Execução do serviço conforme escopo',
                        'quantity'            => 1,
                        'unit'                => 'serviço',
                        'unit_price'          => 0,
                        'discount_percentage' => 0,
                        'tax_percentage'      => 0,
                        'order_index'         => 1,
                    ],
                ],
                'variables'       => [
                    'servico_nome'      => 'Nome do Serviço',
                    'servico_descricao' => 'Descrição do Serviço',
                    'servico_valor'     => 'Valor do Serviço',
                ],
                'estimated_hours' => 40,
                'is_public'       => true,
            ],
        ];
    }

    /**
     * Cria templates padrão para um tenant.
     */
    public static function createDefaultTemplates( int $tenantId, int $userId ): void
    {
        foreach ( self::getDefaultTemplates() as $template ) {
            self::create( array_merge( $template, [
                'tenant_id' => $tenantId,
                'user_id'   => $userId,
                'is_active' => true,
            ] ) );
        }
    }

    /**
     * Obtém preview do template com valores aplicados.
     */
    public function getPreview( array $variables = [] ): array
    {
        $preview = [
            'template' => [
                'name'            => $this->name,
                'description'     => $this->description,
                'category'        => $this->category,
                'estimated_hours' => $this->estimated_hours,
            ],
            'items'    => [],
            'totals'   => [
                'subtotal'       => 0,
                'discount_total' => 0,
                'taxes_total'    => 0,
                'grand_total'    => 0,
            ],
        ];

        foreach ( $this->default_items as $item ) {
            $processedItem = $this->processItemVariables( $item, $variables );

            $quantity  = floatval( $processedItem[ 'quantity' ] ?? 1 );
            $unitPrice = floatval( $processedItem[ 'unit_price' ] ?? 0 );
            $discount  = floatval( $processedItem[ 'discount_percentage' ] ?? 0 );
            $tax       = floatval( $processedItem[ 'tax_percentage' ] ?? 0 );

            $total          = $quantity * $unitPrice;
            $discountAmount = $total * ( $discount / 100 );
            $subtotal       = $total - $discountAmount;
            $taxAmount      = $subtotal * ( $tax / 100 );
            $netTotal       = $subtotal + $taxAmount;

            $preview[ 'items' ][] = [
                'title'       => $processedItem[ 'title' ] ?? 'Item',
                'description' => $processedItem[ 'description' ] ?? '',
                'quantity'    => $quantity,
                'unit'        => $processedItem[ 'unit' ] ?? 'un',
                'unit_price'  => $unitPrice,
                'total'       => $total,
                'net_total'   => $netTotal,
            ];

            $preview[ 'totals' ][ 'subtotal' ] += $total;
            $preview[ 'totals' ][ 'discount_total' ] += $discountAmount;
            $preview[ 'totals' ][ 'taxes_total' ] += $taxAmount;
            $preview[ 'totals' ][ 'grand_total' ] += $netTotal;
        }

        return $preview;
    }

    /**
     * Verifica se o template pode ser editado pelo usuário.
     */
    public function canBeEditedBy( int $userId ): bool
    {
        return $this->user_id === $userId || $this->is_public;
    }

    /**
     * Verifica se o template pode ser excluído.
     */
    public function canBeDeleted(): bool
    {
        return $this->usage_count === 0 && $this->childTemplates()->count() === 0;
    }

    /**
     * Duplica o template.
     */
    public function duplicate( int $userId ): BudgetTemplate
    {
        $newTemplate                     = $this->replicate();
        $newTemplate->name               = 'Cópia de ' . $this->name;
        $newTemplate->slug               = $this->slug . '-copia-' . time();
        $newTemplate->user_id            = $userId;
        $newTemplate->parent_template_id = $this->id;
        $newTemplate->usage_count        = 0;
        $newTemplate->last_used_at       = null;
        $newTemplate->save();

        return $newTemplate;
    }

}
