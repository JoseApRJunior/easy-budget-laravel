# 🔐 Autenticação e Roles no Blade (Laravel)

No Laravel isso é bem direto, porque o **sistema de autenticação** já expõe helpers e diretivas Blade para você usar tanto a autenticação quanto as roles.

---

## 🔑 Verificar se está autenticado

```blade
@auth
    <p>Bem-vindo, {{ auth()->user()->email }}</p>
@endauth

@guest
    <p>Você não está logado.</p>
@endguest
```

-  `@auth` → só renderiza se o usuário estiver autenticado.
-  `@guest` → só renderiza se **não** estiver autenticado.

---

## 🔎 Acessar dados do usuário

```blade
{{ auth()->user()->name ?? auth()->user()->email }}
{{ auth()->user()->avatar ?? asset('images/default-avatar.png') }}
```

---

## 🛡️ Verificar roles

Como você já implementou roles no seu `User` model (`hasRole`, `hasAnyRole`, `hasRoles`), pode usar direto no Blade:

```blade
@auth
    @if(auth()->user()->hasRole('admin'))
        <a href="/admin">Área administrativa</a>
    @endif

    @if(auth()->user()->hasAnyRole(['editor', 'manager']))
        <p>Você tem permissões especiais.</p>
    @endif
@endauth
```

---

## 🎨 Diretivas customizadas (opcional)

No `AppServiceProvider`:

```php
use Illuminate\Support\Facades\Blade;

public function boot()
{
    Blade::if('role', fn($role) => auth()->check() && auth()->user()->hasRole($role));
    Blade::if('anyrole', fn($roles) => auth()->check() && auth()->user()->hasAnyRole((array) $roles));
}
```

E no Blade:

```blade
@role('admin')
    <a href="/admin">Área administrativa</a>
@endrole

@anyrole(['manager','editor'])
    <p>Você tem permissões especiais.</p>
@endanyrole
```

---

## 🧩 Navbar completa (exemplo)

```blade
<nav class="navbar navbar-expand-lg navbar-light bg-light">
    <div class="container-fluid">
        <a class="navbar-brand" href="{{ url('/') }}">
            EasyBudget
        </a>

        <div class="collapse navbar-collapse">
            <ul class="navbar-nav ms-auto">

                {{-- Visitante --}}
                @guest
                    <li class="nav-item">
                        <a class="btn btn-google btn-primary" href="{{ route('google') }}">
                            <i class="fab fa-google"></i> Entrar com Google
                        </a>
                    </li>
                @endguest

                {{-- Usuário autenticado --}}
                @auth
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="navbarDropdown"
                           role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <img src="{{ auth()->user()->avatar ?? asset('images/default-avatar.png') }}"
                                 alt="Avatar"
                                 class="rounded-circle me-2"
                                 width="32" height="32">
                            {{ auth()->user()->name ?? auth()->user()->email }}
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                            <li><a class="dropdown-item" href="{{ route('profile') }}">Meu Perfil</a></li>

                            @role('admin')
                                <li><a class="dropdown-item" href="{{ route('admin.dashboard') }}">Administração</a></li>
                            @endrole

                            @anyrole(['manager','editor'])
                                <li><a class="dropdown-item" href="{{ route('manager.panel') }}">Painel de Gestão</a></li>
                            @endanyrole

                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form action="{{ route('logout') }}" method="POST">
                                    @csrf
                                    <button class="dropdown-item" type="submit">Sair</button>
                                </form>
                            </li>
                        </ul>
                    </li>
                @endauth

            </ul>
        </div>
    </div>
</nav>
```

---

## 🎨 CSS para botão Google

```css
.btn-google {
   background-color: #db4437;
   color: #fff;
   border: none;
}
.btn-google:hover {
   background-color: #c23321;
   color: #fff;
}
```

---

## ✅ Resumo

-  Use `@auth` / `@guest` para verificar login.
-  Use `auth()->user()` para acessar dados.
-  Use `hasRole`, `hasAnyRole`, `hasRoles` para controle de conteúdo.
-  Crie diretivas Blade (`@role`, `@anyrole`) para deixar o código mais limpo.
-  Estruture menus (navbar/sidebar) com base em autenticação e roles.

---

👉 Esse Markdown já está pronto para ser usado na sua **documentação interna** ou colado direto no README/wiki do projeto.

Quer que eu monte também a versão **sidebar (menu lateral)** em Markdown, seguindo a mesma lógica?
