# 📐 VIEW PATTERNS - Padrões de Interface

> **📚 Documentação Completa de Padrões de Views**
>
> Baseado na implementação de **Category** (Dashboard, Index, Create, Edit, Show)
>
> ✅ **OBRIGATÓRIO:** Todos os novos módulos devem seguir estes padrões
>
> 🎯 **Objetivo:** Consistência visual, UX padronizada e manutenibilidade

## 📋 Índice Rápido

1. [Dashboard Pattern](#-1-dashboard-pattern) - Cards de métricas + Layout 8-4
2. [Index Pattern](#-2-index-listagem-pattern) - Listagem com filtros e tabela
3. [Create Pattern](#-3-create-pattern) - Formulário de criação
4. [Edit Pattern](#-4-edit-pattern) - Formulário de edição
5. [Show Pattern](#-5-show-detalhes-pattern) - Visualização de detalhes
6. [Componentes](#-padrões-de-componentes) - Badges, botões, modais
7. [Ícones](#-ícones-bootstrap-icons-por-contexto) - Referência de ícones
8. [Responsividade](#-responsividade) - Classes responsivas
9. [Checklist](#-checklist-de-implementação) - Verificação antes do commit
10.   [Referência Rápida](#-referência-rápida---copy--paste) - Templates prontos
11.   [Integração com Backend](#-integração-com-backend) - Controllers, Services e Repositories

## 🎯 Estrutura Geral de Views

### Layout Base

```blade
@extends('layouts.app')
@section('title', 'Título da Página')
@section('content')
    <div class="container-fluid py-1">
        <!-- Conteúdo aqui -->
    </div>
@endsection
```

---

## 🎨 Padrão de Ícones

### Ícones de Ação "Novo/Criar"

-  Use ícone **específico** quando existir no Bootstrap Icons
-  Fallback para `bi-plus-circle` quando não houver específico

**Exemplos:**

-  Cliente: `bi-person-plus`
-  Produto: `bi-bag-plus`
-  Categoria: `bi-plus-circle`
-  Serviço: `bi-plus-circle`

---

## 🔧 10. ENUM PATTERNS - Padrões para Uso de Enums

> **📚 Documentação Completa de Padrões para Enums**
>
> Baseado na implementação de **InvoiceStatus** e outros Enums do sistema
>
> ✅ **OBRIGATÓRIO:** Todos os novos Enums devem seguir estes padrões
>
> 🎯 **Objetivo:** Consistência, segurança de tipos e manutenibilidade

### 📋 Índice Rápido

1. [Estrutura Básica de Enum](#-estrutura-básica-de-enum)
2. [Métodos Úteis em Enums](#-métodos-úteis-em-enums)
3. [Uso em Controllers](#-uso-em-controllers)
4. [Uso em Views](#-uso-em-views)
5. [Validação com Enums](#-validação-com-enums)
6. [Case Sensitivity](#-case-sensitivity)
7. [Exemplos Práticos](#-exemplos-práticos)

---

### 🏗️ Estrutura Básica de Enum

```php
<?php

namespace App\Enums;

enum InvoiceStatus: string
{
    case PENDING = 'pending';
    case PAID = 'paid';
    case CANCELLED = 'cancelled';
    case OVERDUE = 'overdue';
    case PARTIAL = 'partial';

    // Métodos úteis serão adicionados aqui
}
```

**Boas Práticas:**

-  ✅ Usar **UPPER_CASE** para nomes de casos
-  ✅ Usar **lowercase** para valores (backing values)
-  ✅ Sempre definir tipo de backing (string, int)
-  ✅ Manter consistência com valores no banco de dados
-  ❌ Evitar espaços ou caracteres especiais nos valores

---

### 🔧 Métodos Úteis em Enums

```php
// Método para obter todos os valores
public static function values(): array
{
    return array_column(self::cases(), 'value');
}

// Método para obter todas as opções para selects
public static function options(): array
{
    return array_combine(self::values(), self::labels());
}

// Método para obter labels legíveis
public static function labels(): array
{
    return [
        self::PENDING->value => 'Pendente',
        self::PAID->value => 'Pago',
        self::CANCELLED->value => 'Cancelado',
        self::OVERDUE->value => 'Vencido',
        self::PARTIAL->value => 'Parcial',
    ];
}

// Método para obter label de um valor específico
public static function label(string $value): string
{
    return self::labels()[$value] ?? $value;
}

// Método para verificar se um valor é válido
public static function isValid(string $value): bool
{
    return in_array($value, self::values());
}

// Método para obter cor associada ao status
public static function color(string $value): string
{
    $colors = [
        self::PENDING->value => 'warning',
        self::PAID->value => 'success',
        self::CANCELLED->value => 'danger',
        self::OVERDUE->value => 'danger',
        self::PARTIAL->value => 'info',
    ];

    return $colors[$value] ?? 'secondary';
}

// Método para obter ícone associado ao status
public static function icon(string $value): string
{
    $icons = [
        self::PENDING->value => 'bi-hourglass-split',
        self::PAID->value => 'bi-check-circle',
        self::CANCELLED->value => 'bi-x-circle',
        self::OVERDUE->value => 'bi-exclamation-triangle',
        self::PARTIAL->value => 'bi-cash-coin',
    ];

    return $icons[$value] ?? 'bi-question-circle';
}
```

---

### 🎯 Uso em Controllers

```php
// No controller - Exemplo de uso seguro com Enums

public function updateStatus(Invoice $invoice, Request $request)
{
    $validated = $request->validate([
        'status' => ['required', 'string', Rule::in(InvoiceStatus::values())],
    ]);

    $status = InvoiceStatus::from($validated['status']);

    $invoice->update(['status' => $status]);

    return redirect()->back()->with('success', 'Status atualizado com sucesso!');
}

// Exemplo com ServiceResult
public function getInvoicesByStatus(string $status): ServiceResult
{
    if (!InvoiceStatus::isValid($status)) {
        return $this->error('Status inválido', 400);
    }

    $invoices = Invoice::where('status', $status)
        ->where('tenant_id', tenant('id'))
        ->get();

    return $this->success($invoices);
}
```

---

### 👁️ Uso em Views

```blade
{{-- Exemplo seguro de uso de Enums em views --}}

{{-- Verificar se status existe antes de usar --}}
@if($invoice->status)
    <span class="badge bg-{{ \App\Enums\InvoiceStatus::color($invoice->status) }}">
        <i class="{{ \App\Enums\InvoiceStatus::icon($invoice->status) }} me-1"></i>
        {{ \App\Enums\InvoiceStatus::label($invoice->status) }}
    </span>
@else
    <span class="badge bg-secondary">Sem status</span>
@endif

{{-- Select com opções do Enum --}}
<select name="status" class="form-control">
    @foreach(\App\Enums\InvoiceStatus::options() as $value => $label)
        <option value="{{ $value }}" {{ $invoice->status === $value ? 'selected' : '' }}>
            {{ $label }}
        </option>
    @endforeach
</select>

{{-- Uso em tabelas com verificação --}}
@foreach($invoices as $invoice)
    <tr>
        <td>{{ $invoice->code }}</td>
        <td>
            @if($invoice->status)
                <span class="badge bg-{{ \App\Enums\InvoiceStatus::color($invoice->status) }}">
                    {{ \App\Enums\InvoiceStatus::label($invoice->status) }}
                </span>
            @else
                <span class="badge bg-secondary">Sem status</span>
            @endif
        </td>
    </tr>
@endforeach
```

---

### 🛡️ Validação com Enums

```php
// Em Form Requests
public function rules()
{
    return [
        'status' => ['required', 'string', Rule::in(InvoiceStatus::values())],
    ];
}

// Em controllers
$request->validate([
    'status' => ['required', 'string', Rule::in(InvoiceStatus::values())],
]);

// Validação manual
if (!InvoiceStatus::isValid($request->status)) {
    return back()->withErrors(['status' => 'Status inválido']);
}
```

---

### 🔤 Case Sensitivity

> **⚠️ IMPORTANTE:** PHP Enums são **case-sensitive** para os valores (backing values)

```php
// ❌ Isso causará erro:
InvoiceStatus::from('PENDING'); // Erro! Valor deve ser 'pending'

// ✅ Correto:
InvoiceStatus::from('pending'); // OK

// ✅ Melhor prática: Sempre usar o Enum diretamente
$status = InvoiceStatus::PENDING; // Melhor abordagem
$value = $status->value; // 'pending'

// ✅ Comparação segura:
if ($invoice->status === InvoiceStatus::PENDING->value) {
    // Faz algo
}

// ✅ Verificação de igualdade:
if (InvoiceStatus::isValid($someValue)) {
    $status = InvoiceStatus::from($someValue);
}
```

**Boas Práticas para Case Sensitivity:**

1. ✅ **Sempre usar o Enum diretamente** quando possível
2. ✅ **Validar valores de entrada** antes de converter para Enum
3. ✅ **Usar métodos helper** como `isValid()` para verificar valores
4. ✅ **Manter consistência** entre valores no banco e no Enum
5. ❌ **Nunca assumir** que valores de entrada são válidos
6. ❌ **Evitar comparações diretas** de strings sem validação

---

### 📋 Exemplos Práticos

#### Exemplo 1: Filtro por Status

```blade
{{-- Filtro seguro por status --}}
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-filter me-1"></i> Filtrar por Status</h5>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('provider.invoices.index') }}">
            <div class="row g-3">
                <div class="col-md-4">
                    <select name="status" class="form-control">
                        <option value="">Todos os status</option>
                        @foreach(\App\Enums\InvoiceStatus::options() as $value => $label)
                            <option value="{{ $value }}" {{ request('status') === $value ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search me-1"></i> Filtrar
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
```

#### Exemplo 2: Badge de Status com Tooltip

```blade
@if($invoice->status)
    <span class="badge bg-{{ \App\Enums\InvoiceStatus::color($invoice->status) }}"
          title="{{ \App\Enums\InvoiceStatus::label($invoice->status) }}">
        <i class="{{ \App\Enums\InvoiceStatus::icon($invoice->status) }} me-1"></i>
        {{ \App\Enums\InvoiceStatus::label($invoice->status) }}
    </span>
@else
    <span class="badge bg-secondary" title="Sem status definido">
        <i class="bi-question-circle me-1"></i>
        Sem status
    </span>
@endif
```

#### Exemplo 3: Tabela com Status Coloridos

```blade
<table class="table">
    <thead>
        <tr>
            <th>Código</th>
            <th>Cliente</th>
            <th>Valor</th>
            <th>Status</th>
            <th>Ações</th>
        </tr>
    </thead>
    <tbody>
        @forelse($invoices as $invoice)
            <tr>
                <td>{{ $invoice->code }}</td>
                <td>{{ $invoice->customer->name }}</td>
                <td>{{ format_currency($invoice->total) }}</td>
                <td>
                    @if($invoice->status)
                        <span class="badge bg-{{ \App\Enums\InvoiceStatus::color($invoice->status) }}">
                            {{ \App\Enums\InvoiceStatus::label($invoice->status) }}
                        </span>
                    @else
                        <span class="badge bg-secondary">Sem status</span>
                    @endif
                </td>
                <td>
                    <a href="{{ route('provider.invoices.show', $invoice->code) }}" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-eye"></i>
                    </a>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="5" class="text-center text-muted">
                    <i class="bi bi-inbox mb-2" style="font-size: 2rem;"></i>
                    <br>
                    Nenhuma fatura encontrada.
                </td>
            </tr>
        @endforelse
    </tbody>
</table>
```

---

### ⚠️ Erros Comuns e Soluções

#### Erro 1: Valor vazio para Enum

```php
// ❌ Causa erro:
$status = InvoiceStatus::from(''); // ValueError: "" is not a valid backing value

// ✅ Solução:
if (!empty($value) && InvoiceStatus::isValid($value)) {
    $status = InvoiceStatus::from($value);
}
```

#### Erro 2: Case sensitivity

```php
// ❌ Causa erro:
$status = InvoiceStatus::from('PENDING'); // Erro! Deve ser 'pending'

// ✅ Solução:
$status = InvoiceStatus::from(strtolower($input)); // Se necessário converter
// Ou melhor:
$status = InvoiceStatus::PENDING; // Usar o Enum diretamente
```

#### Erro 3: Valor não válido

```php
// ❌ Causa erro:
$status = InvoiceStatus::from('invalid_status');

// ✅ Solução:
if (InvoiceStatus::isValid($value)) {
    $status = InvoiceStatus::from($value);
} else {
    // Tratar erro ou usar valor padrão
    $status = InvoiceStatus::PENDING;
}
```

---

### 🎯 Checklist para Uso de Enums

-  [ ] Definir Enum com backing type adequado (string/int)
-  [ ] Implementar métodos helper (values, options, labels, etc.)
-  [ ] Validar entradas de usuário antes de converter para Enum
-  [ ] Usar Enum diretamente sempre que possível
-  [ ] Implementar verificações de null/empty antes de usar
-  [ ] Documentar todos os casos de uso do Enum
-  [ ] Testar todos os valores do Enum
-  [ ] Manter consistência entre banco de dados e Enum

---

### 📚 Referência Rápida

```php
// Obter todos os valores
InvoiceStatus::values();

// Obter opções para select
InvoiceStatus::options();

// Obter label legível
InvoiceStatus::label('pending'); // "Pendente"

// Verificar se valor é válido
InvoiceStatus::isValid('pending'); // true

// Obter cor para badge
InvoiceStatus::color('pending'); // "warning"

// Obter ícone
InvoiceStatus::icon('pending'); // "bi-hourglass-split"

// Usar Enum diretamente (melhor prática)
$status = InvoiceStatus::PENDING;
$value = $status->value; // "pending"
```

---

## 📊 1. DASHBOARD Pattern

### Cabeçalho (Responsivo)

```blade
<div class="mb-4">
    <div class="d-flex justify-content-between align-items-start mb-2">
        <div class="flex-grow-1">
            <h1 class="h4 h3-md mb-1">
                <i class="bi bi-[icone] me-2"></i>
                <span class="d-none d-sm-inline">Dashboard de [Módulo]</span>
                <span class="d-sm-none">[Módulo]</span>
            </h1>
        </div>
        <nav aria-label="breadcrumb" class="d-none d-md-block">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('provider.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Dashboard de [Módulo]</li>
            </ol>
        </nav>
    </div>
    <p class="text-muted mb-0 small">Descrição contextual do dashboard</p>
</div>
```

### Cards de Métricas (4 colunas)

```blade
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex flex-column justify-content-between">
                <div class="d-flex align-items-center mb-3">
                    <div class="avatar-circle bg-primary bg-gradient me-3">
                        <i class="bi bi-[icone] text-white"></i>
                    </div>
                    <div>
                        <h6 class="text-muted mb-1">Título da Métrica</h6>
                        <h3 class="mb-0">{{ $valor }}</h3>
                    </div>
                </div>
                <p class="text-muted small mb-0">Descrição da métrica</p>
            </div>
        </div>
    </div>
    <!-- Repetir para outras métricas -->
</div>
```

**Cores de Avatar:**

-  `bg-primary` - Métrica principal/total
-  `bg-success` - Métricas positivas/ativas
-  `bg-secondary` - Métricas neutras/inativas
-  `bg-info` - Métricas de análise/percentuais
-  `bg-warning` - Métricas de atenção
-  `bg-danger` - Métricas críticas

### Layout 8-4 (Conteúdo + Sidebar)

```blade
<div class="row g-4">
    <!-- Conteúdo Principal (8 colunas) -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-0">
                <h5 class="mb-0">
                    <i class="bi bi-[icone] me-2"></i>
                    <span class="d-none d-sm-inline">Título Completo</span>
                    <span class="d-sm-none">Título Curto</span>
                </h5>
            </div>
            <div class="card-body p-0">
                <!-- Desktop View -->
                <div class="desktop-view">
                    <div class="table-responsive">
                        <table class="modern-table table mb-0">
                            <thead>
                                <tr>
                                    <th>Coluna 1</th>
                                    <th class="text-center">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Conteúdo</td>
                                    <td class="text-center">
                                        <a href="#" class="btn btn-sm btn-outline-secondary">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Mobile View -->
                <div class="mobile-view">
                    <div class="list-group">
                        <a href="#" class="list-group-item list-group-item-action py-3">
                            <div class="d-flex align-items-start">
                                <i class="bi bi-[icone] text-muted me-2 mt-1"></i>
                                <div class="flex-grow-1">
                                    <div class="fw-semibold mb-2">Título do Item</div>
                                    <div class="d-flex gap-2 flex-wrap">
                                        <span class="badge bg-primary" title="Pessoal"><i class="bi bi-person-fill"></i></span>
                                        <span class="badge bg-success-subtle text-success">Ativa</span>
                                    </div>
                                </div>
                                <i class="bi bi-chevron-right text-muted ms-2"></i>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sidebar (4 colunas) -->
    <div class="col-lg-4">
        <!-- Insights -->
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-transparent border-0">
                <h6 class="mb-0"><i class="bi bi-lightbulb me-2"></i>Insights Rápidos</h6>
            </div>
            <div class="card-body">
                <ul class="list-unstyled mb-0 small text-muted">
                    <li class="mb-2">
                        <i class="bi bi-[icone] text-primary me-2"></i>Dica 1
                    </li>
                </ul>
            </div>
        </div>

        <!-- Atalhos -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-0">
                <h6 class="mb-0"><i class="bi bi-link-45deg me-2"></i>Atalhos</h6>
            </div>
            <div class="card-body d-grid gap-2">
                <a href="{{ route('[modulo].create') }}" class="btn btn-sm btn-success">
                    <i class="bi bi-plus-circle me-2"></i>Novo [Item]
                </a>
                <a href="{{ route('[modulo].index') }}" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-[icone] me-2"></i>Listar [Itens]
                </a>
                <a href="{{ route('[modulo].index', ['deleted' => 'only']) }}"
                    class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-archive me-2"></i>Ver Deletados
                </a>
            </div>
        </div>
    </div>
</div>
```

---

## 📋 2. INDEX (Listagem) Pattern

### Cabeçalho

```blade
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0">
            <i class="bi bi-[icone] me-2"></i>[Módulo Plural]
        </h1>
        <p class="text-muted">Lista de todos os [itens] registrados no sistema</p>
    </div>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('provider.dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('[modulo].dashboard') }}">[Módulo]</a></li>
            <li class="breadcrumb-item active">Listar</li>
        </ol>
    </nav>
</div>
```

### Card de Filtros

```blade
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-filter me-1"></i> Filtros de Busca</h5>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('[modulo].index') }}">
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="search">Buscar</label>
                        <input type="text" class="form-control" id="search" name="search"
                            value="{{ $filters['search'] ?? '' }}" placeholder="...">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label for="active">Status</label>
                        <select class="form-control" id="active" name="active">
                            <option value="">Todos</option>
                            <option value="1">Ativo</option>
                            <option value="0">Inativo</option>
                        </select>
                    </div>
                </div>
                <div class="col-12">
                    <div class="d-flex gap-2 flex-nowrap">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search me-1"></i>Filtrar
                        </button>
                        <a href="{{ route('[modulo].index') }}" class="btn btn-secondary">
                            <i class="bi bi-x me-1"></i>Limpar
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
```

### Card de Tabela

```blade
<div class="card">
    <div class="card-header">
        <div class="row align-items-center">
            <div class="col-12 col-lg-8 mb-2 mb-lg-0">
                <h5 class="mb-0 d-flex align-items-center flex-wrap">
                    <span class="me-2">
                        <i class="bi bi-list-ul me-1"></i>
                        <span class="d-none d-sm-inline">Lista de [Itens]</span>
                        <span class="d-sm-none">[Itens]</span>
                    </span>
                    <span class="text-muted" style="font-size: 0.875rem;">
                        ({{ $items->total() }})
                    </span>
                </h5>
            </div>
            <div class="col-12 col-lg-4 mt-2 mt-lg-0">
                <div class="d-flex justify-content-start justify-content-lg-end">
                    <a href="{{ route('[modulo].create') }}" class="btn btn-primary btn-sm">
                        <i class="bi bi-plus"></i>
                        <span class="ms-1">Novo</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="desktop-view">
            <div class="table-responsive">
                <table class="modern-table table mb-0">
                    <thead>
                        <tr>
                            <th>Coluna 1</th>
                            <th class="text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $item)
                            <tr>
                                <td>{{ $item->name }}</td>
                                <td class="text-center">
                                    <!-- Botões de ação -->
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="X" class="text-center text-muted">
                                    <i class="bi bi-inbox mb-2" style="font-size: 2rem;"></i>
                                    <br>
                                    Nenhum [item] encontrado.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @if ($items instanceof \Illuminate\Pagination\LengthAwarePaginator && $items->hasPages())
        @include('partials.components.paginator', ['p' => $items->appends(request()->query()), 'show_info' => true])
    @endif
</div>
```

---

## ➕ 3. CREATE Pattern

### Cabeçalho

```blade
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0">
            <i class="bi bi-[icone-especifico] me-2"></i>Novo [Item]
        </h1>
        <p class="text-muted mb-0">Preencha os dados para criar um novo [item]</p>
    </div>
    <nav aria-label="breadcrumb" class="d-none d-md-block">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('provider.dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('[modulo].index') }}">[Módulo]</a></li>
            <li class="breadcrumb-item active" aria-current="page">Novo</li>
        </ol>
    </nav>
</div>
```

### Card de Formulário

```blade
<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        @if ($errors->any())
            <div class="alert alert-danger" role="alert">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('[modulo].store') }}" method="POST">
            @csrf

            <div class="row g-4">
                <div class="col-md-12">
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control @error('name') is-invalid @enderror"
                            id="name" name="name" placeholder="Nome" value="{{ old('name') }}" required>
                        <label for="name">Nome *</label>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-between mt-4">
                <div>
                    <a href="{{ url()->previous(route('[modulo].index')) }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-2"></i>Cancelar
                    </a>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle me-2"></i>Criar
                </button>
            </div>
        </form>
    </div>
</div>
```

---

## ✏️ 4. EDIT Pattern

### Cabeçalho

```blade
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0">
            <i class="bi bi-pencil-square me-2"></i>Editar [Item]
        </h1>
        <p class="text-muted mb-0">Atualize as informações do [item]</p>
    </div>
    <nav aria-label="breadcrumb" class="d-none d-md-block">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('provider.dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('[modulo].index') }}">[Módulo]</a></li>
            <li class="breadcrumb-item"><a href="{{ route('[modulo].show', $item->slug) }}">{{ $item->name }}</a></li>
            <li class="breadcrumb-item active" aria-current="page">Editar</li>
        </ol>
    </nav>
</div>
```

### Card de Formulário

```blade
<form action="{{ route('[modulo].update', $item->slug) }}" method="POST">
    @csrf
    @method('PUT')

    <div class="row g-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-transparent">
                    <h5 class="mb-0">
                        <i class="bi bi-[icone] me-2"></i>Informações do [Item]
                    </h5>
                </div>
                <div class="card-body">
                    <!-- Campos do formulário -->
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between mt-4">
        <div>
            <a href="{{ url()->previous(route('[modulo].index')) }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>Cancelar
            </a>
        </div>
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-circle me-2"></i>Salvar
        </button>
    </div>
</form>
```

---

## 👁️ 5. SHOW (Detalhes) Pattern

### Cabeçalho

```blade
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0 text-gray-800">
        <i class="bi bi-[icone] me-2"></i>Detalhes do [Item]
    </h1>
</div>
```

### Card de Detalhes

```blade
<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <div class="row g-4">
            <div class="col-md-3">
                <div class="d-flex flex-column">
                    <label class="text-muted small mb-1">Campo</label>
                    <h5 class="mb-0">{{ $item->campo }}</h5>
                </div>
            </div>
            <!-- Repetir para outros campos -->
        </div>
    </div>
</div>
```

### Botões de Ação (Footer)

```blade
<div class="d-flex justify-content-between align-items-center mt-4">
    <div class="d-flex gap-2">
        <a href="{{ route('[modulo].index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Voltar
        </a>
    </div>
    <small class="text-muted">
        Última atualização: {{ $item->updated_at?->format('d/m/Y H:i') }}
    </small>
    <div class="d-flex gap-2">
        <a href="{{ route('[modulo].edit', $item->slug) }}" class="btn btn-primary">
            <i class="bi bi-pencil-fill me-2"></i>Editar
        </a>
        <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal">
            <i class="bi bi-trash-fill me-2"></i>Excluir
        </button>
    </div>
</div>
```

---

## 🎨 Padrões de Componentes

### Badges de Status

```blade
<!-- Ativo/Inativo -->
<span class="modern-badge {{ $item->is_active ? 'badge-active' : 'badge-inactive' }}">
    {{ $item->is_active ? 'Ativo' : 'Inativo' }}
</span>

<!-- Tipo (Sistema/Pessoal) -->
<span class="modern-badge {{ $isCustom ? 'badge-personal' : 'badge-system' }}">
    {{ $isCustom ? 'Pessoal' : 'Sistema' }}
</span>

<!-- Bootstrap Badges -->
<span class="badge bg-success">Ativo</span>
<span class="badge bg-danger">Inativo</span>
<span class="badge bg-primary">Pessoal</span>
<span class="badge bg-secondary">Sistema</span>
```

### Botões de Ação (Tabela)

```blade
<div class="d-flex justify-content-center gap-2">
    <!-- Visualizar -->
    <a href="{{ route('[modulo].show', $item) }}" class="btn btn-sm btn-outline-primary" title="Visualizar">
        <i class="bi bi-eye"></i>
    </a>

    <!-- Editar -->
    <a href="{{ route('[modulo].edit', $item) }}" class="btn btn-sm btn-outline-secondary" title="Editar">
        <i class="bi bi-pencil"></i>
    </a>

    <!-- Excluir (com modal) -->
    <button type="button" class="btn btn-sm btn-outline-danger"
            data-bs-toggle="modal"
            data-bs-target="#deleteModal{{ $item->id }}"
            title="Excluir">
        <i class="bi bi-trash"></i>
    </button>
</div>

<!-- Modal de Confirmação -->
<div class="modal fade" id="deleteModal{{ $item->id }}" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar Exclusão</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Tem certeza que deseja excluir "{{ $item->name }}"?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form action="{{ route('[modulo].destroy', $item) }}" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Excluir</button>
                </form>
            </div>
        </div>
    </div>
</div>
```

---

## 📱 Padrões de Responsividade

### Classes de Quebra

```blade
<!-- Desktop primeiro, mobile segundo -->
<div class="d-none d-md-block">Visível apenas no desktop</div>
<div class="d-md-none">Visível apenas no mobile</div>

<!-- Textos responsivos -->
<h1 class="h3 h2-md">Título responsivo</h1>
<p class="text-muted small text-md-normal">Texto responsivo</p>

<!-- Botões responsivos -->
<button class="btn btn-primary btn-sm btn-md-normal">
    Botão responsivo
</button>
```

### Grid Responsivo

```blade
<div class="row g-3">
    <div class="col-12 col-md-6 col-lg-4">
        <!-- Coluna que se adapta: 1 coluna no mobile, 2 no tablet, 3 no desktop -->
    </div>
</div>
```

### Tabelas Responsivas

```blade
<div class="table-responsive">
    <table class="table">
        <!-- Tabela que vira cards no mobile -->
    </table>
</div>
```

---

## 🏷️ Ícones Bootstrap Icons por Contexto

### Ações Principais

-  **Criar/Novo**: `bi-plus-circle`
-  **Editar**: `bi-pencil`
-  **Visualizar**: `bi-eye`
-  **Excluir**: `bi-trash`
-  **Salvar**: `bi-check-circle`
-  **Cancelar**: `bi-x-circle`

### Ações Secundárias

-  **Download**: `bi-download`
-  **Upload**: `bi-upload`
-  **Exportar**: `bi-file-earmark-arrow-down`
-  **Importar**: `bi-file-earmark-arrow-up`
-  **Imprimir**: `bi-printer`
-  **Compartilhar**: `bi-share`

### Status e Indicadores

-  **Ativo**: `bi-check-circle text-success`
-  **Inativo**: `bi-x-circle text-danger`
-  **Pendente**: `bi-hourglass-split text-warning`
-  **Concluído**: `bi-check-all text-success`

### Navegação

-  **Voltar**: `bi-arrow-left`
-  **Avançar**: `bi-arrow-right`
-  **Home**: `bi-house`
-  **Menu**: `bi-list`

### Módulos Específicos

-  **Categorias**: `bi-tags`
-  **Produtos**: `bi-box-seam`
-  **Clientes**: `bi-people`
-  **Orçamentos**: `bi-file-earmark-text`
-  **Faturas**: `bi-receipt`
-  **Serviços**: `bi-gear`
-  **Relatórios**: `bi-graph-up`
-  **Configurações**: `bi-gear-fill`

---

## ✅ Checklist de Implementação

### Antes de Criar uma Nova View

-  [ ] Verificar se existe pattern correspondente neste documento
-  [ ] Usar layout base `container-fluid py-1`
-  [ ] Implementar breadcrumbs quando necessário
-  [ ] Usar ícones apropriados do Bootstrap Icons
-  [ ] Garantir responsividade (desktop + mobile)
-  [ ] Implementar empty states com CTAs
-  [ ] Usar sistema de paginação quando aplicável

### Antes do Commit

-  [ ] Verificar se todos os padrões foram seguidos
-  [ ] Testar responsividade em diferentes tamanhos
-  [ ] Validar se breadcrumbs estão corretos
-  [ ] Confirmar se todos os links funcionam
-  [ ] Verificar se paginação está implementada
-  [ ] Testar modais e confirmações
-  [ ] Validar accessibility (labels, alt texts)

### Estrutura de Arquivos

```
resources/views/pages/
├── [module]/
│   ├── dashboard.blade.php    # Se aplicável
│   ├── index.blade.php        # Listagem
│   ├── create.blade.php       # Criação
│   ├── edit.blade.php         # Edição
│   ├── show.blade.php         # Detalhes
│   └── components/            # Componentes específicos
```

### Convenções de Nomenclatura

-  **Views**: snake_case (index, create, edit, show)
-  **Routes**: kebab-case (provider.categories.index)
-  **Controllers**: PascalCase (CategoryController)
-  **Models**: PascalCase (Category)
-  **Methods**: camelCase (getCategories)

---

## 🎯 Referência Rápida - Copy & Paste

### Container Base

```blade
@extends('layouts.app')
@section('title', 'Título da Página')
@section('content')
    <div class="container-fluid py-1">
        <!-- Seu conteúdo aqui -->
    </div>
@endsection
```

### Cabeçalho com Breadcrumbs

```blade
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0">
            <i class="bi bi-[icone] me-2"></i>Título da Página
        </h1>
        <p class="text-muted">Descrição da página</p>
    </div>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('provider.dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item active">Página Atual</li>
        </ol>
    </nav>
</div>
```

### Card de Filtros

```blade
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-filter me-1"></i> Filtros de Busca</h5>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('[modulo].index') }}">
            <div class="row g-3">
                <div class="col-md-4">
                    <input type="text" class="form-control" name="search" placeholder="Buscar...">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search me-1"></i>Filtrar
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
```

### Tabela com Paginação

```blade
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Coluna 1</th>
                        <th class="text-center">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                        <tr>
                            <td>{{ $item->name }}</td>
                            <td class="text-center">
                                <a href="{{ route('[modulo].show', $item) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="text-center text-muted">
                                <i class="bi bi-inbox mb-2" style="font-size: 2rem;"></i>
                                <br>
                                Nenhum item encontrado.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if ($items instanceof \Illuminate\Pagination\LengthAwarePaginator && $items->hasPages())
        {{ $items->links() }}
    @endif
</div>
```

### Empty State com CTA

```blade
<div class="text-center py-5">
    <i class="bi bi-inbox text-muted" style="font-size: 4rem;"></i>
    <h3 class="mt-3 text-muted">Nenhum item encontrado</h3>
    <p class="text-muted">Comece criando seu primeiro item.</p>
    <a href="{{ route('[modulo].create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle me-2"></i>Criar Primeiro Item
    </a>
</div>
```

### Formulário Básico

```blade
<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('[modulo].store') }}" method="POST">
            @csrf
            <div class="mb-3">
                <label for="name" class="form-label">Nome *</label>
                <input type="text" class="form-control @error('name') is-invalid @enderror"
                       id="name" name="name" value="{{ old('name') }}" required>
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="d-flex justify-content-between">
                <a href="{{ route('[modulo].index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-2"></i>Cancelar
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle me-2"></i>Criar
                </button>
            </div>
        </form>
    </div>
</div>
```

---

## 🔗 11. Integração com Backend

### Arquitetura Completa

```blade
{{-- Exemplo de como views se integram com controllers, services e repositories --}}

{{-- 1. Controller (app/Http/Controllers/CategoryController.php) --}}
{{-- @see app/Http/Controllers/CategoryController.php --}}
{{-- - Recebe requisições HTTP --}}
{{-- - Chama métodos do Service --}}
{{-- - Retorna views com dados processados --}}

{{-- 2. Service (app/Services/CategoryService.php) --}}
{{-- @see app/Services/CategoryService.php --}}
{{-- - Contém lógica de negócio --}}
{{-- - Usa Repository para acesso a dados --}}
{{-- - Retorna ServiceResult padronizado --}}

{{-- 3. Repository (app/Repositories/CategoryRepository.php) --}}
{{-- @see app/Repositories/CategoryRepository.php --}}
{{-- - Acesso direto ao banco de dados --}}
{{-- - Implementa métodos de consulta --}}
{{-- - Usa Eloquent ORM --}}

{{-- 4. View (resources/views/pages/category/index.blade.php) --}}
{{-- - Recebe dados do Controller --}}
{{-- - Renderiza interface para usuário --}}
{{-- - Usa componentes Blade --}}
```

### Exemplo de Fluxo Completo

```blade
{{-- 1. Rota (routes/web.php) --}}
{{-- Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index'); --}}

{{-- 2. Controller --}}
{{-- public function index(Request $request) --}}
{{-- { --}}
{{--     $result = $this->categoryService->listWithFilters($request->all()); --}}
{{--     return view('pages.category.index', ['categories' => $result->getData()]); --}}
{{-- } --}}

{{-- 3. Service --}}
{{-- public function listWithFilters(array $filters): ServiceResult --}}
{{-- { --}}
{{--     $query = $this->repository->getQueryBuilder(); --}}
{{--     if (!empty($filters['search'])) { --}}
{{--         $query->where('name', 'like', '%'.$filters['search'].'%'); --}}
{{--     } --}}
{{--     return $this->success($query->paginate(15)); --}}
{{-- } --}}

{{-- 4. Repository --}}
{{-- public function getQueryBuilder() --}}
{{-- { --}}
{{--     return Category::query() --}}
{{--         ->where('tenant_id', auth()->user()->tenant_id) --}}
{{--         ->orderBy('name'); --}}
{{-- } --}}

{{-- 5. View (index.blade.php) --}}
{{-- @foreach($categories as $category) --}}
{{--     <tr> --}}
{{--         <td>{{ $category->name }}</td> --}}
{{--         <td>{{ $category->slug }}</td> --}}
{{--         <td> --}}
{{--             <a href="{{ route('categories.edit', $category->id) }}" class="btn btn-sm btn-outline-primary"> --}}
{{--                 <i class="bi bi-pencil"></i> --}}
{{--             </a> --}}
{{--         </td> --}}
{{--     </tr> --}}
{{-- @endforeach --}}
```

### Padrões de Integração

```blade
{{-- ✅ Padrão Recomendado: --}}
{{-- 1. Controller → Service → Repository → Model --}}
{{-- 2. Usar ServiceResult para respostas padronizadas --}}
{{-- 3. Injeção de dependência via constructor --}}
{{-- 4. Validação via Form Requests --}}
{{-- 5. Autorização via Gates/Policies --}}

{{-- ❌ Evitar: --}}
{{-- 1. Acesso direto ao Model na View --}}
{{-- 2. Lógica de negócio na View --}}
{{-- 3. Queries SQL diretas na View --}}
{{-- 4. Cálculos complexos na View --}}
```

### Exemplo de Formulário com Integração

```blade
{{-- Formulário de criação com validação --}}
<form action="{{ route('categories.store') }}" method="POST">
    @csrf

    <div class="mb-3">
        <label for="name" class="form-label">Nome *</label>
        <input type="text" class="form-control @error('name') is-invalid @enderror"
               id="name" name="name" value="{{ old('name') }}" required>
        @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <button type="submit" class="btn btn-primary">
        <i class="bi bi-check-circle me-2"></i>Criar Categoria
    </button>
</form>

{{-- Validação via Form Request --}}
{{-- @see app/Http/Requests/CategoryStoreRequest.php --}}
{{-- - Valida campos obrigatórios --}}
{{-- - Valida formatos de dados --}}
{{-- - Retorna mensagens de erro --}}
```

### Exemplo de Tabela com Dados do Backend

```blade
{{-- Tabela com dados paginados --}}
<table class="table">
    <thead>
        <tr>
            <th>Nome</th>
            <th>Slug</th>
            <th>Status</th>
            <th>Ações</th>
        </tr>
    </thead>
    <tbody>
        @forelse($categories as $category)
            <tr>
                <td>{{ $category->name }}</td>
                <td>{{ $category->slug }}</td>
                <td>
                    <span class="badge bg-{{ $category->is_active ? 'success' : 'secondary' }}">
                        {{ $category->is_active ? 'Ativo' : 'Inativo' }}
                    </span>
                </td>
                <td>
                    <div class="d-flex gap-2">
                        <a href="{{ route('categories.edit', $category->id) }}"
                           class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <form action="{{ route('categories.destroy', $category->id) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger"
                                    onclick="return confirm('Tem certeza?')">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="4" class="text-center text-muted">
                    <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                    <p class="mb-0">Nenhuma categoria encontrada.</p>
                </td>
            </tr>
        @endforelse
    </tbody>
</table>

{{-- Paginação --}}
<div class="mt-4">
    {{ $categories->links() }}
</div>
```

### Melhores Práticas

```blade
{{-- ✅ Fazer: --}}
{{-- 1. Usar @foreach para listagens --}}
{{-- 2. Usar @if/@unless para condicionais --}}
{{-- 3. Usar @error para mensagens de validação --}}
{{-- 4. Usar componentes Blade para reutilização --}}
{{-- 5. Manter views focadas apenas na apresentação --}}

{{-- ❌ Evitar: --}}
{{-- 1. Lógica complexa na view --}}
{{-- 2. Queries diretas ao banco --}}
{{-- 3. Cálculos matemáticos complexos --}}
{{-- 4. Manipulação de dados brutos --}}
{{-- 5. Chamadas a serviços externos --}}
```

---

---

# 📋 PROJETO DE PADRÕES DE INTERFACE - TODO

## ✅ TAREAS CONCLUÍDAS (12/2025)

### Análise de Padrões e Correções

-  [x] **Análise completa de padrões vs implementação atual**

   -  Verificação de consistência entre padrões definidos e implementação
   -  Identificação de 15 inconsistências críticas em múltiplos módulos
   -  Documentação de desvios e correções necessárias

-  [x] **Correção do Schedule Index (100%)**

   -  Container padrão `container-fluid py-1` implementado
   -  Filtros separados em card próprio com classe `card mb-4`
   -  Botão "Novo" posicionado corretamente no header da tabela
   -  Estrutura responsiva desktop/mobile implementada
   -  Paginação com opção "por página" configurada

-  [x] **Correção do Schedule Calendar (100%)**

   -  Estrutura padronizada com breadcrumbs administrativo
   -  Layout responsivo mantido
   -  Consistência com padrões de interface implementada

-  [x] **Correção de 6 relatórios principais (100%)**

   -  Dashboard de relatórios com container padrão
   -  Filtros implementados em todos os relatórios
   -  Mobile view completa para todos os módulos
   -  Paginação configurada adequadamente
   -  URLs hardcoded corrigidas por helpers Laravel

-  [x] **Correção de 2 módulos principais (100%)**

   -  Invoice Index: Container `container-fluid py-1` implementado
   -  Service Index: Estrutura padronizada aplicada
   -  Tabelas responsivas e ações em modais

-  [x] **Correção de 4 views admin de prioridade ALTA (100%)**
   -  Alerts Index: Estrutura padronizada implementada
   -  Advanced Metrics: Layout responsivo corrigido
   -  Financial Index: Container e filtros padronizados
   -  Enterprises Index: Estrutura consistente aplicada

### Sistema de Relatórios

-  [x] **Dashboard de relatórios com 6 cards principais**

   -  Cards de métricas implementados
   -  Layout 8-4 (conteúdo + sidebar) configurado
   -  Cores de avatar padronizadas

-  [x] **Correção de URLs hardcoded por helpers Laravel**

   -  Substituição de URLs fixas por `route()` helpers
   -  31 rotas verificadas e corrigidas
   -  Consistência de navegação implementada

-  [x] **Criação da view analytics com métricas avançadas**

   -  Interface de analytics criada
   -  Métricas avançadas implementadas
   -  Gráficos e visualizações integradas

-  [x] **Verificação e correção de todas as rotas (31 rotas funcionais)**

   -  Rotas do sistema de relatórios verificadas
   -  URLs atualizadas para padrão Laravel
   -  Navegação funcional em todo o sistema

-  [x] **Atualização do menu navbar com dashboard reports**
   -  Link para dashboard reports adicionado
   -  Estrutura de navegação atualizada
   -  Breadcrumbs administrativo configurado

### Estrutura e Arquitetura

-  [x] **Container padrão `container-fluid py-1` implementado**

   -  Aplicado consistentemente em todas as views
   -  Padding padronizado para layout responsivo

-  [x] **Sistema de breadcrumbs administrativo**

   -  Breadcrumbs implementados em todas as views
   -  Estrutura hierárquica consistente
   -  Navegação intuitiva configurada

-  [x] **Empty states padronizados com CTAs**

   -  Estados vazios implementados com ícones
   -  Call-to-actions apropriados configurados
   -  Feedback visual consistente

-  [x] **Mobile view completa em todos os módulos**

   -  Views responsivas implementadas
   -  Desktop/mobile view configurado
   -  Navegação otimizada para dispositivos móveis

-  [x] **Paginação com opção "por página"**

   -  Paginação configurada em todos os módulos
   -  Opção de items por página implementada
   -  Performance otimizada para grandes datasets

-  [x] **Sistema de ações avançado com modais**
   -  Modais de confirmação implementados
   -  Ações em lote configuradas
   -  Interface de ações padronizada

## 📊 ESTATÍSTICAS FINAIS

-  **Arquivos analisados:** 25+ arquivos de views
-  **Problemas identificados:** 15 inconsistências críticas
-  **Arquivos corrigidos:** 15 arquivos principais
-  **Conformidade final:**
   -  Schedule: 98% ✅
   -  Reports: 98% ✅
   -  Modules: 98% ✅
   -  Admin Views: 95% ✅
-  **Relatórios gerados:** 4 relatórios detalhados
-  **Tempo investido:** 8+ horas de análise e implementação

## 🎯 RESULTADOS ALCANÇADOS

### Conformidade de Padrões

-  **98% de conformidade** nos módulos principais (Schedule, Reports, Modules)
-  **95% de conformidade** nas views administrativas
-  **100% das URLs** convertidas para helpers Laravel
-  **100% da navegação** funcionando corretamente

### Melhorias Implementadas

-  **Interface padronizada** em todos os módulos
-  **Responsividade completa** para dispositivos móveis
-  **Performance otimizada** com paginação adequada
-  **UX consistente** em todo o sistema

### Documentação Produzida

-  **4 relatórios detalhados** de análise e correções
-  **TODO.md atualizado** com status do projeto
-  **Padrões documentados** para futuras implementações

---

/\*\*

-  TODO: IMPLEMENTAR SISTEMA DE RESERVAS COMPLETO
-
-  Funcionalidades pendentes:
-  1. Criar tabela inventory_reservations (product_id, quantity, reserved_by_type, reserved_by_id, status, expires_at)
-  2. Implementar lógica de reserva real (diminuir estoque disponível)
-  3. Implementar expiração automática de reservas
-  4. Adicionar campo reserved_quantity na tabela inventories
-  5. Calcular estoque disponível = quantity - reserved_quantity
-  6. Criar job para limpar reservas expiradas
-  7. Atualizar métodos reserveProduct() e releaseReservation() com lógica real
      \*/

## 🔄 Sistema de Reservas de Estoque (PENDENTE)

### Objetivo

Implementar sistema completo de reservas de estoque para controlar produtos reservados vs disponíveis.

### Tarefas

#### 1. Estrutura de Banco de Dados

-  [ ] Criar migration para tabela `inventory_reservations`
   -  Campos: `id`, `tenant_id`, `product_id`, `quantity`, `reserved_by_type`, `reserved_by_id`, `status`, `expires_at`, `created_at`, `updated_at`
-  [ ] Adicionar campo `reserved_quantity` na tabela `inventories`
-  [ ] Criar índices para performance (product_id, tenant_id, status, expires_at)

#### 2. Models e Relacionamentos

-  [ ] Criar model `InventoryReservation`
-  [ ] Adicionar relacionamentos em `Product` e `Inventory`
-  [ ] Implementar scopes (active, expired, byProduct)

#### 3. Lógica de Negócio

-  [ ] Atualizar `InventoryService::reserveProduct()` com lógica real
   -  Validar estoque disponível (quantity - reserved_quantity)
   -  Criar registro em inventory_reservations
   -  Incrementar reserved_quantity
-  [ ] Atualizar `InventoryService::releaseReservation()` com lógica real
   -  Marcar reserva como liberada
   -  Decrementar reserved_quantity
-  [ ] Criar método `InventoryService::getAvailableStock()` (quantity - reserved_quantity)

#### 4. Expiração de Reservas

-  [ ] Criar job `ExpireInventoryReservations`
-  [ ] Agendar job no Kernel (rodar a cada hora)
-  [ ] Implementar lógica de expiração automática
-  [ ] Notificar quando reserva expirar

#### 5. Testes

-  [ ] Testes unitários para InventoryService
-  [ ] Testes de integração para fluxo completo
-  [ ] Testes de expiração de reservas

#### 6. Documentação

-  [ ] Documentar fluxo de reservas
-  [ ] Atualizar diagramas de banco de dados
-  [ ] Criar guia de uso para desenvolvedores

### Prioridade

**Média** - Sistema funciona sem reservas reais, mas implementação futura melhora controle de estoque.

### Estimativa

**8-12 horas** de desenvolvimento + testes
