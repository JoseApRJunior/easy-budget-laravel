# 📋 Especificações Técnicas - Sistema de Alertas (Fase 1)

## 🎯 Visão Geral

Especificação completa para implementação do sistema de alertas do Easy Budget Laravel, substituindo o sistema de macros Twig por componentes Blade modernos com Alpine.js.

---

## 📊 Sistema de Alertas Especificado

### 1. Componente de Alerta Base

#### 🎨 Design e Estrutura

**Arquivo:** `components/alert.blade.php`
**Uso:** Alertas individuais em toda a aplicação

```blade
@props([
    'type' => 'info',
    'message' => '',
    'dismissible' => true,
    'icon' => true,
    'autoHide' => true,
    'duration' => 5000
])

@php
    $typeClasses = [
        'success' => 'bg-green-50 border-green-200 text-green-800',
        'error' => 'bg-red-50 border-red-200 text-red-800',
        'danger' => 'bg-red-50 border-red-200 text-red-800',
        'warning' => 'bg-yellow-50 border-yellow-200 text-yellow-800',
        'info' => 'bg-blue-50 border-blue-200 text-blue-800',
    ];

    $icons = [
        'success' => 'bi-check-circle-fill',
        'error' => 'bi-exclamation-circle-fill',
        'danger' => 'bi-exclamation-circle-fill',
        'warning' => 'bi-exclamation-triangle-fill',
        'info' => 'bi-info-circle-fill',
    ];

    $baseClasses = 'border rounded-lg p-4 flex items-start space-x-3';
    $classes = collect([$baseClasses, $typeClasses[$type] ?? $typeClasses['info']])->implode(' ');
@endphp

<div
    x-data="alertComponent({
        autoHide: {{ $autoHide ? 'true' : 'false' }},
        duration: {{ $duration }}
    })"
    x-show="visible"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 transform scale-90"
    x-transition:enter-end="opacity-100 transform scale-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100 transform scale-100"
    x-transition:leave-end="opacity-0 transform scale-90"
    class="{{ $classes }}"
    role="alert"
    aria-live="polite"
>
    <!-- Ícone -->
    @if($icon)
        <div class="flex-shrink-0">
            <i class="{{ $icons[$type] ?? $icons['info'] }} text-xl"></i>
        </div>
    @endif

    <!-- Conteúdo -->
    <div class="flex-1 min-w-0">
        @if($message)
            {!! $message !!}
        @else
            {{ $slot }}
        @endif
    </div>

    <!-- Botão Dismiss -->
    @if($dismissible)
        <div class="flex-shrink-0">
            <button
                @click="dismiss()"
                type="button"
                class="text-current opacity-50 hover:opacity-100 transition-opacity focus:outline-none focus:ring-2 focus:ring-current focus:ring-offset-2 rounded"
                aria-label="Fechar alerta"
            >
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
    @endif
</div>

<script>
function alertComponent(config = {}) {
    return {
        visible: true,
        autoHide: config.autoHide ?? true,
        duration: config.duration ?? 5000,

        init() {
            if (this.autoHide) {
                setTimeout(() => {
                    this.dismiss();
                }, this.duration);
            }
        },

        dismiss() {
            this.visible = false;
        }
    }
}
</script>
```

#### 🔧 Funcionalidades

-  **Auto-hide**: Desaparece automaticamente após duração
-  **Dismiss manual**: Botão para fechar
-  **Animações**: Transições suaves
-  **Tipos variados**: Success, error, warning, info
-  **Acessibilidade**: ARIA labels e live regions

---

### 2. Sistema de Flash Messages

#### 🎨 Design e Estrutura

**Arquivo:** `components/flash-messages.blade.php`
**Uso:** Renderiza todas as flash messages da sessão

```blade
@props(['class' => ''])

<div class="space-y-4 {{ $class }}" x-data="{ messages: @js($this->getFlashMessages()) }">
    <!-- Success Messages -->
    <template x-for="message in messages.success" :key="message.id">
        <x-alert type="success" :message="message.text" />
    </template>

    <!-- Error Messages -->
    <template x-for="message in messages.error" :key="message.id">
        <x-alert type="error" :message="message.text" />
    </template>

    <!-- Warning Messages -->
    <template x-for="message in messages.warning" :key="message.id">
        <x-alert type="warning" :message="message.text" />
    </template>

    <!-- Info Messages -->
    <template x-for="message in messages.info" :key="message.id">
        <x-alert type="info" :message="message.text" />
    </template>

    <!-- Validation Errors -->
    @if($errors->any())
        <x-alert type="error">
            <div class="font-medium mb-2">Por favor, corrija os seguintes erros:</div>
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </x-alert>
    @endif
</div>
```

#### 🔧 Funcionalidades

-  **Múltiplas mensagens**: Suporte a vários tipos simultâneos
-  **Validação automática**: Integração com Laravel validation
-  **Persistência**: Mantém mensagens até dismiss
-  **Categorias**: Organização por tipo

