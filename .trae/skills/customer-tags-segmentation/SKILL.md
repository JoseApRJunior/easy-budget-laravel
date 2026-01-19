# 🏷️ Skill: Customer Tags and Segmentation (Tags e Segmentação de Clientes)

**Descrição:** Sistema de classificação e segmentação de clientes por tags, prioridades e categorias para melhor gestão de relacionamento.

**Categoria:** CRM e Segmentação
**Complexidade:** Média
**Status:** ✅ Implementado e Documentado

## 🎯 Objetivo

Padronizar a classificação e segmentação de clientes no Easy Budget através de um sistema de tags, permitindo melhor organização, filtragem avançada e estratégias de relacionamento personalizadas.

## 📋 Requisitos Técnicos

### **✅ Sistema de Tags**

```php
class CustomerTag extends Model
{
    use HasFactory, TenantScoped;

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'color',
        'icon',
        'is_active',
        'category',
        'priority',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'priority' => 'integer',
    ];

    public function customers(): BelongsToMany
    {
        return $this->belongsToMany(Customer::class, 'customer_tag_assignments')
            ->withTimestamps();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function getPriorityLabelAttribute(): string
    {
        return match ($this->priority) {
            1 => 'Alta',
            2 => 'Média',
            3 => 'Baixa',
            default => 'Normal',
        };
    }
}
```

### **✅ Categorias de Tags**

```php
enum TagCategory: string
{
    case PRIORITY = 'priority';        // Prioridade (VIP, Premium, Normal)
    case SEGMENT = 'segment';          // Segmento (B2B, B2C, Governo)
    case INTEREST = 'interest';        // Interesse (Tecnologia, Saúde, Educação)
    case STATUS = 'status';            // Status (Potencial, Ativo, Inativo)
    case SOURCE = 'source';            // Origem (Indicação, Site, Evento)
    case BEHAVIOR = 'behavior';        // Comportamento (Frequente, Ocasional, Novo)
    case PAIN_POINT = 'pain_point';    // Dor (Preço, Qualidade, Prazo)
    case SOLUTION = 'solution';        // Solução (Básico, Premium, Enterprise)

    public function getDisplayName(): string
    {
        return match ($this) {
            self::PRIORITY => 'Prioridade',
            self::SEGMENT => 'Segmento',
            self::INTEREST => 'Interesse',
            self::STATUS => 'Status',
            self::SOURCE => 'Origem',
            self::BEHAVIOR => 'Comportamento',
            self::PAIN_POINT => 'Dor',
            self::SOLUTION => 'Solução',
        };
    }
}
```

### **✅ Prioridades de Clientes**

```php
enum CustomerPriority: string
{
    case VIP = 'vip';              // Cliente VIP
    case PREMIUM = 'premium';      // Cliente Premium
    case NORMAL = 'normal';        // Cliente Normal
    case LOW = 'low';              // Cliente de baixa prioridade

    public function getLabel(): string
    {
        return match ($this) {
            self::VIP => 'VIP',
            self::PREMIUM => 'Premium',
            self::NORMAL => 'Normal',
            self::LOW => 'Baixa',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::VIP => '#FFD700',      // Dourado
            self::PREMIUM => '#4169E1',  // Azul Royal
            self::NORMAL => '#32CD32',   // Verde Limão
            self::LOW => '#A9A9A9',      // Cinza Escuro
        };
    }

    public function getWeight(): int
    {
        return match ($this) {
            self::VIP => 4,
            self::PREMIUM => 3,
            self::NORMAL => 2,
            self::LOW => 1,
        };
    }
}
```

## 🏗️ Estrutura de Segmentação

### **📊 Modelo de Segmentação**

```php
class CustomerSegmentation extends Model
{
    protected $fillable = [
        'customer_id',
        'tenant_id',
        'segment_name',
        'segment_type',
        'segment_value',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function getSegmentTypeLabelAttribute(): string
    {
        return match ($this->segment_type) {
            'demographic' => 'Demográfico',
            'behavioral' => 'Comportamental',
            'geographic' => 'Geográfico',
            'psychographic' => 'Psicográfico',
            'firmographic' => 'Firmográfico',
            default => 'Personalizado',
        };
    }
}
```

