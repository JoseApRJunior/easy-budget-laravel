# 📋 Especificações Técnicas - Páginas de Autenticação (Fase 1)

## 🎯 Visão Geral

Especificação completa para implementação das páginas de autenticação do sistema Easy Budget Laravel, incluindo login, recuperação de senha e reset de senha.

---

## 📊 Páginas de Autenticação Especificadas

### 1. Página de Login

#### 🎨 Design e Layout

**Layout:** `layouts.guest`
**Template:** `auth/login.blade.php`
**Rota:** `GET /login`

```blade
@extends('layouts.guest')

@section('title', 'Login - Easy Budget')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4">
    <div class="max-w-md w-full">
        <!-- Card Principal -->
        <div class="bg-white rounded-lg shadow-lg p-8">
            <!-- Header -->
            <div class="text-center mb-8">
                <img src="{{ asset('img/logo.png') }}" alt="Easy Budget" class="h-12 mx-auto mb-4">
                <h1 class="text-2xl font-bold text-gray-900">Seja bem-vindo</h1>
                <p class="text-gray-600">Faça login para continuar</p>
            </div>

            <!-- Flash Messages -->
            <x-flash-messages />

            <!-- Formulário -->
            <form method="POST" action="{{ route('login') }}" class="space-y-6">
                @csrf

                <!-- Email -->
                <div>
                    <x-form.input
                        label="Email"
                        name="email"
                        type="email"
                        value="{{ old('email') }}"
                        placeholder="seu@email.com"
                        required
                        autofocus
                        :error="$errors->first('email')"
                    />
                </div>

                <!-- Senha -->
                <div x-data="{ showPassword: false }">
                    <x-form.input
                        label="Senha"
                        name="password"
                        :type="showPassword ? 'text' : 'password'"
                        placeholder="Sua senha"
                        required
                        :error="$errors->first('password')"
                        container-class="relative"
                    >
                        <x-slot:hint>
                            <button
                                type="button"
                                @click="showPassword = !showPassword"
                                class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600"
                                tabindex="-1"
                            >
                                <i :class="showPassword ? 'bi-eye-slash' : 'bi-eye'"></i>
                            </button>
                        </x-slot>
                    </x-form.input>
                </div>

                <!-- Lembrar-me -->
                <div class="flex items-center justify-between">
                    <x-form.checkbox
                        name="remember"
                        :checked="old('remember')"
                    >
                        Lembrar-me
                    </x-form.checkbox>

                    <a href="{{ route('password.request') }}" class="text-sm text-blue-600 hover:text-blue-700">
                        Esqueceu a senha?
                    </a>
                </div>

                <!-- Botão Submit -->
                <x-ui.button
                    type="submit"
                    variant="primary"
                    size="lg"
                    class="w-full"
                >
                    <i class="bi bi-box-arrow-in-right mr-2"></i>
                    Entrar
                </x-ui.button>
            </form>

            <!-- Links Adicionais -->
            <div class="mt-6 text-center">
                <span class="text-gray-600">Não tem uma conta?</span>
                <a href="{{ route('register') }}" class="text-blue-600 hover:text-blue-700 ml-1">
                    Registre-se
                </a>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center mt-8">
            <div class="flex items-center justify-center gap-2 text-sm text-gray-500">
                <i class="bi bi-shield-lock"></i>
                <span>Login seguro via SSL</span>
            </div>
        </div>
    </div>
</div>
@endsection
```

#### 🔧 Funcionalidades

-  **Toggle de senha**: Alpine.js para mostrar/ocultar senha
-  **Validação em tempo real**: HTML5 validation + Laravel validation
-  **Auto-focus**: Campo email recebe foco automaticamente
-  **Lembrete de senha**: Checkbox para sessão persistente
-  **Links contextuais**: Registro e recuperação de senha

#### ♿ Acessibilidade

-  **Labels associadas**: Todos os campos têm labels
-  **Tab order**: Navegação lógica por teclado
-  **Screen reader**: Anúncios adequados
-  **Contraste**: Cores com contraste WCAG AA

---

### 2. Página de Recuperação de Senha

