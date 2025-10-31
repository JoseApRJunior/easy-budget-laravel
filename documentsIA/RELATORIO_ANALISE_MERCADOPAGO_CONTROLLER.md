# Relatório de Análise - MercadoPago Integration (Sistema Antigo)

## 📋 Sumário Executivo

Análise completa da integração com Mercado Pago do sistema antigo para migração ao Laravel 12.

**Arquivos Analisados:**
- `old-system/app/controllers/MercadoPagoController.php`
- `old-system/app/database/services/PaymentMercadoPagoPlanService.php`
- `old-system/app/database/services/PaymentMercadoPagoInvoiceService.php`

**Data:** 2025  
**Objetivo:** Mapear integração completa com Mercado Pago para implementação no novo sistema.

---

## 🎯 Visão Geral

### Dois Fluxos de Pagamento

#### 1. Pagamento de Planos (Assinaturas)
- Provider paga plano do sistema
- Usa credenciais globais do sistema
- Webhook: `/webhooks/mercadopago/plans`

#### 2. Pagamento de Faturas (Clientes)
- Cliente paga fatura de serviço
- Usa credenciais do provider (OAuth)
- Webhook: `/webhooks/mercadopago/invoices`

---

## 📊 MercadoPagoController (3 métodos)

### 1. `index()` - Página de Integração
- **Rota:** GET `/provider/integrations/mercadopago`
- **View:** `pages/mercadopago/index.twig`
- **Dados:**
  - Status de conexão (isConnected)
  - URL de autorização OAuth
  - Public Key (se conectado)

### 2. `callback()` - Callback OAuth
- **Rota:** GET `/provider/integrations/mercadopago/callback`
- **Parâmetros:** code, state
- **Lógica:**
  1. Valida código de autorização
  2. Processa via `MercadoPagoService->handleCallback()`
  3. Salva credenciais criptografadas
- **Redirect:** `/provider/integrations/mercadopago`

### 3. `disconnect()` - Desconectar Conta
- **Rota:** POST `/provider/integrations/mercadopago/disconnect`
- **Lógica:**
  1. Remove credenciais do banco
  2. Desconecta conta OAuth
- **Redirect:** `/provider/integrations/mercadopago`

---

## 💳 PaymentMercadoPagoPlanService (Pagamento de Planos)

### Métodos Principais

#### 1. `createOrUpdateFromWebhook(array $webhookPaymentData)`
- **Função:** Processa notificações de webhook para planos
- **Transação:** Sim
- **Lógica:**
  1. Valida se já existe pagamento ativo (pending/approved)
  2. Se existe pagamento diferente em andamento: bloqueia
  3. Busca pagamento específico por payment_id
  4. Mapeia status do MP para enum local
  5. Se não existe: cria novo registro
  6. Se existe com mesmo status: retorna sem alterar
  7. Se existe com status diferente: atualiza
- **Retorno:** Array com status, message, data

#### 2. `getPaymentFromMercadoPagoAPI(string $paymentId)`
- **Função:** Busca detalhes do pagamento na API do MP
- **SDK:** PaymentClient
- **Retorno:** Payment object ou null

#### 3. `cancelPaymentOnMercadoPago(int $paymentId)`
- **Função:** Cancela pagamento in_process
- **SDK:** PaymentClient->cancel()
- **Retorno:** bool

#### 4. `refundPaymentOnMercadoPago(string $paymentId)`
- **Função:** Reembolsa pagamento aprovado
- **SDK:** PaymentRefundClient->refundTotal()
- **Retorno:** bool

#### 5. `updatePaymentStatus(string $paymentId, string $newStatus, int $tenantId)`
- **Função:** Atualiza status local do pagamento
- **Retorno:** Array com status, message

#### 6. `createMercadoPagoPreference($lastPlan, $newPlan, $plan_subscription_id)`
- **Função:** Cria preferência de pagamento para plano
- **SDK:** PreferenceClient
- **Lógica:**
  1. Autentica com credenciais globais
  2. Monta dados da preferência
  3. Cria preferência no MP
  4. Retorna init_point (link de pagamento)

---

## 💰 PaymentMercadoPagoInvoiceService (Pagamento de Faturas)