### **📝 DTO de Tag**

```php
readonly class CustomerTagDTO extends AbstractDTO
{
    public function __construct(
        public string $name,
        public ?string $description = null,
        public ?string $color = '#007bff',
        public ?string $icon = 'tag',
        public bool $is_active = true,
        public string $category,
        public int $priority = 2,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            name: $data['name'],
            description: $data['description'] ?? null,
            color: $data['color'] ?? '#007bff',
            icon: $data['icon'] ?? 'tag',
            is_active: $data['is_active'] ?? true,
            category: $data['category'],
            priority: $data['priority'] ?? 2,
        );
    }
}
```

## 📋 Gestão de Tags

### **✅ Criação e Gerenciamento de Tags**

```php
class CustomerTagService extends AbstractBaseService
{
    public function createTag(CustomerTagDTO $dto): ServiceResult
    {
        return $this->safeExecute(function() use ($dto) {
            // 1. Validar tag
            $validation = $this->validateTag($dto);
            if (!$validation->isSuccess()) {
                return $validation;
            }

            // 2. Verificar duplicação
            if ($this->checkDuplicateTag($dto->name, $dto->category)) {
                return $this->error('Já existe tag com este nome na categoria', OperationStatus::DUPLICATE_DATA);
            }

            // 3. Criar tag
            $tagData = [
                'tenant_id' => $this->getTenantId(),
                'name' => $dto->name,
                'description' => $dto->description,
                'color' => $dto->color,
                'icon' => $dto->icon,
                'is_active' => $dto->is_active,
                'category' => $dto->category,
                'priority' => $dto->priority,
            ];

            return $this->repository->create($tagData);
        });
    }

    public function assignTags(Customer $customer, array $tagIds): ServiceResult
    {
        return $this->safeExecute(function() use ($customer, $tagIds) {
            // 1. Validar tags
            $validation = $this->validateTags($tagIds);
            if (!$validation->isSuccess()) {
                return $validation;
            }

            // 2. Atribuir tags
            $customer->tags()->sync($tagIds);

            // 3. Disparar eventos
            event(new TagsAssigned($customer, $tagIds));

            return $this->success(null, 'Tags atribuídas com sucesso');
        });
    }

    public function removeTag(Customer $customer, CustomerTag $tag): ServiceResult
    {
        return $this->safeExecute(function() use ($customer, $tag) {
            $customer->tags()->detach($tag->id);

            // Disparar eventos
            event(new TagRemoved($customer, $tag));

            return $this->success(null, 'Tag removida com sucesso');
        });
    }

    public function getCustomerTags(Customer $customer): ServiceResult
    {
        $tags = $customer->tags()->orderBy('priority', 'desc')->get();

        return $this->success($tags, 'Tags do cliente');
    }

    private function validateTag(CustomerTagDTO $dto): ServiceResult
    {
        if (strlen($dto->name) < 2) {
            return $this->error('Nome da tag deve ter pelo menos 2 caracteres', OperationStatus::INVALID_DATA);
        }

        if (strlen($dto->name) > 50) {
            return $this->error('Nome da tag deve ter no máximo 50 caracteres', OperationStatus::INVALID_DATA);
        }

        if (! TagCategory::tryFrom($dto->category)) {
            return $this->error('Categoria de tag inválida', OperationStatus::INVALID_DATA);
        }

        return $this->success(null, 'Tag válida');
    }

    private function checkDuplicateTag(string $name, string $category): bool
    {
        return $this->repository->where('name', $name)
            ->where('category', $category)
            ->where('tenant_id', $this->getTenantId())
            ->exists();
    }

    private function validateTags(array $tagIds): ServiceResult
    {
        $existingTags = $this->repository->whereIn('id', $tagIds)
            ->where('tenant_id', $this->getTenantId())
            ->pluck('id')
            ->toArray();

        $missingTags = array_diff($tagIds, $existingTags);

        if (! empty($missingTags)) {
            return $this->error('Algumas tags não existem', OperationStatus::INVALID_DATA);
        }

        return $this->success(null, 'Tags válidas');
    }
}
```

