# 🔄 Referência Rápida: Twig → Blade

## 📖 Guia de Conversão de Sintaxe

Este documento serve como referência rápida para conversão de sintaxe Twig para Blade durante a migração.

---

## 1. VARIÁVEIS E OUTPUT

### Básico

| Twig                     | Blade               | Descrição                              |
| ------------------------ | ------------------- | -------------------------------------- |
| `{{ variable }}`         | `{{ $variable }}`   | Output com escape automático           |
| `{{ variable\|escape }}` | `{{ $variable }}`   | Escape explícito (automático no Blade) |
| `{{ variable\|raw }}`    | `{!! $variable !!}` | Output sem escape (HTML raw)           |
| `{{ variable\|e }}`      | `{{ $variable }}`   | Alias para escape                      |

### Exemplos

```twig
<!-- TWIG -->
<h1>{{ title }}</h1>
<div>{{ content|raw }}</div>
<p>{{ user.name }}</p>

<!-- BLADE -->
<h1>{{ $title }}</h1>
<div>{!! $content !!}</div>
<p>{{ $user->name }}</p>
```

---

## 2. ESTRUTURAS DE CONTROLE

### If / Else

| Twig                 | Blade             |
| -------------------- | ----------------- |
| `{% if condition %}` | `@if($condition)` |
| `{% elseif other %}` | `@elseif($other)` |
| `{% else %}`         | `@else`           |
| `{% endif %}`        | `@endif`          |

**Exemplo:**

```twig
<!-- TWIG -->
{% if user.isAdmin %}
    <p>Admin</p>
{% elseif user.isModerator %}
    <p>Moderator</p>
{% else %}
    <p>User</p>
{% endif %}

<!-- BLADE -->
@if($user->isAdmin)
    <p>Admin</p>
@elseif($user->isModerator)
    <p>Moderator</p>
@else
    <p>User</p>
@endif
```

### Unless (Condição Negativa)

| Twig                     | Blade                 |
| ------------------------ | --------------------- |
| `{% if not condition %}` | `@unless($condition)` |

```twig
<!-- TWIG -->
{% if not user.isActive %}
    <p>Inactive</p>
{% endif %}

<!-- BLADE -->
@unless($user->isActive)
    <p>Inactive</p>
@endunless
```

### Ternário

| Twig                             | Blade                             |
| -------------------------------- | --------------------------------- |
| `{{ condition ? 'yes' : 'no' }}` | `{{ $condition ? 'yes' : 'no' }}` |
| `{{ variable ?: 'default' }}`    | `{{ $variable ?? 'default' }}`    |

---

## 3. LOOPS

### For Loop

| Twig                      | Blade                       |
| ------------------------- | --------------------------- |
| `{% for item in items %}` | `@foreach($items as $item)` |
| `{% endfor %}`            | `@endforeach`               |

**Exemplo:**

```twig
<!-- TWIG -->
{% for user in users %}
    <p>{{ user.name }}</p>
{% endfor %}

<!-- BLADE -->
@foreach($users as $user)
    <p>{{ $user->name }}</p>
@endforeach
```

### For com Chave/Valor

```twig
<!-- TWIG -->
{% for key, value in items %}
    <p>{{ key }}: {{ value }}</p>
{% endfor %}

<!-- BLADE -->
@foreach($items as $key => $value)
    <p>{{ $key }}: {{ $value }}</p>
@endforeach
```

### Loop com Índice

```twig
<!-- TWIG -->
{% for item in items %}
    <p>{{ loop.index }}: {{ item }}</p>
{% endfor %}

<!-- BLADE -->
@foreach($items as $item)
    <p>{{ $loop->index }}: {{ $item }}</p>
@endforeach
```

### Variável Loop

| Twig          | Blade                  |
| ------------- | ---------------------- |
| `loop.index`  | `$loop->index`         |
| `loop.index0` | `$loop->iteration - 1` |
| `loop.first`  | `$loop->first`         |
| `loop.last`   | `$loop->last`          |
| `loop.length` | `$loop->count`         |
| `loop.parent` | `$loop->parent`        |

