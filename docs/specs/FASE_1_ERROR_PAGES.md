# 📋 Especificações Técnicas - Páginas de Erro (Fase 1)

## 🎯 Visão Geral

Especificação completa para implementação das páginas de erro críticas do sistema Easy Budget Laravel.

---

## 📊 Páginas de Erro Especificadas

### 1. Página 404 - Não Encontrada

#### 🎨 Design e Layout

**Layout:** `layouts.guest` (sem navegação)
**Template:** `errors/404.blade.php`

```blade
@extends('layouts.guest')

@section('title', 'Página não encontrada')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4">
    <div class="max-w-md w-full text-center">
        <!-- Ilustração -->
        <div class="mb-8">
            <i class="bi bi-emoji-dizzy text-6xl text-blue-600"></i>
            <h1 class="mt-4 text-4xl font-bold text-gray-900">404</h1>
        </div>

        <!-- Mensagens -->
        <h2 class="text-2xl font-semibold text-gray-900 mb-4">
            Página não encontrada
        </h2>

        <p class="text-gray-600 mb-8">
            Desculpe, a página que você está procurando não existe ou foi movida.
        </p>

        <!-- Ações -->
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="/" class="btn btn-primary">
                <i class="bi bi-house-door mr-2"></i>Página Inicial
            </a>
            <button onclick="history.back()" class="btn btn-secondary">
                <i class="bi bi-arrow-left mr-2"></i>Voltar
            </button>
        </div>

        <!-- Links úteis -->
        <div class="mt-8 pt-8 border-t border-gray-200">
            <p class="text-sm text-gray-500 mb-4">Ou acesse:</p>
            <div class="flex flex-wrap gap-2 justify-center">
                <a href="/dashboard" class="text-sm text-blue-600 hover:text-blue-700">Dashboard</a>
                <span class="text-gray-300">•</span>
                <a href="/login" class="text-sm text-blue-600 hover:text-blue-700">Login</a>
                <span class="text-gray-300">•</span>
                <a href="/settings" class="text-sm text-blue-600 hover:text-blue-700">Configurações</a>
            </div>
        </div>
    </div>
</div>
@endsection
```

#### 🔧 Funcionalidades

-  **Botão "Voltar"**: Usa `history.back()` JavaScript
-  **Links rápidos**: Acesso direto às principais áreas
-  **Responsivo**: Layout adaptável mobile/desktop
-  **Animações**: Transições suaves com Alpine.js

#### ♿ Acessibilidade

-  **Código de erro**: Anunciado para screen readers
-  **Navegação**: Todos os elementos focusable
-  **Semântica**: Uso correto de headings
-  **Contraste**: Cores com contraste WCAG AA

---

### 2. Página 403 - Acesso Negado

#### 🎨 Design e Layout

**Layout:** `layouts.guest`
**Template:** `errors/403.blade.php`

```blade
@extends('layouts.guest')

@section('title', 'Acesso negado')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4">
    <div class="max-w-md w-full text-center">
        <!-- Ilustração -->
        <div class="mb-8">
            <i class="bi bi-shield-x text-6xl text-red-600"></i>
            <h1 class="mt-4 text-4xl font-bold text-gray-900">403</h1>
        </div>

        <!-- Mensagens -->
        <h2 class="text-2xl font-semibold text-gray-900 mb-4">
            Acesso negado
        </h2>

        <p class="text-gray-600 mb-8">
            Você não tem permissão para acessar esta página ou recurso.
        </p>

        <!-- Ações -->
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="/" class="btn btn-primary">
                <i class="bi bi-house-door mr-2"></i>Página Inicial
            </a>
            <button onclick="history.back()" class="btn btn-secondary">
                <i class="bi bi-arrow-left mr-2"></i>Voltar
            </button>
        </div>

        <!-- Contato suporte -->
        <div class="mt-8 pt-8 border-t border-gray-200">
            <p class="text-sm text-gray-500 mb-2">
                Precisa de acesso? Entre em contato:
            </p>
            <a href="mailto:suporte@easybudget.com" class="text-sm text-blue-600 hover:text-blue-700">
                <i class="bi bi-envelope mr-1"></i>suporte@easybudget.com
            </a>
        </div>
    </div>
</div>
@endsection
```

#### 🔧 Funcionalidades

