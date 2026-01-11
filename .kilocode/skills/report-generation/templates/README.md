# 📁 Templates - Report Generation

**Descrição:** Modelos e templates para implementação de relatórios no Easy Budget Laravel.

## 📋 Estrutura de Templates

### **📄 Templates de Views**

#### **PDF Templates**
- `pdf/header.blade.php` - Cabeçalho padrão para relatórios PDF
- `pdf/content.blade.php` - Conteúdo principal do relatório
- `pdf/footer.blade.php` - Rodapé com numeração de páginas

#### **Excel Templates**
- `excel/template.blade.php` - Estrutura base para exportação Excel
- `excel/styles.blade.php` - Estilos e formatação

#### **Dashboard Templates**
- `dashboard/summary.blade.php` - Resumo executivo
- `dashboard/charts.blade.php` - Visualizações gráficas
- `dashboard/metrics.blade.php` - Métricas de performance

### **⚙️ Templates de Configuração**

#### **Report Configuration**
- `config/report-types.php` - Configuração de tipos de relatórios
- `config/export-formats.php` - Configuração de formatos de exportação
- `config/schedule-types.php` - Configuração de tipos de agendamento

#### **Cache Configuration**
- `config/report-cache.php` - Configuração de cache de relatórios
- `config/performance.php` - Configuração de performance

### **📧 Templates de Email**

#### **Scheduled Reports**
- `emails/scheduled-report.blade.php` - E-mail de relatórios agendados
- `emails/report-ready.blade.php` - E-mail de relatório pronto para download

## 🚀 Como Utilizar os Templates

### **Criar Novo Tipo de Relatório**

1. **Adicionar configuração:**
```php
// config/report-types.php
'custom_report' => [
    'name' => 'Relatório Personalizado',
    'description' => 'Relatório sob medida para necessidades específicas',
    'required_filters' => ['start_date', 'end_date'],
    'optional_filters' => ['customer_id', 'product_id'],
    'data_source' => 'CustomReportService',
],
```

2. **Criar service de dados:**
```php
class CustomReportService extends AbstractBaseService
{
    public function getCustomReportData(array $filters, int $tenantId): array
    {
        // Implementar lógica de geração de dados
    }
}
```

3. **Criar template de visualização:**
```blade
{{-- resources/views/reports/custom-report.blade.php --}}
<div class="report-content">
    <h2>Relatório Personalizado</h2>
    {{-- Implementar visualização --}}
</div>
```

### **Criar Novo Formato de Exportação**

1. **Adicionar configuração:**
```php
// config/export-formats.php
'word' => [
    'enabled' => true,
    'template' => 'reports.word.default',
    'extension' => 'docx',
],
```

2. **Criar service de exportação:**
```php
class WordExportService extends AbstractBaseService
{
    public function generateWordContent(array $reportData): string
    {
        // Implementar lógica de geração Word
    }
}
```

3. **Criar template de exportação:**
```blade
{{-- resources/views/reports/word/content.blade.php --}}
<html>
    <body>
        {{-- Implementar template Word --}}
    </body>
</html>
```

### **Criar Novo Tipo de Agendamento**

1. **Adicionar configuração:**
```php
// config/schedule-types.php
'quarterly' => [
    'description' => 'Trimestralmente',
    'config_fields' => ['quarter', 'hour', 'minute'],
],
```

2. **Implementar lógica de cálculo:**
```php
private function calculateQuarterlySchedule(\DateTime $now, array $config): \DateTime
{
    // Implementar lógica de cálculo trimestral
}
```

## 📊 Templates de Relatórios Específicos

### **Financial Summary Template**
```php
// Template para relatórios financeiros
'report_type' => 'financial_summary',
'filters' => [
    'start_date' => '2025-01-01',
    'end_date' => '2025-12-31',
    'customer_type' => 'all',
],
'formats' => ['pdf', 'excel'],
```

### **Inventory Report Template**
```php
// Template para relatórios de estoque
'report_type' => 'inventory_movements',
'filters' => [
    'start_date' => '2025-01-01',
    'end_date' => '2025-12-31',
    'product_category' => 'electronics',
],
'formats' => ['pdf', 'csv'],
```

### **Sales Performance Template**
```php
// Template para relatórios de vendas
'report_type' => 'sales_performance',
'filters' => [
    'start_date' => '2025-01-01',
    'end_date' => '2025-12-31',
    'salesperson' => 'all',
],
'formats' => ['excel', 'pdf'],
```

## 🎨 Templates de Dashboard

### **Executive Dashboard**
```php
// Template para dashboard executivo
'dashboard_type' => 'executive',
'widgets' => [
    'revenue_summary',
    'customer_metrics',
    'product_performance',
    'alerts',
],
'refresh_interval' => 300, // 5 minutos
```

### **Operational Dashboard**
```php
// Template para dashboard operacional
'dashboard_type' => 'operational',
'widgets' => [
    'inventory_status',
    'pending_orders',
    'production_metrics',
    'quality_control',
],
'refresh_interval' => 60, // 1 minuto
```

### **Financial Dashboard**
```php
// Template para dashboard financeiro
'dashboard_type' => 'financial',
'widgets' => [
    'cash_flow',
    'accounts_receivable',
    'accounts_payable',
    'profit_loss',
],
'refresh_interval' => 1800, // 30 minutos
```

## 🔧 Templates de Configuração Avançada

### **Performance Optimization**
```php
// config/report-performance.php
'optimization' => [
    'query_timeout' => 30, // segundos
    'max_results' => 10000,
    'cache_ttl' => [
        'simple' => 300,
        'complex' => 1800,
        'real_time' => 60,
    ],
],
```

### **Security Configuration**
```php
// config/report-security.php
'security' => [
    'max_export_size' => 50, // MB
    'allowed_formats' => ['pdf', 'excel', 'csv'],
    'email_recipients_limit' => 10,
    'download_expiration' => 86400, // 24 horas
],
```

### **Integration Configuration**
```php
// config/report-integrations.php
'integrations' => [
    'bi_tools' => [
        'power_bi' => [
            'enabled' => true,
            'api_key' => env('POWER_BI_API_KEY'),
        ],
        'tableau' => [
            'enabled' => true,
            'server_url' => env('TABLEAU_SERVER_URL'),
        ],
    ],
    'storage' => [
        's3' => [
            'enabled' => true,
            'bucket' => env('REPORTS_S3_BUCKET'),
        ],
        'local' => [
            'enabled' => true,
            'path' => storage_path('app/reports'),
        ],
    ],
],
```

## 📚 Documentação de Templates

- [Report Types](./config/report-types.php) - Tipos de relatórios disponíveis
- [Export Formats](./config/export-formats.php) - Formatos de exportação
- [Schedule Types](./config/schedule-types.php) - Tipos de agendamento
- [Performance Config](./config/report-performance.php) - Configuração de performance
- [Security Config](./config/report-security.php) - Configuração de segurança

## 🎯 Próximos Templates

### **Templates Planejados**
- [ ] Templates para relatórios em tempo real
- [ ] Templates para integração com ferramentas de BI
- [ ] Templates para relatórios móveis
- [ ] Templates para relatórios interativos

### **Templates em Desenvolvimento**
- [ ] Templates para machine learning insights
- [ ] Templates para relatórios colaborativos
- [ ] Templates para dashboards personalizados

---

**Última atualização:** 11/01/2026
**Versão:** 1.0.0
**Status:** ✅ Templates criados e documentados
