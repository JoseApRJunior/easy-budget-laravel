# Relatório de Análise - WebhookController (Sistema Antigo)

## 📋 Sumário Executivo

Análise completa do `WebhookController` do sistema antigo para migração ao Laravel 12.

**Arquivo Analisado:** `old-system/app/controllers/WebhookController.php`  
**Data:** 2025  
**Objetivo:** Mapear funcionalidades, dependências e fluxos de webhooks do Mercado Pago.

---

## 🎯 Visão Geral

### Responsabilidade Principal
Processar notificações de webhook do Mercado Pago para dois tipos de pagamento:
1. **Pagamentos de Planos** - Assinaturas do sistema
2. **Pagamentos de Faturas** - Pagamentos de clientes

### Características Importantes
- ✅ Validação de autenticidade via assinatura (X-Signature)
- ✅ Processamento idempotente (evita duplicatas)
- ✅ Transações de banco de dados
- ✅ Notificações por email
- ✅ Registro de atividades (audit log)
- ✅ Tratamento robusto de erros

---

## 📦 Dependências Injetadas (9 total)

```php
1. Request $request - HTTP Request
2. Provider $providerModel - Model de providers
3. Customer $customerModel - Model de clientes
4. ActivityService $activityService - Logs de atividade
5. PaymentMercadoPagoInvoiceService - Serviço de pagamento de faturas
6. PaymentMercadoPagoPlanService - Serviço de pagamento de planos
7. NotificationService $notificationService - Envio de notificações
8. PlanService $planService - Lógica de planos
9. InvoiceService $invoiceService - Lógica de faturas
```

---

## 📊 Métodos do WebhookController (6 total)

### 1. `handleMercadoPagoInvoice()` ⭐⭐⭐
**Rota:** POST `/webhooks/mercadopago/invoices`  
**Função:** Processa webhooks de pagamento de faturas

#### Fluxo de Execução:
```
1. Valida headers (X-Request-Id)
2. Extrai payment_id e topic do body
3. Valida se é notificação de pagamento
4. Valida autenticidade (validateMercadoPagoAuthenticity)
5. Busca detalhes do pagamento na API MP (getResponsePaymentInvoice)
6. Cria/atualiza registro de pagamento (PaymentMercadoPagoInvoiceService)
7. Atualiza status da fatura (InvoiceService)
8. Envia notificação por email (NotificationService)
9. Registra atividades (ActivityService)
10. Retorna resposta de sucesso
```

#### Validações:
- Header `X-Request-Id` obrigatório
- Topic deve ser `payment`
- Autenticidade via assinatura X-Signature
- Evita processamento duplicado

#### Tratamento de Erros:
- `MPApiException` - Erro da API do Mercado Pago
- `\Throwable` - Erro genérico
- Logs detalhados em todos os casos

---

### 2. `handleMercadoPagoPlan()` ⭐⭐⭐
**Rota:** POST `/webhooks/mercadopago/plans`  
**Função:** Processa webhooks de pagamento de planos

#### Fluxo de Execução:
```
1. Valida headers (X-Request-Id)
2. Extrai payment_id e topic do body
3. Valida se é notificação de pagamento
4. Valida autenticidade (validateMercadoPagoAuthenticity)
5. Busca detalhes do pagamento na API MP (getResponsePaymentPlan)
6. Cria/atualiza registro de pagamento (PaymentMercadoPagoPlanService)
7. Atualiza status da assinatura (PlanService)
8. Envia notificação por email (NotificationService)
9. Registra atividades (ActivityService)
10. Retorna resposta de sucesso
```

#### Diferenças do Invoice:
- Usa credenciais globais do sistema
- Atualiza `plan_subscriptions` ao invés de `invoices`
- External reference contém dados do plano

---

### 3. `getPaymentInfo($payment_id)` 🔒 Private
**Função:** Busca detalhes do pagamento na API do Mercado Pago

```php
private function getPaymentInfo($payment_id)
{
    $this->authenticate();
    $client = new PaymentClient();
    return $client->get($payment_id);
}
```

