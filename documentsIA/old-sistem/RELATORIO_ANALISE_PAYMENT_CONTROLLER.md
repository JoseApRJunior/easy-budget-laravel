# Relatório de Análise - PaymentController (Sistema Antigo)

## 📋 Sumário Executivo

Análise completa do `PaymentController` do sistema antigo para migração ao Laravel 12.

**Arquivo Analisado:** `old-system/app/controllers/PaymentController.php`  
**Data:** 2025  
**Objetivo:** Mapear funcionalidades de gestão de pagamentos e assinaturas de planos.

---

## 🎯 Visão Geral

### Responsabilidade Principal
Gerenciar o fluxo completo de pagamento de assinaturas de planos via Mercado Pago:
1. **Criação de Assinaturas** - Validação e criação de preferências de pagamento
2. **Processamento de Webhooks** - Recebimento de notificações (Payment e Merchant Order)
3. **Páginas de Retorno** - Success, Pending, Failure
4. **Validações de Negócio** - Regras de upgrade/downgrade de planos

### Características Importantes
- ✅ Validação de planos (upgrade/downgrade)
- ✅ Integração com Mercado Pago SDK
- ✅ Processamento de webhooks (Payment + Merchant Order)
- ✅ Páginas de retorno personalizadas
- ✅ Registro de atividades
- ✅ Limpeza de sessão após pagamento

---

## 📦 Dependências Injetadas (4 total)

```php
1. Twig $twig - Template engine
2. Plan $plan - Model de planos
3. ProviderPlanService $providerPlanService - Lógica de negócio de planos
4. ActivityService $activityService - Logs de atividade
```

---

## 📊 Métodos do PaymentController (14 total)

### 1. `authenticate()` 🔒 Protected
**Função:** Autentica com Mercado Pago usando credenciais globais

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

---

### 2. `createSubscription($planSlug)` ⭐⭐⭐
**Função:** Cria assinatura de plano com validações de negócio

#### Validações de Negócio:
```php
1. Verifica se plano existe e está ativo
2. Verifica se usuário tem sessão válida
3. Valida se já não tem o mesmo plano ativo
4. Valida se plano atual não é superior ao selecionado
5. Valida data de expiração do plano atual
```

#### Fluxo:
```
1. Busca plano por slug
2. Valida plano existe
3. Pega plano atual da sessão
4. Valida regras de negócio
5. Cria nova assinatura (ProviderPlanService)
6. Registra atividade
7. Se plano FREE: redireciona para dashboard
8. Se plano PAGO: cria preferência MP e redireciona
```

#### Regras de Negócio:
- **Mesmo Plano Ativo:** Bloqueia se já tem o mesmo plano não expirado
- **Plano Superior:** Bloqueia downgrade se plano atual é superior e não expirado
- **Plano Free:** Ativa imediatamente sem pagamento
- **Plano Pago:** Cria preferência de pagamento no MP

---

### 3. `payment()` ⭐⭐
**Rota:** POST `/payment`  
**Função:** Processa requisição de pagamento

```php
public function payment(): Response
{
    // Valida requisição
    $validated = PaymentPlanRequest::validate($this->request);
    
    if (!$validated) {
        return Redirect::redirect('/payment/error')
            ->withMessage('message', 'Dados inválidos');
    }
    
    $data = $this->request->all();
    
    try {
        return $this->createSubscription($data['planSlug']);
    } catch (\Throwable $th) {
        getDetailedErrorInfo($th);
        return Redirect::redirect('/payment')
            ->withMessage('error', 'Falha no pagamento...');
    }
}
```

---

### 4. `handleWebhook()` ⭐⭐⭐ (DEPRECATED)
**Rota:** POST `/payment/webhook`  
**Função:** Processa webhooks do Mercado Pago

**⚠️ NOTA:** Este método está DEPRECATED. O sistema atual usa `WebhookController` separado.

#### Fluxo:
```
1. Valida X-Request-Id
2. Identifica tipo: merchant_order ou payment
3. Se merchant_order:
   - Busca dados do Merchant Order
   - Processa via ProviderPlanService
4. Se payment:
   - Valida autenticidade
   - Busca dados do Payment
   - Processa pagamento
   - Atualiza assinatura
5. Retorna resposta HTTP
```

**Tipos de Webhook:**
- `merchant_order` - Ordem de pagamento
- `payment` - Pagamento individual

---

### 5. `success()` ⭐⭐
**Rota:** GET `/payment/success`  
**View:** `pages/payment/success.twig`  
**Função:** Página de sucesso após pagamento aprovado