---

### 3. Service Provider para Flash Messages

#### 🛠️ Implementação Técnica

```php
// app/Providers/AppServiceProvider.php
namespace App\Providers;

use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Compartilhar flash messages com todas as views
        View::composer('*', function ($view) {
            $flash = session()->get('flash', []);

            // Converter para formato padronizado
            $messages = $this->formatFlashMessages($flash);

            $view->with('flashMessages', $messages);
        });
    }

    private function formatFlashMessages($flash)
    {
        $messages = [
            'success' => [],
            'error' => [],
            'warning' => [],
            'info' => [],
        ];

        foreach ($flash as $type => $content) {
            if (isset($messages[$type])) {
                $message = is_array($content) ? $content['message'] : $content;
                $messages[$type][] = [
                    'id' => uniqid(),
                    'text' => $message,
                    'type' => $type,
                ];
            }
        }

        return $messages;
    }
}
```

### 4. Helper Functions para Flash Messages

```php
// app/Helpers/FlashHelper.php
namespace App\Helpers;

class FlashHelper
{
    /**
     * Flash success message
     */
    public static function success($message)
    {
        session()->flash('flash.success', $message);
    }

    /**
     * Flash error message
     */
    public static function error($message)
    {
        session()->flash('flash.error', $message);
    }

    /**
     * Flash warning message
     */
    public static function warning($message)
    {
        session()->flash('flash.warning', $message);
    }

    /**
     * Flash info message
     */
    public static function info($message)
    {
        session()->flash('flash.info', $message);
    }

    /**
     * Flash multiple messages
     */
    public static function messages($messages)
    {
        foreach ($messages as $type => $message) {
            self::{$type}($message);
        }
    }
}
```

---

## 📱 Design Responsivo

### Mobile (320px+)

```css
/* Alert compacto */
.alert {
   padding: 0.75rem;
   font-size: 0.875rem;
}

/* Ícones menores */
.alert .bi {
   font-size: 1rem;
}

/* Botão dismiss menor */
.alert button {
   padding: 0.25rem;
}
```

### Tablet (768px+)

```css
/* Alert padrão */
.alert {
   padding: 1rem;
   font-size: 0.875rem;
}

/* Ícones padrão */
.alert .bi {
   font-size: 1.25rem;
}
```

### Desktop (1024px+)

```css
/* Alert espaçado */
.alert {
   padding: 1rem;
   margin-bottom: 1rem;
}

/* Layout horizontal */
.alert {
   display: flex;
   align-items: center;
}
```

---

## ♿ Acessibilidade

### WCAG 2.1 AA Compliance

```html
<!-- Role e aria-live -->
<div role="alert" aria-live="polite" aria-atomic="true">
   <!-- Conteúdo do alerta -->
</div>

<!-- Descrição para screen readers -->
<div class="sr-only">
   Alerta de {{ $type === 'error' ? 'erro' : $type }}: {{ $message }}
</div>

<!-- Botão com label acessível -->
<button
   aria-label="Fechar alerta de {{ $type }}"
   class="focus:ring-2 focus:ring-current"
>
   <i class="bi bi-x-lg" aria-hidden="true"></i>
</button>
```

### Navegação por Teclado

```css
/* Focus visível */
.alert button:focus {
   outline: 2px solid currentColor;
   outline-offset: 2px;
}

/* Ordem lógica */
.alert {
   /* Tab order natural */
   display: flex;
   align-items: flex-start;
}
```

---

## 🔧 Funcionalidades Avançadas

### 1. Alertas com Ações

```blade
<x-alert type="warning" dismissible="false">
    <div class="flex items-center justify-between">
        <div>
            <p class="font-medium">Confirmação necessária</p>
            <p class="text-sm">Esta ação não pode ser desfeita.</p>
        </div>
        <div class="flex space-x-2 ml-4">
            <x-ui.button size="sm" variant="outline-secondary">
                Cancelar
            </x-ui.button>
            <x-ui.button size="sm" variant="danger">
                Confirmar
            </x-ui.button>
        </div>
    </div>
</x-alert>
```

### 2. Alertas de Loading

```blade
<x-alert type="info" :dismissible="false">
    <div class="flex items-center">
        <i class="bi bi-arrow-clockwise animate-spin mr-3"></i>
        <span>Processando...</span>
    </div>
</x-alert>
```

### 3. Alertas com Lista

```blade
<x-alert type="error">
    <div>
        <p class="font-medium mb-2">Ocorreram os seguintes erros:</p>
        <ul class="list-disc list-inside space-y-1">
            <li>Email inválido</li>
            <li>Senha muito curta</li>
            <li>CPF já cadastrado</li>
        </ul>
    </div>
</x-alert>
```

---

## 🛠️ Implementação Técnica

### Controller Integration