**Retorno:** Objeto `Payment` do SDK do Mercado Pago

---

### 4. `authenticate()` 🔒 Protected
**Função:** Autentica com o Mercado Pago usando credenciais globais

```php
protected function authenticate(): void
{
    $mpAccessToken = env('MERCADO_PAGO_ACCESS_TOKEN');
    
    if (env('APP_ENV') === 'development') {
        MercadoPagoConfig::setRuntimeEnviroment(MercadoPagoConfig::LOCAL);
    } else {
        MercadoPagoConfig::setRuntimeEnviroment(MercadoPagoConfig::SERVER);
    }
    
    MercadoPagoConfig::setAccessToken($mpAccessToken);
}
```

**Nota:** Usa credenciais GLOBAIS do sistema (não do provider)

---

### 5. `getResponsePaymentPlan($payment_id)` 🔒 Private
**Função:** Extrai dados do pagamento de plano

```php
private function getResponsePaymentPlan($payment_id): array
{
    $get = $this->getPaymentInfo($payment_id);
    $externalReference = html_entity_decode($get->external_reference ?? '');
    $externalReferenceData = json_decode($externalReference, true);
    
    return [
        'payment_id' => $get->id,
        'status' => $get->status,
        'payment_method' => $get->payment_method_id,
        'user_id' => $externalReferenceData['user_id'] ?? null,
        'provider_id' => $externalReferenceData['provider_id'] ?? null,
        'tenant_id' => $externalReferenceData['tenant_id'] ?? null,
        'plan_id' => $externalReferenceData['plan_id'] ?? null,
        'plan_name' => $externalReferenceData['plan_name'] ?? null,
        'plan_slug' => $externalReferenceData['plan_slug'] ?? null,
        'plan_price' => $externalReferenceData['plan_price'] ?? null,
        'plan_subscription_id' => $externalReferenceData['plan_subscription_id'] ?? null,
        'last_plan_subscription_id' => $externalReferenceData['last_plan_subscription_id'] ?? null,
        'transaction_amount' => $get->transaction_amount,
        'transaction_date' => convertToDateTime($get->date_last_updated),
    ];
}
```

---

### 6. `getResponsePaymentInvoice($payment_id)` 🔒 Private
**Função:** Extrai dados do pagamento de fatura

```php
private function getResponsePaymentInvoice($payment_id): array
{
    $get = $this->getPaymentInfo($payment_id);
    $externalReference = html_entity_decode($get->external_reference ?? '');
    $externalReferenceData = json_decode($externalReference, true);
    
    return [
        'payment_id' => $get->id,
        'status' => $get->status,
        'payment_method' => $get->payment_method_id,
        'user_id' => $externalReferenceData['user_id'] ?? null,
        'invoice_id' => $externalReferenceData['invoice_id'],
        'tenant_id' => $externalReferenceData['tenant_id'],
        'customer_id' => $externalReferenceData['customer_id'],
        'service_id' => $externalReferenceData['service_id'],
        'public_hash' => $externalReferenceData['public_hash'],
        'code' => $externalReferenceData['invoice_code'],
        'transaction_amount' => $get->transaction_amount,
        'transaction_date' => convertToDateTime($get->date_last_updated),
    ];
}
```

---

## 🔐 Sistema de Validação de Autenticidade

### Função: `validateMercadoPagoAuthenticity($data)`
**Localização:** `old-system/app/helpers/functions.php`

```php
function validateMercadoPagoAuthenticity($data): bool
{
    // 1. Extrai headers
    $headers = getallheaders();
    $xSignature = $headers['X-Signature'] ?? $headers['x-signature'];
    $xRequestId = $headers['X-Request-Id'] ?? $headers['x-request-id'];
    
    // 2. Parse da assinatura (formato: ts=123456,v1=hash)
    $parts = explode(',', $xSignature);
    $ts = null;
    $hash = null;
    foreach ($parts as $part) {
        $keyValue = explode('=', $part, 2);
        if ($key === "ts") $ts = $value;
        elseif ($key === "v1") $hash = $value;
    }
    
    // 3. Extrai ID do pagamento
    $dataId = $data['data.id'] ?? $data['data_id'] ?? $data['id'];
    
    // 4. Constrói manifest
    $manifest = "id:{$dataId};request-id:{$xRequestId};ts:{$ts};";
    
    // 5. Calcula hash HMAC-SHA256
    $calculatedHash = hash_hmac('sha256', $manifest, env('MERCADO_PAGO_WEBHOOK_SECRET'));
    
    // 6. Compara hashes
    return hash_equals($calculatedHash, $hash);
}
```

