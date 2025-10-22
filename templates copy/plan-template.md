# Implementation Plan: Login com Google (OAuth 2.0)

**Branch**: `001-login-google` | **Date**: 2025-10-21 | **Spec**: `/specs/001-login-google/spec.md`
**Input**: Feature specification from `/specs/001-login-google/spec.md`

---

## Summary

Implementar autenticação via **Google OAuth 2.0** no sistema Easy Budget Laravel.
O fluxo permitirá cadastro rápido, vinculação de contas existentes e sincronização de dados básicos (nome, e-mail, avatar).
A abordagem técnica será baseada no **Laravel Socialite**, com rotas dedicadas (`/auth/google`, `/auth/google/callback`), controller específico e ajustes no model `User`.

---

## Technical Context

**Language/Version**: PHP 8.2 + Laravel 10
**Primary Dependencies**: `laravel/socialite:^5.10`
**Storage**: MySQL/MariaDB (já existente no projeto)
**Testing**: PHPUnit + Laravel Dusk (para testes de fluxo de autenticação)
**Target Platform**: Web (Laravel backend + Blade/SPA frontend)
**Project Type**: Web application (backend Laravel, frontend já existente)
**Performance Goals**: Login concluído em < 2s em 95% dos casos
**Constraints**: Tokens nunca armazenados em texto puro; conformidade com LGPD/GDPR
**Scale/Scope**: Suporte inicial para até 10k usuários ativos/mês

---

## Constitution Check

-  ✅ Segurança em primeiro lugar: uso de HTTPS, state token contra CSRF, tokens não persistidos.
-  ✅ Experiência fluida: login em até 3 cliques.
-  ✅ Testes obrigatórios: unitários e de integração cobrindo login, callback e vinculação.
-  ✅ Integração confiável: tratamento de erros e logs estruturados.
-  ✅ Simplicidade e escalabilidade: Socialite como base, preparado para expansão futura (Facebook, Apple ID).

---

## Project Structure

### Documentation (this feature)

```
specs/001-login-google/
├── plan.md
├── research.md
├── data-model.md
├── quickstart.md
├── contracts/
└── tasks.md
```

### Source Code (repository root)

```
app/
├── Http/
│   └── Controllers/
│       └── Auth/
│           └── GoogleController.php        # Controller fino, chama Service
├── Services/
│   ├── Application/
│   │   └── Auth/
│   │       └── SocialAuthenticationService.php   # Orquestra fluxo de login/vinculação
│   ├── Infrastructure/
│   │   └── OAuth/
│   │       └── GoogleOAuthClient.php       # Wrapper para Socialite
│   └── Domain/
│       └── Users/
│           └── UserAccountService.php      # Regras de negócio do User
├── Models/
│   └── User.php                            # Ajustado com google_id, avatar, name
├── Contracts/
│   └── Interfaces/
│       └── Auth/
│           ├── SocialAuthenticationInterface.php
│           └── OAuthClientInterface.php
└── Repositories/
    └── Contracts/
        └── UserRepositoryInterface.php     # Se precisar persistência custom
```

**Structure Decision**: Web application com backend Laravel.
Frontend já existente consumirá endpoints de autenticação.

---

## Complexity Tracking

Nenhuma violação da constituição detectada.
Todas as escolhas (Socialite, testes obrigatórios, logs estruturados) estão alinhadas com os princípios definidos.

---
