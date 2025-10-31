# Relatório de Análise - ReportController (Sistema Antigo)

## 📋 Sumário Executivo

Análise completa do `ReportController` do sistema antigo para migração ao Laravel 12.

**Arquivo:** `old-system/app/controllers/ReportController.php`  
**Data:** 2025  
**Objetivo:** Mapear funcionalidades, dependências e fluxos para implementação no novo sistema.

---

## 🎯 Visão Geral

### Dependências Injetadas (8 total)

1. **Twig** - Template engine
2. **Budget** - Model de orçamentos
3. **PdfGenerator** - Gerador de PDFs
4. **ExcelGenerator** - Gerador de Excel
5. **Report** - Model de relatórios
6. **ActivityService** - Logs
7. **BudgetExcel** - Gerador específico de Excel para orçamentos
8. **Request** - HTTP Request

---

## 📊 Métodos (7 total)

### 1. `index()` - Página Principal de Relatórios
- **Rota:** GET `/provider/reports`
- **View:** `pages/report/index.twig`
- **Função:** Dashboard de relatórios

### 2. `customers()` - Relatório de Clientes
- **Rota:** GET `/provider/reports/customers`
- **View:** `pages/report/customer/customer.twig`
- **Função:** Formulário de filtros para relatório de clientes

### 3. `products()` - Relatório de Produtos
- **Rota:** GET `/provider/reports/products`
- **View:** `pages/report/product/product.twig`
- **Função:** Formulário de filtros para relatório de produtos

### 4. `budgets()` - Relatório de Orçamentos (Formulário)
- **Rota:** GET `/provider/reports/budgets`
- **View:** `pages/report/budget/budget.twig`
- **Função:** Formulário de filtros para relatório de orçamentos

### 5. `budgets_pdf()` - Gerar PDF de Orçamentos
- **Rota:** GET `/provider/reports/budgets/pdf`
- **Lógica:**
  1. Recebe filtros via query string
  2. Busca dados: `$this->budget->getBudgetsByFilterReport()`
  3. Calcula totais (count, sum, avg)
  4. Gera nome do arquivo: `relatorio_orcamentos_YYYYMMDD_HHMMSS_XXX_registros.pdf`
  5. Renderiza HTML: `pages/report/budget/pdf_budget.twig`
  6. Gera PDF via `PdfGenerator->generate()`
  7. Cria registro na tabela reports
  8. Registra atividade: `report_created`
- **Response:** PDF inline

### 6. `budgets_excel()` - Gerar Excel de Orçamentos
- **Rota:** GET `/provider/reports/budgets/excel`
- **Lógica:**
  1. Recebe filtros via query string
  2. Busca dados: `$this->budget->getBudgetsByFilterReport()`
  3. Calcula totais (count, sum, avg)
  4. Gera nome do arquivo: `relatorio_orcamentos_YYYYMMDD_HHMMSS_XXX_registros.xlsx`
  5. Gera Excel via `BudgetExcel->generateExcel()`
  6. Processa via `ExcelGenerator->generate()`
  7. Cria registro na tabela reports
  8. Registra atividade: `report_created`
- **Response:** Excel inline

### 7. `services()` - Relatório de Serviços
- **Rota:** GET `/provider/reports/services`
- **View:** `pages/report/service/view_service.twig`
- **Função:** Formulário de filtros para relatório de serviços

---

## 📦 Estrutura de Dados

### ReportEntity (Campos)
```
id, tenant_id, user_id, hash, type, description,
file_name, status, format, size, created_at
```

### Tipos de Relatório
- `budget` - Relatórios de orçamentos
- `customer` - Relatórios de clientes (não implementado)
- `product` - Relatórios de produtos (não implementado)
- `service` - Relatórios de serviços (não implementado)

### Formatos Suportados
- `pdf` - Documento PDF
- `excel` - Planilha XLSX

### Status
- `generated` - Relatório gerado com sucesso
- `pending` - Aguardando geração (não usado)
- `processing` - Em processamento (não usado)
- `failed` - Falha na geração (não usado)

---

## 🔄 Fluxos de Negócio

### Fluxo 1: Geração de Relatório PDF
1. Provider acessa formulário de filtros
2. Preenche filtros (código, data, cliente, status, valor)
3. Clica em "Gerar PDF"
4. Sistema busca dados filtrados
5. Calcula estatísticas (total, média, contagem)
6. Renderiza HTML com template Twig
7. Gera PDF via mPDF
8. Calcula hash único do relatório
9. Salva registro na tabela reports
10. Registra atividade
11. Retorna PDF inline no navegador

### Fluxo 2: Geração de Relatório Excel
1. Provider acessa formulário de filtros
2. Preenche filtros
3. Clica em "Gerar Excel"
4. Sistema busca dados filtrados
5. Calcula estatísticas
6. Gera planilha via PhpSpreadsheet
7. Calcula hash único
8. Salva registro na tabela reports
9. Registra atividade
10. Retorna Excel inline no navegador

---

## 🔧 Componentes Auxiliares

### PdfGenerator
- **Função:** Gera PDFs a partir de HTML
- **Biblioteca:** mPDF
- **Método:** `generate($html, $filename)`
- **Retorno:** Array com content, size

### ExcelGenerator
- **Função:** Processa planilhas Excel
- **Biblioteca:** PhpSpreadsheet
- **Método:** `generate($spreadsheet)`
- **Retorno:** Array com content, size

### BudgetExcel
- **Função:** Cria estrutura da planilha de orçamentos
- **Método:** `generateExcel($authenticated, $budgets, $filters, $date, $totals, $filename)`
- **Retorno:** Objeto Spreadsheet

