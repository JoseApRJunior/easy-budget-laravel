# Plano de Migração: `resources/views/pages` (Twig para Blade)

## 🎯 Objetivo

Este documento detalha a estratégia e a ordem de migração dos arquivos de visualização do diretório `resources/views/pages`, convertendo-os do motor de templates Twig para o Blade do Laravel. O objetivo é garantir uma transição estruturada, modular e controlada.

## 📋 Estratégia Geral

A migração será dividida em etapas, agrupando os arquivos por funcionalidade ou módulo. A abordagem será a seguinte:

1. **Migração Incremental:** Focar em um módulo por vez (ex: Autenticação, Clientes, Orçamentos).
2. **Do Simples ao Complexo:** Começar com páginas mais simples e estáticas (ex: páginas de erro, legais) para depois avançar para as mais complexas com lógica de negócios (ex: admin, orçamentos).
3. **Validação Contínua:** Após a migração de cada módulo, é recomendado testar as funcionalidades correspondentes para garantir que nada foi quebrado.

## ⚡ Ordem de Migração Sugerida

A seguir está a ordem recomendada para a migração, agrupada por diretórios/módulos.

### Etapa 1: Páginas Básicas e de Erro

Páginas com pouco ou nenhum processamento dinâmico. São ideais para começar.

-  **Diretório:** `pages/error/`
   -  `internalError.twig`
   -  `notAllowed.twig`
   -  `notFound.twig`
-  **Diretório:** `pages/legal/`
   -  `privacy_policy.twig`
   -  `terms_of_service.twig`
-  **Diretório:** `pages/home/`
   -  _Observação: Estes arquivos já são `.blade.php`. Apenas revisar se necessário._

### Etapa 2: Autenticação e Contas de Usuário

Fluxos essenciais para o acesso e gerenciamento de contas.

-  **Diretório:** `pages/login/`
   -  `index.twig`
   -  `forgot_password.twig`
-  **Diretório:** `pages/user/`
   -  `confirm-account.twig`
   -  `resend-confirmation.twig`
   -  `block-account.twig`

### Etapa 3: CRUDs (Cadastro, Leitura, Atualização e Deleção)

Migração das entidades principais do sistema. Recomenda-se migrar um CRUD de cada vez.

1. **Unidades (`pages/unit/`)**
   -  `index.twig`
   -  `create.twig`
2. **Categorias (`pages/category/`)**
   -  `index.twig`
   -  `create.twig`
   -  `edit.twig`
   -  `show.twig`
3. **Áreas de Atuação (`pages/area-of-activity/`)**
   -  `index.twig`
   -  `create.twig`
4. **Profissões (`pages/profession/`)**
   -  `index.twig`
   -  `create.twig`
5. **Clientes (`pages/customer/`)**
   -  `index.twig`
   -  `create.twig`
   -  `show.twig`
   -  `update.twig`
   -  `services_and_quotes.twig`
6. **Fornecedores (`pages/provider/`)**
   -  `index.twig`
   -  `update.twig`
   -  `change_password.twig`
7. **Produtos (`pages/product/`)**
   -  `index.twig`
   -  `create.twig`
   -  `show.twig`
   -  `update.twig`
8. **Serviços (`pages/service/`)**
   -  `index.twig`
   -  `create.twig`
   -  `show.twig`
   -  `update.twig`
   -  `pdf_service_print.twig`
   -  `view_service_status.twig`

### Etapa 4: Funcionalidades Centrais

Módulos com maior complexidade e lógica de negócios.

1. **Orçamentos (`pages/budget/`)**
   -  `index.twig`
   -  `create.twig`
   -  `show.twig`
   -  `update.twig`
   -  `choose_budget_status.twig`
   -  `pdf_budget.twig`
   -  `pdf_budget_print.twig`
2. **Faturas e Pagamentos (`pages/invoice/` e `pages/payment/`)**
   -  `invoice/index.twig`
   -  `invoice/create.twig`
   -  `invoice/show.twig`
   -  `invoice/pdf_invoice_print.twig`
   -  `invoice/payment/` (todos os arquivos: `success`, `failure`, etc.)
   -  `payment/` (todos os arquivos)
3. **Planos (`pages/plan/`)**
   -  `index.twig`
   -  `status.twig`

### Etapa 5: Relatórios

Páginas de geração e visualização de relatórios.

-  **Diretório:** `pages/report/`
   -  `index.twig`
   -  E todos os subdiretórios (`budget`, `customer`, `product`, `service`)

### Etapa 6: Painel de Administração

A seção mais complexa, que deve ser migrada por último e, se possível, dividida em sub-etapas.

-  **Diretório:** `pages/admin/`
   1. Páginas principais: `dashboard.twig`, `home.twig`, `executive-dashboard.twig`.
   2. Módulos de gestão: `tenant/`, `user/`, `plan/`, `settings/`.
   3. Módulos de monitoramento: `logs/`, `metrics/`, `monitoring/`, `analysis/`, `alerts/`, `ai/`.

## 🔧 Padrões de Conversão (Twig para Blade)

Durante a migração, preste atenção aos seguintes padrões:

-  **Herança de Layout:**
   -  `{% extends 'layouts/app.twig' %}` → `@extends('layouts.app')`
-  **Seções de Conteúdo:**
   -  `{% block content %}` ... `{% endblock %}` → `@section('content')` ... `@endsection`
-  **Inclusão de Partials:**
   -  `{% include 'partials/sidebar.twig' %}` → `@include('partials.sidebar')`
-  **Variáveis:**
   -  `{{ user.name }}` → `{{ $user->name }}`
-  **Estruturas de Controle:**
   -  `{% if condition %}` → `@if (condition)`
   -  `{% for item in items %}` → `@foreach ($items as $item)`
-  **Funções e Filtros:**
   -  `{{ 'text'|trans }}` → `__('text')`
   -  `{{ path('route_name') }}` → `{{ route('route_name') }}`
   -  `{{ flash('error')|raw }}` → Substituir por diretivas de erro do Blade, como `@error('field') <div class="alert alert-danger">{{ $message }}</div> @enderror`.

## ✅ Próximos Passos

1. Crie um novo chat para iniciar a migração.
2. Informe a primeira etapa que deseja realizar (ex: "Vamos começar pela Etapa 1: Páginas Básicas e de Erro").
3. Siga o plano, migrando um módulo de cada vez.

Este plano servirá como um guia para garantir que a migração seja feita de forma organizada e eficiente.
