# PROMPTS Detalhados - Migração do Módulo Report

## 📋 Contexto do Módulo

-  **Base:** Análise completa em `RELATORIO_ANALISE_REPORT_CONTROLLER.md`
-  **Status:** 0% implementado
-  **Objetivo:** Implementar o módulo de report completo seguindo a arquitetura moderna, baseado na análise do `ReportController` legado.
-  **Ordem:** Sequência lógica por dependências (Database → Repository → Form Requests → Service → Controller).

-  **Tokens Globais:**
   -  `report` (singular), `reports` (plural)
   -  `Report` (modelo), `ReportController` (controller)
   -  `ReportRepository` (repositório), `ReportService` (serviço)
   -  `reports` (tabela), `id` (PK), `hash` (campo único)
   -  `tenant_id, user_id` (FKs), `tenant, user` (relações)
   -  `TenantScoped` (trait)

---

# 🎯 Grupo 1: Database & Repository (Base de Dados) — Primeiro

## 🎯 Prompt 1.1: Atualizar Migration, Model e Factory

Implemente apenas a atualização da Migration, Model e Factory para reports:

-  **Migration:** Atualizar schema inicial para adicionar campos em `reports`:

   -  FKs: `tenant_id`, `user_id`
   -  Campo único: `hash`
   -  Campos: `type`, `format`, `status`, `file_path`, etc.
   -  `softDeletes`

-  **Model:** Atualizar `Report.php` com fillable, casts e relacionamentos.

-  **Factory:** Atualizar `ReportFactory.php` para novos campos.

```php
// Migration
Schema::create('reports', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
    $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
    $table->string('hash', 64)->unique();
    $table->string('type', 50);
    $table->text('description')->nullable();
    $table->string('file_name');
    $table->string('file_path')->nullable();
    $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
    $table->enum('format', ['pdf', 'excel', 'csv'])->default('pdf');
    $table->float('size');
    $table->json('filters')->nullable();
    $table->text('error_message')->nullable();
    $table->timestamp('generated_at')->nullable();
    $table->timestamps();
    $table->softDeletes();
});

// Model
class Report extends Model
{
    use HasFactory, SoftDeletes, TenantScoped;

    protected $fillable = [
        'tenant_id', 'user_id', 'hash', 'type', 'description',
        'file_name', 'file_path', 'status', 'format', 'size',
        'filters', 'error_message', 'generated_at'
    ];

    protected $casts = [
        'size' => 'float',
        'filters' => 'array',
        'generated_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

// Factory
public function definition(): array
{
    return [
        'tenant_id' => Tenant::factory(),
        'user_id' => User::factory(),
        'hash' => $this->faker->unique()->md5(),
        'type' => $this->faker->randomElement(['budget', 'customer', 'product', 'service']),
        'description' => $this->faker->sentence(),
        'file_name' => $this->faker->word() . '.pdf',
        'file_path' => null,
        'status' => 'completed',
        'format' => 'pdf',
        'size' => $this->faker->randomFloat(2, 1, 100),
        'filters' => ['start_date' => now()->subMonth()->format('Y-m-d')],
        'error_message' => null,
        'generated_at' => now(),
    ];
}
```

-  **Arquivos:** `database/migrations/..._create_initial_schema.php`, `app/Models/Report.php`, `database/factories/ReportFactory.php`
-  **Critério:** Estrutura de banco e Eloquent funcionais.

---

## 🎯 Prompt 1.2: Implementar ReportRepository — getPaginated()

Abstrair queries com filtros avançados, tenant scoping e eager loading.

```php
class ReportRepository extends AbstractTenantRepository
{
    public function __construct(Report $model)
    {
        parent::__construct($model);
    }

    public function getPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->newQuery()->with(['tenant', 'user']);

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('file_name', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('description', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('hash', 'like', '%' . $filters['search'] . '%');
            });
        }

        if (!empty($filters['type'])) $query->where('type', $filters['type']);
        if (!empty($filters['status'])) $query->where('status', $filters['status']);
        if (!empty($filters['format'])) $query->where('format', $filters['format']);
        if (!empty($filters['start_date'])) $query->whereDate('created_at', '>=', $filters['start_date']);
        if (!empty($filters['end_date'])) $query->whereDate('created_at', '<=', $filters['end_date']);
        if (!empty($filters['user_id'])) $query->where('user_id', $filters['user_id']);

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }
}
```