```php
// app/Http/Controllers/DashboardController.php
namespace App\Http\Controllers;

use App\Helpers\FlashHelper;

class DashboardController extends Controller
{
    public function store(Request $request)
    {
        try {
            // Lógica de negócio
            $result = $this->service->create($request->all());

            FlashHelper::success('Dados salvos com sucesso!');

            return redirect()->back();
        } catch (\Exception $e) {
            FlashHelper::error('Erro ao salvar dados. Tente novamente.');

            return redirect()->back()->withInput();
        }
    }
}
```

### Middleware para Flash Messages

```php
// app/Http/Middleware/ShareFlashMessages.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ShareFlashMessages
{
    public function handle(Request $request, Closure $next)
    {
        // Compartilhar com JavaScript se necessário
        if ($request->ajax()) {
            $flash = session()->get('flash', []);
            $request->merge(['flash_messages' => $flash]);
        }

        return $next($request);
    }
}
```

---

## 🧪 Testes

### Testes de Componente

```php
// tests/Feature/Components/AlertTest.php
class AlertTest extends TestCase
{
    public function test_alert_component_renders()
    {
        $view = $this->blade('<x-alert type="success" message="Teste" />');

        $view->assertSee('Teste');
        $view->assertSee('bg-green-50');
        $view->assertSee('bi-check-circle-fill');
    }

    public function test_alert_is_dismissible()
    {
        $view = $this->blade('<x-alert type="info" dismissible />');

        $view->assertSee('x-data');
        $view->assertSee('dismiss()');
        $view->assertSee('bi-x-lg');
    }

    public function test_alert_auto_hide()
    {
        $view = $this->blade('<x-alert type="success" :auto-hide="true" :duration="3000" />');

        $view->assertSee('setTimeout');
        $view->assertSee('3000');
    }
}
```

### Testes de Flash Messages

```php
// tests/Feature/FlashMessagesTest.php
class FlashMessagesTest extends TestCase
{
    public function test_flash_success_message()
    {
        $response = $this->get('/test?flash=success');

        $response->assertStatus(200);
        $response->assertSee('Dados salvos com sucesso');
        $response->assertSee('bg-green-50');
    }

    public function test_flash_error_message()
    {
        $response = $this->get('/test?flash=error');

        $response->assertStatus(200);
        $response->assertSee('Erro ao processar');
        $response->assertSee('bg-red-50');
    }

    public function test_validation_errors_display()
    {
        $response = $this->post('/test', ['invalid' => 'data']);

        $response->assertStatus(302);
        $response->assertSessionHasErrors();

        // Verificar redirecionamento com erros
        $response = $this->get('/test');
        $response->assertSee('Por favor, corrija os seguintes erros');
    }
}
```

### Testes de Responsividade

```php
public function test_alerts_are_responsive()
{
    $view = $this->blade('<x-alert type="info" message="Test message" />');

    $view->assertSee('flex items-start');
    $view->assertSee('space-x-3');
    $view->assertSee('min-w-0');
}
```

---

## 📋 Checklist de Implementação

### Para o Sistema de Alertas

-  [ ] Componente Alert criado e funcional
-  [ ] Sistema de Flash Messages implementado
-  [ ] Service Provider configurado
-  [ ] Helper functions criados
-  [ ] Design responsivo implementado
-  [ ] Acessibilidade verificada
-  [ ] Testes automatizados criados
-  [ ] Animações funcionando
-  [ ] Auto-hide configurável
-  [ ] Tipos de alerta variados

### Critérios de Aceitação

-  [ ] Alertas renderizam corretamente
-  [ ] Flash messages aparecem e desaparecem
-  [ ] Validação de formulário mostra erros
-  [ ] Responsividade em mobile/tablet/desktop
-  [ ] Navegação por teclado funcional
-  [ ] Screen readers anunciam mensagens
-  [ ] Performance adequada (animações suaves)
-  [ ] Sem erros JavaScript no console
-  [ ] Testes automatizados passando
-  [ ] Auto-hide funciona corretamente

---

## 🚀 Deploy e Monitoramento

### Configuração de Produção

```php
// .env
FLASH_MESSAGE_DURATION=5000
FLASH_MESSAGE_AUTO_HIDE=true

// Session configuration
SESSION_LIFETIME=120
SESSION_ENCRYPT=true
SESSION_SECURE=true
```

### Monitoramento

```php
// Log de mensagens importantes
Log::channel('alerts')->info('Flash message displayed', [
    'type' => 'success',
    'message' => 'User registered successfully',
    'user_id' => auth()->id(),
    'ip' => request()->ip(),
]);
```

---

## 📚 Referências

-  [Laravel Blade Components](https://laravel.com/docs/blade#components)
-  [Alpine.js Documentation](https://alpinejs.dev/)
-  [ARIA Live Regions](https://developer.mozilla.org/en-US/docs/Web/Accessibility/ARIA/ARIA_Live_Regions)
-  [WCAG Alert Guidelines](https://www.w3.org/WAI/WCAG21/quickref/#error-identification)

---

**Documento criado em:** 2025-09-30
**Versão:** 1.0
**Status:** ✅ Especificações Completas