#### 🎨 Design e Layout

**Layout:** `layouts.guest`
**Template:** `auth/forgot-password.blade.php`
**Rota:** `GET /forgot-password`

```blade
@extends('layouts.guest')

@section('title', 'Recuperar senha - Easy Budget')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4">
    <div class="max-w-md w-full">
        <!-- Card Principal -->
        <div class="bg-white rounded-lg shadow-lg p-8">
            <!-- Header -->
            <div class="text-center mb-8">
                <img src="{{ asset('img/logo.png') }}" alt="Easy Budget" class="h-12 mx-auto mb-4">
                <h1 class="text-2xl font-bold text-gray-900">Recuperar senha</h1>
                <p class="text-gray-600">Digite seu email para receber instruções</p>
            </div>

            <!-- Flash Messages -->
            <x-flash-messages />

            <!-- Formulário -->
            <form method="POST" action="{{ route('password.email') }}" class="space-y-6">
                @csrf

                <!-- Email -->
                <div>
                    <x-form.input
                        label="Email"
                        name="email"
                        type="email"
                        value="{{ old('email') }}"
                        placeholder="seu@email.com"
                        required
                        autofocus
                        :error="$errors->first('email')"
                    />
                </div>

                <!-- Botão Submit -->
                <x-ui.button
                    type="submit"
                    variant="primary"
                    size="lg"
                    class="w-full"
                >
                    <i class="bi bi-envelope-arrow-up mr-2"></i>
                    Enviar instruções
                </x-ui.button>
            </form>

            <!-- Links Adicionais -->
            <div class="mt-6 text-center">
                <a href="{{ route('login') }}" class="text-blue-600 hover:text-blue-700">
                    <i class="bi bi-arrow-left mr-1"></i>
                    Voltar ao login
                </a>
            </div>
        </div>

        <!-- Ajuda -->
        <div class="text-center mt-8">
            <p class="text-sm text-gray-500">
                Não consegue acessar?
                <a href="mailto:suporte@easybudget.com" class="text-blue-600 hover:text-blue-700">
                    Entre em contato conosco
                </a>
            </p>
        </div>
    </div>
</div>
@endsection
```

#### 🔧 Funcionalidades

-  **Envio de email**: Integração com sistema de email
-  **Rate limiting**: Proteção contra spam
-  **Validação**: Verificação de email existente
-  **Feedback visual**: Status do envio

---

### 3. Página de Reset de Senha

#### 🎨 Design e Layout

**Layout:** `layouts.guest`
**Template:** `auth/reset-password.blade.php`
**Rota:** `GET /reset-password/{token}`