### **✅ Segmentação Automática**

```php
class CustomerSegmentationService extends AbstractBaseService
{
    public function autoSegmentCustomer(Customer $customer): ServiceResult
    {
        return $this->safeExecute(function() use ($customer) {
            $segments = [];

            // 1. Segmentação por valor de negócios
            $segments = array_merge($segments, $this->segmentByValue($customer));

            // 2. Segmentação por comportamento
            $segments = array_merge($segments, $this->segmentByBehavior($customer));

            // 3. Segmentação por demografia
            $segments = array_merge($segments, $this->segmentByDemographics($customer));

            // 4. Segmentação por interações
            $segments = array_merge($segments, $this->segmentByInteractions($customer));

            // 5. Salvar segmentações
            foreach ($segments as $segment) {
                $this->createSegmentation($customer, $segment);
            }

            return $this->success($segments, 'Segmentação automática concluída');
        });
    }

    private function segmentByValue(Customer $customer): array
    {
        $totalValue = $customer->budgets()->sum('total_value');
        $totalInvoices = $customer->invoices()->sum('total');

        if ($totalValue > 50000) {
            return [
                [
                    'segment_name' => 'Alto Valor',
                    'segment_type' => 'value',
                    'segment_value' => $totalValue,
                    'metadata' => ['total_budgets' => $totalValue, 'total_invoices' => $totalInvoices],
                ],
                [
                    'segment_name' => 'Premium',
                    'segment_type' => 'tier',
                    'segment_value' => 'high',
                    'metadata' => ['priority' => 'high'],
                ],
            ];
        } elseif ($totalValue > 10000) {
            return [
                [
                    'segment_name' => 'Médio Valor',
                    'segment_type' => 'value',
                    'segment_value' => $totalValue,
                    'metadata' => ['total_budgets' => $totalValue, 'total_invoices' => $totalInvoices],
                ],
            ];
        }

        return [
            [
                'segment_name' => 'Baixo Valor',
                'segment_type' => 'value',
                'segment_value' => $totalValue,
                'metadata' => ['total_budgets' => $totalValue, 'total_invoices' => $totalInvoices],
            ],
        ];
    }

    private function segmentByBehavior(Customer $customer): array
    {
        $interactionCount = $customer->interactions()->count();
        $lastInteraction = $customer->interactions()->latest('interaction_date')->first();

        if ($interactionCount > 10 && $lastInteraction && $lastInteraction->interaction_date > now()->subMonths(1)) {
            return [
                [
                    'segment_name' => 'Muito Ativo',
                    'segment_type' => 'behavior',
                    'segment_value' => 'high',
                    'metadata' => ['interaction_count' => $interactionCount],
                ],
            ];
        } elseif ($interactionCount > 5) {
            return [
                [
                    'segment_name' => 'Ativo',
                    'segment_type' => 'behavior',
                    'segment_value' => 'medium',
                    'metadata' => ['interaction_count' => $interactionCount],
                ],
            ];
        }

        return [
            [
                'segment_name' => 'Pouco Ativo',
                'segment_type' => 'behavior',
                'segment_value' => 'low',
                'metadata' => ['interaction_count' => $interactionCount],
            ],
        ];
    }

    private function segmentByDemographics(Customer $customer): array
    {
        $commonData = $customer->commonData;

        if ($commonData) {
            if ($commonData->type === 'company') {
                return [
                    [
                        'segment_name' => 'Pessoa Jurídica',
                        'segment_type' => 'demographic',
                        'segment_value' => 'company',
                        'metadata' => ['company_type' => $commonData->company_name],
                    ],
                ];
            } else {
                return [
                    [
                        'segment_name' => 'Pessoa Física',
                        'segment_type' => 'demographic',
                        'segment_value' => 'individual',
                        'metadata' => ['age' => $commonData->birth_date?->age],
                    ],
                ];
            }
        }

        return [];
    }

    private function segmentByInteractions(Customer $customer): array
    {
        $positiveInteractions = $customer->interactions()
            ->whereIn('outcome', ['success', 'partial'])
            ->count();
        $totalInteractions = $customer->interactions()->count();

        if ($totalInteractions > 0) {
            $successRate = ($positiveInteractions / $totalInteractions) * 100;

            if ($successRate > 80) {
                return [
                    [
                        'segment_name' => 'Cliente Satisfeito',
                        'segment_type' => 'interaction',
                        'segment_value' => 'satisfied',
                        'metadata' => ['success_rate' => $successRate],
                    ],
                ];
            } elseif ($successRate > 50) {
                return [
                    [
                        'segment_name' => 'Cliente Regular',
                        'segment_type' => 'interaction',
                        'segment_value' => 'regular',
                        'metadata' => ['success_rate' => $successRate],
                    ],
                ];
            }
        }

        return [
            [
                'segment_name' => 'Cliente Insatisfeito',
                'segment_type' => 'interaction',
                'segment_value' => 'unsatisfied',
                'metadata' => ['success_rate' => $successRate ?? 0],
            ],
        ];
    }

    private function createSegmentation(Customer $customer, array $segment): void
    {
        CustomerSegmentation::updateOrCreate(
            [
                'customer_id' => $customer->id,
                'segment_name' => $segment['segment_name'],
                'segment_type' => $segment['segment_type'],
            ],
            [
                'tenant_id' => $customer->tenant_id,
                'segment_value' => $segment['segment_value'],
                'is_active' => true,
                'metadata' => $segment['metadata'],
            ]
        );
    }
}
```