```php
public function success(): Response
{
    $externalReference = $this->request->get('external_reference');
    $payment_id = $this->request->get('payment_id');
    $plan_name = $externalReference['plan_name'];
    $plan_price = $externalReference['plan_price'];
    
    // Valida dados
    if ($payment_id == null || $plan_name == null || $plan_price == null) {
        return $this->error();
    }
    
    // Limpa sessão
    Session::remove('checkPlan');
    Session::remove('last_updated_session_provider');
    
    // Renderiza view
    return new Response($this->twig->env->render('pages/payment/success.twig', [
        'payment_id' => $payment_id,
        'plan_name' => $plan_name,
        'plan_price' => $plan_price
    ]));
}
```

**Ação Importante:** Limpa sessão do plano após sucesso

---

### 6. `pending()` ⭐
**Rota:** GET `/payment/pending`  
**View:** `pages/payment/pending.twig`  
**Função:** Página de pagamento pendente

**Lógica:** Idêntica ao `success()`, mas sem limpar sessão

---

### 7. `failure()` ⭐
**Rota:** GET `/payment/failure`  
**View:** `pages/payment/failure.twig`  
**Função:** Página de falha no pagamento

**Lógica:** Idêntica ao `success()`, mas sem limpar sessão

---

### 8. `error()` ⭐
**Rota:** GET `/payment/error`  
**View:** `pages/payment/error.twig`  
**Função:** Página genérica de erro

```php
public function error(): Response
{
    return new Response($this->twig->env->render('pages/payment/error.twig'));
}
```

---

### 9. `handlePaymentError($message)` 🔒 Private
**Função:** Helper para renderizar página de erro com mensagem

```php
private function handlePaymentError($message): Response
{
    return new Response($this->twig->env->render('pages/payment/error.twig', [
        'message' => 'Ocorreu um erro ao processar o pagamento...',
        'error_details' => $message
    ]));
}
```

---

### 10. `getPaymentInfo($paymentId)` 🔒 Private
**Função:** Busca detalhes do pagamento na API do MP

```php
private function getPaymentInfo($paymentId)
{
    $this->authenticate();
    $client = new \MercadoPago\Client\Payment\PaymentClient();
    return $client->get($paymentId);
}
```

---

### 11. `getMerchantOrder($merchantOrderId)` 🔒 Private
**Função:** Busca detalhes da ordem de pagamento na API do MP

```php
private function getMerchantOrder($merchantOrderId)
{
    $this->authenticate();
    $client = new \MercadoPago\Client\MerchantOrder\MerchantOrderClient();
    $payment = $client->get($merchantOrderId);
    
    if ($payment == null) {
        return false;
    }
    
    return $payment;
}
```

---

### 12. `getResponsePayment($paymentId)` 🔒 Private
**Função:** Extrai e formata dados do pagamento

```php
private function getResponsePayment($paymentId): array
{
    $get = $this->getPaymentInfo($paymentId);
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
        'transaction_date' => new \DateTime(),
    ];
}
```

---

### 13. `getResponseMerchantOrder($merchantOrderId)` 🔒 Private
**Função:** Extrai e formata dados da ordem de pagamento

```php
private function getResponseMerchantOrder($merchantOrderId): array
{
    $get = $this->getMerchantOrder($merchantOrderId);
    $externalReference = html_entity_decode($get->external_reference ?? '');
    $externalReferenceData = json_decode($externalReference, true);
    
    return [
        'merchant' => $get,
        'merchant_order_id' => $get->id,
        'status' => $get->status,
        'order_status' => $get->order_status,
        'paid_amount' => $get->paid_amount,
        // ... external reference data
        'transaction_date' => convertDateLocale($get->date_created, 'America/Sao_Paulo')
    ];
}
```

---

### 14. `buildPreferenceData()` 🔒 Private ⭐⭐⭐
**Função:** Constrói dados da preferência de pagamento para o MP