### Métodos Principais

#### 1. `createOrUpdatePayment(array $webhookPaymentData)`
- **Função:** Processa notificações de webhook para faturas
- **Transação:** Sim
- **Lógica:** Similar ao de planos
  1. Valida pagamentos ativos
  2. Busca pagamento específico
  3. Mapeia status
  4. Cria ou atualiza
- **Retorno:** Array com status, message, data

#### 2. `createMercadoPagoPreference(string $invoiceCode, int $tenantId)`
- **Função:** Cria preferência de pagamento para fatura
- **SDK:** PreferenceClient
- **Lógica:**
  1. Busca fatura completa
  2. Busca cliente vinculado
  3. Busca credenciais do provider
  4. Descriptografa access_token do provider
  5. Autentica com token do provider
  6. Monta dados da preferência
  7. Cria preferência no MP
  8. Retorna init_point

---

## 🔐 Sistema de Credenciais

### ProviderCredential (Campos)
```
id, payment_gateway, access_token_encrypted, refresh_token_encrypted,
public_key, user_id_gateway, expires_in, provider_id, tenant_id
```

### Fluxo OAuth
1. Provider clica em "Conectar Mercado Pago"
2. Redireciona para URL de autorização do MP
3. Provider autoriza aplicação
4. MP redireciona para callback com code
5. Sistema troca code por access_token
6. Sistema criptografa tokens
7. Sistema salva credenciais no banco

---

## 📦 Estrutura de Dados

### PaymentMercadoPagoPlans (Campos)
```
id, payment_id, tenant_id, provider_id, plan_subscription_id,
status, payment_method, transaction_amount, transaction_date
```

### PaymentMercadoPagoInvoices (Campos)
```
id, payment_id, tenant_id, invoice_id,
status, payment_method, transaction_amount, transaction_date
```

### Status Mapeados
```php
- pending → pending
- authorized → authorized
- in_process → in_process
- in_mediation → in_mediation
- approved → approved
- rejected → rejected
- cancelled → cancelled
- refunded → refunded
- charged_back → charged_back
```

---

## 🔄 Fluxos de Negócio

### Fluxo 1: Pagamento de Plano (Provider)
1. Provider seleciona novo plano
2. Sistema cria preferência com credenciais globais
3. Provider é redirecionado para checkout MP
4. Provider efetua pagamento
5. MP envia webhook para `/webhooks/mercadopago/plans`
6. Sistema processa webhook:
   - Valida pagamentos ativos
   - Cria/atualiza registro de pagamento
   - Atualiza plan_subscription
7. Sistema envia notificação

### Fluxo 2: Pagamento de Fatura (Cliente)
1. Cliente acessa fatura via link público
2. Cliente clica em "Pagar"
3. Sistema verifica credenciais do provider
4. Sistema cria preferência com token do provider
5. Cliente é redirecionado para checkout MP
6. Cliente efetua pagamento
7. MP envia webhook para `/webhooks/mercadopago/invoices`
8. Sistema processa webhook:
   - Valida pagamentos ativos
   - Cria/atualiza registro de pagamento
   - Atualiza invoice
9. Sistema envia notificação

### Fluxo 3: Conexão OAuth (Provider)
1. Provider acessa integração
2. Clica em "Conectar"
3. Redireciona para MP OAuth
4. Provider autoriza
5. MP redireciona com code
6. Sistema troca code por tokens
7. Sistema criptografa tokens
8. Sistema salva credenciais

---

## ⚠️ Pontos Críticos

### 1. Dois Sistemas de Autenticação
```php
// Planos: Credenciais globais do sistema
$mpAccessToken = env('MERCADO_PAGO_ACCESS_TOKEN');

// Faturas: Credenciais do provider (OAuth)
$mpAccessToken = $this->encryptionService->decrypt($credentials->access_token_encrypted);
```

### 2. Validação de Duplicatas
- Bloqueia criação se já existe pagamento ativo
- Permite atualização do mesmo payment_id
- Impede múltiplos pagamentos simultâneos