### **✅ Filtros Avançados por Tags**

```php
class CustomerTagFilterService extends AbstractBaseService
{
    public function filterByTags(array $tagIds, array $filters = []): ServiceResult
    {
        $query = Customer::query();

        // 1. Filtrar por tags específicas
        if (! empty($tagIds)) {
            $query->whereHas('tags', function($q) use ($tagIds) {
                $q->whereIn('customer_tags.id', $tagIds);
            });
        }

        // 2. Filtrar por categorias de tags
        if (isset($filters['tag_categories'])) {
            $query->whereHas('tags', function($q) use ($filters) {
                $q->whereIn('category', $filters['tag_categories']);
            });
        }

        // 3. Filtrar por combinação de tags (AND)
        if (isset($filters['tag_combination']) && $filters['tag_combination'] === 'and') {
            foreach ($tagIds as $tagId) {
                $query->whereHas('tags', function($q) use ($tagId) {
                    $q->where('customer_tags.id', $tagId);
                });
            }
        }

        // 4. Filtrar por combinação de tags (OR)
        if (isset($filters['tag_combination']) && $filters['tag_combination'] === 'or') {
            $query->whereHas('tags', function($q) use ($tagIds) {
                $q->whereIn('customer_tags.id', $tagIds);
            });
        }

        // 5. Filtrar por prioridade
        if (isset($filters['priority'])) {
            $query->whereHas('tags', function($q) use ($filters) {
                $q->where('priority', '<=', $filters['priority']);
            });
        }

        // 6. Aplicar filtros adicionais
        $query = $this->applyAdditionalFilters($query, $filters);

        $customers = $query->with('tags')->get();

        return $this->success($customers, 'Clientes filtrados por tags');
    }

    public function getTagAnalytics(array $filters = []): array
    {
        $query = CustomerTag::query();

        // Aplicar filtros
        if (isset($filters['tenant_id'])) {
            $query->where('tenant_id', $filters['tenant_id']);
        }

        if (isset($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        $tags = $query->withCount('customers')->get();

        return [
            'total_tags' => $tags->count(),
            'tags_by_category' => $this->getTagsByCategory($tags),
            'most_used_tags' => $this->getMostUsedTags($tags),
            'tag_usage_trend' => $this->getTagUsageTrend($tags),
            'customer_distribution' => $this->getCustomerDistribution($tags),
        ];
    }

    private function getTagsByCategory(Collection $tags): array
    {
        return $tags->groupBy('category')
            ->map(fn($group) => $group->count())
            ->toArray();
    }

    private function getMostUsedTags(Collection $tags): array
    {
        return $tags->sortByDesc('customers_count')
            ->take(10)
            ->map(fn($tag) => [
                'name' => $tag->name,
                'usage_count' => $tag->customers_count,
                'category' => $tag->category,
            ])
            ->toArray();
    }

    private function getTagUsageTrend(Collection $tags): array
    {
        return CustomerTagAssignment::whereHas('tag', function($q) use ($tags) {
            $q->whereIn('id', $tags->pluck('id'));
        })
        ->selectRaw('DATE(created_at) as date, count(*) as count')
        ->groupBy('date')
        ->orderBy('date')
        ->pluck('count', 'date')
        ->toArray();
    }

    private function getCustomerDistribution(Collection $tags): array
    {
        $distribution = [];

        foreach ($tags as $tag) {
            $customerCount = $tag->customers_count;

            if ($customerCount >= 100) {
                $distribution['high_usage'][] = $tag->name;
            } elseif ($customerCount >= 20) {
                $distribution['medium_usage'][] = $tag->name;
            } else {
                $distribution['low_usage'][] = $tag->name;
            }
        }

        return $distribution;
    }
}
```