-  **Detecção automática**: Laravel identifica automaticamente
-  **Link para suporte**: Email direto para contato
-  **Mesma estrutura**: Consistência com página 404

---

### 3. Página 500 - Erro Interno

#### 🎨 Design e Layout

**Layout:** `layouts.guest`
**Template:** `errors/500.blade.php`

```blade
@extends('layouts.guest')

@section('title', 'Erro interno do servidor')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4">
    <div class="max-w-md w-full text-center">
        <!-- Ilustração -->
        <div class="mb-8">
            <i class="bi bi-exclamation-triangle text-6xl text-yellow-600"></i>
            <h1 class="mt-4 text-4xl font-bold text-gray-900">500</h1>
        </div>

        <!-- Mensagens -->
        <h2 class="text-2xl font-semibold text-gray-900 mb-4">
            Erro interno do servidor
        </h2>

        <p class="text-gray-600 mb-4">
            Ocorreu um erro inesperado em nossos servidores.
        </p>

        <p class="text-sm text-gray-500 mb-8">
            Nossa equipe foi notificada e estamos trabalhando para resolver o problema.
        </p>

        <!-- Ações -->
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <button onclick="location.reload()" class="btn btn-primary">
                <i class="bi bi-arrow-clockwise mr-2"></i>Tentar novamente
            </button>
            <a href="/" class="btn btn-secondary">
                <i class="bi bi-house-door mr-2"></i>Página Inicial
            </a>
        </div>

        <!-- ID do erro -->
        <div class="mt-8 pt-8 border-t border-gray-200">
            <p class="text-xs text-gray-400">
                ID do erro: {{ uniqid() }}
            </p>
        </div>
    </div>
</div>
@endsection
```

#### 🔧 Funcionalidades

-  **Reload automático**: Botão para tentar novamente
-  **ID único**: Para rastreamento de erros
-  **Notificação**: Sistema de logging automático

---

## 🛠️ Implementação Técnica

### Handler de Erros

```php
// app/Exceptions/Handler.php
public function render($request, Throwable $exception)
{
    // Página 404 customizada
    if ($exception instanceof NotFoundHttpException) {
        return response()->view('errors.404', [], 404);
    }

    // Página 403 customizada
    if ($exception instanceof AuthorizationException) {
        return response()->view('errors.403', [], 403);
    }

    // Página 500 customizada
    if ($this->isHttpException($exception)) {
        return response()->view('errors.500', [], 500);
    }

    return parent::render($request, $exception);
}
```

### Tratamento de Exceções

```php
// Para exceptions específicas
public function register()
{
    $this->renderable(function (ModelNotFoundException $e, $request) {
        if ($request->is('api/*')) {
            return response()->json(['error' => 'Recurso não encontrado'], 404);
        }

        return response()->view('errors.404', [], 404);
    });
}
```

---

## 📱 Design Responsivo

### Mobile (320px+)

```css
/* Layout single column */
.min-h-screen {
   padding: 3rem 1rem;
}

/* Ícones menores */
.text-6xl {
   font-size: 3rem;
}

/* Botões stacked */
.flex-col {
   gap: 1rem;
}
```

### Tablet (768px+)

```css
/* Ícones médios */
.text-6xl {
   font-size: 4rem;
}

/* Botões lado a lado */
.sm\:flex-row {
   flex-direction: row;
}
```

### Desktop (1024px+)

```css
/* Layout otimizado */
.max-w-md {
   max-width: 28rem;
}

/* Espaçamentos adequados */
.mb-8 {
   margin-bottom: 2rem;
}
```

---

## ♿ Acessibilidade

### WCAG 2.1 AA Compliance

```html
<!-- Anúncio para screen readers -->
<h1 class="sr-only">
   Erro {{ $status ?? '500' }} - {{ $title ?? 'Erro interno' }}
</h1>

<!-- Descrição clara -->
<p id="error-description">{{ $message ?? 'Ocorreu um erro inesperado.' }}</p>

<!-- Navegação alternativa -->
<nav aria-label="Navegação alternativa" class="mt-8">
   <ul class="flex gap-4 justify-center">
      <li><a href="/" class="btn btn-primary">Início</a></li>
      <li><a href="/dashboard" class="btn btn-secondary">Dashboard</a></li>
   </ul>
</nav>
```

### Navegação por Teclado