-  **Arquivo:** `app/Repositories/ReportRepository.php`
-  **Critério:** Paginação com filtros funcionais.

---

## 🎯 Prompt 1.3: Implementar ReportRepository — findByHash()

Buscar por hash com eager loading opcional.

```php
public function findByHash(string $hash, array $with = []): ?Model
{
    $query = $this->model->where('hash', $hash);
    if (!empty($with)) $query->with($with);
    return $query->first();
}
```

-  **Arquivo:** `app/Repositories/ReportRepository.php`
-  **Critério:** Busca por hash único.

---

## 🎯 Prompt 1.4: Implementar ReportRepository — countByType()

Contar relatórios por tipo.

```php
public function countByType(string $type): int
{
    return $this->model->where('type', $type)->count();
}
```

-  **Arquivo:** `app/Repositories/ReportRepository.php`
-  **Critério:** Métrica por tipo.

---

## 🎯 Prompt 1.5: Implementar ReportRepository — getRecentReports()

Listar relatórios recentes completados.

```php
public function getRecentReports(int $limit = 10): Collection
{
    return $this->model->with(['user'])
        ->where('status', 'completed')
        ->orderBy('generated_at', 'desc')
        ->limit($limit)
        ->get();
}
```

-  **Arquivo:** `app/Repositories/ReportRepository.php`
-  **Critério:** Lista de recentes.

---

# 🎯 Grupo 2: Form Requests (Validação) — Segundo

## 🎯 Prompt 2.1: Criar ReportGenerateRequest

Validação para geração de relatórios.

```php
class ReportGenerateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'type' => 'required|in:budget,customer,product,service',
            'format' => 'required|in:pdf,excel,csv',
            'filters' => 'required|array',
            'filters.start_date' => 'nullable|date',
            'filters.end_date' => 'nullable|date|after_or_equal:filters.start_date',
            'filters.customer_name' => 'nullable|string|max:255',
            'filters.status' => 'nullable|string',
            'filters.min_total' => 'nullable|numeric|min:0',
            'filters.max_total' => 'nullable|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'type.required' => 'O tipo de relatório é obrigatório.',
            'type.in' => 'Tipo de relatório inválido.',
            'format.required' => 'O formato é obrigatório.',
            'format.in' => 'Formato inválido.',
            'filters.required' => 'Os filtros são obrigatórios.',
            'filters.end_date.after_or_equal' => 'A data final deve ser posterior ou igual à inicial.',
        ];
    }
}
```

-  **Arquivo:** `app/Http/Requests/ReportGenerateRequest.php`
-  **Critério:** Validação robusta.

---

# 🎯 Grupo 3: Services (Lógica de Negócio) — Terceiro

## 🎯 Prompt 3.1: Implementar ReportService — generateReport()

Gerar relatório via job assíncrono.

```php
public function generateReport(array $data): ServiceResult
{
    try {
        return DB::transaction(function () use ($data) {
            $report = $this->repository->create([
                'type' => $data['type'],
                'format' => $data['format'],
                'status' => 'processing',
                'filters' => $data['filters'],
                'description' => $this->generateDescription($data['filters']),
                'file_name' => $this->generateFileName($data['type'], $data['format']),
            ]);

            GenerateReportJob::dispatch($report);

            return $this->success($report, 'Relatório solicitado. Você será notificado quando pronto.');
        });
    } catch (Exception $e) {
        return $this->error(OperationStatus::ERROR, 'Erro ao solicitar relatório', null, $e);
    }
}

private function generateDescription(array $filters): string
{
    $parts = [];
    if (!empty($filters['start_date'])) $parts[] = 'De: ' . $filters['start_date'];
    if (!empty($filters['end_date'])) $parts[] = 'Até: ' . $filters['end_date'];
    if (!empty($filters['customer_name'])) $parts[] = 'Cliente: ' . $filters['customer_name'];
    return implode(' | ', $parts) ?: 'Relatório geral';
}

private function generateFileName(string $type, string $format): string
{
    $timestamp = now()->format('Ymd_His');
    return "relatorio_{$type}_{$timestamp}.{$format}";
}
```

