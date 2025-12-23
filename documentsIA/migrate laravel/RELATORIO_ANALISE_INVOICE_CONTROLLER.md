# Relatório de Análise - InvoiceController (Sistema Antigo)

## 📋 Sumário Executivo

Análise completa do `InvoiceController` do sistema antigo para migração ao Laravel 12.

**Arquivo:** `old-system/app/controllers/InvoiceController.php`  
**Data:** 2025  
**Objetivo:** Mapear funcionalidades, dependências e fluxos para implementação no novo sistema.

---

## 🎯 Visão Geral

### Dependências Injetadas (10 total)

1. **Twig** - Template engine
2. **Sanitize** - Sanitização
3. **Invoice** (Model) - Model de faturas
4. **InvoiceService** - Lógica de negócio
5. **ActivityService** - Logs
6. **InvoiceStatuses** - Model de status
7. **NotificationService** - Envio de emails
8. **PdfService** - Geração de PDFs
9. **PaymentMercadoPagoInvoiceService** - Integração pagamentos
10. **Request** - HTTP Request

---

## 📊 Métodos (6 total + 1 comentado)

### 1. `index()` - Lista de Faturas
- **Rota:** GET `/provider/invoices`
- **View:** `pages/invoice/index.twig`
- **Dados:** Lista de status de faturas
- **Chamadas:** `$this->invoiceStatuses->getAllStatuses()`

### 2. `create($code)` - Formulário de Criação
- **Rota:** GET `/provider/invoices/create/{code}`
- **View:** `pages/invoice/create.twig`
- **Lógica:**
  1. Sanitiza código do serviço
  2. Gera dados da fatura: `InvoiceService->generateInvoiceDataFromService()`
  3. Verifica se já existe fatura para o serviço
  4. Se existe: redireciona com erro
  5. Se não: exibe formulário com dados pré-preenchidos

### 3. `store()` - Criar Fatura
- **Rota:** POST `/provider/invoices`
- **Lógica:**
  1. Recebe dados do formulário (incluindo JSON)
  2. Cria fatura via `InvoiceService->storeInvoice()`
  3. Registra atividade: `invoice_created`
  4. Busca fatura completa criada
  5. Envia email de notificação ao cliente
  6. Registra atividade
- **Redirect:** `/provider/invoices` (sucesso)

### 4. `show($code)` - Detalhes da Fatura
- **Rota:** GET `/provider/invoices/show/{code}`
- **View:** `pages/invoice/show.twig`
- **Dados:** Fatura completa via `Invoice->getInvoiceFullByCode()`

### 5. `print($code)` - Gerar PDF
- **Rota:** GET `/provider/invoices/print/{code}`
- **Lógica:**
  1. Busca fatura completa
  2. Gera PDF via `PdfService->generateInvoicePdf()`
  3. Sanitiza nome do arquivo
- **Response:** PDF inline

### 6. `activityLogger()` - Helper de Log
- **Função:** Registra atividades no sistema

### 7. `redirectToPayment($code)` - Redirecionar para Pagamento (COMENTADO)
- **Status:** Não implementado/desabilitado
- **Função:** Criaria preferência no Mercado Pago e redirecionaria

---

## 📦 Estrutura de Dados

### InvoiceEntity (Campos)
```
id, tenant_id, service_id, customer_id, code, public_hash,
invoice_statuses_id, subtotal, discount, total, due_date,
payment_id, payment_method, payment_date, notes,
created_at, updated_at
```

### Status de Fatura (InvoiceStatusEnum)
- `pending` - Pendente
- `paid` - Paga
- `overdue` - Vencida (não mencionado mas comum)
- `cancelled` - Cancelada (não mencionado mas comum)

---

## 🔄 Fluxos de Negócio

