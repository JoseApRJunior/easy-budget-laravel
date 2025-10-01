# 📋 Especificações Técnicas - Layouts Base (Fase 1)

## 🎯 Visão Geral

Especificação completa para implementação dos layouts base do sistema Easy Budget Laravel, incluindo layout principal (app), administrativo (admin) e convidado (guest).

---

## 📊 Layouts Especificados

### 1. Layout Principal (App)

#### 🎨 Design e Estrutura

**Arquivo:** `layouts/app.blade.php`
**Uso:** Páginas autenticadas (dashboard, módulos internos)
**Extends:** Nenhum (layout raiz)

```blade
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Easy Budget') }} - @yield('title', 'Dashboard')</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet">

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

        <!-- Main Content -->
        <main class="flex-1">
            <!-- Flash Messages -->
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                <x-flash-messages />
            </div>

            <!-- Page Content -->
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                @yield('content')
            </div>
        </main>

        <!-- Footer -->
        <x-navigation.footer />
    </div>

    @stack('scripts')
</body>
</html>
```

#### 🔧 Funcionalidades

-  **Meta tags dinâmicas**: Título e descrição configuráveis
-  **Assets compilados**: Vite para CSS e JS
-  **Stacks**: Para estilos e scripts adicionais
-  **Flash messages**: Sistema de feedback global
-  **Responsive**: Layout adaptável

---

### 2. Layout Administrativo (Admin)

#### 🎨 Design e Estrutura

**Arquivo:** `layouts/admin.blade.php`
**Uso:** Páginas administrativas
**Extends:** `layouts.app`

```blade
@extends('layouts.app')

@section('title', 'Administração - ' . ($title ?? 'Dashboard'))

@push('styles')
<style>
    /* Estilos específicos do admin */
    .admin-sidebar {
        width: 280px;
        min-height: calc(100vh - 64px);
    }

    .admin-content {
        flex: 1;
        margin-left: 280px;
    }

    @media (max-width: 768px) {
        .admin-sidebar {
            position: fixed;
            z-index: 40;
            transform: translateX(-100%);
            transition: transform 0.3s ease-in-out;
        }

        .admin-sidebar.open {
            transform: translateX(0);
        }

        .admin-content {
            margin-left: 0;
        }
    }
</style>
@endpush

@section('content')
<div class="flex bg-gray-100 min-h-screen">
    <!-- Sidebar -->
    <aside class="admin-sidebar bg-white shadow-lg">
        <x-admin.sidebar />
    </aside>

    <!-- Main Content -->
    <div class="admin-content p-8">
        <!-- Breadcrumb -->
        <x-admin.breadcrumb :items="$breadcrumb ?? []" class="mb-6" />

        <!-- Page Header -->
        @hasSection('page-header')
            <div class="mb-8">
                @yield('page-header')
            </div>
        @endif

        <!-- Page Content -->
        <div class="bg-white rounded-lg shadow-sm">
            @yield('admin-content')
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Mobile sidebar toggle
    document.addEventListener('DOMContentLoaded', function() {
        const sidebar = document.querySelector('.admin-sidebar');
        const toggleBtn = document.querySelector('[data-sidebar-toggle]');

        if (toggleBtn) {
            toggleBtn.addEventListener('click', function() {
                sidebar.classList.toggle('open');
            });
        }
    });
</script>
@endpush
```

#### 🔧 Funcionalidades

-  **Sidebar fixa**: Navegação administrativa
-  **Breadcrumb dinâmico**: Navegação secundária
-  **Header de página**: Área para títulos e ações
-  **Mobile responsive**: Sidebar colapsável
-  **Área de conteúdo**: Container principal

---

### 3. Layout Convidado (Guest)

#### 🎨 Design e Estrutura

**Arquivo:** `layouts/guest.blade.php`
**Uso:** Páginas públicas (login, erro, landing page)
**Extends:** Nenhum (layout raiz)

```blade
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Easy Budget') }} - @yield('title', 'Bem-vindo')</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet">

    <!-- Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="h-full bg-gray-50 font-sans antialiased">
    <div class="min-h-full">
        <!-- Page Content (Full Screen) -->
        @yield('content')
    </div>

    @stack('scripts')
</body>
</html>
```

#### 🔧 Funcionalidades

-  **Full screen**: Sem header/footer para páginas públicas
-  **Minimalista**: Apenas conteúdo essencial
-  **Assets otimizados**: CSS e JS necessários
-  **Responsive**: Layout adaptável

---

## 🧩 Componentes de Layout

### 1. Header Principal

**Arquivo:** `components/navigation/header.blade.php`

