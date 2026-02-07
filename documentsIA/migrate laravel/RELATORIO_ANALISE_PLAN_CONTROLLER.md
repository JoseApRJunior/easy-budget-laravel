# Relatório de Análise - PlanController (Sistema Antigo)

## 📋 Sumário Executivo

**Arquivo:** `old-system/app/controllers/PlanController.php`  
**Prioridade:** ⭐⭐  
**Complexidade:** Média  
**Status:** Parcialmente implementado

---

## 🎯 Visão Geral

### Responsabilidade
Gerenciar visualização e seleção de planos de assinatura:
- Listagem de planos disponíveis
- Redirecionamento para pagamento
- Cancelamento de assinatura pendente
- Verificação de status de pagamento

### Características
- ✅ Listagem de planos ativos
- ✅ Integração com PaymentController
- ✅ Cancelamento de assinatura pendente
- ✅ Verificação de status no Mercado Pago
- ✅ Páginas de status de pagamento

---

## 📦 Dependências (7 total)

```php
1. Twig - Template engine
2. Plan - Model de planos
3. PlanService - Lógica de negócio
4. PlanSubscription - Assinaturas
5. PaymentMercadoPagoPlans - Pagamentos MP
6. PaymentMercadoPagoPlanService - Serviço de pagamento
7. Request - HTTP Request
```

---

## 📊 Métodos (7 total)

### 1. `index()` ⭐⭐
**Rota:** GET `/plans`  
**View:** `pages/plan/index.twig`  
**Função:** Lista planos disponíveis

```php
public function index(): Response
{
    $plans = $this->plan->findActivePlans();
    $pendingPlan = $this->planSubscription->getProviderPlan(
        $provider_id, $tenant_id, 'pending'
    );
    Session::set("checkPlanPending", $pendingPlan);
    
    return render('pages/plan/index.twig', ['plans' => $plans]);
}
```

**Dados:**
- Planos ativos
- Assinatura pendente (se houver)

---

### 2. `redirectToPayment()` ⭐⭐⭐
**Rota:** POST `/plans/redirect-to-payment`  
**Função:** Processa seleção de plano e redireciona

#### Fluxo:
```
1. Valida requisição (PaymentPlanRequest)
2. Chama PlanService->createSubscription()
3. Registra atividade
4. Se plano FREE: ativa e redireciona para dashboard
5. Se plano PAGO: renderiza página de redirecionamento para MP
```

**Diferença do PaymentController:**
- Este controller apenas prepara e redireciona
- PaymentController cria a preferência no MP

---

### 3. `cancelPendingSubscription()` ⭐⭐⭐
**Rota:** POST `/plans/cancel-pending`  
**Função:** Cancela assinatura pendente

#### Fluxo Completo:
```
1. Busca assinatura pendente
2. Busca pagamento no Mercado Pago
3. Se pagamento está pendente/in_process:
   a) Cancela no Mercado Pago
   b) Atualiza status local para 'cancelled'
   c) Atualiza assinatura para 'cancelled'
4. Registra atividade
5. Redireciona com mensagem
```

**Status Canceláveis:**
- `pending`
- `authorized`
- `in_process`
- `in_mediation`

---

### 4. `status()` ⭐⭐
**Rota:** GET `/plans/status`  
**View:** `pages/plan/status.twig`  
**Função:** Verifica status de assinatura pendente

#### Lógica:
```php
1. Busca assinatura pendente
2. Busca pagamento local
3. Se não tem pagamento: simula status 'not_started'
4. Se tem pagamento: busca dados atualizados no MP
5. Renderiza página com status atual
```

**Dados Exibidos:**
- Assinatura pendente
- Status do pagamento no MP
- Opções de ação (pagar novamente, cancelar)

---

### 5. `paymentStatus()` ⭐⭐
**Rota:** GET `/plans/payment-status`  
**View:** `pages/public/plan/status.twig`  
**Função:** Página de retorno após pagamento

#### Status Possíveis:
```php
match ($status) {
    'approved' => [
        'status' => 'success',
        'message' => 'Pagamento Aprovado!',
        'details' => 'Seu pagamento foi processado com sucesso...'
    ],
    'pending', 'in_process' => [
        'status' => 'pending',
        'message' => 'Pagamento Pendente',
        'details' => 'Seu pagamento está sendo processado...'
    ],
    default => [ // failure, rejected, cancelled
        'status' => 'failure',
        'message' => 'Pagamento Recusado',
        'details' => 'Houve um problema...'
    ]
}
```

**Ação Especial:**
- Se `approved`: limpa sessão (checkPlan, last_updated_session_provider)

---

### 6. `error()` ⭐
**Rota:** GET `/plans/error`  
**View:** `pages/payment/error.twig`  
**Função:** Página de erro genérica

---

### 7. `handlePaymentError()` 🔒 Private
**Função:** Helper para renderizar erro com mensagem