### Loop Vazio

```twig
<!-- TWIG -->
{% for item in items %}
    <p>{{ item }}</p>
{% else %}
    <p>No items</p>
{% endfor %}

<!-- BLADE -->
@forelse($items as $item)
    <p>{{ $item }}</p>
@empty
    <p>No items</p>
@endforelse
```

---

## 4. INCLUDES E TEMPLATES

### Include

| Twig                                               | Blade                                    |
| -------------------------------------------------- | ---------------------------------------- |
| `{% include 'partial.twig' %}`                     | `@include('partial')`                    |
| `{% include 'partial.twig' with {'var': value} %}` | `@include('partial', ['var' => $value])` |

**Exemplo:**

```twig
<!-- TWIG -->
{% include 'partials/header.twig' %}
{% include 'partials/user.twig' with {'user': currentUser} %}

<!-- BLADE -->
@include('partials.header')
@include('partials.user', ['user' => $currentUser])
```

### Extends e Blocos

| Twig                                   | Blade                               |
| -------------------------------------- | ----------------------------------- |
| `{% extends "layout.twig" %}`          | `@extends('layouts.app')`           |
| `{% block content %}...{% endblock %}` | `@section('content')...@endsection` |
| `{{ parent() }}`                       | `@parent`                           |

**Exemplo:**

```twig
<!-- TWIG -->
{% extends "layout.twig" %}

{% block title %}Page Title{% endblock %}

{% block content %}
    <h1>Content</h1>
{% endblock %}

<!-- BLADE -->
@extends('layouts.app')

@section('title', 'Page Title')

@section('content')
    <h1>Content</h1>
@endsection
```

### Include Condicional

```twig
<!-- TWIG -->
{% if condition %}
    {% include 'partial.twig' %}
{% endif %}

<!-- BLADE -->
@includeIf('partial', ['condition' => true])
<!-- ou -->
@if($condition)
    @include('partial')
@endif
```

---

## 5. MACROS → BLADE COMPONENTS

### Definição de Macro

```twig
<!-- TWIG: macros/alerts.twig -->
{% macro alert(type, message) %}
    <div class="alert alert-{{ type }}">
        {{ message|raw }}
    </div>
{% endmacro %}

<!-- USO -->
{% import 'macros/alerts.twig' as alerts %}
{{ alerts.alert('success', 'Saved!') }}
```

```blade
<!-- BLADE: components/alert.blade.php -->
@props(['type', 'message'])

<div class="alert alert-{{ $type }}">
    {!! $message !!}
</div>

<!-- USO -->
<x-alert type="success" message="Saved!" />
```

### Macro com Slot

```twig
<!-- TWIG -->
{% macro card(title) %}
    <div class="card">
        <h3>{{ title }}</h3>
        {% block body %}{% endblock %}
    </div>
{% endmacro %}

<!-- BLADE -->
@props(['title'])

<div class="card">
    <h3>{{ $title }}</h3>
    {{ $slot }}
</div>

<!-- USO -->
<x-card title="Title">
    <p>Content here</p>
</x-card>
```

---

## 6. FILTROS → HELPERS/DIRECTIVES

### Filtros de String

| Twig                              | Blade/PHP                            |
| --------------------------------- | ------------------------------------ |
| `{{ text\|upper }}`               | `{{ strtoupper($text) }}`            |
| `{{ text\|lower }}`               | `{{ strtolower($text) }}`            |
| `{{ text\|capitalize }}`          | `{{ ucfirst($text) }}`               |
| `{{ text\|title }}`               | `{{ Str::title($text) }}`            |
| `{{ text\|trim }}`                | `{{ trim($text) }}`                  |
| `{{ text\|length }}`              | `{{ strlen($text) }}`                |
| `{{ text\|slice(0, 10) }}`        | `{{ substr($text, 0, 10) }}`         |
| `{{ text\|replace({' ': '-'}) }}` | `{{ str_replace(' ', '-', $text) }}` |

### Filtros de Array

