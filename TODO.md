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
<div class="action-btn-group">
    <a href="{{ route('[modulo].show', $item->slug) }}" class="action-btn action-btn-view" title="Visualizar">
        <i class="bi bi-eye-fill"></i>
    </a>
    <a href="{{ route('[modulo].edit', $item->slug) }}" class="action-btn action-btn-edit" title="Editar">
        <i class="bi bi-pencil-fill"></i>
    </a>
    <button type="button" class="action-btn action-btn-delete" data-bs-toggle="modal"
        data-bs-target="#deleteModal" title="Excluir">
        <i class="bi bi-trash-fill"></i>
    </button>
</div>
```

### Modal de Confirmação

```blade
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar Exclusão</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Tem certeza de que deseja excluir <strong id="itemName"></strong>?
                <br><small class="text-muted">Esta ação não pode ser desfeita.</small>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form id="deleteForm" action="#" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Excluir</button>
                </form>
            </div>
        </div>
    </div>
</div>
```

### Empty State

```blade
<tr>
    <td colspan="X" class="text-center text-muted">
        <i class="bi bi-inbox mb-2" aria-hidden="true" style="font-size: 2rem;"></i>
        <br>
        @if (($filters['deleted'] ?? '') === 'only')
            Nenhum [item] deletado encontrado.
            <br>
            <small>Você ainda não deletou nenhum [item].</small>
        @else
            Nenhum [item] encontrado.
        @endif
    </td>
</tr>
```

---

## 🎯 Ícones Bootstrap Icons por Contexto

### ⚠️ Regra Geral de Ícones

**SEMPRE use ícones SEM `-fill`** exceto em casos específicos de badges/status.

**Exemplos:**

-  ✅ `bi-person` (correto)
-  ❌ `bi-person-fill` (incorreto)
-  ✅ `bi-tag` (correto)
-  ❌ `bi-tag-fill` (incorreto)

### Ações

-  `bi-plus` / `bi-plus-circle` - Criar/Adicionar
-  `bi-pencil-square` / `bi-pencil-fill` - Editar
-  `bi-eye` / `bi-eye-fill` - Visualizar
-  `bi-trash` / `bi-trash-fill` - Excluir
-  `bi-archive` - Ver Deletados/Arquivados
-  `bi-arrow-counterclockwise` - Restaurar
-  `bi-check-circle` / `bi-check-lg` - Confirmar/Ativar
-  `bi-x` / `bi-x-circle` - Cancelar/Fechar
-  `bi-arrow-left` - Voltar
-  `bi-search` - Buscar/Filtrar

### Status

-  `bi-check-circle-fill` - Ativo/Sucesso
-  `bi-pause-circle-fill` - Inativo/Pausado
-  `bi-exclamation-triangle` - Aviso
-  `bi-shield-lock-fill` - Segurança/Admin

### Navegação

-  `bi-house` / `bi-speedometer2` - Dashboard
-  `bi-list-ul` - Listagem
-  `bi-filter` - Filtros
-  `bi-link-45deg` - Atalhos/Links

### Informação

-  `bi-lightbulb` - Insights/Dicas
-  `bi-clock-history` - Recentes/Histórico
-  `bi-graph-up-arrow` - Métricas/Estatísticas
-  `bi-diagram-3` - Hierarquia/Estrutura
-  `bi-inbox` - Vazio/Sem dados

### Módulos Específicos

-  `bi-tags` / `bi-tag` - Categorias
-  `bi-box-seam` - Produtos
-  `bi-person` / `bi-people` - Clientes/Usuários
-  `bi-file-earmark-text` - Documentos/Relatórios

---

## 📱 Responsividade

### Classes Responsivas Padrão

```blade
<!-- Ocultar em mobile -->
<span class="d-none d-sm-inline">Texto completo</span>
<span class="d-sm-none">Texto curto</span>

<!-- Grid responsivo -->
<div class="col-12 col-md-6 col-lg-4">...</div>

<!-- Alinhamento responsivo -->
<div class="justify-content-start justify-content-lg-end">...</div>

<!-- Margem responsiva -->
<div class="mb-2 mb-lg-0">...</div>
<div class="mt-3 mt-lg-0">...</div>
```

---

## 🔗 Navegação e URLs

### Botão Cancelar (Inteligente)

```blade
<a href="{{ url()->previous(route('[modulo].index')) }}" class="btn btn-outline-secondary">
    <i class="bi bi-arrow-left me-2"></i>Cancelar