### 3. External Reference (JSON)
```php
// Planos
$externalReference = json_encode([
    'plan_id' => $planSelected->id,
    'user_id' => $authenticated->user_id,
    'provider_id' => $authenticated->id,
    'tenant_id' => $authenticated->tenant_id,
    'plan_subscription_id' => $plan_subscription_id
]);

// Faturas
$externalReference = json_encode([
    'invoice_id' => $invoice->id,
    'customer_id' => $invoice->customer_id,
    'service_id' => $invoice->service_id,
    'tenant_id' => $tenantId
]);
```

### 4. Webhook URLs
```php
// Planos
$webhookUrl = buildUrl('/webhooks/mercadopago/plans', true);

// Faturas
$webhookUrl = buildUrl('/webhooks/mercadopago/invoices', true);
```

### 5. Criptografia de Tokens
- Tokens OAuth são criptografados antes de salvar
- Descriptografados apenas quando necessário
- Usa EncryptionService

---

## 🔧 Operações Avançadas

### Cancelamento de Pagamento
```php
$client = new PaymentClient();
$payment = $client->cancel($paymentId);
```

### Reembolso de Pagamento
```php
$client = new PaymentRefundClient();
$refund = $client->refundTotal($paymentId);
```

### Consulta de Pagamento
```php
$client = new PaymentClient();
$payment = $client->get($paymentId);
```

---

## 📝 Recomendações Laravel

### Models
```php
ProviderCredential (belongsTo: Provider, Tenant)
PaymentMercadoPagoPlan (belongsTo: PlanSubscription, Provider, Tenant)
PaymentMercadoPagoInvoice (belongsTo: Invoice, Tenant)
```

### Controllers
```php
MercadoPagoController - Integração OAuth
MercadoPagoWebhookController - Processamento de webhooks
```

### Services
```php
MercadoPagoOAuthService - Gestão OAuth
MercadoPagoPlanPaymentService - Pagamentos de planos
MercadoPagoInvoicePaymentService - Pagamentos de faturas
MercadoPagoWebhookService - Processamento de webhooks
EncryptionService - Criptografia de tokens
```

### Jobs (Filas)
```php
ProcessMercadoPagoWebhook - Processar webhook assíncrono
SyncMercadoPagoPayment - Sincronizar status de pagamento
RefreshMercadoPagoToken - Renovar tokens OAuth
```

### Events & Listeners
```php
PlanPaymentReceived → UpdatePlanSubscription
InvoicePaymentReceived → UpdateInvoiceStatus
PaymentRefunded → ProcessRefund
```

---

## ✅ Checklist de Implementação

- [ ] Criar migrations (provider_credentials, payments)
- [ ] Criar models com relationships
- [ ] Criar MercadoPagoOAuthService
- [ ] Criar MercadoPagoPlanPaymentService
- [ ] Criar MercadoPagoInvoicePaymentService
- [ ] Criar MercadoPagoWebhookService
- [ ] Criar EncryptionService
- [ ] Criar MercadoPagoController
- [ ] Criar MercadoPagoWebhookController
- [ ] Implementar OAuth flow
- [ ] Implementar criação de preferências
- [ ] Implementar processamento de webhooks
- [ ] Implementar validação de duplicatas
- [ ] Implementar cancelamento
- [ ] Implementar reembolso
- [ ] Criar Jobs para processamento assíncrono
- [ ] Criar Events & Listeners
- [ ] Implementar testes
- [ ] Documentar fluxos

---

## 🐛 Melhorias Identificadas

### 1. Processamento Síncrono de Webhooks
**Problema:** Webhooks processados em tempo real
**Solução:** Usar filas para processamento assíncrono

### 2. Sem Retry de Webhooks
**Problema:** Se falhar, não tenta novamente
**Solução:** Implementar sistema de retry com backoff

### 3. Sem Validação de Assinatura
**Problema:** Não valida assinatura do webhook
**Solução:** Implementar validação de x-signature

### 4. Tokens OAuth Sem Renovação
**Problema:** Não renova tokens expirados automaticamente
**Solução:** Job para renovar tokens antes de expirar

### 5. Sem Logs Estruturados
**Problema:** Logs básicos
**Solução:** Implementar logs estruturados com contexto

---

**Fim do Relatório**