| Twig                      | Blade/PHP                                 |
| ------------------------- | ----------------------------------------- |
| `{{ array\|length }}`     | `{{ count($array) }}`                     |
| `{{ array\|first }}`      | `{{ head($array) }}` ou `{{ $array[0] }}` |
| `{{ array\|last }}`       | `{{ last($array) }}`                      |
| `{{ array\|join(', ') }}` | `{{ implode(', ', $array) }}`             |
| `{{ array\|sort }}`       | `{{ collect($array)->sort() }}`           |
| `{{ array\|reverse }}`    | `{{ array_reverse($array) }}`             |

### Filtros de Data

| Twig                         | Blade/Laravel                  |
| ---------------------------- | ------------------------------ |
| `{{ date\|date('Y-m-d') }}`  | `{{ $date->format('Y-m-d') }}` |
| `{{ date\|date('d/m/Y') }}`  | `{{ $date->format('d/m/Y') }}` |
| `{{ 'now'\|date('Y-m-d') }}` | `{{ now()->format('Y-m-d') }}` |

### Filtros de Número

| Twig                                       | Blade/PHP                                   |
| ------------------------------------------ | ------------------------------------------- |
| `{{ number\|number_format(2, ',', '.') }}` | `{{ number_format($number, 2, ',', '.') }}` |
| `{{ number\|abs }}`                        | `{{ abs($number) }}`                        |
| `{{ number\|round }}`                      | `{{ round($number) }}`                      |

### Filtros Customizados

```twig
<!-- TWIG -->
{{ text|customFilter }}

<!-- BLADE: Criar Helper -->
// app/helpers.php
function customFilter($text) {
    return strtoupper($text);
}

<!-- USO -->
{{ customFilter($text) }}
```

---

## 7. FUNÇÕES GLOBAIS

### Funções Twig Comuns

| Twig                                  | Blade/Laravel                     |
| ------------------------------------- | --------------------------------- |
| `{{ dump(variable) }}`                | `{{ dump($variable) }}`           |
| `{{ random(array) }}`                 | `{{ collect($array)->random() }}` |
| `{{ range(1, 10) }}`                  | `{{ range(1, 10) }}`              |
| `{{ attribute(object, 'property') }}` | `{{ $object->property }}`         |

### Funções Específicas do Projeto

```twig
<!-- TWIG -->
{% if getResource('feature') %}
    ...
{% endif %}

<!-- BLADE: Criar Helper ou usar Service -->
@if(app('feature.service')->getResource('feature'))
    ...
@endif
```

---

## 8. COMENTÁRIOS

| Twig            | Blade               |
| --------------- | ------------------- |
| `{# comment #}` | `{{-- comment --}}` |

**Exemplo:**

```twig
<!-- TWIG -->
{# This is a comment #}
<p>Content</p>

<!-- BLADE -->
{{-- This is a comment --}}
<p>Content</p>
```

---

## 9. OPERADORES

### Comparação

| Operador    | Twig | Blade         |
| ----------- | ---- | ------------- |
| Igual       | `==` | `==` ou `===` |
| Diferente   | `!=` | `!=` ou `!==` |
| Maior       | `>`  | `>`           |
| Menor       | `<`  | `<`           |
| Maior igual | `>=` | `>=`          |
| Menor igual | `<=` | `<=`          |

### Lógicos

| Operador | Twig  | Blade  |
| -------- | ----- | ------ |
| E        | `and` | `&&`   |
| Ou       | `or`  | `\|\|` |
| Não      | `not` | `!`    |

**Exemplo:**

```twig
<!-- TWIG -->
{% if user.isActive and user.isAdmin %}
    ...
{% endif %}

<!-- BLADE -->
@if($user->isActive && $user->isAdmin)
    ...
@endif
```

### Outros Operadores

| Twig         | Blade          | Descrição                 |
| ------------ | -------------- | ------------------------- |
| `in`         | `in_array()`   | Verifica se está no array |
| `is defined` | `isset()`      | Verifica se está definido |
| `is null`    | `is_null()`    | Verifica se é nulo        |
| `is empty`   | `empty()`      | Verifica se está vazio    |
| `matches`    | `preg_match()` | Regex match               |

