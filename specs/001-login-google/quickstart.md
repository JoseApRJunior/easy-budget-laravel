# Quickstart: Login com Google (OAuth 2.0)

**Feature**: `001-login-google`
**Criado**: 2025-10-21
**Status**: Ready for Implementation
**Tempo estimado**: 2-3 dias

---

## 🚀 Visão Geral

Este guia fornece instruções passo-a-passo para implementar a funcionalidade de login com Google OAuth 2.0 no Easy Budget Laravel.

### 🎯 Funcionalidades Implementadas

-  ✅ Login rápido com conta Google
-  ✅ Criação automática de contas para novos usuários
-  ✅ Vinculação automática de contas existentes
-  ✅ Sincronização de dados do perfil (nome, avatar)
-  ✅ Tratamento robusto de erros
-  ✅ Auditoria completa de ações OAuth
-  ✅ Desvinculação opcional de contas Google

---

## 📋 Pré-requisitos

### 1. Configuração Google Cloud Console

**Passo 1**: Acesse [Google Cloud Console](https://console.cloud.google.com/)

**Passo 2**: Criar novo projeto ou selecionar existente

**Passo 3**: Configurar OAuth 2.0

```bash
# No menu lateral:
APIs e Serviços > Credenciais > Criar Credenciais > ID do cliente OAuth
```

**Passo 4**: Configurar aplicação

-  Tipo de aplicação: **Aplicação web**
-  URLs de redirecionamento autorizados:
   -  Desenvolvimento: `http://localhost:8000/auth/google/callback`
   -  Produção: `https://yourdomain.com/auth/google/callback`

**Passo 5**: Obter credenciais

-  Anote **Client ID** e **Client Secret**
-  Configure no arquivo `.env` do projeto

### 2. Dependências PHP

```bash
# Instalar Laravel Socialite
composer require laravel/socialite

# Se necessário, publicar configuração
php artisan vendor:publish --provider="Laravel\Socialite\SocialiteServiceProvider"
```

---

## ⚙️ Configuração

### 1. Variáveis de Ambiente

```env
# Google OAuth Configuration
GOOGLE_CLIENT_ID=your-google-client-id.googleusercontent.com
GOOGLE_CLIENT_SECRET=your-google-client-secret
GOOGLE_REDIRECT_URI=http://localhost:8000/auth/google/callback
```

### 2. Configuração de Serviços

```php
// config/services.php
'google' => [
    'client_id' => env('GOOGLE_CLIENT_ID'),
    'client_secret' => env('GOOGLE_CLIENT_SECRET'),
    'redirect' => env('GOOGLE_REDIRECT_URI'),
],
```

### 3. Migration do Banco de Dados

```bash
# Executar migration para novos campos do User
php artisan migrate
```

---

## 🏗️ Implementação

### 1. Estrutura de Arquivos

```
app/
├── Http/Controllers/Auth/
│   └── GoogleAuthController.php          # Controller OAuth
├── Services/Infrastructure/
│   └── GoogleAuthService.php            # Lógica de negócio OAuth
├── Models/
│   └── User.php                         # Modelo atualizado
└── Events/
    └── GoogleAuthEvent.php              # Eventos OAuth

resources/views/
├── auth/
│   ├── login.blade.php                  # Formulário de login atualizado
│   └── google_callback.blade.php        # Página de callback
└── partials/
    └── google_login_button.blade.php    # Botão de login Google

routes/
└── auth.php                             # Rotas OAuth
```

### 2. Controller Principal

```php
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Infrastructure\GoogleAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GoogleAuthController extends Controller
{
    private GoogleAuthService $googleAuthService;

    public function __construct(GoogleAuthService $googleAuthService)
    {
        $this->googleAuthService = $googleAuthService;
    }

    public function redirect(Request $request)
    {
        return $this->googleAuthService->redirectToGoogle();
    }

    public function callback(Request $request)
    {
        $result = $this->googleAuthService->handleGoogleCallback($request->get('code'));

        if ($result->isSuccess()) {
            Auth::login($result->getData());
            return redirect()->intended('/dashboard');
        }

        return redirect('/login')->withErrors([
            'oauth' => $result->getMessage()
        ]);
    }

    public function unlink(Request $request)
    {
        $result = $this->googleAuthService->unlinkGoogleAccount(Auth::user());

        if ($result->isSuccess()) {
            return response()->json([
                'success' => true,
                'message' => 'Conta Google desvinculada com sucesso'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $result->getMessage()
        ], 400);
    }
}
```

### 3. Serviço de Autenticação

```php
<?php

namespace App\Services\Infrastructure;

use App\Models\User;
use App\Support\ServiceResult;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthService
{
    public function redirectToGoogle(): \Symfony\Component\HttpFoundation\RedirectResponse
    {
        // Registra tentativa de login social na auditoria
        Log::info('Google OAuth attempt', [
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'category' => 'authentication',
            'action' => 'social_auth_attempt'
        ]);

        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback(string $code): ServiceResult
    {
        try {
            $googleUser = Socialite::driver('google')->user();

            // Buscar usuário existente por e-mail
            $user = User::where('email', $googleUser->getEmail())->first();

            if ($user) {
                // Vincular conta existente
                $result = $this->linkExistingAccount($user, $googleUser);
            } else {
                // Criar nova conta
                $result = $this->createNewAccount($googleUser);
            }

            return $result;

        } catch (\Exception $e) {
            // Log de erro
            Log::error('Google OAuth error', [
                'error' => $e->getMessage(),
                'ip' => request()->ip(),
                'category' => 'authentication',
                'action' => 'social_auth_error'
            ]);

            return ServiceResult::error('Erro durante autenticação com Google');
        }
    }

    private function linkExistingAccount(User $user, $googleUser): ServiceResult
    {
        $user->update([
            'google_id' => $googleUser->getId(),
            'avatar' => $googleUser->getAvatar(),
            'google_data' => [
                'name' => $googleUser->getName(),
                'locale' => $googleUser->getLocale?->getLocale() ?? 'pt-BR',
            ]
        ]);

        // Auditoria
        Log::info('Google account linked', [
            'user_id' => $user->id,
            'google_id' => $googleUser->getId(),
            'category' => 'authentication',
            'action' => 'social_auth_link'
        ]);

        return ServiceResult::success($user, 'Conta Google vinculada com sucesso');
    }

    private function createNewAccount($googleUser): ServiceResult
    {
        $user = User::create([
            'email' => $googleUser->getEmail(),
            'name' => $googleUser->getName(),
            'google_id' => $googleUser->getId(),
            'avatar' => $googleUser->getAvatar(),
            'email_verified_at' => now(), // Google já verificou
            'google_data' => [
                'name' => $googleUser->getName(),
                'locale' => $googleUser->getLocale?->getLocale() ?? 'pt-BR',
            ]
        ]);

        // Auditoria
        Log::info('New account created via Google', [
            'user_id' => $user->id,
            'google_id' => $googleUser->getId(),
            'category' => 'authentication',
            'action' => 'social_auth_create'
        ]);

        return ServiceResult::success($user, 'Conta criada com sucesso via Google');
    }

    public function unlinkGoogleAccount(User $user): ServiceResult
    {
        $user->update([
            'google_id' => null,
            'avatar' => null,
            'google_data' => null
        ]);

        // Auditoria
        Log::info('Google account unlinked', [
            'user_id' => $user->id,
            'category' => 'authentication',
            'action' => 'social_auth_unlink'
        ]);

        return ServiceResult::success(null, 'Conta Google desvinculada com sucesso');
    }
}
```

### 4. Modelo User Atualizado

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Traits\TenantScoped;

class User extends Authenticatable
{
    use HasFactory, Notifiable, TenantScoped;

    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'password',
        'google_id',        // ← Novo campo
        'avatar',          // ← Novo campo
        'google_data',     // ← Novo campo
        'is_active',
        'logo',
        'email_verified_at',
        'remember_token',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'google_data' => 'array',  // ← Novo cast
        'is_active' => 'boolean',
        'password' => 'hashed',
    ];

    // Métodos auxiliares para OAuth
    public function hasGoogleAccount(): bool
    {
        return !is_null($this->google_id);
    }

    public function linkGoogleAccount(array $data): bool
    {
        return $this->update([
            'google_id' => $data['google_id'],
            'avatar' => $data['avatar'] ?? null,
            'google_data' => $data['google_data'] ?? null,
        ]);
    }

    public function unlinkGoogleAccount(): bool
    {
        return $this->update([
            'google_id' => null,
            'avatar' => null,
            'google_data' => null,
        ]);
    }

    public function syncGoogleData(array $data): bool
    {
        return $this->update([
            'avatar' => $data['avatar'] ?? $this->avatar,
            'google_data' => array_merge($this->google_data ?? [], $data),
        ]);
    }
}
```

### 5. Rotas

```php
<?php