```blade
@props(['title' => null])

<header class="bg-white shadow-sm border-b border-gray-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            <!-- Logo -->
            <div class="flex items-center">
                <a href="{{ route('dashboard') }}" class="flex items-center">
                    <img src="{{ asset('img/logo.png') }}" alt="Easy Budget" class="h-8 w-auto">
                    @if($title)
                        <span class="ml-3 text-lg font-semibold text-gray-900">{{ $title }}</span>
                    @endif
                </a>
            </div>

            <!-- Navigation -->
            <nav class="hidden md:flex space-x-8">
                <a href="{{ route('dashboard') }}" class="text-gray-700 hover:text-gray-900 px-3 py-2 text-sm font-medium">
                    Dashboard
                </a>
                <a href="{{ route('budgets.index') }}" class="text-gray-700 hover:text-gray-900 px-3 py-2 text-sm font-medium">
                    Orçamentos
                </a>
                <a href="{{ route('reports.index') }}" class="text-gray-700 hover:text-gray-900 px-3 py-2 text-sm font-medium">
                    Relatórios
                </a>
            </nav>

            <!-- User Menu -->
            <div class="flex items-center space-x-4">
                <!-- Notifications -->
                <button class="text-gray-400 hover:text-gray-600">
                    <i class="bi bi-bell"></i>
                </button>

                <!-- User Dropdown -->
                <x-navigation.user-menu />
            </div>
        </div>
    </div>
</header>
```

### 2. Sidebar Administrativa

**Arquivo:** `components/admin/sidebar.blade.php`

```blade
@props(['active' => 'dashboard'])

<aside class="bg-white shadow-lg">
    <!-- Logo -->
    <div class="p-6 border-b border-gray-200">
        <img src="{{ asset('img/logo.png') }}" alt="Easy Budget" class="h-8 w-auto">
        <p class="text-xs text-gray-500 mt-2">Administração</p>
    </div>

    <!-- Navigation -->
    <nav class="p-4 space-y-2">
        <!-- Dashboard -->
        <a href="{{ route('admin.dashboard') }}"
           class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors {{
               $active === 'dashboard'
                   ? 'bg-blue-50 text-blue-700 border-r-2 border-blue-600'
                   : 'text-gray-700 hover:bg-gray-50'
           }}">
            <i class="bi bi-speedometer2 mr-3"></i>
            Dashboard
        </a>

        <!-- Users -->
        <a href="{{ route('admin.users.index') }}"
           class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors {{
               $active === 'users'
                   ? 'bg-blue-50 text-blue-700 border-r-2 border-blue-600'
                   : 'text-gray-700 hover:bg-gray-50'
           }}">
            <i class="bi bi-people mr-3"></i>
            Usuários
        </a>

        <!-- Settings -->
        <a href="{{ route('admin.settings') }}"
           class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors {{
               $active === 'settings'
                   ? 'bg-blue-50 text-blue-700 border-r-2 border-blue-600'
                   : 'text-gray-700 hover:bg-gray-50'
           }}">
            <i class="bi bi-gear mr-3"></i>
            Configurações
        </a>

        <!-- Divider -->
        <div class="border-t border-gray-200 my-4"></div>

        <!-- Back to App -->
        <a href="{{ route('dashboard') }}"
           class="flex items-center px-4 py-3 text-sm font-medium text-gray-600 hover:text-gray-900 rounded-lg hover:bg-gray-50">
            <i class="bi bi-arrow-left mr-3"></i>
            Voltar ao App
        </a>
    </nav>
</aside>
```

### 3. Breadcrumb Dinâmico

**Arquivo:** `components/admin/breadcrumb.blade.php`

```blade
@props(['items' => []])

@if(count($items) > 0)
    <nav class="flex" aria-label="Breadcrumb">
        <ol class="inline-flex items-center space-x-1 md:space-x-3">
            @foreach($items as $index => $item)
                <li class="inline-flex items-center">
                    @if($index > 0)
                        <i class="bi bi-chevron-right text-gray-400 mx-1"></i>
                    @endif

                    @if(isset($item['url']) && $index < count($items) - 1)
                        <a href="{{ $item['url'] }}"
                           class="text-sm font-medium text-gray-700 hover:text-blue-600">
                            @if(isset($item['icon']))
                                <i class="{{ $item['icon'] }} mr-1"></i>
                            @endif
                            {{ $item['label'] }}
                        </a>
                    @else
                        <span class="text-sm font-medium text-gray-500">
                            @if(isset($item['icon']))
                                <i class="{{ $item['icon'] }} mr-1"></i>
                            @endif
                            {{ $item['label'] }}
                        </span>
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>
@endif
```

### 4. Menu do Usuário

**Arquivo:** `components/navigation/user-menu.blade.php`