**Exemplos:**

```twig
<!-- TWIG -->
{% if 'admin' in user.roles %}
    ...
{% endif %}

{% if variable is defined %}
    ...
{% endif %}

<!-- BLADE -->
@if(in_array('admin', $user->roles))
    ...
@endif

@if(isset($variable))
    ...
@endif

<!-- Ou melhor com isset() embutido -->
@isset($variable)
    ...
@endisset
```

---

## 10. CASOS ESPECIAIS

### Set de Variáveis

```twig
<!-- TWIG -->
{% set total = 0 %}
{% for item in items %}
    {% set total = total + item.value %}
{% endfor %}

<!-- BLADE -->
@php
    $total = 0;
    foreach($items as $item) {
        $total += $item->value;
    }
@endphp

<!-- Ou melhor: fazer no Controller -->
// Controller
$total = $items->sum('value');
```

### Expressões Complexas

```twig
<!-- TWIG -->
{% set discountedTotal = budget.total - (budget.discount|default(0)) %}

<!-- BLADE -->
@php
    $discountedTotal = $budget->total - ($budget->discount ?? 0);
@endphp

<!-- Ou no Controller -->
// Controller
$discountedTotal = $budget->total - ($budget->discount ?? 0);
```

### Filtros em Cadeia

```twig
<!-- TWIG -->
{{ text|trim|upper|slice(0, 10) }}

<!-- BLADE -->
{{ substr(strtoupper(trim($text)), 0, 10) }}

<!-- Ou com Str helper -->
{{ Str::of($text)->trim()->upper()->substr(0, 10) }}
```

---

## 11. AUTENTICAÇÃO E AUTORIZAÇÃO

### Verificação de Autenticação

```twig
<!-- TWIG -->
{% if app.user %}
    <p>Welcome {{ app.user.name }}</p>
{% endif %}

<!-- BLADE -->
@auth
    <p>Welcome {{ auth()->user()->name }}</p>
@endauth

<!-- Ou -->
@if(auth()->check())
    <p>Welcome {{ auth()->user()->name }}</p>
@endif
```

### Verificação de Guest

```twig
<!-- TWIG -->
{% if not app.user %}
    <a href="/login">Login</a>
{% endif %}

<!-- BLADE -->
@guest
    <a href="{{ route('login') }}">Login</a>
@endguest
```

### Verificação de Permissões

```twig
<!-- TWIG -->
{% if is_granted('ROLE_ADMIN') %}
    <a href="/admin">Admin Panel</a>
{% endif %}

<!-- BLADE -->
@can('access-admin')
    <a href="{{ route('admin') }}">Admin Panel</a>
@endcan

<!-- Ou -->
@if(auth()->user()->can('access-admin'))
    <a href="{{ route('admin') }}">Admin Panel</a>
@endif
```

---

## 12. CSRF PROTECTION

```twig
<!-- TWIG -->
{{ csrf.field|raw }}

<!-- BLADE -->
@csrf

<!-- Ou explicitamente -->
<input type="hidden" name="_csrf_token" value="{{ csrf_token() }}">
```

---

## 13. ROTAS E URLs

### Geração de URLs

```twig
<!-- TWIG -->
<a href="/budgets/{{ budget.code }}">View</a>

<!-- BLADE -->
<a href="{{ route('budgets.show', $budget->code) }}">View</a>

<!-- Ou -->
<a href="{{ url('/budgets/' . $budget->code) }}">View</a>
```

### URL com Parâmetros

```twig
<!-- TWIG -->
<a href="/search?q={{ query }}">Search</a>

<!-- BLADE -->
<a href="{{ route('search', ['q' => $query]) }}">Search</a>
```

---

## 14. ASSETS

### Imagens e Arquivos