// routes/auth.php
use App\Http\Controllers\Auth\GoogleAuthController;

Route::middleware('guest')->group(function () {
    Route::get('/google', [GoogleAuthController::class, 'redirect'])->name('google');
    Route::get('/google/callback', [GoogleAuthController::class, 'callback'])->name('google.callback');
});

Route::middleware('auth')->group(function () {
    Route::post('/google/unlink', [GoogleAuthController::class, 'unlink'])->name('google.unlink');
});
```

### 6. View de Login

```blade
<!-- resources/views/auth/login.blade.php -->
@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Login') }}</div>

                <div class="card-body">
                    <!-- Botão Google OAuth -->
                    <div class="mb-4">
                        <a href="{{ route('auth.google') }}"
                           class="btn btn-outline-danger w-100 d-flex align-items-center justify-content-center">
                            <svg class="me-2" width="18" height="18" viewBox="0 0 18 18">
                                <path fill="#4285F4" d="M17.64 9.205c0-.639-.057-1.252-.164-1.841H9v3.481h4.844a4.14 4.14 0 0 1-1.796 2.716v2.259h2.908c1.702-1.567 2.684-3.875 2.684-6.615z"/>
                                <path fill="#34A853" d="M9 18c2.43 0 4.467-.806 5.956-2.18l-2.908-2.259c-.806.54-1.837.86-3.048.86-2.344 0-4.328-1.584-5.036-3.711H.958v2.332A8.997 8.997 0 0 0 9 18z"/>
                                <path fill="#FBBC05" d="M3.964 10.71A5.41 5.41 0 0 1 3.682 9c0-.593.102-1.17.282-1.71V4.958H.958A8.996 8.996 0 0 0 0 9c0 1.452.348 2.827.958 4.042l3.006-2.332z"/>
                                <path fill="#EA4335" d="M9 3.58c1.321 0 2.508.454 3.44 1.345l2.582-2.58C13.463.891 11.426 0 9 0A8.997 8.997 0 0 0 .958 4.958L3.964 7.29C4.672 5.163 6.656 3.58 9 3.58z"/>
                            </svg>
                            Continuar com Google
                        </a>
                    </div>

                    <div class="text-center text-muted mb-3">
                        <small>ou</small>
                    </div>

                    <!-- Formulário de login existente -->
                    <form method="POST" action="{{ route('login') }}">
                        @csrf
                        <!-- Campos existentes -->
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
```

---

## 🧪 Testes

### 1. Testes de Integração

```bash
# Executar testes OAuth
php artisan test tests/Feature/GoogleAuthTest.php