```blade
@props(['align' => 'right'])

<div class="relative" x-data="{ open: false }">
    <!-- Trigger -->
    <button @click="open = !open"
            class="flex items-center space-x-2 text-sm rounded-full focus:outline-none focus:ring-2 focus:ring-blue-500">
        <img src="{{ auth()->user()->avatar ?? asset('img/default-avatar.png') }}"
             alt="Avatar"
             class="h-8 w-8 rounded-full">
        <span class="hidden md:block font-medium text-gray-700">
            {{ auth()->user()->name }}
        </span>
        <i class="bi bi-chevron-down text-xs text-gray-400"></i>
    </button>

    <!-- Dropdown -->
    <div x-show="open"
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         @click.away="open = false"
         class="absolute {{ $align === 'right' ? 'right-0' : 'left-0' }} z-50 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200">

        <div class="py-1">
            <!-- Profile -->
            <a href="{{ route('profile.show') }}"
               class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                <i class="bi bi-person mr-3"></i>
                Meu Perfil
            </a>

            <!-- Settings -->
            <a href="{{ route('settings.index') }}"
               class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                <i class="bi bi-gear mr-3"></i>
                Configurações
            </a>

            <!-- Divider -->
            <div class="border-t border-gray-200 my-1"></div>

            <!-- Logout -->
            <form method="POST" action="{{ route('logout') }}" class="inline">
                @csrf
                <button type="submit"
                        class="flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                    <i class="bi bi-box-arrow-right mr-3"></i>
                    Sair
                </button>
            </form>
        </div>
    </div>
</div>
```

### 5. Footer

**Arquivo:** `components/navigation/footer.blade.php`

```blade
@props([])

<footer class="bg-white border-t border-gray-200 mt-auto">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <!-- Company Info -->
            <div class="col-span-1 md:col-span-2">
                <img src="{{ asset('img/logo.png') }}" alt="Easy Budget" class="h-8 w-auto mb-4">
                <p class="text-sm text-gray-600 mb-4">
                    Sistema completo de gestão financeira para empresas.
                </p>
                <div class="flex space-x-6">
                    <a href="/sobre" class="text-sm text-gray-500 hover:text-gray-900">Sobre</a>
                    <a href="/contato" class="text-sm text-gray-500 hover:text-gray-900">Contato</a>
                    <a href="/ajuda" class="text-sm text-gray-500 hover:text-gray-900">Ajuda</a>
                </div>
            </div>

            <!-- Legal -->
            <div>
                <h3 class="text-sm font-semibold text-gray-900 mb-4">Legal</h3>
                <div class="space-y-2">
                    <a href="/termos" class="text-sm text-gray-500 hover:text-gray-900 block">Termos de Uso</a>
                    <a href="/privacidade" class="text-sm text-gray-500 hover:text-gray-900 block">Política de Privacidade</a>
                </div>
            </div>
        </div>

        <!-- Copyright -->
        <div class="border-t border-gray-200 mt-8 pt-8 text-center">
            <p class="text-sm text-gray-500">
                © {{ date('Y') }} Easy Budget. Todos os direitos reservados.
            </p>
        </div>
    </div>
</footer>
```

---

## 🛠️ Implementação Técnica

### View Composers

```php
// app/Providers/ViewServiceProvider.php
namespace App\Providers;

use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class ViewServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Compartilhar dados com todos os layouts
        View::composer(['layouts.*'], function ($view) {
            $view->with('appName', config('app.name'));
            $view->with('currentUser', auth()->user());
            $view->with('flash', session()->get('flash', []));
        });

        // Dados específicos para admin
        View::composer(['layouts.admin'], function ($view) {
            $view->with('sidebarItems', $this->getSidebarItems());
            $view->with('currentRoute', request()->route()?->getName());
        });
    }

    private function getSidebarItems()
    {
        return [
            [
                'label' => 'Dashboard',
                'route' => 'admin.dashboard',
                'icon' => 'bi-speedometer2',
                'active' => request()->routeIs('admin.dashboard'),
            ],
            // ... outros itens
        ];
    }
}
```

### Service Provider Registration

```php
// config/app.php
'providers' => [
    // ...
    App\Providers\ViewServiceProvider::class,
];
```

---

## 📱 Design Responsivo

### Mobile (320px+)

```css
/* Header mobile */
@media (max-width: 768px) {
   .header-nav {
      display: none;
   }

   .mobile-menu {
      display: block;
   }
}

/* Sidebar mobile */
.admin-sidebar {
   position: fixed;
   z-index: 40;
   transform: translateX(-100%);
}

.admin-sidebar.open {
   transform: translateX(0);
}
```

### Tablet (768px+)

```css
/* Layout híbrido */
@media (min-width: 768px) and (max-width: 1024px) {
   .admin-content {
      margin-left: 240px;
   }

   .admin-sidebar {
      width: 240px;
   }
}
```

### Desktop (1024px+)

```css
/* Layout completo */
.admin-sidebar {
   width: 280px;
}

.admin-content {
   margin-left: 280px;
}
```

---

## ♿ Acessibilidade