### Fluxo 1: Criação de Fatura a partir de Serviço
1. Provider visualiza serviço concluído/parcial
2. Clica em "Gerar Fatura"
3. Sistema busca dados do serviço
4. Sistema busca dados do cliente
5. Sistema busca itens do serviço
6. Sistema calcula valores:
   - Subtotal = total do serviço
   - Desconto = desconto do serviço
   - Total = subtotal - desconto
7. Se serviço PARTIAL:
   - Aplica desconto adicional de 10%
   - Adiciona nota explicativa
8. Exibe formulário pré-preenchido
9. Provider confirma/ajusta dados
10. Sistema gera código: `FAT-YYYYMMDD0001`
11. Sistema gera hash público (64 caracteres)
12. Sistema cria fatura com status PENDING
13. Sistema envia email ao cliente
14. Registra atividade

### Fluxo 2: Visualização de Fatura
1. Provider acessa lista de faturas
2. Clica em fatura específica
3. Sistema exibe detalhes completos
4. Provider pode gerar PDF

### Fluxo 3: Geração de PDF
1. Provider clica em "Imprimir"
2. Sistema busca fatura completa
3. Sistema gera PDF via mPDF
4. Retorna PDF inline no navegador

### Fluxo 4: Atualização de Pagamento (Webhook)
1. Mercado Pago envia notificação
2. Sistema busca fatura por ID
3. Sistema mapeia status do pagamento
4. Sistema atualiza fatura:
   - Status → PAID
   - payment_id
   - payment_method
   - payment_date
5. Verifica duplicatas
6. Registra atualização

---

## 🔧 InvoiceService (Métodos)

### 1. `generateInvoiceDataFromService(string $serviceCode)`
- **Função:** Gera dados da fatura a partir do serviço
- **Lógica:**
  1. Busca serviço por código
  2. Busca cliente vinculado
  3. Busca itens do serviço
  4. Monta array com dados
  5. Se serviço PARTIAL: aplica desconto de 10%
- **Retorno:** Array com dados pré-preenchidos

### 2. `storeInvoice(array $data)`
- **Função:** Cria nova fatura
- **Lógica:**
  1. Valida serviço existe
  2. Verifica se já existe fatura para o serviço
  3. Busca status PENDING
  4. Gera código único: `FAT-YYYYMMDD0001`
  5. Gera hash público (64 caracteres)
  6. Cria InvoiceEntity
  7. Salva no banco
- **Retorno:** Array com status, message, data

### 3. `updateInvoice(array $payment)`
- **Função:** Atualiza fatura com dados de pagamento
- **Transação:** Sim
- **Lógica:**
  1. Busca fatura atual
  2. Mapeia status do pagamento para status da fatura
  3. Verifica duplicatas (mesmo payment_id + status)
  4. Atualiza status e dados de pagamento
  5. Suporta PAID e PENDING
- **Retorno:** Array com status, message, data

---

## ⚠️ Pontos Críticos

### 1. Geração de Código Único
```php
$last_code = $this->invoiceModel->getLastCode($tenant_id);
$last_code = (float)(substr($last_code, -4)) + 1;
$code = 'FAT-' . date('Ymd') . str_pad((string)$last_code, 4, '0', STR_PAD_LEFT);
```
**Formato:** `FAT-YYYYMMDD0001`

### 2. Hash Público
```php
$public_hash = bin2hex(random_bytes(32)); // 64 caracteres
```
**Uso:** Link público para cliente acessar fatura

### 3. Desconto Automático para Serviços Parciais
```php
if ($service->status_slug === 'PARTIAL') {
    $partialDiscountPercentage = 0.90; // 10% de desconto
    $invoiceData['discount'] += $invoiceData['total'] * (1 - $partialDiscountPercentage);
    $invoiceData['total'] *= $partialDiscountPercentage;
    $invoiceData['notes'] = "Fatura gerada com base na conclusão parcial do serviço...";
}
```

### 4. Validação de Duplicatas
- Verifica se já existe fatura para o serviço
- Verifica duplicatas de pagamento (payment_id + status)

