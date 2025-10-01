# 🚀 Quick Start Guide - Migração Twig → Blade

## 📋 Guia Rápido de Implementação

Este documento fornece comandos e checklist práticos para executar cada fase da migração.

---

## ⚙️ Setup Inicial

### Passo 1: Preparar Ambiente

```bash
# Instalar dependências
npm install

# Verificar versões
npm list vite tailwindcss alpinejs

# Build de desenvolvimento
npm run dev
```

### Passo 2: Criar Estrutura Base

```bash
# Criar diretórios
mkdir -p resources/views/{layouts,components/{ui,form,navigation},pages,emails,errors}

# Criar subdiretorios de componentes
mkdir -p resources/views/components/{budget,customer,invoice,service,reports}

# Criar subdiretorios de páginas
mkdir -p resources/views/pages/{auth,dashboard,budgets,customers,products,services,invoices,reports,settings,admin}
```

### Passo 3: Configurar Tailwind

```bash
# Atualizar tailwind.config.js
# Ver arquivo completo em MIGRATION_TWIG_TO_BLADE.md seção 8.1.1

# Criar app.css
cat > resources/css/app.css << 'EOF'
@tailwind base;
@tailwind components;
@tailwind utilities;

@layer components {
  .btn {
    @apply px-4 py-2 rounded-lg font-medium transition-colors;
  }

  .btn-primary {
    @apply bg-blue-600 text-white hover:bg-blue-700;
  }

  .btn-secondary {
    @apply bg-gray-600 text-white hover:bg-gray-700;
  }

  .card {
    @apply bg-white rounded-lg shadow-sm border border-gray-200;
  }

  .form-input {
    @apply w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent;
  }
}
EOF

# Build
npm run build
```

---

## 📝 FASE 1: FUNDAÇÃO

### Checklist de Implementação

#### 1.1 Error Pages (2h)

```bash
# Criar error pages
php artisan make:view errors.404
php artisan make:view errors.403
php artisan make:view errors.500

# Testar
curl -I http://localhost:8000/non-existent-page
# Deve retornar 404 e renderizar errors.404
```

**Template Base:**

```blade
<!-- resources/views/errors/404.blade.php -->
@extends('layouts.guest')

@section('title', 'Página não encontrada')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4">
    <div class="max-w-md w-full text-center">
        <div class="mb-8">
            <i class="bi bi-emoji-dizzy text-6xl text-blue-600"></i>
            <h1 class="mt-4 text-4xl font-bold text-gray-900">404</h1>
        </div>

        <h2 class="text-2xl font-semibold text-gray-900 mb-4">
            Página não encontrada
        </h2>

        <p class="text-gray-600 mb-8">
            Desculpe, a página que você está procurando não existe ou foi movida.
        </p>

        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="/" class="btn btn-primary">
                <i class="bi bi-house-door mr-2"></i>Página Inicial
            </a>
            <button onclick="history.back()" class="btn btn-secondary">
                <i class="bi bi-arrow-left mr-2"></i>Voltar
            </button>
        </div>
    </div>
</div>
@endsection
```

**Validação:**

-  [ ] 404 renderiza corretamente
-  [ ] 403 renderiza corretamente
-  [ ] 500 renderiza corretamente
-  [ ] Estilos TailwindCSS aplicados
-  [ ] Botões funcionam
-  [ ] Responsivo em mobile

---

#### 1.2 Login & Auth (5h)

```bash
# Criar views de autenticação
php artisan make:view auth.login
php artisan make:view auth.forgot-password
php artisan make:view auth.reset-password

# Atualizar rotas
# Ver routes/auth.php
```

**Template Base Login:**

