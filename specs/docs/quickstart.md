# Quickstart: Login com Google (OAuth 2.0)

## 🎯 Objetivo

Permitir que usuários façam login/cadastro no Easy Budget Laravel usando **Google OAuth 2.0** via Laravel Socialite.

---

## ⚙️ Pré-requisitos

-  PHP 8.2+
-  Laravel 10+
-  Composer
-  Conta no [Google Cloud Console](https://console.cloud.google.com/)

---

## 🔑 Configuração do Google

1. Crie um projeto no Google Cloud Console.
2. Ative **OAuth 2.0 Client ID**.
3. Configure o **Authorized redirect URI**:
   ```
   https://dev.easybudget.net.br/auth/google/callback
   ```
4. Copie o **Client ID** e o **Client Secret**.

---

## 📂 Configuração no Laravel

No arquivo `.env`:

```env
GOOGLE_CLIENT_ID=seu_client_id
GOOGLE_CLIENT_SECRET=seu_client_secret
GOOGLE_REDIRECT_URI=https://dev.easybudget.net.br/auth/google/callback
```

No arquivo `config/services.php`:

```php
'google' => [
    'client_id' => env('GOOGLE_CLIENT_ID'),
    'client_secret' => env('GOOGLE_CLIENT_SECRET'),
    'redirect' => env('GOOGLE_REDIRECT_URI'),
],
```

---

## 🛠️ Rotas

Em `routes/web.php`:

```php
use App\Http\Controllers\Auth\GoogleController;

Route::get('auth/google', [GoogleController::class, 'redirect'])->name('google.login');
Route::get('auth/google/callback', [GoogleController::class, 'callback']);
```

---

## 👨‍💻 Controller

Em `app/Http/Controllers/Auth/GoogleController.php`:

```php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class GoogleController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback()
    {
        $googleUser = Socialite::driver('google')->stateless()->user();

        $user = User::updateOrCreate(
            ['email' => $googleUser->getEmail()],
            [
                'name' => $googleUser->getName(),
                'google_id' => $googleUser->getId(),
                'avatar' => $googleUser->getAvatar(),
            ]
        );

        Auth::login($user);

        return redirect('/dashboard');
    }
}
```

---

## ✅ Testando

1. Rode o servidor:
   ```bash
   php artisan serve
   ```
2. Acesse:
   ```
   https://dev.easybudget.net.br/auth/google
   ```
3. Faça login com sua conta Google.
4. Você deve ser redirecionado para `/dashboard` já autenticado.

---

## 🧪 Validação Rápida

-  [ ] Login concluído em até 3 cliques.
-  [ ] Conta criada ou vinculada corretamente.
-  [ ] Nome, e-mail e avatar sincronizados.
-  [ ] Mensagem clara em caso de erro/cancelamento.

---

**Pronto!** Esse quickstart garante que qualquer dev consiga configurar e validar o **Login com Google** em minutos 🚀

```

```
