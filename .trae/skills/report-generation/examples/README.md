# 📚 Examples - Report Generation

**Descrição:** Exemplos práticos de implementação de relatórios no Easy Budget Laravel.

## 🎯 Exemplos Disponíveis

### **📊 Relatórios Financeiros**

#### **1. Relatório de Resumo Financeiro**
```php
// Exemplo de uso do ReportService para relatórios financeiros
$reportService = app(ReportService::class);

$filters = [
    'start_date' => '2025-01-01',
    'end_date' => '2025-12-31',
    'customer_type' => 'all',
];

$result = $reportService->generateReport('financial_summary', $filters, $tenantId);

if ($result->isSuccess()) {
    $reportData = $result->getData();
    // Processar dados do relatório
    $summary = $reportData['summary'];
    $charts = $reportData['charts'];
    $data = $reportData['data'];
}
```

#### **2. Exportação em Múltiplos Formatos**
```php
$exportService = app(ReportExportService::class);

// Exportar em PDF
$pdfResult = $exportService->exportReport($reportData, 'pdf', $tenantId);

// Exportar em Excel
$excelResult = $exportService->exportReport($reportData, 'excel', $tenantId);

// Exportar em CSV
$csvResult = $exportService->exportReport($reportData, 'csv', $tenantId);

// Download automático
if ($pdfResult->isSuccess()) {
    return response()->download($pdfResult->getData()['file_path']);
}
```

### **📈 Relatórios Operacionais**

#### **3. Relatório de Movimentação de Estoque**
```php
$filters = [
    'start_date' => now()->subDays(30),
    'end_date' => now(),
    'product_id' => null, // Todos os produtos
    'movement_type' => 'all', // Todas as movimentações
];

$result = $reportService->generateReport('inventory_movements', $filters, $tenantId);

if ($result->isSuccess()) {
    $inventoryData = $result->getData();

    // Dados de movimentação
    foreach ($inventoryData['data'] as $movement) {
        echo "Produto: {$movement['product_name']}\n";
        echo "Tipo: {$movement['type']}\n";
        echo "Quantidade: {$movement['quantity']}\n";
        echo "Data: {$movement['date']}\n";
    }
}
```

#### **4. Relatório de Performance de Vendas**
```php
$filters = [
    'start_date' => now()->startOfMonth(),
    'end_date' => now()->endOfMonth(),
    'salesperson' => 'all',
    'product_category' => null,
];

$result = $reportService->generateReport('sales_performance', $filters, $tenantId);

if ($result->isSuccess()) {
    $salesData = $result->getData();

    // Produtos mais vendidos
    $topProducts = $salesData['summary']['top_products'];

    foreach ($topProducts as $product) {
        echo "Produto: {$product['name']}\n";
        echo "Quantidade: {$product['quantity']}\n";
        echo "Valor Total: R$ {$product['total_value']}\n";
    }
}
```

### **🎯 Relatórios Analíticos**

#### **5. Análise de Comportamento de Clientes**
```php
$filters = [
    'start_date' => now()->subMonths(6),
    'end_date' => now(),
    'customer_segment' => 'all',
    'region' => null,
];

$result = $reportService->generateReport('customer_analytics', $filters, $tenantId);

if ($result->isSuccess()) {
    $customerData = $result->getData();

    // Segmentação de clientes
    $segments = $customerData['summary']['segments'];

    foreach ($segments as $segment) {
        echo "Segmento: {$segment['name']}\n";
        echo "Clientes: {$segment['count']}\n";
        echo "Valor Médio: R$ {$segment['avg_value']}\n";
    }
}
```

### **⏰ Relatórios Agendados**

#### **6. Agendamento de Relatório Mensal**
```php
$scheduleService = app(ReportScheduleService::class);

$scheduleData = [
    'report_type' => 'financial_summary',
    'filters' => [
        'start_date' => '2025-01-01',
        'end_date' => '2025-12-31',
    ],
    'schedule_type' => 'monthly',
    'schedule_config' => [
        'day_of_month' => 1,
        'hour' => 9,
        'minute' => 0,
    ],
    'recipients' => ['financeiro@empresa.com', 'diretoria@empresa.com'],
    'formats' => ['pdf', 'excel'],
];

$result = $scheduleService->scheduleReport($scheduleData, $tenantId);

if ($result->isSuccess()) {
    $schedule = $result->getData();
    echo "Relatório agendado com sucesso!\n";
    echo "Próxima execução: {$schedule->next_run_at}\n";
    echo "Formatos: " . implode(', ', $schedule->formats) . "\n";
}
```

#### **7. Execução de Relatórios Agendados**
```php
// Executar manualmente relatórios agendados
$scheduleService = app(ReportScheduleService::class);
$result = $scheduleService->runScheduledReports();

if ($result->isSuccess()) {
    $executions = $result->getData();

    foreach ($executions as $execution) {
        echo "Agendamento: {$execution['schedule_id']}\n";
        echo "Status: {$execution['status']}\n";

        if ($execution['status'] === 'success') {
            echo "Formatos gerados: " . count($execution['exports']) . "\n";
        } else {
            echo "Erro: {$execution['error']}\n";
        }
    }
}
```

### **📊 Dashboards Executivos**