**Segurança:**
- ✅ Usa HMAC-SHA256
- ✅ Compara com `hash_equals()` (timing-safe)
- ✅ Valida timestamp e request ID
- ✅ Requer secret configurado

---

## 📦 Serviços Relacionados

### PaymentMercadoPagoInvoiceService

#### Método: `createOrUpdatePayment(array $webhookPaymentData)`
**Função:** Cria ou atualiza registro de pagamento de fatura

**Lógica de Idempotência:**
```php
1. Busca pagamentos ativos da fatura (pending, authorized, in_process, approved)
2. Se existe pagamento ativo DIFERENTE: retorna sem fazer nada
3. Busca pagamento específico por payment_id
4. Se não existe: CRIA novo registro
5. Se existe com mesmo status: retorna sem alterar
6. Se existe com status diferente: ATUALIZA
```

**Status Bloqueantes:**
- `pending`
- `authorized`
- `in_process`
- `in_mediation`
- `approved`

**Transação:** ✅ Sim (usa `$connection->transactional()`)

---

### PaymentMercadoPagoPlanService

#### Método: `createOrUpdateFromWebhook(array $webhookPaymentData)`
**Função:** Cria ou atualiza registro de pagamento de plano

**Lógica:** Idêntica ao de faturas, mas para planos

**Diferenças:**
- Busca por `plan_subscription_id`
- Valida por `provider_id` e `tenant_id`

---

## 🔄 Fluxos de Negócio Completos

### Fluxo 1: Webhook de Pagamento de Fatura

```
┌─────────────────────────────────────────────────────────────┐
│ 1. Mercado Pago envia webhook                               │
│    POST /webhooks/mercadopago/invoices                      │
│    Headers: X-Request-Id, X-Signature                       │
│    Body: { type: "payment", data: { id: "123" } }          │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 2. WebhookController::handleMercadoPagoInvoice()           │
│    - Valida X-Request-Id                                    │
│    - Valida topic = "payment"                               │
│    - Valida autenticidade (X-Signature)                     │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 3. Busca detalhes na API do Mercado Pago                   │
│    - getPaymentInfo(payment_id)                             │
│    - Extrai external_reference (JSON)                       │
│    - Monta array com dados do pagamento                     │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 4. PaymentMercadoPagoInvoiceService                        │
│    - createOrUpdatePayment()                                │
│    - Valida pagamentos ativos                               │
│    - Cria ou atualiza registro                              │
│    - Retorna flag: invoicePaymentAlreadyExists              │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 5. InvoiceService::updateInvoice()                         │
│    - Atualiza status da fatura                              │
│    - Mapeia status MP → InvoiceStatus                       │
│    - Retorna flag: invoiceAlreadyExists                     │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 6. NotificationService (se não processado antes)            │
│    - Busca provider e customer                              │
│    - sendInvoiceStatusUpdate()                              │
│    - Envia email de notificação                             │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 7. ActivityService (se não processado antes)                │
│    - Registra payment_mercado_pago_invoice_created          │
│    - Registra invoice_updated                               │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 8. Retorna HTTP 200 para o Mercado Pago                    │
│    { message: "Webhook processado com sucesso" }            │
└─────────────────────────────────────────────────────────────┘
```

---

### Fluxo 2: Webhook de Pagamento de Plano