---

## 🎯 Prompt 3.2: Implementar ReportService — getFilteredReports()

Obter relatórios filtrados.

```php
public function getFilteredReports(array $filters = [], array $with = []): ServiceResult
{
    try {
        $reports = $this->repository->getPaginated($filters, 15);
        return $this->success($reports, 'Relatórios filtrados');
    } catch (Exception $e) {
        return $this->error(OperationStatus::ERROR, 'Erro ao filtrar relatórios', null, $e);
    }
}
```

---

## 🎯 Prompt 3.3: Implementar ReportService — downloadReport()

Preparar download de relatório.

```php
public function downloadReport(string $hash): ServiceResult
{
    try {
        $report = $this->repository->findByHash($hash, ['user']);
        if (!$report) return $this->error(OperationStatus::NOT_FOUND, 'Relatório não encontrado');

        if ($report->status !== 'completed') {
            return $this->error(OperationStatus::VALIDATION_ERROR, 'Relatório ainda não está pronto');
        }

        if (!$report->file_path || !Storage::disk('reports')->exists($report->file_path)) {
            return $this->error(OperationStatus::ERROR, 'Arquivo não encontrado');
        }

        return $this->success([
            'file_path' => $report->file_path,
            'file_name' => $report->file_name,
            'mime_type' => $this->getMimeType($report->format)
        ], 'Relatório pronto para download');
    } catch (Exception $e) {
        return $this->error(OperationStatus::ERROR, 'Erro ao preparar download', null, $e);
    }
}

private function getMimeType(string $format): string
{
    return match($format) {
        'pdf' => 'application/pdf',
        'excel' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'csv' => 'text/csv',
        default => 'application/octet-stream'
    };
}
```

---

## 🎯 Prompt 3.4: Implementar ReportService — getReportStats()

Calcular estatísticas de relatórios.

```php
public function getReportStats(): ServiceResult
{
    try {
        $stats = [
            'total_reports' => $this->repository->count(),
            'completed_today' => $this->repository->where('status', 'completed')->whereDate('generated_at', today())->count(),
            'by_type' => $this->repository->selectRaw('type, count(*) as count')->groupBy('type')->pluck('count', 'type')->toArray(),
            'by_status' => $this->repository->selectRaw('status, count(*) as count')->groupBy('status')->pluck('count', 'status')->toArray(),
        ];
        return $this->success($stats, 'Estatísticas calculadas');
    } catch (Exception $e) {
        return $this->error(OperationStatus::ERROR, 'Erro ao calcular estatísticas', null, $e);
    }
}
```

---

# 🎯 Grupo 4: Controllers (Interface HTTP) — Quarto

## 🎯 Prompt 4.1: Implementar index() — Lista de Relatórios

```php
public function index(Request $request): View
{
    try {
        $filters = $request->only(['search', 'type', 'status', 'format', 'start_date', 'end_date']);
        $result = $this->service->getFilteredReports($filters, ['user']);
        if (!$result->isSuccess()) abort(500, 'Erro ao carregar relatórios');

        $stats = $this->service->getReportStats();

        return view('reports.index', [
            'reports' => $result->getData(),
            'filters' => $filters,
            'stats' => $stats->getData(),
        ]);
    } catch (Exception $e) {
        Log::error('Erro ao carregar página de relatórios', ['error' => $e->getMessage()]);
        abort(500, 'Erro interno');
    }
}
```

---

## 🎯 Prompt 4.2: Implementar create() — Formulário de Geração