```php
private function buildPreferenceData(
    PlanEntity $planSelected, 
    string $planSlug, 
    int $plan_subscription_id
): array {
    $checkPlan = Session::get('checkPlan');
    
    $externalReference = json_encode([
        'plan_id' => $planSelected->id,
        'plan_name' => $planSelected->name,
        'plan_slug' => $planSlug,
        'plan_price' => $planSelected->price,
        'user_id' => $this->authenticated->user_id,
        'provider_id' => $this->authenticated->id,
        'tenant_id' => $this->authenticated->tenant_id,
        'plan_subscription_id' => $plan_subscription_id,
        'last_plan_subscription_id' => $checkPlan->id,
    ]);
    
    $webhookUrl = buildUrl('/payment/webhook', true);
    
    return [
        'items' => [[
            'title' => sprintf('Assinatura do Plano %s', ucfirst($planSlug)),
            'quantity' => 1,
            'currency_id' => 'BRL',
            'unit_price' => (float) $planSelected->price,
            'description' => 'Assinatura recorrente mensal'
        ]],
        'payer' => [
            'first_name' => $this->authenticated->first_name,
            'last_name' => $this->authenticated->last_name,
            'email' => $this->authenticated->email,
        ],
        'payment_methods' => [
            "excluded_payment_methods" => [],
            "installments" => 12,
            "default_installments" => 1
        ],
        'external_reference' => $externalReference,
        'back_urls' => [
            'success' => buildUrl('/payment/success', true),
            'failure' => buildUrl('/payment/failure', true),
            'pending' => buildUrl('/payment/pending', true)
        ],
        'auto_return' => 'approved',
        'notification_url' => $webhookUrl,
        'notification_topics' => ['payment', 'merchant_order']
    ];
}
```

**Campos Importantes:**
- `external_reference` - JSON com contexto da assinatura
- `back_urls` - URLs de retorno após pagamento
- `notification_url` - URL do webhook
- `notification_topics` - Tipos de notificação (payment + merchant_order)

---

## 🔄 Fluxos de Negócio

### Fluxo 1: Criação de Assinatura de Plano Pago

```
┌─────────────────────────────────────────────────────────────┐
│ 1. Usuário seleciona plano                                  │
│    POST /payment { planSlug: "premium" }                    │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 2. PaymentController::payment()                             │
│    - Valida requisição (PaymentPlanRequest)                 │
│    - Chama createSubscription()                             │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 3. createSubscription($planSlug)                            │
│    - Busca plano por slug                                   │
│    - Valida plano existe e está ativo                       │
│    - Pega plano atual da sessão                             │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 4. Validações de Negócio                                    │
│    ✓ Não é o mesmo plano ativo                              │
│    ✓ Não é downgrade de plano superior não expirado         │
│    ✓ Sessão válida                                          │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 5. ProviderPlanService::createPlanSubscription()           │
│    - Cria registro de assinatura com status PENDING         │
│    - Retorna plan_subscription_id                           │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 6. ActivityService::logActivity()                          │
│    - Registra: plan_subscription_create                     │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 7. Cria Preferência de Pagamento                           │
│    - authenticate() com MP                                   │
│    - buildPreferenceData()                                  │
│    - PreferenceClient::create()                             │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 8. Renderiza página de redirecionamento                    │
│    View: pages/payment/redirect.twig                        │
│    Data: { payment_url: $preference->init_point }           │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 9. Usuário é redirecionado para Mercado Pago               │
│    URL: $preference->init_point                             │
└─────────────────────────────────────────────────────────────┘
```

---

### Fluxo 2: Criação de Assinatura de Plano Free

```
┌─────────────────────────────────────────────────────────────┐
│ 1. Usuário seleciona plano FREE                            │
│    POST /payment { planSlug: "free" }                       │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 2. createSubscription("free")                               │
│    - Validações de negócio                                  │
│    - Cria assinatura com status ACTIVE                      │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 3. Limpa sessão                                             │
│    - Session::remove('checkPlan')                           │
│    - Session::remove('last_updated_session_provider')       │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 4. Redireciona para dashboard                               │
│    Redirect: /provider                                      │
│    Message: "Plano atualizado com sucesso..."               │
└─────────────────────────────────────────────────────────────┘
```

---

### Fluxo 3: Retorno após Pagamento (Success)

```
┌─────────────────────────────────────────────────────────────┐
│ 1. Mercado Pago redireciona usuário                        │
│    GET /payment/success?payment_id=123&external_reference={}│
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 2. PaymentController::success()                             │
│    - Extrai payment_id                                      │
│    - Extrai external_reference (JSON)                       │
│    - Valida dados obrigatórios                              │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 3. Limpa sessão do plano                                    │
│    - Session::remove('checkPlan')                           │
│    - Session::remove('last_updated_session_provider')       │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 4. Renderiza página de sucesso                             │
│    View: pages/payment/success.twig                         │
│    Data: { payment_id, plan_name, plan_price }             │
└─────────────────────────────────────────────────────────────┘
```