---

## 📊 Filtros de Relatório (Budget)

### Filtros Disponíveis
```php
- code (código do orçamento)
- start_date (data inicial)
- end_date (data final)
- customer_name (nome/CPF/CNPJ do cliente)
- total (valor mínimo)
- status (status do orçamento)
```

### Cálculos Automáticos
```php
$totals = [
    'count' => count($budgets),
    'sum' => array_sum(array_column($budgets, 'total')),
    'avg' => count($budgets) > 0 ? sum / count : 0
];
```

---

## 🔐 Sistema de Hash

### Geração de Hash
```php
$hash = generateReportHash(
    $content,
    $filters,
    $user_id,
    $tenant_id
);
```

**Função:** Identificador único do relatório para:
- Evitar duplicatas
- Rastreamento
- Auditoria

---

## 📝 Nomenclatura de Arquivos

### Padrão PDF
```
relatorio_orcamentos_YYYYMMDD_HHMMSS_XXX_registros.pdf
Exemplo: relatorio_orcamentos_20250115_143022_042_registros.pdf
```

### Padrão Excel
```
relatorio_orcamentos_YYYYMMDD_HHMMSS_XXX_registros.xlsx
Exemplo: relatorio_orcamentos_20250115_143022_042_registros.xlsx
```

**Componentes:**
- Data/hora da geração
- Quantidade de registros (3 dígitos com zero à esquerda)

---

## ⚠️ Pontos Críticos

### 1. Relatórios Não Implementados
- **customers()** - Apenas view, sem geração
- **products()** - Apenas view, sem geração
- **services()** - Apenas view, sem geração

### 2. Geração Síncrona
- Relatórios gerados em tempo real
- Pode causar timeout em grandes volumes
- Sem sistema de filas

### 3. Armazenamento de Relatórios
- Apenas metadados salvos no banco
- Conteúdo não armazenado
- Não há histórico de downloads

### 4. Sem Paginação
- Busca todos os registros de uma vez
- Limite de 100 registros no model
- Pode causar problemas de memória

### 5. Descrição Gerada Automaticamente
```php
$description = generateDescriptionPipe($filters);
```
- Função helper que formata filtros em texto

---

## 📝 Recomendações Laravel

### Controllers
```php
ReportController (provider - dashboard e formulários)
ReportGeneratorController (geração assíncrona)
```

### Services
```php
ReportService - Lógica de negócio
ReportPdfService - Geração de PDFs
ReportExcelService - Geração de Excel
ReportStorageService - Armazenamento de arquivos
ReportHashService - Geração de hashes
```

### Jobs (Filas)
```php
GenerateBudgetPdfReport
GenerateBudgetExcelReport
GenerateCustomerReport
GenerateProductReport
GenerateServiceReport
```

### Models
```php
Report (belongsTo: User, Tenant)
```

### Events & Listeners
```php
ReportGenerated → SendReportReadyNotification
ReportFailed → SendReportFailedNotification
```

### Storage
```php
// Armazenar arquivos gerados
Storage::disk('reports')->put($filename, $content);

// Estrutura sugerida
storage/app/reports/{tenant_id}/{year}/{month}/{filename}
```

---

## 🔄 Migração para Sistema de Filas

### Fluxo Recomendado
1. Provider solicita relatório
2. Sistema cria registro com status `pending`
3. Dispara job para fila
4. Job processa relatório
5. Salva arquivo no storage
6. Atualiza status para `completed`
7. Envia notificação ao provider
8. Provider acessa relatório via link

### Benefícios
- Não bloqueia interface
- Suporta grandes volumes
- Permite retry em caso de falha
- Histórico de relatórios gerados

---

## 📊 Estrutura Sugerida para Laravel

### Migration Reports
```php
Schema::create('reports', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->string('hash', 64)->nullable();
    $table->string('type', 50); // budget, customer, product, service
    $table->text('description')->nullable();
    $table->string('file_name');
    $table->string('file_path')->nullable(); // NOVO
    $table->enum('status', ['pending', 'processing', 'completed', 'failed']);
    $table->enum('format', ['pdf', 'excel', 'csv']);
    $table->float('size');
    $table->json('filters')->nullable(); // NOVO
    $table->text('error_message')->nullable(); // NOVO
    $table->timestamp('generated_at')->nullable(); // NOVO
    $table->timestamps();
});
```

---

## ✅ Checklist de Implementação

- [ ] Criar migration de reports
- [ ] Criar model Report
- [ ] Criar ReportService
- [ ] Criar ReportController
- [ ] Implementar geração de PDF (orçamentos)
- [ ] Implementar geração de Excel (orçamentos)
- [ ] Implementar sistema de filas
- [ ] Implementar armazenamento de arquivos
- [ ] Implementar geração de hash
- [ ] Implementar relatórios de clientes
- [ ] Implementar relatórios de produtos
- [ ] Implementar relatórios de serviços
- [ ] Criar Jobs para geração assíncrona
- [ ] Criar notificações de conclusão
- [ ] Implementar histórico de relatórios
- [ ] Implementar download de relatórios
- [ ] Criar testes

---

## 🐛 Melhorias Identificadas

### 1. Implementar Relatórios Faltantes
- Clientes (customers)
- Produtos (products)
- Serviços (services)

### 2. Sistema de Filas
- Geração assíncrona
- Notificações de conclusão

### 3. Armazenamento
- Salvar arquivos gerados
- Permitir re-download

### 4. Paginação
- Limitar registros por página
- Exportação em lotes

### 5. Agendamento
- Relatórios recorrentes
- Envio automático por email

---

**Fim do Relatório**
