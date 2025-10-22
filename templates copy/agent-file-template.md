# Easy Budget Laravel – Development Guidelines

Auto-generated from all feature plans. Last updated: 2025-10-21

---

## Active Technologies

-  **PHP 8.2**
-  **Laravel 10**
-  **MySQL/MariaDB**
-  **Laravel Socialite (^5.10)** – integração com Google OAuth 2.0
-  **PHPUnit** – testes unitários
-  **Laravel Dusk** – testes de integração/end-to-end

---

## Project Structure

```bash
backend/
├── app/
│   ├── models/
│   │   └── User.php
│   ├── services/
│   │   └── SocialAuthenticationService.php
│   └── api/
│       └── Auth/
│           └── GoogleController.php
└── tests/
    ├── contract/
    ├── integration/
    │   └── GoogleLoginFlowTest.php
    └── unit/
docs/
└── google-login.md
```

---

## Commands

-  **Laravel Artisan** (`php artisan serve`, `php artisan migrate`, `php artisan test`)
-  **Composer** (`composer install`, `composer require laravel/socialite`)
-  **PHPUnit** (`vendor/bin/phpunit`)
-  **Laravel Dusk** (`php artisan dusk`)

---

## Code Style

-  **PSR-12** como padrão de estilo PHP.
-  **Nomes de classes** em PascalCase (ex.: `GoogleController`).
-  **Nomes de métodos** em camelCase (ex.: `redirect()`, `callback()`).
-  **Models** devem usar singular (ex.: `User.php`).
-  **Services** devem encapsular lógica de negócio, mantendo controllers enxutos.
-  **Testes** devem seguir convenção `FeatureTest` ou `UnitTest` no nome da classe.

---

## Recent Changes

1. **Login com Google (OAuth 2.0)**

   -  Adicionado `GoogleController` e `SocialAuthenticationService`.
   -  Configuração de rotas `/auth/google` e `/auth/google/callback`.
   -  Atualização do model `User` com campos `google_id` e `avatar`.

2. **Autenticação de Dois Fatores (2FA)** _(planejado)_

   -  Integração com Google Authenticator.
   -  Suporte a backup codes e dispositivos confiáveis.

3. **Sistema de Notificações Avançado** _(planejado)_
   -  Templates HTML para e-mail.
   -  Integração com SMS (Twilio) e notificações in-app.

---

<!-- MANUAL ADDITIONS START -->

-  Todos os commits devem seguir **Conventional Commits** (`feat:`, `fix:`, `chore:`).
-  Revisões de código são obrigatórias antes de merge em `main`.
-  Deploys devem passar por ambiente **DEV** (`https://dev.easybudget.net.br`) antes de staging/produção.
<!-- MANUAL ADDITIONS END -->

---

👉 Esse documento já consolida **tecnologias, estrutura, comandos, estilo de código e histórico de features**.

Quer que eu prepare também uma versão **“Developer Onboarding Guide”** (um passo a passo para novos devs configurarem o ambiente e rodarem o projeto do zero)?