## 📊 Métricas e Análisis

### **✅ Métricas de Segmentação**

```php
class CustomerSegmentationMetricsService extends AbstractBaseService
{
    public function getSegmentationMetrics(array $filters = []): array
    {
        $query = Customer::query();

        // Aplicar filtros
        if (isset($filters['tenant_id'])) {
            $query->where('tenant_id', $filters['tenant_id']);
        }

        $customers = $query->with(['tags', 'segmentations'])->get();

        return [
            'total_customers' => $customers->count(),
            'by_priority' => $this->getCustomersByPriority($customers),
            'by_segment' => $this->getCustomersBySegment($customers),
            'by_tag_category' => $this->getCustomersByTagCategory($customers),
            'segmentation_effectiveness' => $this->calculateSegmentationEffectiveness($customers),
            'tag_distribution' => $this->getTagDistribution($customers),
        ];
    }

    private function getCustomersByPriority(Collection $customers): array
    {
        return $customers->groupBy(function($customer) {
            $priorityTag = $customer->tags->where('category', 'priority')->first();
            return $priorityTag ? $priorityTag->name : 'normal';
        })->map(fn($group) => $group->count())->toArray();
    }

    private function getCustomersBySegment(Collection $customers): array
    {
        return $customers->flatMap(function($customer) {
            return $customer->segmentations->pluck('segment_name');
        })->groupBy(function($segment) {
            return $segment;
        })->map(fn($group) => $group->count())->toArray();
    }

    private function getCustomersByTagCategory(Collection $customers): array
    {
        return $customers->flatMap(function($customer) {
            return $customer->tags->pluck('category');
        })->groupBy(function($category) {
            return $category;
        })->map(fn($group) => $group->count())->toArray();
    }

    private function calculateSegmentationEffectiveness(Collection $customers): float
    {
        $totalCustomers = $customers->count();
        $segmentedCustomers = $customers->where('segmentations', '!=', null)->count();

        return $totalCustomers > 0 ? ($segmentedCustomers / $totalCustomers) * 100 : 0.0;
    }

    private function getTagDistribution(Collection $customers): array
    {
        $tagUsage = [];

        foreach ($customers as $customer) {
            foreach ($customer->tags as $tag) {
                $tagUsage[$tag->name] = ($tagUsage[$tag->name] ?? 0) + 1;
            }
        }

        return $tagUsage;
    }
}
```

### **✅ Dashboard de Segmentação**

