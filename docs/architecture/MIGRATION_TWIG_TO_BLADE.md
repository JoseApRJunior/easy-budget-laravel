# 🏗️ Arquitetura de Migração: Twig → Laravel Blade

## 📋 ÍNDICE

1. [Visão Geral](#visão-geral)
2. [Análise do Sistema Atual](#análise-do-sistema-atual)
3. [Mapeamento de Dependências](#mapeamento-de-dependências)
4. [Matriz de Complexidade](#matriz-de-complexidade)
5. [Roadmap de Migração em Fases](#roadmap-de-migração-em-fases)
6. [Padrões de Conversão](#padrões-de-conversão)
7. [Arquitetura de Diretórios Target](#arquitetura-de-diretórios-target)
8. [Especificações Técnicas por Fase](#especificações-técnicas-por-fase)
9. [Estratégia de Testes](#estratégia-de-testes)
10.   [Riscos e Mitigação](#riscos-e-mitigação)

---

## 1. VISÃO GERAL

### 1.1 Objetivo

Migrar 150+ templates Twig para Laravel Blade de forma incremental e segura, mantendo 100% da funcionalidade e melhorando a arquitetura frontend com TailwindCSS.

### 1.2 Escopo

-  **Total de Arquivos:** 150+ templates Twig
-  **Infraestrutura Atual:**
   -  ✅ Vite 7.0.4 (configurado)
   -  ✅ TailwindCSS 3.4.17 (instalado)
   -  ✅ Alpine.js 3.4.2 (disponível)
   -  ⚠️ Bootstrap 5.0.2 (via CDN - será substituído)
   -  ⚠️ jQuery 3.6.0 (via CDN - será removido gradualmente)

### 1.3 Princípios da Migração

1. **Incremental:** Migração por módulos, nunca quebrando funcionalidade
2. **Testável:** Cada fase com critérios de aceitação claros
3. **Modular:** Componentes reutilizáveis via Blade Components
4. **Moderno:** TailwindCSS + Alpine.js substituindo Bootstrap + jQuery
5. **Documentado:** Cada decisão arquitetural registrada

---

## 2. ANÁLISE DO SISTEMA ATUAL

### 2.1 Estrutura de Templates Mapeada

```
resources/views-old/
├── layouts/                    # 3 arquivos
│   ├── admin.twig             # Layout administrativo (extends layout.twig)
│   ├── layout.twig            # Layout principal do sistema
│   └── layout_pdf_base.twig   # Base para PDFs
│
├── macros/                     # 2 arquivos
│   ├── alerts.twig            # Macro de alertas (4 tipos)
│   └── utils.twig             # Macro de verificação de features
│
├── emails/                     # 16 arquivos
│   ├── forgot-password.twig
│   ├── new-user.twig
│   ├── notification-*.twig    # 9 notificações de status
│   └── invoice/*.twig         # 1 email
│
├── pages/                      # 100+ arquivos (25+ módulos)
│   ├── error/                 # 3 templates
│   ├── login/                 # 2 templates
│   ├── home/                  # 3 templates
│   ├── admin/                 # 20+ templates (dashboard, metrics, logs, etc)
│   ├── budget/                # 6 templates
│   ├── customer/              # 5 templates
│   ├── invoice/               # 5 templates + 5 payment
│   ├── product/               # 4 templates
│   ├── service/               # 6 templates
│   ├── report/                # 8 templates (4 PDFs)
│   ├── settings/              # 1 template
│   └── [outros 15+ módulos]
│
└── partials/                   # 30+ arquivos
    ├── components/            # 3 componentes
    │   ├── alerts.twig
    │   ├── breadcrumbs.twig
    │   └── table_paginator.twig
    ├── shared/                # 4 arquivos comuns
    │   ├── head.twig
    │   ├── header.twig
    │   ├── navigation.twig
    │   └── footer.twig
    ├── customer/              # 3 fieldsets
    ├── provider/              # 3 fieldsets
    └── settings/              # 15+ arquivos
        ├── header.twig
        ├── sidebar.twig
        ├── forms/
        ├── profile/
        └── tabs/
```

### 2.2 Assets Identificados

```
resources/assets-old/
├── css/
│   ├── layout.css             # Estilos principais
│   ├── base/variables.css     # Variáveis CSS customizadas
│   └── components/alerts.css  # Estilos de alertas
│
├── js/                        # 25+ arquivos JavaScript
│   ├── main.js                # Script principal
│   ├── alert/alert.js         # Sistema de alertas
│   ├── modules/               # 15+ módulos utilitários
│   │   ├── auth.js
│   │   ├── cep-service.js
│   │   ├── form-validation.js
│   │   ├── moneyFormatter.js
│   │   ├── masks/             # Sistema de máscaras
│   │   └── utils.js
│   └── [específicos por página]
│
└── img/                       # 20+ imagens
    ├── logo.png
    ├── default-avatar.png
    └── [banners, screenshots]
```

### 2.3 Dependências Externas Atuais

**Via CDN (a serem removidas):**

```html
<!-- Bootstrap 5.0.2 -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/..." />
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/..."></script>

<!-- jQuery 3.6.0 -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Bootstrap Icons -->
<link
   rel="stylesheet"
   href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/..."
/>
```

**NPM (já instaladas):**

```json
{
   "vite": "^7.0.4",
   "tailwindcss": "^3.4.17",
   "alpinejs": "^3.4.2",
   "@tailwindcss/forms": "^0.5.9"
}
```

---

## 3. MAPEAMENTO DE DEPENDÊNCIAS

### 3.1 Grafo de Dependências Core

```mermaid
graph TD
    A[layout.twig] --> B[partials/shared/head.twig]
    A --> C[partials/shared/header.twig]
    A --> D[partials/components/alerts.twig]
    A --> E[partials/shared/footer.twig]

    C --> F[partials/shared/navigation.twig]

    G[layouts/admin.twig] --> A

    H[macros/alerts.twig] --> D
    I[macros/utils.twig] --> ALL_PAGES

    ALL_PAGES --> A
    ADMIN_PAGES --> G
```

### 3.2 Dependências Críticas

**Templates Base (Prioridade Máxima):**

-  `layout.twig` → Usado por 100+ páginas
-  `layouts/admin.twig` → Usado por 20+ páginas admin
-  `partials/shared/head.twig` → Configurações, meta tags, assets
-  `partials/shared/header.twig` → Navegação principal
-  `partials/shared/navigation.twig` → Menu do sistema

**Componentes Reutilizáveis:**

-  `macros/alerts.twig` → Usado em todos os módulos
-  `partials/components/alerts.twig` → Sistema de flash messages
-  `partials/components/breadcrumbs.twig` → Navegação secundária
-  `partials/components/table_paginator.twig` → Paginação de tabelas

**Módulos Independentes (Podem ser migrados em paralelo):**

-  Error pages (`pages/error/*`)
-  Login (`pages/login/*`)
-  Emails (`emails/*`)

### 3.3 Interdependências Complexas

**Budget ↔ Service ↔ Customer:**

```mermaid
graph LR
    A[pages/budget/*] -.-> B[pages/service/*]
    B -.-> C[pages/customer/*]
    A -.-> C
    A --> D[partials/customer/*]
    B --> D
```

**Reports → Múltiplos Módulos:**

```mermaid
graph TD
    A[pages/report/*] --> B[Budget Data]
    A --> C[Customer Data]
    A --> D[Service Data]
    A --> E[Product Data]
    A --> F[PDF Generation]
```

---

## 4. MATRIZ DE COMPLEXIDADE

### 4.1 Categorização por Complexidade

| Módulo              | Arquivos | Complexidade  | Dependências            | Prioridade | Estimativa |
| ------------------- | -------- | ------------- | ----------------------- | ---------- | ---------- |
| **Error Pages**     | 3        | 🟢 Baixa      | 1 (layout)              | 🔴 Crítica | 2h         |
| **Login**           | 2        | 🟢 Baixa      | 1 (layout)              | 🔴 Crítica | 3h         |
| **Layout Base**     | 1        | 🟡 Média      | 4 (partials)            | 🔴 Crítica | 6h         |
| **Layouts Admin**   | 1        | 🟡 Média      | 2 (layout + breadcrumb) | 🔴 Crítica | 4h         |
| **Shared Partials** | 4        | 🟡 Média      | 2 (macros)              | 🔴 Crítica | 8h         |
| **Components**      | 3        | 🟢 Baixa      | 1 (macros)              | 🟡 Alta    | 6h         |
| **Macros**          | 2        | 🟡 Média      | 0                       | 🔴 Crítica | 4h         |
| **Dashboard**       | 2        | 🟡 Média      | 3                       | 🟡 Alta    | 8h         |
| **Settings**        | 16       | 🔴 Alta       | 5 (tabs, forms)         | 🟡 Alta    | 16h        |
| **Customer**        | 5        | 🟡 Média      | 3 (partials)            | 🟢 Média   | 12h        |
| **Budget**          | 6        | 🔴 Alta       | 5 (service, customer)   | 🟢 Média   | 20h        |
| **Service**         | 6        | 🔴 Alta       | 4 (budget, customer)    | 🟢 Média   | 18h        |
| **Invoice**         | 10       | 🔴 Alta       | 4 (budget, payment)     | 🟢 Média   | 24h        |
| **Product**         | 4        | 🟡 Média      | 2                       | 🟢 Média   | 10h        |
| **Reports**         | 8        | 🔴 Muito Alta | 6 (PDFs + dados)        | 🟢 Baixa   | 32h        |
| **Emails**          | 16       | 🟡 Média      | 2 (layouts)             | 🟢 Baixa   | 16h        |
| **Admin Avançado**  | 20+      | 🔴 Muito Alta | 8+ (metrics, logs)      | 🟢 Baixa   | 40h        |

**TOTAL ESTIMADO:** ~230 horas (~6 semanas de desenvolvimento)

### 4.2 Fatores de Complexidade

**🟢 Baixa Complexidade:**

-  Templates simples, poucos dados dinâmicos
-  Sem dependências complexas
-  Lógica business mínima no template

**🟡 Média Complexidade:**

-  Formulários com validação
-  Múltiplas condicionais e loops
-  2-4 dependências de outros templates
-  Interação com JavaScript moderada

**🔴 Alta Complexidade:**

-  Lógica de negócio complexa no template
-  Múltiplas dependências interdependentes
-  Integração com APIs externas
-  Geração dinâmica de componentes

**🔴 Muito Alta Complexidade:**

-  Geração de PDFs
-  Dashboards com métricas em tempo real
-  Sistemas de notificação complexos
-  Relatórios com agregações de dados

---

## 5. ROADMAP DE MIGRAÇÃO EM FASES

### 🎯 FASE 1: FUNDAÇÃO (Semana 1-2) - 33h

**Objetivo:** Estabelecer infraestrutura base e migrar templates críticos independentes.

#### 1.1 Configuração Base (8h)

-  [ ] Configurar estrutura de diretórios `/resources/views/`
-  [ ] Criar componentes Blade base (Button, Card, Badge, etc)
-  [ ] Migrar CSS variables para TailwindCSS config
-  [ ] Configurar Alpine.js e remover dependências jQuery críticas
-  [ ] Criar sistema de Design Tokens

**Entregáveis:**

```
resources/views/
├── layouts/
│   └── base.blade.php (estrutura inicial)
├── components/
│   ├── button.blade.php
│   ├── card.blade.php
│   ├── badge.blade.php
│   └── input.blade.php
└── tailwind.config.js (atualizado)
```

#### 1.2 Error Pages (2h)

-  [ ] `errors/404.blade.php`
-  [ ] `errors/403.blade.php`
-  [ ] `errors/500.blade.php`

**Critérios de Aceitação:**

-  ✅ Páginas renderizam corretamente
-  ✅ Estilos em TailwindCSS puro
-  ✅ Responsivas em mobile/tablet/desktop
-  ✅ Sem dependências de Bootstrap

#### 1.3 Login & Auth (5h)

-  [ ] `auth/login.blade.php`
-  [ ] `auth/forgot-password.blade.php`
-  [ ] `auth/reset-password.blade.php`

**Critérios de Aceitação:**

-  ✅ Formulários funcionais com validação
-  ✅ Flash messages renderizando
-  ✅ CSRF tokens corretos
-  ✅ Toggle de senha com Alpine.js
-  ✅ Testes de login/logout funcionando

#### 1.4 Layout Principal (10h)

-  [ ] `layouts/app.blade.php` (migração de layout.twig)
-  [ ] `components/head.blade.php`
-  [ ] `components/header.blade.php`
-  [ ] `components/navigation.blade.php`
-  [ ] `components/footer.blade.php`

**Critérios de Aceitação:**

-  ✅ Vite assets carregando corretamente
-  ✅ Meta tags dinâmicas funcionando
-  ✅ Menu de navegação responsivo
-  ✅ Alpine.js para interações do menu
-  ✅ Footer com informações corretas

#### 1.5 Layout Admin (6h)

-  [ ] `layouts/admin.blade.php`
-  [ ] `components/admin/breadcrumb.blade.php`
-  [ ] `components/admin/sidebar.blade.php`

**Critérios de Aceitação:**

-  ✅ Extends layout principal corretamente
-  ✅ Breadcrumbs dinâmicos funcionando
-  ✅ Sidebar colapsável (Alpine.js)
-  ✅ Permissões de acesso validadas

#### 1.6 Sistema de Alertas (2h)

-  [ ] `components/alert.blade.php` (Blade Component)
-  [ ] Configurar flash messages no AppServiceProvider
-  [ ] Remover dependência de macros/alerts.twig

**Critérios de Aceitação:**

-  ✅ 4 tipos de alerta (success, error, warning, info)
-  ✅ Auto-dismiss com Alpine.js
-  ✅ Animações suaves
-  ✅ Acessibilidade (ARIA)

---

### 🚀 FASE 2: CORE MODULES (Semana 3-4) - 44h

**Objetivo:** Migrar módulos essenciais do sistema que são usados diariamente.

#### 2.1 Dashboard Provider (8h)

-  [ ] `dashboard/index.blade.php`
-  [ ] `components/dashboard/stats-card.blade.php`
-  [ ] `components/dashboard/chart.blade.php`
-  [ ] `components/dashboard/recent-activities.blade.php`

**Critérios de Aceitação:**

-  ✅ Cards de estatísticas renderizando
-  ✅ Gráficos carregando (Chart.js ou similar)
-  ✅ Lista de atividades recentes
-  ✅ Performance < 1s de carregamento

#### 2.2 Settings (16h)

-  [ ] `settings/index.blade.php`
-  [ ] `settings/profile.blade.php`
-  [ ] `settings/general.blade.php`
-  [ ] `settings/notifications.blade.php`
-  [ ] `settings/security.blade.php`
-  [ ] `settings/integration.blade.php`
-  [ ] `components/settings/tabs.blade.php`
-  [ ] `components/settings/forms/*.blade.php`

**Critérios de Aceitação:**

-  ✅ Sistema de tabs funcional (Alpine.js)
-  ✅ Upload de logo/avatar funcionando
-  ✅ Formulários com validação client-side
-  ✅ Preview de alterações antes de salvar
-  ✅ Máscaras de CEP/CNPJ/CPF funcionando

#### 2.3 Navigation Enhancement (4h)

-  [ ] Adicionar busca global no header
-  [ ] Menu de notificações
-  [ ] Menu de usuário (dropdown)
-  [ ] Breadcrumbs dinâmicos

**Critérios de Aceitação:**

-  ✅ Busca funcionando (Alpine.js + fetch)
-  ✅ Notificações em tempo real
-  ✅ Dropdown sem jQuery
-  ✅ Breadcrumbs auto-gerados via Route

#### 2.4 Componentes Reutilizáveis (10h)

-  [ ] `components/table.blade.php`
-  [ ] `components/pagination.blade.php`
-  [ ] `components/modal.blade.php`
-  [ ] `components/form-input.blade.php`
-  [ ] `components/form-select.blade.php`
-  [ ] `components/form-textarea.blade.php`
-  [ ] `components/form-checkbox.blade.php`
-  [ ] `components/date-picker.blade.php`
-  [ ] `components/file-upload.blade.php`

**Critérios de Aceitação:**

-  ✅ Componentes documentados (Storybook ou similar)
-  ✅ Props tipadas e validadas
-  ✅ Slots para customização
-  ✅ Acessibilidade completa
-  ✅ Testes unitários

#### 2.5 Home & Legal (6h)

-  [ ] `pages/home/index.blade.php`
-  [ ] `pages/home/about.blade.php`
-  [ ] `pages/home/support.blade.php`
-  [ ] `pages/legal/terms.blade.php`
-  [ ] `pages/legal/privacy.blade.php`

---

### 📦 FASE 3: BUSINESS MODULES (Semana 5-7) - 84h

**Objetivo:** Migrar módulos de negócio principais (Customer, Budget, Product, Service).

#### 3.1 Customer Module (12h)

-  [ ] `customers/index.blade.php`
-  [ ] `customers/create.blade.php`
-  [ ] `customers/show.blade.php`
-  [ ] `customers/edit.blade.php`
-  [ ] `customers/services-quotes.blade.php`
-  [ ] `components/customer/form-fields.blade.php`

**Critérios de Aceitação:**

-  ✅ CRUD completo funcionando
-  ✅ Busca/filtros na listagem
-  ✅ Validação de CPF/CNPJ
-  ✅ CEP auto-preenchendo endereço
-  ✅ Visualização de orçamentos/serviços

#### 3.2 Product Module (10h)

-  [ ] `products/index.blade.php`
-  [ ] `products/create.blade.php`
-  [ ] `products/show.blade.php`
-  [ ] `products/edit.blade.php`

**Critérios de Aceitação:**

-  ✅ CRUD completo funcionando
-  ✅ Upload de imagem do produto
-  ✅ Controle de estoque
-  ✅ Categorização de produtos

#### 3.3 Budget Module (20h)

-  [ ] `budgets/index.blade.php`
-  [ ] `budgets/create.blade.php`
-  [ ] `budgets/show.blade.php`
-  [ ] `budgets/edit.blade.php`
-  [ ] `budgets/status-change.blade.php`
-  [ ] `budgets/pdf.blade.php` (print view)
-  [ ] `components/budget/summary-card.blade.php`
-  [ ] `components/budget/service-list.blade.php`
-  [ ] `components/budget/status-timeline.blade.php`

**Critérios de Aceitação:**

-  ✅ CRUD completo funcionando
-  ✅ Mudança de status via modal
-  ✅ Cálculos de totais corretos
-  ✅ Vinculação com serviços
-  ✅ Geração de PDF funcional
-  ✅ Envio de notificação ao cliente

#### 3.4 Service Module (18h)

-  [ ] `services/index.blade.php`
-  [ ] `services/create.blade.php`
-  [ ] `services/show.blade.php`
-  [ ] `services/edit.blade.php`
-  [ ] `services/status-view.blade.php`
-  [ ] `services/pdf.blade.php` (print view)
-  [ ] `components/service/item-list.blade.php`
-  [ ] `components/service/schedule.blade.php`

**Critérios de Aceitação:**

-  ✅ CRUD completo funcionando
-  ✅ Agendamento de serviços
-  ✅ Adição de itens/produtos
-  ✅ Cálculo de descontos
-  ✅ Mudança de status
-  ✅ Notificações ao cliente

#### 3.5 Invoice Module (24h)

-  [ ] `invoices/index.blade.php`
-  [ ] `invoices/create.blade.php`
-  [ ] `invoices/show.blade.php`
-  [ ] `invoices/pdf.blade.php`
-  [ ] `invoices/payment/*.blade.php` (5 views de pagamento)
-  [ ] `components/invoice/payment-methods.blade.php`
-  [ ] `components/invoice/status-badge.blade.php`

**Critérios de Aceitação:**

-  ✅ Geração de fatura a partir de orçamento
-  ✅ Múltiplos métodos de pagamento
-  ✅ Integração com gateway de pagamento
-  ✅ PDF da fatura
-  ✅ Status de pagamento em tempo real
-  ✅ Envio de email com fatura

---

### 📊 FASE 4: ADVANCED & REPORTS (Semana 8-9) - 72h

**Objetivo:** Migrar módulos complexos (Reports, Admin avançado, Emails).

#### 4.1 Reports Module (32h)

-  [ ] `reports/index.blade.php`
-  [ ] `reports/budget.blade.php`
-  [ ] `reports/customer.blade.php`
-  [ ] `reports/service.blade.php`
-  [ ] `reports/product.blade.php`
-  [ ] `reports/pdf/budget.blade.php`
-  [ ] `reports/pdf/customer.blade.php`
-  [ ] `reports/pdf/service.blade.php`
-  [ ] `reports/pdf/product.blade.php`
-  [ ] `components/reports/filter-form.blade.php`
-  [ ] `components/reports/chart.blade.php`
-  [ ] `components/reports/export-buttons.blade.php`

**Critérios de Aceitação:**

-  ✅ Filtros avançados funcionando
-  ✅ Gráficos renderizando (Chart.js)
-  ✅ Exportação PDF via DomPDF/Snappy
-  ✅ Exportação Excel via Maatwebsite
-  ✅ Performance com grandes datasets
-  ✅ Cache de relatórios

#### 4.2 Email Templates (16h)

-  [ ] `emails/layouts/base.blade.php`
-  [ ] `emails/auth/*.blade.php` (3 templates)
-  [ ] `emails/notifications/*.blade.php` (10 templates)
-  [ ] `emails/invoice/*.blade.php`
-  [ ] `emails/plan/*.blade.php`

**Critérios de Aceitação:**

-  ✅ Templates responsivos
-  ✅ Inline CSS para compatibilidade
-  ✅ Variáveis dinâmicas funcionando
-  ✅ Preview no Mailtrap/Mailhog
-  ✅ Testes de envio

#### 4.3 Admin Advanced (24h)

-  [ ] `admin/dashboard.blade.php`
-  [ ] `admin/executive-dashboard.blade.php`
-  [ ] `admin/metrics/*.blade.php`
-  [ ] `admin/monitoring/*.blade.php`
-  [ ] `admin/logs/*.blade.php`
-  [ ] `admin/alerts/*.blade.php`
-  [ ] `admin/analysis/*.blade.php`
-  [ ] `admin/tenants/*.blade.php`
-  [ ] `admin/users/*.blade.php`
-  [ ] `admin/plans/*.blade.php`

**Critérios de Aceitação:**

-  ✅ Dashboards com métricas em tempo real
-  ✅ Sistema de alertas funcionando
-  ✅ Logs visualizáveis e pesquisáveis
-  ✅ Monitoramento de performance
-  ✅ Gestão de tenants multi-tenant
-  ✅ RBAC funcionando

---

## 6. PADRÕES DE CONVERSÃO

### 6.1 Sintaxe Twig → Blade

#### Variáveis e Output

```twig
<!-- TWIG -->
{{ variable }}
{{ variable|escape }}
{{ variable|raw }}

<!-- BLADE -->
{{ $variable }}
{{ $variable }} (auto-escaped)
{!! $variable !!}
```

#### Estruturas de Controle

```twig
<!-- TWIG -->
{% if condition %}
{% elseif other %}
{% else %}
{% endif %}

{% for item in items %}
    {{ item }}
{% endfor %}

<!-- BLADE -->
@if($condition)
@elseif($other)
@else
@endif

@foreach($items as $item)
    {{ $item }}
@endforeach
```

#### Includes e Extends

```twig
<!-- TWIG -->
{% extends "layout.twig" %}
{% include "partial.twig" %}
{% import "macros.twig" as macros %}

<!-- BLADE -->
@extends('layouts.app')
@include('partials.name')
<!-- Macros → Blade Components -->
<x-alert type="success" />
```

#### Blocos e Sections

```twig
<!-- TWIG -->
{% block content %}
    ...
{% endblock %}

<!-- BLADE -->
@section('content')
    ...
@endsection
```

### 6.2 Macros → Blade Components

**Exemplo: Alert Macro**

```twig
<!-- ANTES: macros/alerts.twig -->
{% macro alert(type, message) %}
    {% set flashTypes = {
        'error': 'danger',
        'success': 'success'
    } %}
    <div class="alert alert-{{ flashTypes[type] }}">
        {{ message|raw }}
    </div>
{% endmacro %}

<!-- USO -->
{% import 'macros/alerts.twig' as alerts %}
{{ alerts.alert('success', 'Salvo!') }}
```

```blade
<!-- DEPOIS: components/alert.blade.php -->
@props([
    'type' => 'info',
    'message',
    'dismissible' => true
])

@php
    $classes = [
        'success' => 'bg-green-50 text-green-800 border-green-200',
        'error' => 'bg-red-50 text-red-800 border-red-200',
        'warning' => 'bg-yellow-50 text-yellow-800 border-yellow-200',
        'info' => 'bg-blue-50 text-blue-800 border-blue-200',
    ][$type];
@endphp

<div
    x-data="{ show: true }"
    x-show="show"
    x-transition
    class="border rounded-lg p-4 {{ $classes }}"
    role="alert"
>
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-2">
            @if($type === 'success')
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">...</svg>
            @endif
            <div>{!! $message !!}</div>
        </div>

        @if($dismissible)
            <button @click="show = false" class="text-current opacity-50 hover:opacity-100">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">...</svg>
            </button>
        @endif
    </div>
</div>

<!-- USO -->
<x-alert type="success" message="Salvo com sucesso!" />
```

### 6.3 Flash Messages

```php
// app/Providers/AppServiceProvider.php
public function boot()
{
    // Compartilhar flash messages com todas as views
    View::composer('*', function ($view) {
        $view->with('flash', session()->get('flash', []));
    });
}
```

```blade
<!-- resources/views/components/flash-messages.blade.php -->
@if(session()->has('success'))
    <x-alert type="success" :message="session('success')" />
@endif

@if(session()->has('error'))
    <x-alert type="error" :message="session('error')" />
@endif

@if($errors->any())
    <x-alert type="error">
        <ul class="list-disc list-inside">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </x-alert>
@endif
```

### 6.4 JavaScript: jQuery → Alpine.js

```javascript
// ANTES: jQuery
$(document).ready(function () {
   $(".password-toggle").click(function () {
      const input = $(this).data("input");
      const type = $("#" + input).attr("type");
      $("#" + input).attr("type", type === "password" ? "text" : "password");
      $(this).find("i").toggleClass("bi-eye bi-eye-slash");
   });
});
```

```html
<!-- DEPOIS: Alpine.js -->
<div x-data="{ showPassword: false }">
   <input :type="showPassword ? 'text' : 'password'" class="form-input" />
   <button @click="showPassword = !showPassword">
      <i :class="showPassword ? 'bi-eye-slash' : 'bi-eye'"></i>
   </button>
</div>
```

### 6.5 CSS: Bootstrap → TailwindCSS

```html
<!-- ANTES: Bootstrap -->
<div class="container-fluid">
   <div class="row">
      <div class="col-md-6">
         <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
               <h5 class="card-title mb-0">Título</h5>
            </div>
            <div class="card-body">
               <p class="text-muted">Conteúdo</p>
            </div>
         </div>
      </div>
   </div>
</div>

<!-- DEPOIS: TailwindCSS -->
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
   <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div class="bg-white rounded-lg shadow-sm overflow-hidden">
         <div class="bg-blue-600 text-white px-6 py-4">
            <h5 class="text-lg font-semibold">Título</h5>
         </div>
         <div class="p-6">
            <p class="text-gray-600">Conteúdo</p>
         </div>
      </div>
   </div>
</div>
```

---

## 7. ARQUITETURA DE DIRETÓRIOS TARGET

### 7.1 Estrutura Completa

```
resources/views/
├── layouts/
│   ├── app.blade.php              # Layout principal
│   ├── admin.blade.php            # Layout administrativo
│   ├── guest.blade.php            # Layout para não autenticados
│   └── pdf.blade.php              # Layout para PDFs
│
├── components/
│   ├── ui/                        # Componentes de interface
│   │   ├── button.blade.php
│   │   ├── card.blade.php
│   │   ├── badge.blade.php
│   │   ├── alert.blade.php
│   │   ├── modal.blade.php
│   │   ├── dropdown.blade.php
│   │   └── pagination.blade.php
│   │
│   ├── form/                      # Componentes de formulário
│   │   ├── input.blade.php
│   │   ├── select.blade.php
│   │   ├── textarea.blade.php
│   │   ├── checkbox.blade.php
│   │   ├── radio.blade.php
│   │   ├── date-picker.blade.php
│   │   └── file-upload.blade.php
│   │
│   ├── navigation/                # Componentes de navegação
│   │   ├── header.blade.php
│   │   ├── menu.blade.php
│   │   ├── breadcrumb.blade.php
│   │   └── footer.blade.php
│   │
│   └── [domain]/                  # Componentes específicos por domínio
│       ├── budget/
│       ├── customer/
│       ├── invoice/
│       └── service/
│
├── pages/                         # Views de páginas
│   ├── auth/
│   │   ├── login.blade.php
│   │   ├── forgot-password.blade.php
│   │   └── reset-password.blade.php
│   │
│   ├── dashboard/
│   │   ├── index.blade.php
│   │   └── widgets/
│   │
│   ├── budgets/
│   │   ├── index.blade.php
│   │   ├── create.blade.php
│   │   ├── show.blade.php
│   │   ├── edit.blade.php
│   │   └── pdf.blade.php
│   │
│   ├── customers/
│   ├── products/
│   ├── services/
│   ├── invoices/
│   ├── reports/
│   ├── settings/
│   └── admin/
│
├── emails/
│   ├── layouts/
│   │   └── base.blade.php
│   ├── auth/
│   ├── notifications/
│   └── invoices/
│
└── errors/
    ├── 404.blade.php
    ├── 403.blade.php
    └── 500.blade.php
```

### 7.2 Convenções de Nomenclatura

**Blade Components:**

-  **UI Components:** `<x-ui.button>`, `<x-ui.card>`
-  **Form Components:** `<x-form.input>`, `<x-form.select>`
-  **Domain Components:** `<x-budget.summary>`, `<x-customer.card>`

**Views:**

-  **Índice:** `index.blade.php`
-  **Criação:** `create.blade.php`
-  **Visualização:** `show.blade.php`
-  **Edição:** `edit.blade.php`
-  **Formulários:** `_form.blade.php` (partial)

**Assets:**

```javascript
// resources/js/
app.js                   // Entry point
bootstrap.js             // Configurações iniciais
alpine.js                // Alpine plugins

modules/
  auth/
  forms/
  masks/
  utils/

pages/                   // Scripts específicos por página
  budgets/
  customers/
```

```css
/* resources/css/ */
app.css                  // Tailwind imports
components/              /* Componentes customizados */
pages/                   /* Estilos específicos */
```

---

## 8. ESPECIFICAÇÕES TÉCNICAS POR FASE

### 8.1 Fase 1: Fundação

**8.1.1 Configuração do Tailwind**

```javascript
// tailwind.config.js
import forms from "@tailwindcss/forms";
import defaultTheme from "tailwindcss/defaultTheme";

export default {
   content: [
      "./vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php",
      "./storage/framework/views/*.php",
      "./resources/views/**/*.blade.php",
      "./resources/js/**/*.js",
   ],
   theme: {
      extend: {
         fontFamily: {
            sans: ["Inter", ...defaultTheme.fontFamily.sans],
         },
         colors: {
            primary: {
               50: "#eff6ff",
               100: "#dbeafe",
               500: "#3b82f6",
               600: "#2563eb",
               700: "#1d4ed8",
            },
            success: "#10b981",
            danger: "#ef4444",
            warning: "#f59e0b",
            info: "#3b82f6",
         },
         spacing: {
            18: "4.5rem",
            88: "22rem",
            128: "32rem",
         },
      },
   },
   plugins: [forms],
};
```

**8.1.2 Configuração do Vite**

```javascript
// vite.config.js
import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";

export default defineConfig({
   plugins: [
      laravel({
         input: ["resources/css/app.css", "resources/js/app.js"],
         refresh: true,
      }),
   ],
   resolve: {
      alias: {
         "@": "/resources/js",
         "@css": "/resources/css",
      },
   },
});
```

**8.1.3 Alpine.js Setup**

```javascript
// resources/js/app.js
import "./bootstrap";
import Alpine from "alpinejs";
import mask from "@alpinejs/mask";
import focus from "@alpinejs/focus";

// Registrar plugins
Alpine.plugin(mask);
Alpine.plugin(focus);

// Componentes globais
Alpine.data("dropdown", () => ({
   open: false,
   toggle() {
      this.open = !this.open;
   },
}));

// Inicializar
window.Alpine = Alpine;
Alpine.start();
```

**8.1.4 Layout Base**

```blade
<!-- resources/views/layouts/app.blade.php -->
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Easy Budget') }} - @yield('title', 'Dashboard')</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />

    <!-- Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="h-full bg-gray-50 font-sans antialiased">
    <div class="min-h-full">
        <!-- Header -->
        <x-navigation.header />

        <!-- Page Content -->
        <main class="flex-1">
            <!-- Flash Messages -->
            <x-flash-messages />

            @yield('content')
        </main>

        <!-- Footer -->
        <x-navigation.footer />
    </div>

    @stack('scripts')
</body>
</html>
```

### 8.2 Fase 2: Core Modules

**8.2.1 Componente de Tabela Reutilizável**

```blade
<!-- resources/views/components/table.blade.php -->
@props([
    'headers' => [],
    'rows' => [],
    'sortable' => false,
    'searchable' => false,
])

<div
    x-data="{
        search: '',
        sortBy: '',
        sortDir: 'asc',
        filteredRows() {
            let filtered = {{ json_encode($rows) }};

            if (this.search) {
                filtered = filtered.filter(row =>
                    Object.values(row).some(val =>
                        String(val).toLowerCase().includes(this.search.toLowerCase())
                    )
                );
            }

            if (this.sortBy) {
                filtered.sort((a, b) => {
                    const aVal = a[this.sortBy];
                    const bVal = b[this.sortBy];
                    return this.sortDir === 'asc' ? aVal > bVal : aVal < bVal;
                });
            }

            return filtered;
        }
    }"
    class="overflow-x-auto"
>
    @if($searchable)
        <div class="mb-4">
            <input
                type="text"
                x-model="search"
                placeholder="Buscar..."
                class="w-full max-w-md px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
            />
        </div>
    @endif

    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                @foreach($headers as $key => $label)
                    <th
                        @if($sortable)
                            @click="sortBy = '{{ $key }}'; sortDir = sortDir === 'asc' ? 'desc' : 'asc'"
                            class="cursor-pointer"
                        @endif
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                    >
                        {{ $label }}
                        @if($sortable)
                            <i :class="sortBy === '{{ $key }}' ? (sortDir === 'asc' ? 'bi-arrow-up' : 'bi-arrow-down') : ''" class="text-xs"></i>
                        @endif
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            <template x-for="row in filteredRows()" :key="row.id">
                <tr class="hover:bg-gray-50">
                    {{ $slot }}
                </tr>
            </template>
        </tbody>
    </table>
</div>
```

**USO:**

```blade
<x-table
    :headers="['name' => 'Nome', 'email' => 'Email', 'created_at' => 'Criado em']"
    :rows="$customers"
    sortable
    searchable
>
    <td class="px-6 py-4" x-text="row.name"></td>
    <td class="px-6 py-4" x-text="row.email"></td>
    <td class="px-6 py-4" x-text="row.created_at"></td>
</x-table>
```

### 8.3 Fase 3: Business Modules

**8.3.1 Budget Service Pattern**

```php
// app/Services/BudgetService.php
namespace App\Services;

class BudgetService
{
    public function calculateTotals(Budget $budget): array
    {
        $services = $budget->services;

        $cancelledTotal = $services
            ->where('status_slug', 'CANCELLED')
            ->sum('total');

        $partialTotal = $services
            ->where('status_slug', 'PARTIAL')
            ->sum('total');

        $totalDiscount = $budget->discount + $services->sum('discount');

        $realTotal = $budget->total - $cancelledTotal - $totalDiscount;

        return [
            'gross_total' => $budget->total,
            'cancelled_total' => $cancelledTotal,
            'partial_total' => $partialTotal,
            'total_discount' => $totalDiscount,
            'net_total' => $realTotal,
        ];
    }

    public function calculateProgress(Budget $budget): int
    {
        // Lógica de cálculo de progresso
    }
}
```

**8.3.2 View Composer para Budgets**

```php
// app/Providers/ViewServiceProvider.php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Services\BudgetService;

class ViewServiceProvider extends ServiceProvider
{
    public function boot()
    {
        View::composer('pages.budgets.show', function ($view) {
            $budget = $view->getData()['budget'];
            $budgetService = app(BudgetService::class);

            $view->with([
                'totals' => $budgetService->calculateTotals($budget),
                'progress' => $budgetService->calculateProgress($budget),
            ]);
        });
    }
}
```

### 8.4 Fase 4: Advanced & Reports

**8.4.1 PDF Generation**

```php
// app/Services/PdfService.php
namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;

class PdfService
{
    public function generateBudgetPdf(Budget $budget): string
    {
        $pdf = Pdf::loadView('pages.budgets.pdf', [
            'budget' => $budget,
            'services' => $budget->services,
            'provider' => $budget->provider,
        ]);

        return $pdf->download("budget-{$budget->code}.pdf");
    }
}
```

**8.4.2 Report Blade Template**

```blade
<!-- resources/views/pages/reports/budget.blade.php -->
@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Filtros -->
    <x-reports.filter-form :filters="$filters" />

    <!-- Gráficos -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
        <x-reports.chart
            title="Orçamentos por Status"
            type="pie"
            :data="$statusData"
        />

        <x-reports.chart
            title="Receita Mensal"
            type="line"
            :data="$revenueData"
        />
    </div>

    <!-- Tabela de Dados -->
    <div class="mt-6">
        <x-table
            :headers="$headers"
            :rows="$budgets"
            sortable
            searchable
        >
            <!-- Template de linha -->
        </x-table>
    </div>

    <!-- Botões de Exportação -->
    <div class="mt-6 flex gap-4">
        <a href="{{ route('reports.budgets.pdf') }}" class="btn btn-primary">
            <i class="bi bi-file-pdf"></i> Exportar PDF
        </a>
        <a href="{{ route('reports.budgets.excel') }}" class="btn btn-success">
            <i class="bi bi-file-excel"></i> Exportar Excel
        </a>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Inicializar gráficos
</script>
@endpush
```

---

## 9. ESTRATÉGIA DE TESTES

### 9.1 Testes por Fase

**Fase 1: Fundação**

```php
// tests/Feature/Views/ErrorPagesTest.php
class ErrorPagesTest extends TestCase
{
    public function test_404_page_renders()
    {
        $response = $this->get('/non-existent-route');
        $response->assertStatus(404);
        $response->assertViewIs('errors.404');
        $response->assertSee('Página não encontrada');
    }

    public function test_login_page_renders()
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertViewIs('auth.login');
        $response->assertSee('Faça login');
    }
}
```

**Fase 2: Core**

```php
// tests/Feature/Views/DashboardTest.php
class DashboardTest extends TestCase
{
    public function test_dashboard_shows_stats()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertViewIs('pages.dashboard.index');
        $response->assertViewHas('stats');
    }
}
```

**Fase 3: Business**

```php
// tests/Feature/Views/BudgetTest.php
class BudgetTest extends TestCase
{
    public function test_budget_show_displays_totals()
    {
        $budget = Budget::factory()->create();

        $response = $this->get("/budgets/{$budget->code}");

        $response->assertStatus(200);
        $response->assertSee($budget->code);
        $response->assertSee('Total Bruto');
        $response->assertSee('Total Líquido');
    }

    public function test_budget_pdf_generates()
    {
        $budget = Budget::factory()->create();

        $response = $this->get("/budgets/{$budget->code}/pdf");

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }
}
```

### 9.2 Visual Regression Testing

```bash
# Instalar BackstopJS
npm install -D backstopjs

# Configurar
npx backstop init

# Criar reference
npx backstop reference

# Testar
npx backstop test
```

```javascript
// backstop.json
{
  "scenarios": [
    {
      "label": "Login Page",
      "url": "http://localhost:8000/login",
      "selectors": ["document"]
    },
    {
      "label": "Dashboard",
      "url": "http://localhost:8000/dashboard",
      "selectors": ["document"]
    }
  ]
}
```

### 9.3 Component Testing (Storybook)

```bash
# Instalar Storybook
npx storybook@latest init
```

```javascript
// resources/js/stories/Button.stories.js
export default {
   title: "Components/Button",
   component: "x-button",
};

export const Primary = {
   args: {
      variant: "primary",
      label: "Button",
   },
};

export const Secondary = {
   args: {
      variant: "secondary",
      label: "Button",
   },
};
```

### 9.4 Checklist de Testes por Template

**Para cada template migrado:**

-  [ ] Renderiza sem erros
-  [ ] Todos os dados dinâmicos aparecem corretamente
-  [ ] Formulários funcionam (CRUD completo)
-  [ ] Validações client-side funcionam
-  [ ] Flash messages aparecem
-  [ ] Links/navegação funcionam
-  [ ] Responsivo em mobile/tablet/desktop
-  [ ] Acessibilidade (ARIA, keyboard navigation)
-  [ ] Performance (< 1s carregamento)
-  [ ] JavaScript funciona (Alpine.js)
-  [ ] Não há erros no console
-  [ ] CSS correto (TailwindCSS)

---

## 10. RISCOS E MITIGAÇÃO

### 10.1 Matriz de Riscos

| Risco                                | Probabilidade | Impacto  | Mitigação                                                                   |
| ------------------------------------ | ------------- | -------- | --------------------------------------------------------------------------- |
| **Quebra de funcionalidade crítica** | 🟡 Média      | 🔴 Alta  | - Testes automatizados<br>- Deploy incremental<br>- Rollback rápido         |
| **Performance degradada**            | 🟢 Baixa      | 🟡 Média | - Benchmarks antes/depois<br>- Lazy loading<br>- Cache agressivo            |
| **Inconsistência visual**            | 🟡 Média      | 🟡 Média | - Design system<br>- Visual regression tests<br>- Review de design          |
| **Estouro de prazo**                 | 🟡 Média      | 🟡 Média | - Buffer de 20% no cronograma<br>- Priorização clara<br>- Revisões semanais |
| **Perda de SEO**                     | 🟢 Baixa      | 🟡 Média | - Manter URLs<br>- Testes de meta tags<br>- Verificar robots.txt            |
| **Acessibilidade comprometida**      | 🟡 Média      | 🔴 Alta  | - Testes com screen readers<br>- Auditoria WCAG<br>- Linters                |
| **Browser compatibility**            | 🟢 Baixa      | 🟡 Média | - Testes cross-browser<br>- Autoprefixer<br>- Polyfills                     |
| **JavaScript errors**                | 🟡 Média      | 🔴 Alta  | - Error tracking (Sentry)<br>- Testes E2E<br>- Fallbacks                    |

### 10.2 Estratégias de Mitigação Específicas

**10.2.1 Quebra de Funcionalidade**

```php
// Feature Flag para migração gradual
if (config('features.use_blade_templates')) {
    return view('pages.budgets.show'); // Novo Blade
} else {
    return view('pages.budgets.show_twig'); // Twig antigo
}
```

**10.2.2 Performance**

```php
// Benchmark antes/depois
$start = microtime(true);
$view = view('pages.budgets.show', $data)->render();
$time = microtime(true) - $start;

Log::info('View render time', [
    'view' => 'budgets.show',
    'time' => $time,
    'threshold_exceeded' => $time > 0.5
]);
```

**10.2.3 Acessibilidade**

```bash
# Instalar axe-core para testes
npm install -D @axe-core/playwright

# Executar testes
npx playwright test accessibility
```

### 10.3 Plano de Rollback

**Por Fase:**

1. Manter templates Twig antigos intactos
2. Feature flags por módulo
3. Backup de banco de dados antes de cada fase
4. Monitoramento de erros em tempo real
5. Rollback automático se erro rate > 5%

**Rollback Rápido:**

```bash
# Reverter para versão anterior
git revert <commit-hash>
git push origin main

# Limpar cache
php artisan view:clear
php artisan cache:clear

# Rebuild assets
npm run build

# Deploy
./deploy.sh
```

---

## 📊 RESUMO EXECUTIVO

### Métricas do Projeto

-  **Total de Arquivos:** 150+ templates
-  **Duração Estimada:** 9 semanas (230 horas)
-  **Fases:** 4 fases incrementais
-  **Riscos Identificados:** 8 principais (4 mitigados)
-  **Testes:** 100+ casos de teste
-  **Componentes Reutilizáveis:** 30+ Blade Components

### Benefícios Esperados

✅ **Performance:** Redução de 30% no tempo de renderização
✅ **Manutenibilidade:** Código 50% mais limpo e organizado
✅ **Developer Experience:** TailwindCSS + Alpine.js + Vite
✅ **SEO:** Melhor estrutura semântica
✅ **Acessibilidade:** WCAG 2.1 AA compliance
✅ **Mobile:** Responsividade nativa com Tailwind

### Próximos Passos

1. ✅ **Aprovação do Plano Arquitetural** → Esta documentação
2. 🔜 **SUBTAREFA 2/3:** Implementação Fase 1 (Fundação)
3. 🔜 **SUBTAREFA 3/3:** Implementação Fases 2-4 + Testes

---

## 📚 REFERÊNCIAS

-  [Laravel Blade Documentation](https://laravel.com/docs/blade)
-  [TailwindCSS Documentation](https://tailwindcss.com/docs)
-  [Alpine.js Documentation](https://alpinejs.dev/)
-  [Vite Documentation](https://vitejs.dev/)
-  [Laravel Best Practices](https://github.com/alexeymezenin/laravel-best-practices)
-  [WCAG 2.1 Guidelines](https://www.w3.org/WAI/WCAG21/quickref/)

---

**Documento criado em:** 2025-09-30
**Versão:** 1.0
**Autor:** Kilo Code (Arquiteto de Software)
**Status:** ✅ PRONTO PARA REVISÃO E APROVAÇÃO