```php
public function create(): View
{
    return view('reports.create', [
        'types' => ['budget' => 'Orçamentos', 'customer' => 'Clientes', 'product' => 'Produtos', 'service' => 'Serviços'],
        'formats' => ['pdf' => 'PDF', 'excel' => 'Excel', 'csv' => 'CSV'],
    ]);
}
```

---

## 🎯 Prompt 4.3: Implementar store() — Solicitar Geração

```php
public function store(ReportGenerateRequest $request): RedirectResponse
{
    try {
        $result = $this->service->generateReport($request->validated());
        if ($result->isSuccess()) {
            return redirect()->route('reports.index')->with('success', $result->getMessage());
        } else {
            return redirect()->back()->with('error', $result->getMessage())->withInput();
        }
    } catch (Exception $e) {
        Log::error('Erro ao solicitar relatório', ['error' => $e->getMessage(), 'data' => $request->all()]);
        return redirect()->back()->with('error', 'Erro interno')->withInput();
    }
}
```

---

## 🎯 Prompt 4.4: Implementar download() — Fazer Download

```php
public function download(string $hash): BinaryFileResponse
{
    try {
        $result = $this->service->downloadReport($hash);
        if (!$result->isSuccess()) abort(404, $result->getMessage());

        $data = $result->getData();
        $filePath = Storage::disk('reports')->path($data['file_path']);

        return response()->download($filePath, $data['file_name'], [
            'Content-Type' => $data['mime_type']
        ]);
    } catch (Exception $e) {
        Log::error('Erro ao fazer download', ['hash' => $hash, 'error' => $e->getMessage()]);
        abort(500, 'Erro interno');
    }
}
```

---

## 🎯 Prompt 4.5: Implementar budgets() — Formulário Específico para Orçamentos

```php
public function budgets(): View
{
    return view('reports.budgets', [
        'budget_statuses' => BudgetStatus::cases(),
    ]);
}
```

---

## 🎯 Prompt 4.6: Implementar budgetsPdf() — Gerar PDF de Orçamentos (Legacy)

```php
public function budgetsPdf(Request $request): Response
{
    try {
        $filters = $request->only(['code', 'start_date', 'end_date', 'customer_name', 'total', 'status']);

        $budgetService = app(BudgetService::class);
        $result = $budgetService->getFilteredBudgets($filters);
        if (!$result->isSuccess()) abort(500, 'Erro ao buscar dados');

        $budgets = $result->getData();
        $totals = $this->calculateTotals($budgets);

        $html = view('reports.budgets_pdf', compact('budgets', 'totals', 'filters'))->render();
        $filename = $this->generateFileName('orcamentos', 'pdf', count($budgets));

        $pdfService = app(ReportPdfService::class);
        $pdfResult = $pdfService->generateFromHtml($html, $filename);

        if (!$pdfResult->isSuccess()) abort(500, 'Erro ao gerar PDF');

        $this->service->create([
            'type' => 'budget',
            'format' => 'pdf',
            'status' => 'completed',
            'file_name' => $filename,
            'size' => $pdfResult->getData()['size'],
            'filters' => $filters,
            'generated_at' => now(),
        ]);

        return response($pdfResult->getData()['content'], 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"'
        ]);
    } catch (Exception $e) {
        Log::error('Erro ao gerar PDF de orçamentos', ['error' => $e->getMessage()]);
        abort(500, 'Erro interno');
    }
}

private function calculateTotals(Collection $budgets): array
{
    return [
        'count' => $budgets->count(),
        'sum' => $budgets->sum('total'),
        'avg' => $budgets->avg('total') ?? 0,
    ];
}

private function generateFileName(string $type, string $format, int $count): string
{
    $timestamp = now()->format('Ymd_His');
    $countFormatted = str_pad($count, 3, '0', STR_PAD_LEFT);
    return "relatorio_{$type}_{$timestamp}_{$countFormatted}_registros.{$format}";
}
```

---

**Fim dos Prompts de Migração do Módulo Report**