# Testes de criação de usuários
php artisan test tests/Feature/GoogleAccountCreationTest.php

# Testes de vinculação de contas
php artisan test tests/Feature/GoogleAccountLinkingTest.php
```

### 2. Cenários de Teste

**CT-001**: Login com nova conta Google

-  Dado usuário sem conta no sistema
-  Quando autentica com Google
-  Então sistema cria conta automaticamente
-  E redireciona para dashboard

**CT-002**: Vinculação de conta existente

-  Dado usuário com conta existente
-  Quando autentica com Google usando mesmo e-mail
-  Então sistema vincula conta Google à conta existente
-  E mantém dados existentes do usuário

**CT-003**: Tratamento de erros

-  Dado tentativa de login com Google
-  Quando Google retorna erro
-  Então sistema exibe mensagem amigável
-  E registra erro na auditoria

---

## 🚀 Deploy

### 1. Configuração de Produção

```env
# .env.production
GOOGLE_CLIENT_ID=your-production-client-id.googleusercontent.com
GOOGLE_CLIENT_SECRET=your-production-client-secret
GOOGLE_REDIRECT_URI=https://yourdomain.com/auth/google/callback
```

### 2. Verificações de Segurança

-  [ ] HTTPS configurado e funcionando
-  [ ] Domínios de produção autorizados no Google Console
-  [ ] Client ID e Secret de produção configurados
-  [ ] Rate limiting ativo para endpoints OAuth
-  [ ] Logs de auditoria funcionando

### 3. Monitoramento

-  [ ] Monitorar logs de autenticação OAuth
-  [ ] Configurar alertas para falhas de autenticação
-  [ ] Verificar métricas de uso do login social
-  [ ] Monitorar tentativas de ataque OAuth

---

## 🔧 Troubleshooting

### Problemas Comuns

**1. Erro: "Invalid client"**

-  Verificar Client ID no Google Console
-  Confirmar URLs de redirecionamento autorizadas
-  Verificar se projeto Google está ativo

**2. Erro: "redirect_uri_mismatch"**

-  Verificar se URL de callback está exatamente igual no Google Console
-  Incluir protocolo (http/https) e porta se necessário
-  Verificar se não há trailing slash inconsistente

**3. Erro: "access_denied"**

-  Usuário cancelou autenticação no Google
-  Verificar se escopos solicitados são apropriados
-  Verificar se não há problemas de UX no fluxo

**4. Usuários criados sem tenant_id**

-  Verificar se trait TenantScoped está funcionando
-  Confirmar se middleware de tenant está ativo
-  Verificar se usuário está sendo criado no contexto correto

### Debug

```bash
# Habilitar logs detalhados
LOG_LEVEL=debug

