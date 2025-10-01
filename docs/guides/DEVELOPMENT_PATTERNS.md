# 📋 Guia de Padrões de Desenvolvimento - Fase 1

## 🎯 Visão Geral

Este guia estabelece os padrões e convenções para desenvolvimento consistente durante a implementação da Fase 1 da migração Twig → Laravel Blade.

---

## 🏗️ Arquitetura de Desenvolvimento

### Princípios Fundamentais

1. **Componentes Reutilizáveis**: DRY (Don't Repeat Yourself)
2. **Separação de Responsabilidades**: Cada componente tem uma função clara
3. **Mobile-First**: Design responsivo desde o início
4. **Acessibilidade**: WCAG 2.1 AA compliance
5. **Performance**: Otimizações desde o desenvolvimento

---

## 📁 Estrutura de Arquivos

### Convenções de Nomenclatura

#### Blade Components

```php
// ✅ Correto
resources/views/components/ui/button.blade.php
resources/views/components/form/input.blade.php
resources/views/components/admin/sidebar.blade.php

// ❌ Incorreto
resources/views/components/Button.blade.php
resources/views/components/input.blade.php
resources/views/components/sidebar.blade.php
```

#### Views de Páginas

```php
// ✅ Correto
resources/views/pages/auth/login.blade.php
resources/views/pages/budgets/index.blade.php
resources/views/pages/admin/users.blade.php

// ❌ Incorreto
resources/views/auth/login.blade.php
resources/views/budgets.blade.php
resources/views/admin.blade.php
```

#### Assets

```javascript
// ✅ Correto
resources / js / components / Form.js;
resources / js / pages / Dashboard.js;
resources / css / components / buttons.css;

// ❌ Incorreto
resources / js / form.js;
resources / js / dashboard.js;
resources / css / buttons.css;
```

---

## 🎨 Padrões de Design

### Sistema de Cores

```php
// app/Helpers/ColorHelper.php
class ColorHelper
{
    const TYPES = [
        'primary' => [
            'bg' => 'bg-blue-600',
            'hover' => 'hover:bg-blue-700',
            'text' => 'text-blue-600',
            'border' => 'border-blue-600',
        ],
        'success' => [
            'bg' => 'bg-green-600',
            'hover' => 'hover:bg-green-700',
            'text' => 'text-green-600',
            'border' => 'border-green-600',
        ],
        // ... outros tipos
    ];

    public static function getClasses($type, $element = 'bg')
    {
        return self::TYPES[$type][$element] ?? self::TYPES['primary'][$element];
    }
}
```

### Espaçamento Consistente

```php
// Padrão de espaçamento
$spacing = [
    'xs' => 'p-2',      // 8px
    'sm' => 'p-3',      // 12px
    'md' => 'p-4',      // 16px
    'lg' => 'p-6',      // 24px
    'xl' => 'p-8',      // 32px
    '2xl' => 'p-12',    // 48px
];

// Uso consistente
<div class="{{ $spacing[$size] ?? $spacing['md'] }}">
    Conteúdo
</div>
```

---

## 🧩 Padrões de Componentes

### 1. Props Padronizadas

```blade
@props([
    'type' => 'primary',        // Tipo/variante
    'size' => 'md',            // Tamanho
    'disabled' => false,       // Estado desabilitado
    'loading' => false,        // Estado de carregamento
    'variant' => 'default',    // Variação específica
    'class' => '',             // Classes CSS adicionais
    'attributes' => '',        // Atributos HTML adicionais
])
```

### 2. Estrutura de Componentes

```blade
@php
    // 1. Definir props com valores padrão
    // 2. Calcular classes CSS dinâmicas
    // 3. Preparar dados para o template
    // 4. Renderizar HTML
@endphp

<!-- Template HTML -->

@push('scripts')
<!-- Scripts específicos se necessário -->
@endpush
```

### 3. Tratamento de Erros

```blade
@php
    try {
        // Lógica do componente
        $data = $this->processData($props);
    } catch (\Exception $e) {
        // Fallback ou tratamento de erro
        \Log::error('Component error', [
            'component' => static::class,
            'error' => $e->getMessage()
        ]);

        $data = [];
    }
@endphp
```

---

## 🔧 Padrões de Desenvolvimento

### 1. Validação de Dados

```php
// app/Traits/ValidatesProps.php
trait ValidatesProps
{
    public function validateProps($props, $rules)
    {
        $validator = Validator::make($props, $rules);

        if ($validator->fails()) {
            throw new \InvalidArgumentException(
                'Invalid props: ' . $validator->errors()->first()
            );
        }

        return $validator->validated();
    }
}

// Uso no componente
@php
    $validatedProps = $this->validateProps($props, [
        'type' => 'required|string|in:primary,secondary,success,danger',
        'size' => 'required|string|in:xs,sm,md,lg,xl',
    ]);
@endphp
```

### 2. Tratamento de Estados

```php
// Estados possíveis para componentes interativos
$states = [
    'default' => 'cursor-pointer hover:bg-opacity-90',
    'hover' => 'bg-opacity-90 transform scale-105',
    'active' => 'bg-opacity-100 transform scale-95',
    'focus' => 'ring-2 ring-blue-500 ring-offset-2',
    'disabled' => 'opacity-50 cursor-not-allowed pointer-events-none',
    'loading' => 'cursor-wait pointer-events-none',
];

// Aplicar estados dinamicamente
$stateClasses = collect($states)->only($currentStates)->implode(' ');
```

### 3. Responsividade

```php
// Padrão mobile-first
$responsiveClasses = [
    'mobile' => 'block w-full',
    'tablet' => 'md:flex md:w-auto',
    'desktop' => 'lg:grid lg:grid-cols-3',
];

// Aplicar responsividade
$classes = collect($responsiveClasses)->implode(' ');
```

---

## 📝 Padrões de Código

### 1. PHP em Templates

```blade
@php
    // ✅ Correto - Lógica simples
    $total = $items->sum('value');
    $isActive = $user->status === 'active';

    // ✅ Correto - Formatação de dados
    $formattedDate = $date->format('d/m/Y');
    $currencyValue = number_format($value, 2, ',', '.');
@endphp

{{-- ❌ Incorreto - Lógica complexa em template --}}
@php
    $result = DB::table('users')
        ->join('orders', 'users.id', '=', 'orders.user_id')
        ->where('orders.status', 'pending')
        ->select('users.*', DB::raw('SUM(orders.total) as total'))
        ->groupBy('users.id')
        ->get();
@endphp
```

### 2. Controle de Fluxo

```blade
{{-- ✅ Correto - If/Else simples --}}
@if($condition)
    <div>Condicional simples</div>
@else
    <div>Alternativa simples</div>
@endif

{{-- ✅ Correto - Loops com dados --}}
@foreach($items as $item)
    <div>{{ $item->name }}</div>
@endforeach

{{-- ❌ Incorreto - Lógica complexa --}}
@php
    if ($complexCondition) {
        foreach ($items as $item) {
            if ($item->status === 'active') {
                // Múltiplas condições aninhadas
            }
        }
    }
@endphp
```

### 3. Tratamento de Null/Empty

```blade
{{-- ✅ Correto - Null coalescing --}}
{{ $variable ?? 'Valor padrão' }}

{{-- ✅ Correto - Empty check --}}
@unless(empty($items))
    <div>Items existem</div>
@endunless

{{-- ✅ Correto - Optional helper --}}
{{ $object?->property }}

{{-- ❌ Incorreto - Sem tratamento --}}
{{ $variable }}
```

---

## 🎯 Padrões de Formulários

### 1. Estrutura de Formulários

```blade
<form method="POST" action="{{ route('store') }}" class="space-y-6">
    @csrf

    <!-- Campos obrigatórios primeiro -->
    <x-form.input name="name" label="Nome" required />

    <!-- Campos opcionais -->
    <x-form.input name="description" label="Descrição" />

    <!-- Campos avançados -->
    <x-form.select name="category" label="Categoria" :options="$categories" />

    <!-- Ações -->
    <div class="flex justify-end space-x-4">
        <x-ui.button type="button" variant="secondary">
            Cancelar
        </x-ui.button>
        <x-ui.button type="submit" variant="primary">
            Salvar
        </x-ui.button>
    </div>
</form>
```

### 2. Validação de Campos

```php
// Controller padrão
public function store(Request $request)
{
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:users',
        'password' => 'required|string|min:8|confirmed',
        'category' => 'required|exists:categories,id',
    ]);

    // Processar dados validados
    return $this->service->create($validated);
}
```

### 3. Tratamento de Erros

```blade
{{-- ✅ Correto - Erros específicos --}}
<x-form.input
    name="email"
    :error="$errors->first('email')"
/>

{{-- ✅ Correto - Múltiplos erros --}}
@if($errors->has('email'))
    <div class="text-red-600 text-sm mt-1">
        @foreach($errors->get('email') as $error)
            <div>{{ $error }}</div>
        @endforeach
    </div>
@endif
```

---

## 🚀 Padrões de Performance

### 1. Lazy Loading

```blade
{{-- ✅ Correto - Carregamento sob demanda --}}
@push('scripts')
<script>
    // Carregar dados apenas quando necessário
    document.addEventListener('DOMContentLoaded', function() {
        if (document.querySelector('.lazy-component')) {
            loadLazyComponent();
        }
    });
</script>
@endpush
```

### 2. Otimização de Assets

```javascript
// resources/js/app.js
import Alpine from "alpinejs";

// ✅ Correto - Plugins sob demanda
import mask from "@alpinejs/mask";
import focus from "@alpinejs/focus";

// Registrar apenas plugins utilizados
Alpine.plugin(mask);
Alpine.plugin(focus);
```

### 3. Cache de Dados

```php
// app/Traits/Cacheable.php
trait Cacheable
{
    public function getCachedData($key, $callback, $ttl = 3600)
    {
        return Cache::remember($key, $ttl, $callback);
    }
}

// Uso
$data = $this->getCachedData(
    "user.{$userId}.profile",
    fn() => $this->service->getProfile($userId),
    1800 // 30 minutos
);
```

---

## 🧪 Padrões de Testes

### 1. Testes de Componentes

```php
// tests/Feature/Components/ButtonTest.php
class ButtonTest extends TestCase
{
    public function test_button_renders_with_correct_classes()
    {
        $view = $this->blade('<x-ui.button>Click me</x-ui.button>');

        $view->assertSee('inline-flex');
        $view->assertSee('bg-blue-600');
        $view->assertSee('Click me');
    }

    public function test_button_variants()
    {
        $variants = ['primary', 'secondary', 'success', 'danger'];

        foreach ($variants as $variant) {
            $view = $this->blade("<x-ui.button variant=\"{$variant}\">Test</x-ui.button>");
            $view->assertSee("btn-{$variant}");
        }
    }
}
```

### 2. Testes de Responsividade

```php
public function test_components_are_responsive()
{
    $component = '<x-ui.card>Content</x-ui.card>';
    $view = $this->blade($component);

    // Verificar breakpoints
    $view->assertSee('sm:');
    $view->assertSee('md:');
    $view->assertSee('lg:');
}
```

### 3. Testes de Acessibilidade

```php
public function test_components_are_accessible()
{
    $view = $this->blade('<x-ui.button>Accessible Button</x-ui.button>');

    // Verificar atributos ARIA
    $view->assertSee('role="button"');
    $view->assertSee('aria-label');

    // Verificar navegação por teclado
    $view->assertSee('focus:');
    $view->assertSee('outline');
}
```

---

## 📋 Checklist de Desenvolvimento

### Antes de Codificar

-  [ ] Requisitos bem definidos
-  [ ] Design system consultado
-  [ ] Componentes existentes verificados
-  [ ] Responsividade planejada
-  [ ] Acessibilidade considerada

### Durante o Desenvolvimento

-  [ ] Props bem tipadas e validadas
-  [ ] Estados visuais implementados
-  [ ] Responsividade testada
-  [ ] Acessibilidade verificada
-  [ ] Performance otimizada
-  [ ] Código comentado quando necessário

### Antes de Commit

-  [ ] Testes passando
-  [ ] Código revisado
-  [ ] Documentação atualizada
-  [ ] Padrões seguidos
-  [ ] Performance verificada
-  [ ] Acessibilidade testada

---

## 🔧 Ferramentas e Comandos

### Desenvolvimento

```bash
# Build de desenvolvimento
npm run dev

# Build de produção
npm run build

# Análise de bundle
npm run build -- --analyze

# Testes
php artisan test
php artisan test --coverage

# Análise de código
php artisan insights
```

### Qualidade de Código

```bash
# PHP CS Fixer
./vendor/bin/php-cs-fixer fix

# PHPStan
./vendor/bin/phpstan analyse

# Laravel Pint
./vendor/bin/pint

# ESLint
npx eslint resources/js --fix
```

---

## 🚨 Padrões de Tratamento de Erros

### 1. Try/Catch Estruturado

```php
try {
    // Lógica principal
    $result = $this->processData($input);

    return success_response($result);
} catch (ValidationException $e) {
    // Erros de validação
    return back()->withErrors($e->errors());
} catch (ModelNotFoundException $e) {
    // Recurso não encontrado
    return response()->view('errors.404', [], 404);
} catch (\Exception $e) {
    // Erros inesperados
    \Log::error('Unexpected error', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);

    return response()->view('errors.500', [], 500);
}
```

### 2. Logging Estruturado

```php
// app/Helpers/LogHelper.php
class LogHelper
{
    public static function logError($message, $context = [])
    {
        \Log::error($message, array_merge([
            'user_id' => auth()->id(),
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'url' => request()->fullUrl(),
        ], $context));
    }

    public static function logInfo($message, $context = [])
    {
        \Log::info($message, array_merge([
            'user_id' => auth()->id(),
        ], $context));
    }
}
```

---

## 📚 Referências e Recursos

### Documentação Oficial

-  [Laravel Blade](https://laravel.com/docs/blade)
-  [TailwindCSS](https://tailwindcss.com/docs)
-  [Alpine.js](https://alpinejs.dev/)
-  [PSR Standards](https://www.php-fig.org/psr/)

### Padrões de Código

-  [Laravel Best Practices](https://github.com/alexeymezenin/laravel-best-practices)
-  [PHP Standards](https://www.php-standards.info/)
-  [Web Accessibility](https://www.w3.org/WAI/WCAG21/quickref/)

### Ferramentas

-  [Laravel Pint](https://laravel.com/docs/pint)
-  [PHP CS Fixer](https://github.com/FriendsOfPHP/PHP-CS-Fixer)
-  [PHPStan](https://phpstan.org/)
-  [ESLint](https://eslint.org/)

---

## 🔄 Processo de Desenvolvimento

### 1. Planejamento

1. **Analisar requisitos** → Entender o que precisa ser implementado
2. **Consultar design system** → Verificar padrões visuais existentes
3. **Verificar componentes** → Reutilizar ou criar novos
4. **Planejar responsividade** → Mobile-first approach
5. **Considerar acessibilidade** → WCAG compliance

### 2. Implementação

1. **Criar estrutura básica** → Diretórios e arquivos
2. **Implementar funcionalidade** → Lógica principal
3. **Aplicar estilos** → Design system e responsividade
4. **Adicionar interatividade** → Alpine.js quando necessário
5. **Implementar acessibilidade** → ARIA e navegação por teclado

### 3. Testes

1. **Testes unitários** → Componentes individuais
2. **Testes de integração** → Fluxos completos
3. **Testes de responsividade** → Diferentes dispositivos
4. **Testes de acessibilidade** → Screen readers e teclado
5. **Testes de performance** → Lighthouse e benchmarks

### 4. Documentação

1. **Comentários no código** → Explicar lógica complexa
2. **Documentação de componentes** → Props e exemplos de uso
3. **Guia de desenvolvimento** → Manutenção deste documento
4. **Release notes** → Mudanças implementadas

---

**Documento criado em:** 2025-09-30
**Versão:** 1.0
**Status:** ✅ Padrões Estabelecidos