```
┌─────────────────────────────────────────────────────────────┐
│ 1. Mercado Pago envia webhook                               │
│    POST /webhooks/mercadopago/plans                         │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 2. WebhookController::handleMercadoPagoPlan()              │
│    - Validações idênticas ao de fatura                      │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 3. PaymentMercadoPagoPlanService                           │
│    - createOrUpdateFromWebhook()                            │
│    - Lógica idempotente                                     │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 4. PlanService::updatePlanSubscription()                   │
│    - Atualiza status da assinatura                          │
│    - Mapeia status MP → PlanSubscriptionStatus              │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 5. NotificationService                                      │
│    - sendPlanSubscriptionStatusUpdate()                     │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 6. ActivityService                                          │
│    - payment_mercado_pago_plans_created                     │
│    - plan_subscription_updated                              │
└─────────────────────────────────────────────────────────────┘
```

---

## 📊 Mapeamento de Status

### Função: `mapPaymentStatusMercadoPago()`

```php
'approved'     => PaymentStatusMercadoPagoEnum::approved
'pending'      => PaymentStatusMercadoPagoEnum::pending
'authorized'   => PaymentStatusMercadoPagoEnum::authorized
'in_process'   => PaymentStatusMercadoPagoEnum::in_process
'in_mediation' => PaymentStatusMercadoPagoEnum::in_mediation
'rejected'     => PaymentStatusMercadoPagoEnum::rejected
'cancelled'    => PaymentStatusMercadoPagoEnum::cancelled
'refunded'     => PaymentStatusMercadoPagoEnum::refunded
'charged_back' => PaymentStatusMercadoPagoEnum::charged_back
'recovered'    => PaymentStatusMercadoPagoEnum::recovered
default        => PaymentStatusMercadoPagoEnum::pending
```

### Função: `mapPaymentStatusToInvoiceStatus()`

```php
'approved', 'recovered' → InvoiceStatusEnum::paid
'pending', 'authorized', 'in_process', 'in_mediation' → InvoiceStatusEnum::pending
'rejected', 'cancelled', 'refunded', 'charged_back' → InvoiceStatusEnum::cancelled
```

### Função: `mapPaymentStatusToPlanSubscriptionsStatus()`

```php
'approved', 'recovered' → PlanSubscriptionsStatusEnum::active
'pending', 'authorized', 'in_process', 'in_mediation' → PlanSubscriptionsStatusEnum::pending
'rejected', 'cancelled', 'refunded', 'charged_back' → PlanSubscriptionsStatusEnum::cancelled
```

---

## ⚠️ Pontos Críticos

### 1. Processamento Síncrono
**Problema:** Webhook processado em tempo real  
**Risco:** Timeout se operação demorar  
**Solução Laravel:** Usar Jobs/Queues

### 2. Validação de Autenticidade
**Implementado:** ✅ Sim  
**Método:** HMAC-SHA256 com secret  
**Headers:** X-Signature, X-Request-Id

### 3. Idempotência
**Implementado:** ✅ Sim  
**Método:** Valida pagamentos ativos antes de criar  
**Flags:** `invoicePaymentAlreadyExists`, `planPaymentAlreadyExists`

### 4. Notificações Duplicadas
**Prevenção:** ✅ Sim  
**Método:** Só envia email se flags = false

### 5. External Reference (JSON)
**Formato:** String JSON com dados contextuais  
**Decodificação:** `html_entity_decode()` + `json_decode()`

### 6. Transações de Banco
**Implementado:** ✅ Sim  
**Método:** `$connection->transactional()`

---

## 📝 Recomendações para Laravel 12

### 1. Controllers
```php
App\Http\Controllers\Webhooks\MercadoPagoWebhookController
├── handleInvoiceWebhook()
└── handlePlanWebhook()
```

### 2. Jobs (Processamento Assíncrono)
```php
App\Jobs\ProcessMercadoPagoWebhook
├── $tries = 3
├── $backoff = 60
├── handle()
└── failed()
```

### 3. Services
```php
App\Services\Infrastructure\Payment\
├── MercadoPagoWebhookService
│   ├── processInvoicePayment()
│   ├── processPlanPayment()
│   └── validateWebhookSignature()
├── MercadoPagoInvoicePaymentService
│   └── createOrUpdatePayment()
└── MercadoPagoPlanPaymentService
    └── createOrUpdatePayment()
```

