# TODO - Módulo Customer (Easy Budget Laravel)

Status atual consolidado do módulo de Clientes após ajustes recentes.

## 1. Implementado

1. **Dashboard de Clientes como ponto de entrada**

   -  View criada: [`resources/views/pages/customer/dashboard.blade.php`](resources/views/pages/customer/dashboard.blade.php:1)
   -  Exibe:
      -  Total de clientes
      -  Clientes ativos
      -  Clientes inativos
      -  Taxa de atividade
      -  Lista de clientes recentes
      -  Atalhos para criar, listar e relatórios
   -  Conectado ao `CustomerService::getCustomerStats($tenantId)` via [`CustomerController::dashboard`](app/Http/Controllers/CustomerController.php:485).

2. **Navegação ajustada**

   -  Menu "Clientes" no layout autenticado agora aponta para o **Dashboard de Clientes**:
      -  Arquivo: [`resources/views/partials/shared/navigation.blade.php`](resources/views/partials/shared/navigation.blade.php:1)
      -  Padrão:
         -  "Dashboard" → `route('provider.dashboard')`
         -  "Clientes" → `route('provider.customers.dashboard')`
   -  Fluxo UX:
      -  Clientes → Dashboard Clientes → (Listar, Criar, Relatórios)

3. **Autocomplete multi-tenant**

   -  [`CustomerController::autocomplete`](app/Http/Controllers/CustomerController.php:459):
      -  Usa `tenant_id` explícito do usuário autenticado.
      -  Respeita isolamento por tenant.
      -  Retorna dados prontos para UI (nome + contatos).

4. **Views principais alinhadas**

   -  `index`, `create`, `edit`, `show` em:
      -  [`resources/views/pages/customer/index.blade.php`](resources/views/pages/customer/index.blade.php:1)
      -  [`resources/views/pages/customer/create.blade.php`](resources/views/pages/customer/create.blade.php:1)
      -  [`resources/views/pages/customer/edit.blade.php`](resources/views/pages/customer/edit.blade.php:1)
      -  [`resources/views/pages/customer/show.blade.php`](resources/views/pages/customer/show.blade.php:1)
   -  Todas:
      -  Utilizam CommonData, Contact, Address, etc., respeitando a arquitetura multi-tabela.
      -  Mantêm consistência com o novo fluxo (Dashboard como entrada).

5. **Auditoria via Observer**
   -  [`CustomerObserver`](app/Observers/CustomerObserver.php:10) registrado em [`AppServiceProvider::boot`](app/Providers/AppServiceProvider.php:80):
      -  Eventos: created, updated, deleted, restored.
      -  Registra em `AuditLog` com tenant, user, IP, user_agent, metadata.
   -  Serve como mecanismo oficial de auditoria do módulo Customer.

---

## 2. Pendências Prioritárias

### 2.1. CustomerPolicy (Autorização de Acesso)

**Objetivo:** Formalizar autorização do módulo Customer sem quebrar o que existe.

**Situação atual:**

-  Não há `AuthServiceProvider.php` nem `CustomerPolicy` definidos no projeto.
-  Autorização ocorre via `auth` + roles/middlewares genéricos.

**A fazer:**

1. Criar `app/Policies/CustomerPolicy.php` com regras mínimas:

   -  `viewAny(User $user)`
      -  Permite listar clientes do tenant do usuário (provider/admin).
   -  `view(User $user, Customer $customer)`
      -  Garante que o cliente pertence ao mesmo tenant.
   -  `create(User $user)`
      -  Permite apenas usuários autorizados (ex.: provider/admin do tenant).
   -  `update(User $user, Customer $customer)`
   -  `delete(User $user, Customer $customer)`
   -  `restore(User $user, Customer $customer)`

2. Quando existir `AuthServiceProvider` central:

   -  Registrar:
      -  `Customer::class => CustomerPolicy::class`

3. Adaptação gradual em controllers:
   -  Utilizar `$this->authorize()` nas ações sensíveis (show, update, delete) sem alterar contratos de rota.

**Observação:** Não criar `AuthServiceProvider` ad-hoc neste momento para evitar conflito com o bootstrap atual. A policy fica como passo planejado, alinhado ao padrão RBAC/multi-tenant da arquitetura.

---

### 2.2. Testes Automatizados - Módulo Customer

**Objetivo:** Garantir comportamento estável do fluxo completo (Dashboard, CRUD, autocomplete, auditoria).

#### Feature Tests (tests/Feature)

1. **CustomerDashboardTest**

   -  Cenários:
      -  Autenticado:
         -  GET `provider.customers.dashboard` → 200
         -  View correta
         -  Métricas básicas presentes.
      -  Não autenticado:
         -  Redireciona para login.

2. **CustomerNavigationTest**

   -  Verifica se:
      -  Link "Clientes" no navbar autenticado aponta para `provider.customers.dashboard`.
   -  Garante consistência da navegação global.

3. **CustomerAutocompleteTest**

   -  Cria clientes em dois tenants.
   -  Autentica usuário do tenant A.
   -  Chama endpoint de autocomplete.
   -  Asserta:
      -  Apenas clientes do tenant A.
      -  Formato JSON esperado (ex.: `id`, `text`/nome, contatos).

4. **CustomerCrudTest**
   -  Criação PF:
      -  Validação, persistência, relação com CommonData/Contact/Address.
   -  Criação PJ:
      -  CNPJ, Razão Social etc.
   -  Update:
      -  Atualiza dados multi-tabela corretamente.
   -  Delete/Restore (se aplicável):
      -  Respeita regras de negócio (ex.: vínculos).
   -  Sempre:
      -  Isolamento por tenant (não acessar dados de outro tenant).

#### Unit Tests (tests/Unit)

1. **CustomerObserverTest**

   -  Garante:
      -  Ao criar/atualizar/deletar/restaurar Customer:
         -  `AuditLog` é gravado corretamente (tenant_id, user_id, model_type, model_id, action, metadata).

2. **CustomerRepositoryTest**

   -  Testa:
      -  Filtros por search (nome/email/documento),
      -  Filtro por status,
      -  Contagem por tenant.
   -  Garante que todos os métodos respeitam tenant_id.

3. **CustomerServiceTest**
   -  Testa:
      -  Fluxos PF/PJ multi-tabela.
      -  Regras de unicidade (CPF/CNPJ/email) por tenant.
      -  `canDelete`/equivalentes, se definidos.

---

## 3. Regras e Restrições Mantidas

-  Não alterar rotas públicas já existentes.
-  Não quebrar autenticação ou middleware atual.
-  Não introduzir Events redundantes enquanto `CustomerObserver` já atende auditoria.
-  Introduzir `CustomerPolicy` apenas quando `AuthServiceProvider` estiver padronizado.
-  Todos os testes propostos são adicionados sem mudar o contrato atual, apenas validando o comportamento esperado.

---

## 4. Ordem Recomendada de Execução

1. Implementar testes Feature (Dashboard, Navegação, Autocomplete, CRUD).
2. Implementar testes Unit (Observer, Repository, Service).
3. Introduzir `CustomerPolicy` quando o provider de policies estiver definido no projeto.
4. Atualizar documentação/memory bank apontando:
   -  Dashboard Clientes como home do módulo.
   -  Auditoria via `CustomerObserver`.
   -  Cobertura de testes implementada.

Este arquivo serve como guia objetivo para as próximas ações no módulo Customer, garantindo continuidade da migração e alinhamento com a arquitetura Easy Budget Laravel.