```blade
<!-- resources/views/auth/login.blade.php -->
@extends('layouts.guest')

@section('title', 'Login')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4">
    <div class="max-w-md w-full">
        <div class="bg-white rounded-lg shadow-lg p-8">
            <!-- Logo -->
            <div class="text-center mb-8">
                <img src="/assets/img/logo.png" alt="Logo" class="h-12 mx-auto mb-4">
                <h1 class="text-2xl font-bold text-gray-900">Seja bem-vindo</h1>
                <p class="text-gray-600">Faça login para continuar</p>
            </div>

            <!-- Flash Messages -->
            <x-flash-messages />

            <!-- Form -->
            <form method="POST" action="{{ route('login') }}" class="space-y-6">
                @csrf

                <!-- Email -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                        Email
                    </label>
                    <input
                        type="email"
                        name="email"
                        id="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        class="form-input @error('email') border-red-500 @enderror"
                    >
                    @error('email')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Password -->
                <div x-data="{ showPassword: false }">
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                        Senha
                    </label>
                    <div class="relative">
                        <input
                            :type="showPassword ? 'text' : 'password'"
                            name="password"
                            id="password"
                            required
                            class="form-input @error('password') border-red-500 @enderror pr-10"
                        >
                        <button
                            type="button"
                            @click="showPassword = !showPassword"
                            class="absolute inset-y-0 right-0 pr-3 flex items-center"
                        >
                            <i :class="showPassword ? 'bi-eye-slash' : 'bi-eye'" class="text-gray-400"></i>
                        </button>
                    </div>
                    @error('password')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Remember Me -->
                <div class="flex items-center justify-between">
                    <label class="flex items-center">
                        <input type="checkbox" name="remember" class="rounded border-gray-300 text-blue-600">
                        <span class="ml-2 text-sm text-gray-600">Lembrar-me</span>
                    </label>

                    <a href="{{ route('password.request') }}" class="text-sm text-blue-600 hover:text-blue-700">
                        Esqueceu a senha?
                    </a>
                </div>

                <!-- Submit -->
                <button type="submit" class="w-full btn btn-primary">
                    <i class="bi bi-box-arrow-in-right mr-2"></i>Entrar
                </button>
            </form>

            <!-- Register Link -->
            <div class="mt-6 text-center">
                <span class="text-gray-600">Não tem uma conta?</span>
                <a href="{{ route('register') }}" class="text-blue-600 hover:text-blue-700 ml-1">
                    Registre-se
                </a>
            </div>
        </div>

        <!-- Security Badge -->
        <div class="text-center mt-4">
            <small class="text-gray-500">
                <i class="bi bi-shield-lock mr-1"></i>Login seguro via SSL
            </small>
        </div>
    </div>
</div>
@endsection
```

**Validação:**

-  [ ] Login funciona
-  [ ] Validação de campos
-  [ ] Flash messages aparecem
-  [ ] Toggle de senha funciona (Alpine.js)
-  [ ] Link "Esqueci a senha" funciona
-  [ ] Responsivo em mobile

---

#### 1.3 Layout Principal (10h)

```bash
# Criar layout base
php artisan make:view layouts.app
php artisan make:view layouts.guest

# Criar componentes de navegação
php artisan make:component Navigation\\Header
php artisan make:component Navigation\\Menu
php artisan make:component Navigation\\Footer

# Criar componente de head
php artisan make:component Head
```

**Template Layout App:**

```blade
<!-- resources/views/layouts/app.blade.php -->
<!DOCTYPE html>
<html lang="pt-BR" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name') }} - @yield('title', 'Dashboard')</title>

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
            @if(session()->has('success') || session()->has('error') || session()->has('warning') || session()->has('info'))
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                    <x-flash-messages />
                </div>
            @endif

            @yield('content')
        </main>

        <!-- Footer -->
        <x-navigation.footer />
    </div>

    @stack('scripts')
</body>
</html>
```

**Validação:**

-  [ ] Layout renderiza
-  [ ] Vite assets carregam
-  [ ] Header renderiza
-  [ ] Footer renderiza
-  [ ] Flash messages aparecem
-  [ ] Responsivo em mobile

---

#### 1.4 Componente de Alert (2h)

```bash
# Criar componente
php artisan make:component Alert
```

**Implementação:**

```blade
<!-- resources/views/components/alert.blade.php -->
@props([
    'type' => 'info',
    'message' => '',
    'dismissible' => true,
])

@php
    $classes = [
        'success' => 'bg-green-50 text-green-800 border-green-200',
        'error' => 'bg-red-50 text-red-800 border-red-200',
        'danger' => 'bg-red-50 text-red-800 border-red-200',
        'warning' => 'bg-yellow-50 text-yellow-800 border-yellow-200',
        'info' => 'bg-blue-50 text-blue-800 border-blue-200',
    ][$type] ?? 'bg-gray-50 text-gray-800 border-gray-200';

    $icons = [
        'success' => 'bi-check-circle-fill',
        'error' => 'bi-exclamation-circle-fill',
        'danger' => 'bi-exclamation-circle-fill',
        'warning' => 'bi-exclamation-triangle-fill',
        'info' => 'bi-info-circle-fill',
    ][$type] ?? 'bi-info-circle';
@endphp

<div
    x-data="{ show: true }"
    x-show="show"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 transform scale-90"
    x-transition:enter-end="opacity-100 transform scale-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100 transform scale-100"
    x-transition:leave-end="opacity-0 transform scale-90"
    class="border rounded-lg p-4 {{ $classes }}"
    role="alert"
>
    <div class="flex items-start">
        <div class="flex-shrink-0">
            <i class="{{ $icons }} text-xl"></i>
        </div>

        <div class="ml-3 flex-1">
            @if($message)
                {!! $message !!}
            @else
                {{ $slot }}
            @endif
        </div>

        @if($dismissible)
            <button
                @click="show = false"
                type="button"
                class="ml-3 flex-shrink-0 text-current opacity-50 hover:opacity-100 transition-opacity"
            >
                <i class="bi bi-x-lg"></i>
            </button>
        @endif
    </div>
</div>
```

**Componente Flash Messages:**

