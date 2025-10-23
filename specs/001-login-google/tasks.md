---
description: "Task list for Google OAuth 2.0 login feature implementation"
---

# Tasks: Login com Google (OAuth 2.0)

**Input**: Design documents from `/specs/001-login-google/`
**Prerequisites**: impl-plan.md (required), spec.md (required for user stories), research.md, data-model.md, contracts/

**Tests**: Tests are REQUIRED for this feature as specified in the constitution (NON-NEGOTIABLE)

**Organization**: Tasks are grouped by user story to enable independent implementation and testing of each story.

## Format: `[ID] [P?] [Story] Description`

-  **[P]**: Can run in parallel (different files, no dependencies)
-  **[Story]**: Which user story this task belongs to (e.g., US1, US2, US3)
-  Include exact file paths in descriptions

## Path Conventions (Laravel Project)

-  **Backend**: `app/`, `database/`, `routes/`, `config/`
-  **Tests**: `tests/Feature/`, `tests/Unit/`
-  **Views**: `resources/views/`
-  **Config**: `config/`

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Project initialization and basic structure for OAuth

-  [ ] T001 Create project structure for OAuth integration
-  [ ] T002 [P] Configure Laravel Socialite for Google OAuth 2.0
-  [ ] T003 [P] Set up Google Cloud Console credentials and configuration

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Core infrastructure that MUST be complete before ANY user story can be implemented

**⚠️ CRITICAL**: No user story work can begin until this phase is complete

-  [ ] T004 Update User model with Google OAuth fields (google_id, avatar, google_data)
-  [ ] T005 [P] Create database migration for new User fields
-  [ ] T006 [P] Set up OAuth configuration in services.php
-  [ ] T007 Create base GoogleAuthService for OAuth operations
-  [ ] T008 Configure authentication routes for Google OAuth
-  [ ] T009 Set up error handling and logging for OAuth operations

**Checkpoint**: Foundation ready - user story implementation can now begin in parallel

---

## Phase 3: User Story 1 - Login rápido sem cadastro manual (Priority: P1) 🎯 MVP

**Goal**: Usuário sem conta pode fazer login com Google e ser automaticamente cadastrado

**Independent Test**: Acessar página de login, clicar "Entrar com Google", autenticar com conta Google não registrada, verificar criação automática de conta e login no dashboard

### Tests for User Story 1 (REQUIRED) ⚠️

**NOTE: Write these tests FIRST, ensure they FAIL before implementation**

-  [ ] T010 [P] [US1] Contract test for Google OAuth initiation in tests/Feature/GoogleAuthTest.php
-  [ ] T011 [P] [US1] Integration test for automatic user creation in tests/Feature/GoogleAuthIntegrationTest.php
-  [ ] T012 [P] [US1] Test for tenant creation during Google registration in tests/Feature/TenantCreationTest.php

### Implementation for User Story 1

-  [ ] T013 [P] [US1] Create GoogleAuthController for OAuth endpoints in app/Http/Controllers/Auth/GoogleAuthController.php
-  [ ] T014 [US1] Implement automatic user creation logic in GoogleAuthService (depends on T007)
-  [ ] T015 [US1] Implement automatic tenant creation for new Google users
-  [ ] T016 [US1] Add Google login button to login view in resources/views/auth/login.blade.php
-  [ ] T017 [US1] Implement OAuth callback handling in GoogleAuthController
-  [ ] T018 [US1] Add audit logging for Google authentication attempts

**Checkpoint**: At this point, User Story 1 should be fully functional and testable independently

---

## Phase 4: User Story 2 - Vinculação de conta existente (Priority: P2)

**Goal**: Usuário existente pode vincular sua conta Google ao perfil atual

**Independent Test**: Criar conta manualmente, fazer logout, clicar "Entrar com Google" com mesmo e-mail, verificar vinculação automática e login bem-sucedido

### Tests for User Story 2 (REQUIRED) ⚠️

-  [ ] T019 [P] [US2] Contract test for account linking in tests/Feature/GoogleAccountLinkingTest.php
-  [ ] T020 [P] [US2] Integration test for existing user Google login in tests/Feature/GoogleAuthIntegrationTest.php

### Implementation for User Story 2

-  [ ] T021 [P] [US2] Implement account linking logic in GoogleAuthService
-  [ ] T022 [US2] Add method to find existing user by email in UserRepository
-  [ ] T023 [US2] Update GoogleAuthController to handle account linking scenario
-  [ ] T024 [US2] Add audit logging for account linking operations

**Checkpoint**: At this point, User Stories 1 AND 2 should both work independently

---

## Phase 5: User Story 3 - Login futuro simplificado (Priority: P3)

**Goal**: Usuário com conta Google vinculada pode fazer login com um clique

**Independent Test**: Após vinculação inicial, fazer logout e clicar "Entrar com Google" para verificar login automático sem credenciais

### Tests for User Story 3 (REQUIRED) ⚠️

-  [ ] T025 [P] [US3] Contract test for simplified login in tests/Feature/GoogleAuthTest.php
-  [ ] T026 [P] [US3] Integration test for one-click login in tests/Feature/GoogleAuthIntegrationTest.php

### Implementation for User Story 3

-  [ ] T027 [P] [US3] Implement one-click login logic in GoogleAuthController
-  [ ] T028 [US3] Add logic to detect existing Google authentication in GoogleAuthService
-  [ ] T029 [US3] Update login view to show different state for linked accounts