**Nota:** O webhook processa o pagamento em paralelo e atualiza o status da assinatura.

---

## ⚠️ Pontos Críticos

### 1. Validação de Planos
**Regras Complexas:**
- Mesmo plano ativo não expirado → Bloqueia
- Downgrade de plano superior não expirado → Bloqueia
- Plano free → Ativa imediatamente
- Plano pago → Cria preferência MP

### 2. External Reference
**Formato:** JSON string com contexto completo
```json
{
  "plan_id": 1,
  "plan_name": "Premium",
  "plan_slug": "premium",
  "plan_price": 99.90,
  "user_id": 123,
  "provider_id": 456,
  "tenant_id": 789,
  "plan_subscription_id": 1011,
  "last_plan_subscription_id": 1010
}
```

### 3. Limpeza de Sessão
**Importante:** Limpa sessão apenas no `success()`, não no `pending()` ou `failure()`

### 4. Webhook Deprecated
**Atenção:** O método `handleWebhook()` está deprecated. Sistema atual usa `WebhookController` separado.

### 5. Notification Topics
**Configuração:** Recebe notificações de `payment` E `merchant_order`

---

## 📝 Recomendações para Laravel 12

### 1. Controllers
```php
App\Http\Controllers\Provider\
├── PlanSubscriptionController
│   ├── index() - Lista planos disponíveis
│   ├── store() - Cria assinatura
│   ├── success() - Página de sucesso
│   ├── pending() - Página pendente
│   └── failure() - Página de falha
```

### 2. Form Requests
```php
App\Http\Requests\
└── PlanSubscriptionRequest
    ├── rules()
    └── messages()
```

### 3. Services
```php
App\Services\Domain\
├── PlanSubscriptionService
│   ├── validatePlanUpgrade()
│   ├── createSubscription()
│   └── activateFreePlan()
└── MercadoPagoPreferenceService
    ├── createPlanPreference()
    └── buildPreferenceData()
```

### 4. Policies
```php
App\Policies\PlanSubscriptionPolicy
├── create() - Pode criar assinatura?
├── upgrade() - Pode fazer upgrade?
└── downgrade() - Pode fazer downgrade?
```

### 5. Events & Listeners
```php
Events:
├── PlanSubscriptionCreated
└── PlanSubscriptionActivated

Listeners:
├── SendPlanSubscriptionNotification
└── LogPlanSubscriptionActivity
```

---

## ✅ Checklist de Implementação

### Estrutura Base
- [ ] Criar PlanSubscriptionController
- [ ] Criar PlanSubscriptionRequest
- [ ] Criar PlanSubscriptionService
- [ ] Criar MercadoPagoPreferenceService

### Validações de Negócio
- [ ] Implementar validação de plano ativo
- [ ] Implementar validação de upgrade/downgrade
- [ ] Implementar validação de expiração
- [ ] Implementar ativação automática de plano free

### Integração Mercado Pago
- [ ] Implementar criação de preferência
- [ ] Implementar external reference
- [ ] Configurar back_urls
- [ ] Configurar notification_url

### Páginas de Retorno
- [ ] Criar view de sucesso
- [ ] Criar view de pendente
- [ ] Criar view de falha
- [ ] Criar view de erro
- [ ] Criar view de redirecionamento

### Testes
- [ ] Testes de validação de planos
- [ ] Testes de criação de assinatura
- [ ] Testes de integração com MP
- [ ] Testes de páginas de retorno

---

## 🐛 Melhorias Identificadas

### 1. Separação de Responsabilidades ⭐⭐⭐
**Atual:** Controller faz muitas coisas  
**Proposta:** Separar em Services especializados  
**Benefício:** Melhor testabilidade e manutenção

### 2. Validações em Policy ⭐⭐
**Atual:** Validações no controller  
**Proposta:** Mover para PlanSubscriptionPolicy  
**Benefício:** Reutilização e centralização

### 3. Remover Webhook do Controller ⭐⭐⭐
**Atual:** handleWebhook() deprecated  
**Proposta:** Remover completamente  
**Benefício:** Código mais limpo

### 4. Usar Enums para Status ⭐⭐
**Atual:** Strings hardcoded  
**Proposta:** Usar PlanSubscriptionStatus enum  
**Benefício:** Type safety

### 5. Melhorar Tratamento de Erros ⭐
**Atual:** Try-catch genérico  
**Proposta:** Exceptions específicas  
**Benefício:** Melhor debugging

---

**Fim do Relatório**