```php
class CustomerSegmentationDashboardService extends AbstractBaseService
{
    public function getSegmentationDashboard(int $tenantId): array
    {
        return [
            'customer_distribution' => $this->getCustomerDistribution($tenantId),
            'top_segments' => $this->getTopSegments($tenantId),
            'segment_performance' => $this->getSegmentPerformance($tenantId),
            'tag_effectiveness' => $this->getTagEffectiveness($tenantId),
            'segmentation_trends' => $this->getSegmentationTrends($tenantId),
        ];
    }

    private function getCustomerDistribution(int $tenantId): array
    {
        $totalCustomers = Customer::where('tenant_id', $tenantId)->count();

        return [
            'by_priority' => $this->getCustomersByPriorityCount($tenantId),
            'by_segment' => $this->getCustomersBySegmentCount($tenantId),
            'by_tags' => $this->getCustomersByTagsCount($tenantId),
            'growth_rate' => $this->calculateGrowthRate($tenantId),
        ];
    }

    private function getTopSegments(int $tenantId): array
    {
        return CustomerSegmentation::where('tenant_id', $tenantId)
            ->groupBy('segment_name')
            ->selectRaw('segment_name, count(*) as count')
            ->orderByDesc('count')
            ->limit(10)
            ->pluck('count', 'segment_name')
            ->toArray();
    }

    private function getSegmentPerformance(int $tenantId): array
    {
        return CustomerSegmentation::where('tenant_id', $tenantId)
            ->with(['customer.budgets', 'customer.invoices'])
            ->get()
            ->groupBy('segment_name')
            ->map(function($segmentations) {
                $customers = $segmentations->pluck('customer');

                return [
                    'customer_count' => $customers->count(),
                    'avg_budget_value' => $customers->avg(function($customer) {
                        return $customer->budgets->avg('total_value') ?? 0;
                    }),
                    'avg_invoice_value' => $customers->avg(function($customer) {
                        return $customer->invoices->avg('total') ?? 0;
                    }),
                    'conversion_rate' => $this->calculateConversionRate($customers),
                ];
            })
            ->toArray();
    }

    private function getTagEffectiveness(int $tenantId): array
    {
        return CustomerTag::where('tenant_id', $tenantId)
            ->withCount('customers')
            ->orderByDesc('customers_count')
            ->take(20)
            ->map(function($tag) {
                return [
                    'name' => $tag->name,
                    'usage_count' => $tag->customers_count,
                    'category' => $tag->category,
                    'effectiveness_score' => $this->calculateTagEffectiveness($tag),
                ];
            })
            ->toArray();
    }

    private function calculateTagEffectiveness(CustomerTag $tag): float
    {
        $customers = $tag->customers;
        $totalValue = $customers->sum(function($customer) {
            return $customer->invoices->sum('total');
        });

        return $customers->count() > 0 ? $totalValue / $customers->count() : 0.0;
    }
}
```

## 🧪 Testes e Validação

### **✅ Testes de Tags**

```php
public function testCreateTag()
{
    $dto = new CustomerTagDTO([
        'name' => 'VIP',
        'description' => 'Cliente VIP',
        'color' => '#FFD700',
        'category' => 'priority',
        'priority' => 1,
    ]);

    $result = $this->tagService->createTag($dto);
    $this->assertTrue($result->isSuccess());

    $tag = $result->getData();
    $this->assertEquals('VIP', $tag->name);
    $this->assertEquals('priority', $tag->category);
}

public function testAssignTags()
{
    $customer = Customer::factory()->create();
    $tag = CustomerTag::factory()->create();

    $result = $this->tagService->assignTags($customer, [$tag->id]);
    $this->assertTrue($result->isSuccess());

    $this->assertTrue($customer->tags->contains($tag));
}

public function testFilterByTags()
{
    $customer1 = Customer::factory()->create();
    $customer2 = Customer::factory()->create();

    $tag1 = CustomerTag::factory()->create(['name' => 'VIP']);
    $tag2 = CustomerTag::factory()->create(['name' => 'Premium']);

    $customer1->tags()->attach([$tag1->id, $tag2->id]);
    $customer2->tags()->attach([$tag2->id]);

    $result = $this->filterService->filterByTags([$tag1->id]);
    $this->assertTrue($result->isSuccess());

    $customers = $result->getData();
    $this->assertCount(1, $customers);
    $this->assertEquals($customer1->id, $customers[0]->id);
}
```