### 5. Integração Mercado Pago (Comentada)
- Método `redirectToPayment()` está comentado
- Não está em uso no sistema atual

---

## 📧 Sistema de Notificações

### Email de Nova Fatura
```php
$this->notificationService->sendNewInvoiceNotification(
    $authenticated,
    $invoice,
    $customer
);
```

**Conteúdo:**
- Dados da fatura
- Link para visualização
- Dados do cliente
- Instruções de pagamento

---

## 📝 Recomendações Laravel

### Models
```php
Invoice (belongsTo: Service, Customer, InvoiceStatus)
Invoice (hasMany: PaymentMercadoPagoInvoice)
```

### Controllers
```php
InvoiceController (provider - CRUD)
PublicInvoiceController (cliente - visualização)
InvoiceWebhookController (Mercado Pago)
```

### Services
```php
InvoiceService - Lógica de negócio
InvoiceCodeGeneratorService - Códigos únicos
InvoicePdfService - Geração de PDFs
InvoicePaymentService - Processamento de pagamentos
InvoiceNotificationService - Envio de emails
```

### Form Requests
```php
InvoiceStoreRequest
InvoiceUpdateRequest
```

### Events & Listeners
```php
InvoiceCreated → SendInvoiceCreatedNotification
InvoicePaid → SendInvoicePaidNotification
InvoiceOverdue → SendInvoiceOverdueNotification
```

### Jobs (Filas)
```php
ProcessInvoicePayment
SendInvoiceReminder
CheckOverdueInvoices
```

### Policies
```php
InvoicePolicy:
- view, create, update, delete, download
```

---

## 🔄 Integração Mercado Pago

### Fluxo de Pagamento (Sugerido)
1. Cliente acessa fatura via link público
2. Clica em "Pagar"
3. Sistema cria preferência no Mercado Pago
4. Redireciona para checkout
5. Cliente efetua pagamento
6. Mercado Pago envia webhook
7. Sistema atualiza fatura
8. Envia email de confirmação

### Webhook Handler
```php
// Recebe notificação do Mercado Pago
// Valida assinatura
// Busca fatura
// Atualiza status
// Envia notificação
```

---

## ✅ Checklist de Implementação

- [ ] Criar migration de invoices
- [ ] Criar model Invoice com relationships
- [ ] Criar InvoiceService
- [ ] Criar InvoiceController (provider)
- [ ] Criar PublicInvoiceController (cliente)
- [ ] Implementar geração de código único
- [ ] Implementar geração de hash público
- [ ] Implementar desconto automático para parciais
- [ ] Implementar geração de PDF
- [ ] Implementar envio de emails
- [ ] Implementar integração Mercado Pago
- [ ] Implementar webhook handler
- [ ] Criar Form Requests
- [ ] Criar Events & Listeners
- [ ] Criar Jobs para lembretes
- [ ] Criar Policies
- [ ] Criar views Blade
- [ ] Implementar testes
- [ ] Implementar verificação de vencimento

---

## 🐛 Melhorias Identificadas

### 1. Método de Pagamento Comentado
**Problema:** `redirectToPayment()` está desabilitado
**Solução:** Implementar integração completa com Mercado Pago

### 2. Sem Verificação de Vencimento
**Problema:** Não há job para marcar faturas vencidas
**Solução:** Criar job agendado para verificar due_date

### 3. Sem Sistema de Lembretes
**Problema:** Não envia lembretes antes do vencimento
**Solução:** Implementar job para enviar lembretes (3 dias, 1 dia antes)

### 4. Desconto Fixo para Parciais
**Problema:** Desconto de 10% é fixo no código
**Solução:** Tornar configurável por tenant

### 5. Sem Histórico de Pagamentos
**Problema:** Não mantém histórico de tentativas
**Solução:** Criar tabela invoice_payment_attempts

---

**Fim do Relatório**