```css
/* Focus visível */
.btn:focus {
   outline: 2px solid #3b82f6;
   outline-offset: 2px;
}

/* Ordem lógica */
.tab-order-1 {
   tab-index: 1;
}
.tab-order-2 {
   tab-index: 2;
}
```

---

## 🔍 SEO e Performance

### Meta Tags

```html
<meta name="robots" content="noindex, nofollow" />
<meta name="description" content="Página de erro - Easy Budget" />
<meta name="keywords" content="erro, página não encontrada" />

<!-- Open Graph -->
<meta property="og:title" content="Erro - Easy Budget" />
<meta property="og:description" content="Página de erro do sistema" />
<meta property="og:type" content="website" />
```

### Performance

```html
<!-- Cache control -->
<meta
   http-equiv="Cache-Control"
   content="no-cache, no-store, must-revalidate"
/>
<meta http-equiv="Pragma" content="no-cache" />
<meta http-equiv="Expires" content="0" />

<!-- Recursos mínimos -->
<link rel="preload" href="/assets/css/app.css" as="style" />
<link rel="preload" href="/assets/js/app.js" as="script" />
```

---

## 🧪 Testes

### Testes Automatizados

```php
// tests/Feature/ErrorsTest.php
class ErrorsTest extends TestCase
{
    public function test_404_page_renders()
    {
        $response = $this->get('/non-existent-route');

        $response->assertStatus(404);
        $response->assertViewIs('errors.404');
        $response->assertSee('Página não encontrada');
        $response->assertSee('Página Inicial');
    }

    public function test_403_page_renders()
    {
        $response = $this->get('/admin');

        $response->assertStatus(403);
        $response->assertViewIs('errors.403');
        $response->assertSee('Acesso negado');
    }

    public function test_500_page_renders()
    {
        // Forçar erro 500
        config(['app.debug' => false]);

        $response = $this->get('/force-error');

        $response->assertStatus(500);
        $response->assertViewIs('errors.500');
        $response->assertSee('Erro interno do servidor');
    }
}
```

### Testes de Responsividade

```php
public function test_error_pages_are_responsive()
{
    $pages = ['errors.404', 'errors.403', 'errors.500'];

    foreach ($pages as $page) {
        $response = $this->get(route($page));

        $response->assertStatus(200);
        $response->assertSee('class="min-h-screen"');
        $response->assertSee('sm:flex-row');
    }
}
```

---

## 📋 Checklist de Implementação

### Para Cada Página de Erro

-  [ ] Template criado com layout correto
-  [ ] Design responsivo implementado
-  [ ] Acessibilidade verificada
-  [ ] Testes automatizados criados
-  [ ] Meta tags configuradas
-  [ ] Ícones apropriados usados
-  [ ] Mensagens claras e úteis
-  [ ] Botões de ação funcionais
-  [ ] Links alternativos fornecidos
-  [ ] Tratamento de erro no Handler

### Critérios de Aceitação

-  [ ] Todas as páginas retornam códigos HTTP corretos
-  [ ] Layout consistente entre todas as páginas
-  [ ] Responsividade em mobile/tablet/desktop
-  [ ] Navegação por teclado funcional
-  [ ] Screen readers anunciam erros corretamente
-  [ ] Performance adequada (Lighthouse > 90)
-  [ ] Sem erros JavaScript no console
-  [ ] Testes automatizados passando

---

## 🚀 Deploy e Monitoramento

### Configuração de Produção

```php
// .env
APP_DEBUG=false
APP_URL=https://easybudget.com

// Logging de erros
LOG_CHANNEL=stack
LOG_LEVEL=error
```

### Monitoramento

```php
// app/Exceptions/Handler.php
public function report(Throwable $exception)
{
    // Notificar equipe técnica
    if ($this->shouldReport($exception)) {
        // Slack notification, email, etc.
        Notification::route('slack', '#errors')
            ->notify(new ErrorOccurred($exception));
    }

    parent::report($exception);
}
```

---

## 📚 Referências

-  [Laravel Error Handling](https://laravel.com/docs/errors)
-  [HTTP Status Codes](https://developer.mozilla.org/en-US/docs/Web/HTTP/Status)
-  [WCAG Error Handling](https://www.w3.org/WAI/WCAG21/quickref/#error-identification)
-  [Error Page Best Practices](https://web.dev/pwa/)

---

**Documento criado em:** 2025-09-30
**Versão:** 1.0
**Status:** ✅ Especificações Completas