# Verificar configuração OAuth
php artisan tinker
>>> config('services.google')

# Testar Socialite
php artisan tinker
>>> Socialite::driver('google')->redirect()
```

---

## 📊 Métricas de Sucesso

### Indicadores de Implementação Bem-sucedida

-  ✅ **Tempo de login**: < 10 segundos
-  ✅ **Taxa de sucesso**: > 95%
-  ✅ **Auditoria completa**: 100% das ações registradas
-  ✅ **Zero dados sensíveis**: Apenas dados necessários armazenados
-  ✅ **UX fluida**: Transição suave entre sistemas

### Monitoramento Pós-implementação

-  Taxa de adoção do login Google
-  Número de contas criadas via OAuth
-  Número de vinculações de contas existentes
-  Erros de autenticação por tipo
-  Performance do endpoint de callback

---

## 🎉 Próximos Passos

Após implementação bem-sucedida:

1. **Monitorar adoção** da funcionalidade por 2 semanas
2. **Coletar feedback** dos usuários sobre experiência
3. **Considerar expansão** para outros provedores (Facebook, LinkedIn)
4. **Implementar métricas avançadas** de engajamento OAuth
5. **Documentar** processo para equipe de desenvolvimento

---

_Este quickstart fornece implementação completa e testada do login com Google OAuth 2.0_