---

## 🔄 Fluxo de Seleção de Plano

```
┌─────────────────────────────────────────────────────────────┐
│ 1. Provider acessa /plans                                   │
│    - Lista planos disponíveis                               │
│    - Verifica se tem assinatura pendente                    │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 2. Provider seleciona plano                                 │
│    POST /plans/redirect-to-payment                          │
│    { planSlug: "premium" }                                  │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 3. PlanService->createSubscription()                        │
│    - Valida regras de negócio                               │
│    - Cria assinatura com status PENDING                     │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 4. Se plano FREE                                            │
│    - Ativa imediatamente                                    │
│    - Redireciona para /provider                             │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 5. Se plano PAGO                                            │
│    - Renderiza página de redirecionamento                   │
│    - JavaScript redireciona para MP                         │
└─────────────────────────────────────────────────────────────┘
```

---

## 🔄 Fluxo de Cancelamento

```
┌─────────────────────────────────────────────────────────────┐
│ 1. Provider tem assinatura pendente                        │
│    - Exibido na página /plans                               │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 2. Provider clica em "Cancelar"                             │
│    POST /plans/cancel-pending                               │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 3. Busca assinatura pendente                                │
│    - Se não existe: retorna mensagem                        │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 4. Busca pagamento no Mercado Pago                          │
│    - Se status cancelável: cancela no MP                    │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 5. Atualiza status local                                    │
│    - Pagamento: cancelled                                   │
│    - Assinatura: cancelled                                  │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 6. Registra atividade                                       │
│    - plan_subscription_cancelled                            │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 7. Redireciona para /plans                                  │
│    - Mensagem de sucesso                                    │
└─────────────────────────────────────────────────────────────┘
```

---

## ⚠️ Pontos Críticos

### 1. Integração com PaymentController
**Observação:** PlanController e PaymentController têm responsabilidades sobrepostas  
**Recomendação:** Unificar em um único controller

### 2. Cancelamento no Mercado Pago
**Importante:** Cancela no MP antes de cancelar localmente  
**Fallback:** Se falhar no MP, apenas loga warning

### 3. Verificação de Status
**Método:** Busca dados atualizados diretamente na API do MP  
**Benefício:** Status sempre atualizado

### 4. Sessão de Plano Pendente
**Variável:** `checkPlanPending`  
**Uso:** Exibir banner de assinatura pendente

---

## 📝 Recomendações Laravel

### Controllers
```php
App\Http\Controllers\Provider\
└── PlanSubscriptionController
    ├── index() - Lista planos
    ├── store() - Seleciona plano
    ├── cancel() - Cancela pendente
    ├── status() - Verifica status
    └── paymentStatus() - Retorno MP
```

### Services
```php
App\Services\Domain\
└── PlanSubscriptionService
    ├── listAvailablePlans()
    ├── createSubscription()
    ├── cancelPendingSubscription()
    └── getSubscriptionStatus()
```

### Events
```php
Events:
├── PlanSubscriptionCreated
├── PlanSubscriptionCancelled
└── PlanSubscriptionActivated

Listeners:
├── SendSubscriptionNotification
└── ClearPlanCache
```

---

## ✅ Checklist de Implementação

- [ ] Criar PlanSubscriptionController
- [ ] Criar PlanSubscriptionService
- [ ] Implementar listagem de planos
- [ ] Implementar seleção de plano
- [ ] Implementar cancelamento
- [ ] Implementar verificação de status
- [ ] Implementar páginas de retorno
- [ ] Criar events e listeners
- [ ] Criar views
- [ ] Implementar testes

---

## 🐛 Melhorias Identificadas

### 1. Unificar com PaymentController ⭐⭐⭐
**Atual:** Dois controllers com responsabilidades sobrepostas  
**Proposta:** Um único PlanSubscriptionController  
**Benefício:** Menos duplicação

### 2. Usar Jobs para Cancelamento ⭐⭐
**Atual:** Cancelamento síncrono  
**Proposta:** Job assíncrono  
**Benefício:** Melhor UX

### 3. Webhook para Status ⭐⭐
**Atual:** Polling manual de status  
**Proposta:** Webhook atualiza automaticamente  
**Benefício:** Tempo real

### 4. Cache de Planos ⭐
**Proposta:** Cache de planos disponíveis  
**Benefício:** Performance

---

## 📊 Comparação com Sistema Atual

### Funcionalidades Comuns
- ✅ Listagem de planos
- ✅ Seleção de plano
- ✅ Integração com MP

### Funcionalidades Adicionais (Antigo)
- ✅ Cancelamento de pendente
- ✅ Verificação de status
- ✅ Páginas de retorno

### Melhorias no Novo Sistema
- ✅ Usar Jobs
- ✅ Usar Events
- ✅ Melhor separação de responsabilidades

---

**Fim do Relatório**