</a>
```

### Uso de SLUG

-  ✅ Sempre usar `$item->slug` nas rotas
-  ✅ Nunca usar `$item->id` em URLs públicas
-  ✅ Rotas: `route('[modulo].show', $item->slug)`

---

## ✅ Checklist de Implementação

Ao criar uma nova view, verificar:

-  [ ] Cabeçalho com ícone + título H3
-  [ ] Breadcrumb correto
-  [ ] Container `container-fluid py-1`
-  [ ] Cards com `border-0 shadow-sm`
-  [ ] Botões com ícones Bootstrap Icons
-  [ ] Empty state com ícone e mensagem contextual
-  [ ] Badges de status padronizados
-  [ ] Botão Cancelar com `url()->previous()`
-  [ ] Uso de slug nas rotas
-  [ ] Responsividade (mobile/desktop)
-  [ ] Modal de confirmação para exclusão
-  [ ] Paginação centralizada no footer
-  [ ] Mensagens de erro/sucesso

---

## Melhorias Recentes Implementadas

✅ **Categoria Edit/Create - Botão Cancelar Inteligente**

-  Implementado `url()->previous()` com fallback para `categories.index`
-  Usuário volta para onde veio (show ou index)
-  Melhor UX e navegação mais natural

✅ **Categoria Edit - Dropdown Pai Desabilitado**

-  Quando categoria tem subcategorias, dropdown fica desabilitado
-  Mensagem de alerta clara explicando o motivo
-  Campo hidden mantém valor atual

✅ **Padronização Visual Index.blade.php**

-  Estrutura consistente entre category, product, customer
-  Empty state padronizado e contextual
-  Grid responsivo unificado

✅ **Componente de Paginação Customizado**

-  Implementado componente `partials.components.paginator` reutilizável
-  Substituído paginação padrão do Laravel em category e product
-  Parâmetros: `'p'` (paginador) e `'show_info' => true`
-  Padrão atualizado no TODO.md para novos módulos

✅ **Uso Consistente de SLUG**

-  Todas as rotas de categoria usam slug
-  Links de restauração corrigidos

✅ **Botões de Ação Padronizados (Mobile-Friendly)**

-  Create: "Criar" (texto curto)
-  Edit: "Salvar" (texto curto)
-  Ambos ficam em uma linha no mobile
-  Evita quebra de linha em telas pequenas

✅ **Show Pattern - Padronização Completa**

-  Header responsivo com descrição contextual
-  Breadcrumb com `d-none d-md-block`
-  Botão Voltar com `url()->previous()`
-  Badges de tipo com ícone + tooltip (Pessoal/Sistema)
-  Timestamp só aparece para categorias custom
-  Tabela de subcategorias com coluna "Origem" centralizada
-  Botões de ação `btn-outline-secondary` (não `btn-info`)
-  Tabela de subcategorias responsiva (desktop-view/mobile-view)
-  Mobile usa list-group com badges de Origem + Status

---

## Prioridade Média

🔄 Component Modal Reutilizável: Eliminar duplicação de modais de confirmação
💱 Currency Service Centralizado: Reutilizar formatação de moeda (vanilla mask mencionado)
🎨 Interface State Standardization: Padronizar estrutura de estados

Analisei a arquitetura de categorias implementada e sua pergunta faz muito sentido. Pelo que identifiquei no código, existe um sistema híbrido onde:

**Categorias Globais**: Disponíveis para todos os tenants
**Categorias Custom**: Específicas de cada tenant (provider pode CRUD suas custom, admin pode CRUD globais)

Considerando que ambos (provider e admin) lidam com o mesmo conceito "categorias", mas com escopos diferentes, uma rota unificada `/categories/dashboard` seria **tecnicamente melhor** pelas seguintes razões:

**Vantagens da rota unificada:**

1. **UX mais intuitiva** - Ambos acessam "categorias" da mesma forma
2. **Lógica de negócio unificada** - O CategoryController já tem a lógica para distinguir entre global vs custom baseada no usuário
3. **Menor complexidade** - Não precisa de duplicação de rotas
4. **Consistência** - Facilita manutenção e futuras expansões

**Como seria implementado:**

-  Provider acessa `/categories/dashboard` → Vê suas categorias custom + globais disponíveis
-  Admin acessa `/categories/dashboard` → Vê apenas categorias globais para gerenciar
-  O CategoryController já possui toda a lógica para filtrar baseado no tipo de usuário

**Minha recomendação:** Migrar para rota unificada `/categories/dashboard` e remover as rotas separadas `/provider/categories/dashboard` e `/admin/categories/dashboard`.

Você gostaria que eu implemente essa mudança?
Sim, implemente a rota unificada /categories/dashboard. Isso simplifica a UX e aprovecha melhor a lógica já existente.

Faça uma analise, atualmene eu uso as rotas navegador https://dev.easybudget.net.br/provider/dashboard ou admin https://dev.easybudget.net.br/admin/dashboard, e tem rotas publicas https://dev.easybudget.net.br/about nesse padrao, so olhar no arquivo web.php, eu gostaria de url mais curta e seja mais legivel, mais separando as resposabilidades, eu mantenho assim ou crio novo ?

Shorten and Organize URLs
Goal
Simplify the URL structure for provider and admin sections, making them shorter and more readable while keeping clear separation of responsibilities.

Proposed Changes
Provider routes: move from /provider/... to /p/....
Update route prefix to p and keep name prefix provider. (e.g., Route::prefix('p')->name('provider.')->middleware([...])->group(...);).
Adjust controller references accordingly (no change needed, just URLs).
Admin routes: move from /admin/... to /a/....
Use Route::prefix('a')->name('admin.')->middleware([...])->group(...);.
Public routes: keep as is but ensure they are grouped under a public namespace if needed.
Use Route::resource where CRUD patterns exist (e.g., users, customers, providers, categories) to reduce duplication.
Update named routes to reflect new prefixes (e.g., provider.dashboard becomes p.dashboard).
Update any hard‑coded URLs in Blade views, JavaScript, and tests to the new shortened paths.
Add redirects for backward compatibility (e.g., /provider/_ → /p/_, /admin/_ → /a/_).
Verification Plan
Run php artisan route:list and confirm new short URLs.
Execute existing feature tests; they should pass after updating route names.
Manually test a few pages via the browser (dashboard, admin panel, public pages).
Ensure Cloudflare tunnel config still points to the correct host (dev.easybudget.net.br).
User Review Required
Confirm that shortening to /p and /a is acceptable.
Approve the use of redirects for legacy URLs.
Approve updating Blade and JS references automatically or manually.

Como podemos fazer isso
Estratégia O que envolve Prós Contras
Atualizar todas as referências (Blade, JS, arquivos de configuração) Substituir '/provider' → '/p' e '/admin' → '/a' em todos os arquivos que contêm URLs estáticas ou chamadas url()/route() URLs curtas e consistentes; elimina a necessidade de redirecionamentos adicionais. Trabalho mais extenso; risco de esquecer algum ponto se não houver cobertura total.
Adicionar redirecionamentos + Atualizar apenas as rotas críticas Manter os links antigos, mas criar redireções no
web.php
(ex.: Route::redirect('/provider/{any}', '/p/{any}')) e atualizar apenas os menus/links mais visíveis. Reduz o esforço imediato; garante que links antigos ainda funcionem. Ainda haverá duas camadas de URLs; pode gerar confusão se houver muitas redireções.

Analisar rotas atuais relacionadas a categories no arquivo routes/web.php e CategoryController para entender a lógica existente de distinção entre usuário provider e admin. Identificar como a lógica de categorias globais vs custom está implementada e como posso consolidar em uma rota unificada.

**Objetivo:** Entender a estrutura atual antes de implementar a rota unificada /categories/dashboard

**Instruções específicas:**

1. Verificar todas as rotas de categories em routes/web.php
2. Analisar CategoryController para entender a lógica de provider vs admin
3. Identificar como o sistema diferencia categorias globais vs custom
4. Mapear as funcionalidades atuais que precisam ser preservadas na rota unificada

**Contexto:** O usuário mencionou que mudou os grupos de provider para 'p' e admin para 'a' para melhorar as URLs e quer que as rotas de categories fiquem fora dos grupos. Currently tem rotas separadas /provider/categories/dashboard e /admin/categories/dashboard que precisam ser unificadas em /categories/dashboard.

Analise as estruturas dos seguintes arquivos index.blade.php:

-  C:\laragon\www\easy-budget-laravel\resources\views\pages\category\index.blade.php
-  C:\laragon\www\easy-budget-laravel\resources\views\pages\product\index.blade.php
-  C:\laragon\www\easy-budget-laravel\resources\views\pages\customer\index.blade.php

O arquivo de produto apresenta um visual superior inicialmente, especialmente com uma tabela vazia. Identifique as melhorias necessárias para padronizar visualmente os três arquivos.

Indique exatamente o que precisa ser feito, com foco na consistência de títulos, ícones, divs, cards, forms e CSS.

✅ CONCLUÍDO: quando for editar uma categoria ja tiver sub, ela nao pode aparecer o dropdow de categoria Pai, pq ela ja e pai, ou somente desativa, e exibe mensagem

✅ CONCLUÍDO: Botão Cancelar em edit/create agora volta para URL anterior (url()->previous()) com fallback inteligente

✅ CONCLUÍDO: Padronização visual dos arquivos index.blade.php (category, product, customer)

-  Cabeçalho com d-flex justify-content-between
-  Grid de filtros consistente (col-md-4, col-md-2)
-  Empty state padronizado com ícone e mensagem contextual
-  Paginação com footer centralizado

✅ CONCLUÍDO: Uso consistente de SLUG em todas as rotas de categorias

-  Links de restauração usando slug
-  Rotas unificadas fora dos grupos provider/admin

---

## 📚 Referência Rápida - Copy & Paste

### Novo Módulo - Estrutura Completa

```bash
# Criar arquivos de view
touch resources/views/pages/[modulo]/dashboard.blade.php
touch resources/views/pages/[modulo]/index.blade.php
touch resources/views/pages/[modulo]/create.blade.php
touch resources/views/pages/[modulo]/edit.blade.php
touch resources/views/pages/[modulo]/show.blade.php
```

### Template Mínimo - Index

```blade
@extends('layouts.app')
@section('title', '[Módulo Plural]')
@section('content')
<div class="container-fluid py-1">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0"><i class="bi bi-[icone] me-2"></i>[Módulo Plural]</h1>
            <p class="text-muted">Lista de todos os [itens] registrados no sistema</p>
        </div>
        <nav aria-label="breadcrumb" class="d-none d-md-block">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('provider.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Listar</li>
            </ol>
        </nav>
    </div>
    <!-- Filtros e Tabela aqui -->
</div>
@endsection
```

### Template Mínimo - Create/Edit

```blade
@extends('layouts.app')
@section('title', 'Novo [Item]')
@section('content')
<div class="container-fluid py-1">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0"><i class="bi bi-[icone]-plus me-2"></i>Novo [Item]</h1>
        <nav aria-label="breadcrumb" class="d-none d-md-block">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('provider.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('[modulo].index') }}">[Módulo]</a></li>
                <li class="breadcrumb-item active">Novo</li>
            </ol>
        </nav>
    </div>
    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <form action="{{ route('[modulo].store') }}" method="POST">
                @csrf
                <!-- Campos aqui -->
                <div class="d-flex justify-content-between mt-4">
                    <a href="{{ url()->previous(route('[modulo].index')) }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-2"></i>Cancelar
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle me-2"></i>Criar [Item]
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
```

---

## 🎯 Exemplos de Uso por Módulo

| Módulo     | Ícone Principal        | Cor Avatar     | Slug        |
| ---------- | ---------------------- | -------------- | ----------- |
| Categorias | `bi-tags`              | `bg-primary`   | ✅          |
| Produtos   | `bi-box-seam`          | `bg-success`   | ✅          |
| Clientes   | `bi-people`            | `bg-info`      | ❌ (usa ID) |
| Orçamentos | `bi-file-earmark-text` | `bg-warning`   | ✅          |
| Faturas    | `bi-receipt`           | `bg-danger`    | ✅          |
| Serviços   | `bi-gear`              | `bg-secondary` | ✅          |

---

## 🚀 Como Usar Este Documento

1. **Antes de criar uma nova view:** Consulte o pattern correspondente
2. **Durante o desenvolvimento:** Use os templates de Referência Rápida
3. **Antes do commit:** Execute o Checklist de Implementação
4. **Para dúvidas:** Consulte os exemplos em `resources/views/pages/category/`

---

**📌 Nota Importante:**

-  Este documento é a **fonte única de verdade** para padrões de interface
-  Qualquer desvio deve ser documentado e justificado
-  Atualize este documento ao criar novos padrões aprovados

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

<!-- /**
 * TODO: IMPLEMENTAR SISTEMA DE RESERVAS COMPLETO
 *
 * Funcionalidades pendentes:
 * 1. Criar tabela inventory_reservations (product_id, quantity, reserved_by_type, reserved_by_id, status, expires_at)
 * 2. Implementar lógica de reserva real (diminuir estoque disponível)
 * 3. Implementar expiração automática de reservas
 * 4. Adicionar campo reserved_quantity na tabela inventories
 * 5. Calcular estoque disponível = quantity - reserved_quantity
 * 6. Criar job para limpar reservas expiradas
 * 7. Atualizar métodos reserveProduct() e releaseReservation() com lógica real
 */ -->

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