### Navegação por Teclado

```html
<!-- Skip links -->
<a href="#main-content" class="sr-only focus:not-sr-only">
   Pular para o conteúdo principal
</a>

<!-- Navegação com ARIA -->
<nav role="navigation" aria-label="Menu principal">
   <ul role="list">
      <li role="listitem">
         <a href="/dashboard" aria-current="page">Dashboard</a>
      </li>
   </ul>
</nav>
```

### Screen Readers

```html
<!-- Regiões semânticas -->
<header role="banner">
    <nav role="navigation" aria-label="Menu principal">
</header>

<main id="main-content" role="main">
    <section aria-labelledby="section-title">
        <h1 id="section-title">Título da Seção</h1>
    </section>
</main>

<footer role="contentinfo">
```

---

## 🔧 Funcionalidades Avançadas

### Tema Dinâmico

```php
// app/Providers/AppServiceProvider.php
public function boot()
{
    // Tema baseado na preferência do usuário
    $theme = auth()->user()->theme ?? 'light';

    View::share('theme', $theme);
    View::share('themeColors', $this->getThemeColors($theme));
}

private function getThemeColors($theme)
{
    return [
        'primary' => $theme === 'dark' ? '#1e40af' : '#3b82f6',
        'background' => $theme === 'dark' ? '#1f2937' : '#ffffff',
        // ... outras cores
    ];
}
```

### Breadcrumb Dinâmico

```php
// app/Http/Controllers/Controller.php
protected function setBreadcrumb($items)
{
    View::share('breadcrumb', $items);
}

// Uso no controller
$this->setBreadcrumb([
    ['label' => 'Admin', 'url' => route('admin.dashboard')],
    ['label' => 'Usuários', 'url' => route('admin.users.index')],
    ['label' => 'Editar'],
]);
```

---

## 🧪 Testes

### Testes de Layout

```php
// tests/Feature/Layouts/LayoutTest.php
class LayoutTest extends TestCase
{
    public function test_app_layout_renders()
    {
        $response = $this->actingAs(User::factory()->create())
                         ->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('<!DOCTYPE html>');
        $response->assertSee('csrf-token');
    }

    public function test_admin_layout_renders()
    {
        $response = $this->actingAs(User::factory()->create())
                         ->get('/admin');

        $response->assertStatus(200);
        $response->assertSee('admin-sidebar');
        $response->assertSee('admin-content');
    }

    public function test_guest_layout_renders()
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('min-h-full');
        $response->assertDontSee('navigation');
    }
}
```

### Testes de Responsividade

```php
public function test_layouts_are_responsive()
{
    $user = User::factory()->create();

    // Mobile
    $response = $this->actingAs($user)
                     ->get('/dashboard');

    $response->assertSee('sm:px-6');
    $response->assertSee('md:flex');
    $response->assertSee('lg:px-8');
}
```

---

## 📋 Checklist de Implementação

### Para Cada Layout

-  [ ] Template criado corretamente
-  [ ] Componentes necessários implementados
-  [ ] Design responsivo funcionando
-  [ ] Acessibilidade verificada
-  [ ] View composers configurados
-  [ ] Testes automatizados criados
-  [ ] Performance otimizada
-  [ ] Assets carregando corretamente
-  [ ] Estados de erro tratados
-  [ ] Documentação atualizada

### Critérios de Aceitação

-  [ ] Todos os layouts renderizam sem erro
-  [ ] Navegação funcional em todos os layouts
-  [ ] Responsividade em mobile/tablet/desktop
-  [ ] Acessibilidade WCAG AA compliance
-  [ ] Performance adequada (Lighthouse > 90)
-  [ ] Sem erros JavaScript no console
-  [ ] Testes automatizados passando
-  [ ] View composers funcionando
-  [ ] Assets compilados corretamente

---

## 🚀 Deploy e Monitoramento

### Assets Compilation

```bash
# Build de produção
npm run build

# Build com análise
npm run build -- --analyze

# Preview do build
npm run preview
```

### Performance Monitoring

```php
// Monitorar tempo de renderização
$start = microtime(true);
$view = view('layouts.app', $data)->render();
$renderTime = microtime(true) - $start;

Log::info('Layout render time', [
    'layout' => 'app',
    'time' => $renderTime,
    'threshold_exceeded' => $renderTime > 0.5
]);
```

---

## 📚 Referências

-  [Laravel Blade Layouts](https://laravel.com/docs/blade#layouts)
-  [View Composers](https://laravel.com/docs/views#view-composers)
-  [Responsive Design](https://web.dev/responsive-web-design-basics/)
-  [Accessibility Guidelines](https://www.w3.org/WAI/WCAG21/quickref/)

---

**Documento criado em:** 2025-09-30
**Versão:** 1.0
**Status:** ✅ Especificações Completas