### 4. Middleware
```php
App\Http\Middleware\ValidateMercadoPagoWebhook
├── Valida X-Request-Id
├── Valida X-Signature
└── Valida estrutura do payload
```

### 5. Events & Listeners
```php
Events:
├── InvoicePaymentReceived
└── PlanPaymentReceived

Listeners:
├── UpdateInvoiceStatus
├── UpdatePlanSubscriptionStatus
├── SendPaymentNotification
└── LogPaymentActivity
```

### 6. Enums
```php
App\Enums\
├── PaymentStatusMercadoPago (já existe?)
├── InvoiceStatus (já existe)
└── PlanSubscriptionStatus (já existe)
```

---

## ✅ Checklist de Implementação

### Estrutura Base
- [ ] Criar MercadoPagoWebhookController
- [ ] Criar ProcessMercadoPagoWebhook Job
- [ ] Criar MercadoPagoWebhookService
- [ ] Criar ValidateMercadoPagoWebhook Middleware

### Validação e Segurança
- [ ] Implementar validação de assinatura (X-Signature)
- [ ] Implementar validação de X-Request-Id
- [ ] Adicionar rate limiting para webhooks
- [ ] Configurar MERCADO_PAGO_WEBHOOK_SECRET no .env

### Processamento de Pagamentos
- [ ] Implementar processamento de faturas
- [ ] Implementar processamento de planos
- [ ] Implementar lógica de idempotência
- [ ] Implementar mapeamento de status

### Notificações e Logs
- [ ] Criar eventos de pagamento
- [ ] Criar listeners de notificação
- [ ] Implementar registro de atividades
- [ ] Configurar logs estruturados

### Testes
- [ ] Testes unitários do WebhookService
- [ ] Testes de integração do Job
- [ ] Testes de validação de assinatura
- [ ] Testes de idempotência
- [ ] Testes de mapeamento de status

### Rotas
- [ ] POST /webhooks/mercadopago/invoices
- [ ] POST /webhooks/mercadopago/plans
- [ ] Desabilitar CSRF para rotas de webhook
- [ ] Aplicar middleware de validação

---

## 🔧 Configurações Necessárias

### .env
```env
MERCADO_PAGO_ACCESS_TOKEN=your_access_token
MERCADO_PAGO_PUBLIC_KEY=your_public_key
MERCADO_PAGO_WEBHOOK_SECRET=your_webhook_secret
```

### routes/api.php
```php
Route::post('/webhooks/mercadopago/invoices', [MercadoPagoWebhookController::class, 'handleInvoiceWebhook'])
    ->middleware('validate.mercadopago.webhook')
    ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

Route::post('/webhooks/mercadopago/plans', [MercadoPagoWebhookController::class, 'handlePlanWebhook'])
    ->middleware('validate.mercadopago.webhook')
    ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
```

---

## 🐛 Melhorias Identificadas

### 1. Processamento Assíncrono ⭐⭐⭐
**Atual:** Síncrono  
**Proposta:** Jobs com retry e backoff  
**Benefício:** Evita timeout, melhor confiabilidade

### 2. Validação de Timestamp ⭐⭐
**Atual:** Não valida idade do webhook  
**Proposta:** Rejeitar webhooks com timestamp > 5 minutos  
**Benefício:** Previne replay attacks

### 3. Logs Estruturados ⭐⭐
**Atual:** Logs básicos  
**Proposta:** Logs com contexto completo (payment_id, tenant_id, etc)  
**Benefício:** Melhor debugging e monitoramento

### 4. Webhook Retry Tracking ⭐
**Atual:** Não rastreia tentativas  
**Proposta:** Tabela de webhook_logs com tentativas  
**Benefício:** Auditoria e troubleshooting

### 5. Dead Letter Queue ⭐
**Atual:** Falhas são apenas logadas  
**Proposta:** DLQ para webhooks que falharam 3x  
**Benefício:** Reprocessamento manual

---

**Fim do Relatório**