### **✅ Testes de Segmentação**

```php
public function testAutoSegmentation()
{
    $customer = Customer::factory()->create();

    // Criar orçamentos para simular valor
    Budget::factory()->count(5)->create([
        'customer_id' => $customer->id,
        'total_value' => 10000,
    ]);

    // Criar interações
    CustomerInteraction::factory()->count(15)->create([
        'customer_id' => $customer->id,
        'outcome' => 'success',
    ]);

    $result = $this->segmentationService->autoSegmentCustomer($customer);
    $this->assertTrue($result->isSuccess());

    $segments = $result->getData();
    $this->assertNotEmpty($segments);
}

public function testSegmentationMetrics()
{
    $tenant = Tenant::factory()->create();

    // Criar clientes com diferentes segmentações
    $customer1 = Customer::factory()->create(['tenant_id' => $tenant->id]);
    $customer2 = Customer::factory()->create(['tenant_id' => $tenant->id]);

    CustomerSegmentation::create([
        'customer_id' => $customer1->id,
        'tenant_id' => $tenant->id,
        'segment_name' => 'Alto Valor',
        'segment_type' => 'value',
        'segment_value' => 'high',
    ]);

    $metrics = $this->metricsService->getSegmentationMetrics([
        'tenant_id' => $tenant->id,
    ]);

    $this->assertArrayHasKey('total_customers', $metrics);
    $this->assertArrayHasKey('by_segment', $metrics);
}
```

## 🚀 Implementação Gradual

### **Fase 1: Foundation**
- [ ] Implementar CustomerTag model
- [ ] Criar CustomerTagDTO
- [ ] Implementar CustomerTagService básico
- [ ] Definir enums de categorias e prioridades

### **Fase 2: Core Features**
- [ ] Implementar CustomerSegmentation model
- [ ] Criar CustomerSegmentationService
- [ ] Implementar CustomerTagFilterService
- [ ] Sistema de atribuição de tags

### **Fase 3: Advanced Features**
- [ ] Segmentação automática inteligente
- [ ] Métricas de segmentação
- [ ] Dashboard de segmentação
- [ ] Integração com campanhas de marketing

### **Fase 4: Integration**
- [ ] Integração com CRM externos
- [ ] Sistema de recomendação de tags
- [ ] Machine learning para segmentação
- [ ] Relatórios avançados

## 📚 Documentação Relacionada

- [CustomerTag Model](../../app/Models/CustomerTag.php)
- [CustomerTagDTO](../../app/DTOs/Customer/CustomerTagDTO.php)
- [CustomerTagService](../../app/Services/Domain/CustomerTagService.php)
- [CustomerSegmentationService](../../app/Services/Domain/CustomerSegmentationService.php)
- [CustomerTagFilterService](../../app/Services/Domain/CustomerTagFilterService.php)

## 🎯 Benefícios

### **✅ Organização de Clientes**
- Classificação por prioridades e categorias
- Sistema de tags flexível e personalizável
- Segmentação automática inteligente
- Filtros avançados por múltiplos critérios

### **✅ Estratégias de Marketing**
- Segmentação para campanhas direcionadas
- Identificação de clientes VIP
- Estratégias personalizadas por segmento
- Métricas de efetividade de segmentação

### **✅ Gestão de Relacionamento**
- Priorização de atendimento
- Estratégias de retenção por segmento
- Identificação de oportunidades
- Histórico de segmentação

### **✅ Análise de Negócio**
- Métricas de segmentação
- Dashboard executivo
- Tendências de segmentação
- ROI por segmento

---

**Última atualização:** 10/01/2026
**Versão:** 1.0.0
**Status:** ✅ Implementado e em uso