#### **8. Dashboard de KPIs**
```php
$dashboardService = app(DashboardService::class);

$result = $dashboardService->getExecutiveDashboard($tenantId, [
    'start_date' => now()->startOfMonth(),
    'end_date' => now()->endOfMonth(),
]);

if ($result->isSuccess()) {
    $dashboardData = $result->getData();

    // Métricas principais
    $summary = $dashboardData['summary'];
    echo "Receita Total: R$ {$summary['total_revenue']}\n";
    echo "Clientes Ativos: {$summary['active_customers']}\n";
    echo "Faturas Pendentes: {$summary['pending_invoices']}\n";

    // Charts
    $charts = $dashboardData['charts'];
    $revenueChart = $charts['revenue_by_month'];

    // Métricas de performance
    $metrics = $dashboardData['metrics'];
    echo "Crescimento de Receita: {$metrics['revenue_growth']}%\n";
    echo "Rentabilidade: {$metrics['profit_margin']}%\n";
}
```

### **⚡ Performance e Cache**

#### **9. Uso de Cache de Relatórios**
```php
$cacheService = app(ReportCacheService::class);

// Obter relatório do cache ou gerar novo
$result = $cacheService->getCachedReport('financial_summary', $filters, $tenantId);

if ($result->isSuccess()) {
    $reportData = $result->getData();
    echo "Relatório obtido do cache\n";
} else {
    // Gerar relatório e armazenar em cache
    $reportResult = $reportService->generateReport('financial_summary', $filters, $tenantId);
    if ($reportResult->isSuccess()) {
        $cacheService->storeReportInCache('financial_summary', $filters, $tenantId, $reportResult->getData());
    }
}

// Invalidar cache quando necessário
$cacheService->invalidateReportCache('financial_summary', $tenantId);
```

#### **10. Otimização de Queries**
```php
$cacheService = app(ReportCacheService::class);

// Otimizar query para grandes volumes
$result = $cacheService->optimizeReportQuery('financial_summary', $filters, $tenantId);

if ($result->isSuccess()) {
    $optimizedData = $result->getData();
    echo "Tempo de execução: {$optimizedData['execution_time']}s\n";
    echo "Consultas otimizadas: " . count($optimizedData['missing_indexes']) . "\n";
}
```

## 🚀 Como Implementar Novos Relatórios

### **Passo 1: Criar Configuração**
```php
// Adicionar ao config/report-types.php
'custom_report' => [
    'name' => 'Relatório Personalizado',
    'description' => 'Relatório sob medida para necessidades específicas',
    'required_filters' => ['start_date', 'end_date'],
    'optional_filters' => ['custom_field'],
    'data_source' => 'CustomReportService',
    'cache_ttl' => 900,
    'export_formats' => ['pdf', 'excel'],
],
```

### **Passo 2: Criar Service de Dados**
```php
class CustomReportService extends AbstractBaseService
{
    public function getCustomReportData(array $filters, int $tenantId): array
    {
        // Implementar lógica de geração de dados
        return [
            'summary' => [
                'total_customers' => 100,
                'total_revenue' => 50000.00,
            ],
            'data' => [
                // Dados detalhados
            ],
            'charts' => [
                // Dados para gráficos
            ],
        ];
    }
}
```

### **Passo 3: Criar Templates**
```blade
{{-- resources/views/reports/custom-report.blade.php --}}
<div class="report-content">
    <h2>Relatório Personalizado</h2>
    {{-- Implementar visualização --}}
</div>
```

### **Passo 4: Testar Implementação**
```php
public function testCustomReport()
{
    $tenant = Tenant::factory()->create();

    $filters = [
        'start_date' => now()->startOfMonth(),
        'end_date' => now()->endOfMonth(),
    ];

    $result = $this->reportService->generateReport('custom_report', $filters, $tenant->id);
    $this->assertTrue($result->isSuccess());

    $reportData = $result->getData();
    $this->assertArrayHasKey('summary', $reportData);
    $this->assertArrayHasKey('data', $reportData);
    $this->assertArrayHasKey('charts', $reportData);
}
```

## 📈 Métricas de Performance

### **Tempos de Geração**
- **Relatórios simples:** < 2 segundos
- **Relatórios médios:** < 5 segundos
- **Relatórios complexos:** < 10 segundos
- **Com cache:** < 1 segundo

### **Uso de Memória**
- **Relatórios pequenos:** < 50MB
- **Relatórios médios:** < 200MB
- **Relatórios grandes:** < 500MB

### **Capacidade de Exportação**
- **PDF:** Até 1000 páginas
- **Excel:** Até 100.000 linhas
- **CSV:** Até 1.000.000 linhas

## 🔧 Solução de Problemas

### **Problemas Comuns**

#### **1. Tempo de Geração Muito Alto**
```php
// Verificar performance da query
$cacheService = app(ReportCacheService::class);
$result = $cacheService->optimizeReportQuery($reportType, $filters, $tenantId);

// Usar cache
$result = $cacheService->getCachedReport($reportType, $filters, $tenantId);
```

#### **2. Erro de Memória**
```php
// Paginar resultados grandes
$query = $query->paginate(1000);

// Processar em lotes
$collection->chunk(1000, function ($chunk) {
    // Processar lote
});
```

#### **3. Exportação Falhando**
```php
// Verificar tamanho do arquivo
if ($fileSize > 50 * 1024 * 1024) { // 50MB
    // Dividir em partes menores
}

// Verificar permissões de escrita
Storage::put($path, $content);
```

## 📚 Documentação Adicional

- [ReportService](../../app/Services/Domain/ReportService.php) - Código fonte
- [ReportExportService](../../app/Services/Infrastructure/ReportExportService.php) - Exportação
- [ReportScheduleService](../../app/Services/Domain/ReportScheduleService.php) - Agendamento
- [DashboardService](../../app/Services/Domain/DashboardService.php) - Dashboards

---

**Última atualização:** 11/01/2026
**Versão:** 1.0.0
**Status:** ✅ Exemplos criados e documentados