```blade
@extends('layouts.guest')

@section('title', 'Nova senha - Easy Budget')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4">
    <div class="max-w-md w-full">
        <!-- Card Principal -->
        <div class="bg-white rounded-lg shadow-lg p-8">
            <!-- Header -->
            <div class="text-center mb-8">
                <img src="{{ asset('img/logo.png') }}" alt="Easy Budget" class="h-12 mx-auto mb-4">
                <h1 class="text-2xl font-bold text-gray-900">Nova senha</h1>
                <p class="text-gray-600">Digite sua nova senha</p>
            </div>

            <!-- Flash Messages -->
            <x-flash-messages />

            <!-- Formulário -->
            <form method="POST" action="{{ route('password.store') }}" class="space-y-6">
                @csrf

                <!-- Token (hidden) -->
                <input type="hidden" name="token" value="{{ $request->route('token') }}">

                <!-- Email -->
                <div>
                    <x-form.input
                        label="Email"
                        name="email"
                        type="email"
                        value="{{ old('email', $request->email) }}"
                        placeholder="seu@email.com"
                        required
                        autofocus
                        :error="$errors->first('email')"
                    />
                </div>

                <!-- Nova Senha -->
                <div x-data="{ showPassword: false }">
                    <x-form.input
                        label="Nova senha"
                        name="password"
                        :type="showPassword ? 'text' : 'password'"
                        placeholder="Sua nova senha"
                        required
                        :error="$errors->first('password')"
                        container-class="relative"
                    >
                        <x-slot:hint>
                            <button
                                type="button"
                                @click="showPassword = !showPassword"
                                class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600"
                                tabindex="-1"
                            >
                                <i :class="showPassword ? 'bi-eye-slash' : 'bi-eye'"></i>
                            </button>
                        </x-slot>
                    </x-form.input>
                </div>

                <!-- Confirmar Senha -->
                <div x-data="{ showPasswordConfirm: false }">
                    <x-form.input
                        label="Confirmar nova senha"
                        name="password_confirmation"
                        :type="showPasswordConfirm ? 'text' : 'password'"
                        placeholder="Confirme sua nova senha"
                        required
                        :error="$errors->first('password_confirmation')"
                        container-class="relative"
                    >
                        <x-slot:hint>
                            <button
                                type="button"
                                @click="showPasswordConfirm = !showPasswordConfirm"
                                class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600"
                                tabindex="-1"
                            >
                                <i :class="showPasswordConfirm ? 'bi-eye-slash' : 'bi-eye'"></i>
                            </button>
                        </x-slot>
                    </x-form.input>
                </div>

                <!-- Requisitos de Senha -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <h4 class="text-sm font-medium text-blue-800 mb-2">Requisitos da senha:</h4>
                    <ul class="text-sm text-blue-700 space-y-1">
                        <li class="flex items-center">
                            <i class="bi bi-check-circle-fill text-green-600 mr-2"></i>
                            Pelo menos 8 caracteres
                        </li>
                        <li class="flex items-center">
                            <i class="bi bi-check-circle-fill text-green-600 mr-2"></i>
                            Uma letra maiúscula e uma minúscula
                        </li>
                        <li class="flex items-center">
                            <i class="bi bi-check-circle-fill text-green-600 mr-2"></i>
                            Pelo menos um número
                        </li>
                    </ul>
                </div>

                <!-- Botão Submit -->
                <x-ui.button
                    type="submit"
                    variant="primary"
                    size="lg"
                    class="w-full"
                >
                    <i class="bi bi-key mr-2"></i>
                    Redefinir senha
                </x-ui.button>
            </form>

            <!-- Links Adicionais -->
            <div class="mt-6 text-center">
                <a href="{{ route('login') }}" class="text-blue-600 hover:text-blue-700">
                    <i class="bi bi-arrow-left mr-1"></i>
                    Voltar ao login
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
```

#### 🔧 Funcionalidades

-  **Validação de senha**: Requisitos de segurança
-  **Confirmação visual**: Indicadores de requisitos atendidos
-  **Token seguro**: Validação automática do token
-  **UX aprimorada**: Toggle para ambas as senhas

---

## 🛠️ Implementação Técnica

### Rotas de Autenticação

```php
// routes/auth.php
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('login', [App\Http\Controllers\Auth\LoginController::class, 'showLoginForm'])->name('login');
    Route::post('login', [App\Http\Controllers\Auth\LoginController::class, 'login']);

    Route::get('forgot-password', [App\Http\Controllers\Auth\ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('forgot-password', [App\Http\Controllers\Auth\ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');

    Route::get('reset-password/{token}', [App\Http\Controllers\Auth\ResetPasswordController::class, 'showResetForm'])->name('password.reset');
    Route::post('reset-password', [App\Http\Controllers\Auth\ResetPasswordController::class, 'reset'])->name('password.store');
});
```

### Controladores de Autenticação

```php
// app/Http/Controllers/Auth/LoginController.php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (auth()->attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            return redirect()->intended('/dashboard');
        }

        return back()->withErrors([
            'email' => 'As credenciais não correspondem.',
        ])->onlyInput('email');
    }
}
```

### Middleware de Autenticação

```php
// app/Http/Middleware/Authenticate.php
public function redirectTo(Request $request): ?string
{
    return $request->expectsJson() ? null : route('login');
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

/* Card responsivo */
.max-w-md {
   margin: 0 auto;
}

/* Formulário stacked */
.space-y-6 {
   gap: 1.5rem;
}
```

### Tablet (768px+)

