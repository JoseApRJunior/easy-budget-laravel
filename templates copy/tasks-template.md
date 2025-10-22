# Tasks: Login com Google (OAuth 2.0)

**Input**: Design documents from `/specs/001-login-google/`
**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Preparar ambiente e dependências

-  [ ] T001 Instalar dependência `laravel/socialite:^5.10`
-  [ ] T002 [P] Configurar variáveis de ambiente no `.env` (`GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI`)
-  [ ] T003 [P] Atualizar `config/services.php` com credenciais do Google

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Infraestrutura base antes de implementar user stories

-  [ ] T004 Criar migration `add_google_fields_to_users_table` para adicionar `name`, `google_id` e `avatar`
-  [ ] T005 [P] Atualizar `app/Models/User.php` para incluir novos campos em `$fillable`, `$casts` e rules
-  [ ] T006 [P] Configurar rotas no `routes/web.php` (`/auth/google`, `/auth/google/callback`)
-  [ ] T007 Criar `app/Contracts/Interfaces/Auth/OAuthClientInterface.php`
-  [ ] T008 Criar `app/Services/Infrastructure/OAuth/GoogleOAuthClient.php` implementando interface
-  [ ] T009 Criar `app/Contracts/Interfaces/Auth/SocialAuthenticationInterface.php`
-  [ ] T010 Criar `app/Services/Application/Auth/SocialAuthenticationService.php` implementando interface
-  [ ] T011 Configurar logging estruturado para falhas de autenticação

**Checkpoint**: Base pronta para iniciar histórias de usuário

---

## Phase 3: User Story 1 – Login rápido com Google (Priority: P1) 🎯 MVP

**Goal**: Usuário consegue logar/cadastrar com Google em até 3 cliques
**Independent Test**: Acessar `/auth/google` → autenticar → redirecionar para `/dashboard`

### Tests

-  [ ] T012 [P] [US1] Criar teste de contrato para rota `/auth/google` em `tests/Feature/Contract/GoogleAuthTest.php`
-  [ ] T013 [P] [US1] Criar teste de integração para fluxo completo em `tests/Feature/Integration/GoogleLoginFlowTest.php`

### Implementation

-  [ ] T014 [US1] Criar `app/Http/Controllers/Auth/GoogleController.php` com métodos `redirect()` e `callback()`
-  [ ] T015 [US1] Implementar fluxo de login/cadastro automático no `SocialAuthenticationService`
-  [ ] T016 [US1] Adicionar validação de erros (ex.: cancelamento, token inválido)
-  [ ] T017 [US1] Adicionar logging de sucesso/falha

**Checkpoint**: Login com Google funcional e testável

---

## Phase 4: User Story 2 – Sincronização de dados básicos (Priority: P2)

**Goal**: Sincronizar nome, e-mail e avatar do Google
**Independent Test**: Após login, perfil do usuário exibe dados do Google

### Tests

-  [ ] T018 [P] [US2] Criar teste de integração para sincronização de dados em `tests/Feature/Integration/GoogleProfileSyncTest.php`

### Implementation

-  [ ] T019 [US2] Atualizar `SocialAuthenticationService` para salvar `name`, `email`, `avatar`
-  [ ] T020 [US2] Implementar método `syncProfileData()` em `SocialAuthenticationService`
-  [ ] T021 [US2] Garantir fallback para avatar padrão se Google não retornar imagem

**Checkpoint**: Dados sincronizados corretamente após login

---

## Phase 5: User Story 3 – Tratamento de erros (Priority: P3)

**Goal**: Exibir mensagens claras em caso de falha/cancelamento
**Independent Test**: Simular cancelamento → sistema exibe “Login cancelado”

### Tests

-  [ ] T022 [P] [US3] Criar teste de integração para fluxo de erro em `tests/Feature/Integration/GoogleAuthErrorTest.php`

### Implementation

-  [ ] T023 [US3] Implementar tratamento de cancelamento no `GoogleController`
-  [ ] T024 [US3] Implementar mensagens de erro amigáveis no frontend
-  [ ] T025 [US3] Garantir redirecionamento seguro em caso de falha

**Checkpoint**: Fluxo de erros tratado de forma clara e segura

---

## Phase N: Polish & Cross-Cutting Concerns

-  [ ] T026 Atualizar documentação em `docs/google-login.md`
-  [ ] T027 Refatorar `GoogleController` para manter SRP (Single Responsibility Principle)
-  [ ] T028 Revisar logs e adicionar métricas de autenticação
-  [ ] T029 [P] Adicionar testes unitários extras em `tests/Unit/`
-  [ ] T030 Revisão de segurança (CSRF, tokens, LGPD/GDPR)