```twig
<!-- TWIG -->
<img src="/assets/img/logo.png" alt="Logo">
<script src="/assets/js/main.js"></script>

<!-- BLADE com Vite -->
@vite(['resources/js/app.js', 'resources/css/app.css'])

<img src="{{ asset('img/logo.png') }}" alt="Logo">
```

---

## 15. STACK E PUSH

### Scripts e Styles

```twig
<!-- TWIG -->
{% block scripts %}
{{ parent() }}
<script>
    // Custom JS
</script>
{% endblock %}

<!-- BLADE -->
@push('scripts')
<script>
    // Custom JS
</script>
@endpush

<!-- No layout -->
@stack('scripts')
```

---

## 16. COMPONENTES AVANÇADOS

### Componente com Atributos

```blade
<!-- resources/views/components/button.blade.php -->
@props(['type' => 'button', 'variant' => 'primary'])

<button
    type="{{ $type }}"
    {{ $attributes->merge(['class' => "btn btn-{$variant}"]) }}
>
    {{ $slot }}
</button>

<!-- USO -->
<x-button variant="primary" class="mt-4" id="submit-btn">
    Save
</x-button>

<!-- RENDERIZA -->
<button type="button" class="btn btn-primary mt-4" id="submit-btn">
    Save
</button>
```

### Componente com Multiple Slots

```blade
<!-- components/card.blade.php -->
@props(['title'])

<div class="card">
    <div class="card-header">
        <h3>{{ $title }}</h3>
        {{ $actions ?? '' }}
    </div>
    <div class="card-body">
        {{ $slot }}
    </div>
</div>

<!-- USO -->
<x-card title="User Profile">
    <x-slot:actions>
        <button>Edit</button>
    </x-slot>

    <p>User content here</p>
</x-card>
```

---

## 17. ERROS COMUNS E SOLUÇÕES

### Erro 1: Variável não definida

```twig
<!-- TWIG (aceita variável undefined) -->
{{ variable }}

<!-- BLADE (erro se não definida) -->
{{ $variable ?? '' }}
<!-- Ou -->
{{ $variable ?? 'default' }}
```

### Erro 2: Acesso a propriedade de objeto

```twig
<!-- TWIG -->
{{ user.name }}

<!-- BLADE -->
{{ $user->name }}
<!-- Ou se for array -->
{{ $user['name'] }}
```

### Erro 3: Loop em array associativo

```twig
<!-- TWIG -->
{% for key, value in items %}
    {{ key }}: {{ value }}
{% endfor %}

<!-- BLADE -->
@foreach($items as $key => $value)
    {{ $key }}: {{ $value }}
@endforeach
```

---

## 18. CHECKLIST DE CONVERSÃO

Para cada arquivo Twig convertido:

-  [ ] Trocar `{{ variable }}` por `{{ $variable }}`
-  [ ] Trocar `{{ var|raw }}` por `{!! $var !!}`
-  [ ] Trocar `{% if %}` por `@if()`
-  [ ] Trocar `{% for %}` por `@foreach()`
-  [ ] Trocar `{% extends %}` por `@extends()`
-  [ ] Trocar `{% block %}` por `@section()`
-  [ ] Trocar `{% include %}` por `@include()`
-  [ ] Converter macros para Blade Components
-  [ ] Trocar filtros por helpers/métodos PHP
-  [ ] Trocar `loop.index` por `$loop->index`
-  [ ] Adicionar `$` antes de variáveis
-  [ ] Trocar `.` por `->` para objetos
-  [ ] Trocar `and/or/not` por `&&/||/!`
-  [ ] Adicionar `@csrf` nos formulários
-  [ ] Usar `route()` helper para URLs
-  [ ] Testar renderização
-  [ ] Verificar console de erros JS
-  [ ] Testar responsividade

---

## 📚 REFERÊNCIAS

-  [Blade Templates - Laravel Docs](https://laravel.com/docs/blade)
-  [Twig Documentation](https://twig.symfony.com/doc/)
-  [Laravel Helpers](https://laravel.com/docs/helpers)

---

**Documento criado em:** 2025-09-30
**Versão:** 1.0
**Uso:** Referência rápida durante migração