**Checkpoint**: User Stories 1, 2, AND 3 should all work independently

---

## Phase 6: User Story 4 - Sincronização de dados (Priority: P4)

**Goal**: Sistema sincroniza nome, e-mail e avatar do Google automaticamente

**Independent Test**: Fazer login com Google e verificar se dados do perfil aparecem corretamente

### Tests for User Story 4 (REQUIRED) ⚠️

-  [ ] T030 [P] [US4] Contract test for profile synchronization in tests/Feature/GoogleProfileSyncTest.php
-  [ ] T031 [P] [US4] Integration test for data synchronization in tests/Feature/GoogleAuthIntegrationTest.php

### Implementation for User Story 4

-  [ ] T032 [P] [US4] Implement profile data synchronization in GoogleAuthService
-  [ ] T033 [US4] Add method to update user profile from Google data in UserRepository
-  [ ] T034 [US4] Handle avatar URL storage and default avatar logic

---

## Phase 7: User Story 5 - Tratamento de erros e cancelamento (Priority: P5)

**Goal**: Sistema trata cancelamentos e erros de OAuth adequadamente

**Independent Test**: Simular cancelamento na tela do Google e verificar mensagem de erro

### Tests for User Story 5 (REQUIRED) ⚠️

-  [ ] T035 [P] [US5] Contract test for error handling in tests/Feature/GoogleAuthErrorTest.php
-  [ ] T036 [P] [US5] Integration test for cancellation handling in tests/Feature/GoogleAuthIntegrationTest.php

### Implementation for User Story 5

-  [ ] T037 [P] [US5] Implement error handling for OAuth failures in GoogleAuthController
-  [ ] T038 [US5] Add user-friendly error messages for different failure scenarios
-  [ ] T039 [US5] Implement proper logging for OAuth errors and debugging

**Checkpoint**: All user stories should now be independently functional

---

## Phase 8: Polish & Cross-Cutting Concerns

**Purpose**: Improvements that affect multiple user stories

-  [ ] T040 [P] Update authentication documentation in docs/
-  [ ] T041 Code cleanup and optimization across all OAuth components
-  [ ] T042 [P] Additional security hardening for OAuth implementation
-  [ ] T043 [P] Performance optimization for authentication flows
-  [ ] T044 Run integration tests and validate all user stories
-  [ ] T045 Create configuration guide for production deployment

---

## Dependencies & Execution Order

### Phase Dependencies

-  **Setup (Phase 1)**: No dependencies - can start immediately
-  **Foundational (Phase 2)**: Depends on Setup completion - BLOCKS all user stories
-  **User Stories (Phase 3+)**: All depend on Foundational phase completion
   -  User stories can then proceed in parallel (if staffed)
   -  Or sequentially in priority order (P1 → P2 → P3)
-  **Polish (Final Phase)**: Depends on all desired user stories being complete

### User Story Dependencies

-  **User Story 1 (P1)**: Can start after Foundational (Phase 2) - No dependencies on other stories
-  **User Story 2 (P2)**: Can start after Foundational (Phase 2) - May integrate with US1 but should be independently testable
-  **User Story 3 (P3)**: Can start after Foundational (Phase 2) - May integrate with US1/US2 but should be independently testable
-  **User Story 4 (P4)**: Can start after US1 is complete - Depends on basic OAuth flow
-  **User Story 5 (P5)**: Can start after US1 is complete - Depends on basic OAuth flow

### Within Each User Story

-  Tests (REQUIRED) MUST be written and FAIL before implementation
-  Models/migrations before services
-  Services before controllers
-  Controllers before views
-  Core implementation before integration
-  Story complete before moving to next priority

### Parallel Opportunities

-  All Setup tasks marked [P] can run in parallel
-  All Foundational tasks marked [P] can run in parallel (within Phase 2)
-  Once Foundational phase completes, User Stories 1, 2, and 3 can start in parallel
-  All tests for a user story marked [P] can run in parallel
-  Models within a story marked [P] can run in parallel
-  Different user stories can be worked on in parallel by different team members

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Complete Phase 1: Setup
2. Complete Phase 2: Foundational (CRITICAL - blocks all stories)
3. Complete Phase 3: User Story 1
4. **STOP and VALIDATE**: Test User Story 1 independently
5. Deploy/demo if ready

### Incremental Delivery

1. Complete Setup + Foundational → Foundation ready
2. Add User Story 1 → Test independently → Deploy/Demo (MVP!)
3. Add User Story 2 → Test independently → Deploy/Demo
4. Add User Story 3 → Test independently → Deploy/Demo
5. Each story adds value without breaking previous stories

### Parallel Team Strategy

With multiple developers:

1. Team completes Setup + Foundational together
2. Once Foundational is done:
   -  Developer A: User Story 1 (P1)
   -  Developer B: User Story 2 (P2)
   -  Developer C: User Story 3 (P3)
3. Stories complete and integrate independently

---

## Notes

-  [P] tasks = different files, no dependencies
-  [Story] label maps task to specific user story for traceability
-  Each user story should be independently completable and testable
-  Verify tests fail before implementing
-  Commit after each task or logical group
-  Stop at any checkpoint to validate story independently
-  Follow Laravel conventions and existing architecture patterns
-  All authentication must respect multi-tenant isolation
-  All operations must be audited according to system standards
-  Tests are NON-NEGOTIABLE per constitution requirements