```blade
<!-- resources/views/components/flash-messages.blade.php -->
<div class="space-y-4">
    @if(session()->has('success'))
        <x-alert type="success" :message="session('success')" />
    @endif

    @if(session()->has('error'))
        <x-alert type="error" :message="session('error')" />
    @endif

    @if(session()->has('warning'))
        <x-alert type="warning" :message="session('warning')" />
    @endif

    @if(session()->has('info'))
        <x-alert type="info" :message="session('info')" />
    @endif

    @if($errors->any())
        <x-alert type="error">
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </x-alert>
    @endif
</div>
```

**Validação:**

-  [ ] Alert renderiza
-  [ ] 4 tipos funcionam (success, error, warning, info)
-  [ ] Dismiss funciona (Alpine.js)
-  [ ] Animações suaves
-  [ ] Flash messages aparecem

---

## 🧪 Testes de Validação

### Teste Manual - Checklist

```bash
# 1. Páginas de erro
curl -I http://localhost:8000/404
curl -I http://localhost:8000/403
curl -I http://localhost:8000/500

# 2. Login
open http://localhost:8000/login
# Testar:
# - Login com credenciais válidas
# - Login com credenciais inválidas
# - Toggle de senha
# - Validação de campos
# - Flash messages

# 3. Layout
# Verificar em:
# - Desktop (1920x1080)
# - Tablet (768x1024)
# - Mobile (375x667)

# 4. Alertas
# Testar todos os tipos:
php artisan tinker
>>> session()->flash('success', 'Teste de sucesso');
>>> session()->flash('error', 'Teste de erro');
```

### Testes Automatizados

```bash
# Executar testes
php artisan test --filter=ViewTest

# Com coverage
php artisan test --coverage
```

**Exemplo de Teste:**

```php
// tests/Feature/Views/Phase1Test.php
<?php

namespace Tests\Feature\Views;

use Tests\TestCase;
use App\Models\User;

class Phase1Test extends TestCase
{
    public function test_404_page_renders()
    {
        $response = $this->get('/non-existent-route');
        $response->assertStatus(404);
        $response->assertViewIs('errors.404');
    }

    public function test_login_page_renders()
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertViewIs('auth.login');
    }

    public function test_user_can_login()
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123')
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_alert_component_renders()
    {
        $view = $this->blade('<x-alert type="success" message="Test" />');

        $view->assertSee('Test');
        $view->assertSee('bg-green-50');
    }
}
```

---

## 📊 Métricas de Sucesso

### KPIs por Fase

**Fase 1:**

-  [ ] 0 erros no console do navegador
-  [ ] 100% das páginas renderizando
-  [ ] Lighthouse Score > 90
-  [ ] Tempo de carregamento < 1s
-  [ ] 100% dos testes passando

**Comandos de Medição:**

```bash
# Performance
npm run build
npx lighthouse http://localhost:8000/login --view

# Bundle size
npm run build -- --analyze

# Testes
php artisan test --coverage-html=coverage
```

---

## 🚨 Troubleshooting

### Problemas Comuns

**1. Vite não carrega assets**

```bash
# Limpar cache
npm run build
php artisan view:clear
php artisan cache:clear

# Verificar vite.config.js
# Verificar .env: VITE_URL correto
```

**2. Alpine.js não funciona**

```bash
# Verificar importação em app.js
# Verificar @vite directive no layout
# Abrir console: verificar erros JS
```

**3. TailwindCSS não aplica estilos**

```bash
# Verificar tailwind.config.js content paths
# Rebuild
npm run build

# Verificar classes no HTML (inspecionar elemento)
```

**4. Flash messages não aparecem**

```bash
# Verificar session driver em .env
# Verificar middleware web em routes
# Verificar componente <x-flash-messages /> no layout
```

---

## 📝 Checklist Final Fase 1

Antes de avançar para Fase 2, confirme:

-  [ ] Error pages (404, 403, 500) funcionando
-  [ ] Login completo funcionando
-  [ ] Layout base renderizando
-  [ ] Header e Footer renderizando
-  [ ] Sistema de alertas funcionando
-  [ ] Alpine.js funcionando (toggle senha, dismiss alerts)
-  [ ] TailwindCSS aplicando estilos
-  [ ] Vite compilando assets
-  [ ] 0 erros JavaScript no console
-  [ ] Testes automatizados passando
-  [ ] Lighthouse Score > 90
-  [ ] Responsivo em mobile/tablet/desktop
-  [ ] Documentação atualizada

---

## 🎯 Próximos Passos

Após completar Fase 1:

1. **Code Review:** Revisar todo código implementado
2. **Deploy em Staging:** Testar em ambiente de homologação
3. **Feedback do Time:** Coletar feedback da equipe
4. **Iniciar Fase 2:** Dashboard e Settings

---

**Documento criado em:** 2025-09-30
**Versão:** 1.0
**Para:** Implementação rápida da migração