```css
/* Padding ajustado */
.py-12 {
   padding-top: 3rem;
   padding-bottom: 3rem;
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
<!-- Labels semânticas -->
<label for="email" class="block text-sm font-medium text-gray-700">
   Email <span class="text-red-500">*</span>
</label>

<!-- Descrições -->
<p id="password-help" class="mt-1 text-sm text-gray-500">
   Sua senha deve ter pelo menos 8 caracteres
</p>

<!-- Anúncios de erro -->
<div role="alert" aria-live="polite" class="mt-1 text-sm text-red-600">
   {{ $errors->first('email') }}
</div>
```

### Navegação por Teclado

```css
/* Focus visível */
.form-input:focus {
   outline: 2px solid #3b82f6;
   outline-offset: 2px;
}

/* Ordem lógica */
input[name="email"] {
   tab-index: 1;
}
input[name="password"] {
   tab-index: 2;
}
button[type="submit"] {
   tab-index: 3;
}
```

---

## 🔒 Segurança

### Proteções Implementadas

```php
// Rate Limiting
RateLimiter::for('login', function (Request $request) {
    return Limit::perMinute(5)->by($request->email.$request->ip());
});

// Password Hashing
$hashed = Hash::make($request->password);

// CSRF Protection
@csrf

// Session Security
$request->session()->regenerate();

// Secure Headers
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
```

### Validações de Segurança

```php
// Regras de validação
$request->validate([
    'email' => 'required|email:rfc,dns|exists:users,email',
    'password' => 'required|string|min:8',
]);

// Password strength
'password' => [
    'required',
    'string',
    'min:8',
    'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/',
],
```

---

## 🧪 Testes

### Testes Automatizados

```php
// tests/Feature/Auth/LoginTest.php
class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_with_valid_credentials()
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_user_cannot_login_with_invalid_credentials()
    {
        $response = $this->post('/login', [
            'email' => 'invalid@email.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_login_page_renders_correctly()
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertViewIs('auth.login');
        $response->assertSee('Seja bem-vindo');
    }
}
```

### Testes de Responsividade

```php
public function test_auth_pages_are_responsive()
{
    $pages = [
        'login' => 'auth.login',
        'forgot-password' => 'auth.forgot-password',
        'reset-password' => 'auth.reset-password',
    ];

    foreach ($pages as $route => $view) {
        $response = $this->get("/{$route}");

        $response->assertStatus(200);
        $response->assertSee('min-h-screen');
        $response->assertSee('max-w-md');
    }
}
```

---

## 📋 Checklist de Implementação

### Para Cada Página de Autenticação

-  [ ] Template criado com layout correto
-  [ ] Formulários funcionais implementados
-  [ ] Validações client e server-side
-  [ ] Design responsivo implementado
-  [ ] Acessibilidade verificada
-  [ ] Testes automatizados criados
-  [ ] Segurança implementada
-  [ ] Emails transacionais configurados
-  [ ] Rate limiting configurado
-  [ ] Tratamento de erro adequado

### Critérios de Aceitação

-  [ ] Login funciona com credenciais válidas
-  [ ] Login falha com credenciais inválidas
-  [ ] Recuperação de senha envia email
-  [ ] Reset de senha funciona com token válido
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
APP_URL=https://easybudget.com
MAIL_MAILER=smtp
MAIL_HOST=mail.easybudget.com
MAIL_ENCRYPTION=tls

// Session security
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=strict

// Rate limiting
LOGIN_ATTEMPTS=5
LOGIN_LOCKOUT_MINUTES=15
```

### Monitoramento

```php
// Log de tentativas de login
Log::channel('security')->info('Login attempt', [
    'email' => $request->email,
    'ip' => $request->ip(),
    'user_agent' => $request->userAgent(),
    'success' => auth()->check(),
]);
```

---

## 📚 Referências

-  [Laravel Authentication](https://laravel.com/docs/authentication)
-  [Password Reset](https://laravel.com/docs/passwords)
-  [Web Security](https://web.dev/security/)
-  [OWASP Authentication](https://owasp.org/www-project-top-ten/)

---

**Documento criado em:** 2025-09-30
**Versão:** 1.0
**Status:** ✅ Especificações Completas
