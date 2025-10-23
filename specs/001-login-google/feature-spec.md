# Feature Specification: Login com Google (OAuth 2.0)

**Feature Branch**: `dev-junior`
**Created**: 2025-10-21
**Status**: Draft
**Input**: User description: "Permitir login/cadastro via conta Google usando OAuth 2.0"

---

## User Scenarios & Testing

### User Story 1 - Login rápido com Google (Priority: P1)

Usuário clica em "Entrar com Google" e acessa o sistema sem precisar criar senha.

**Why this priority**: É o fluxo principal, reduz atrito no cadastro e aumenta conversão.

**Independent Test**: Pode ser testado acessando `/auth/google` e verificando se o login é concluído com sucesso.

**Acceptance Scenarios**:

1. **Given** um usuário sem conta, **When** ele autentica com Google, **Then** o sistema cria a conta e o autentica.
2. **Given** um usuário já existente, **When** ele autentica com Google, **Then** o sistema vincula e autentica a conta existente.

---

### User Story 2 - Sincronização de dados básicos (Priority: P2)

Após login, o sistema deve sincronizar nome, e-mail e avatar do Google.

**Why this priority**: Garante consistência de dados e melhora UX.

**Independent Test**: Testar se os dados do perfil Google aparecem no perfil do usuário no sistema.

**Acceptance Scenarios**:

1. **Given** login com Google, **When** o usuário acessa seu perfil, **Then** deve ver nome e avatar sincronizados.

---

### User Story 3 - Tratamento de erros (Priority: P3)

Se o usuário cancelar a autenticação ou ocorrer falha, o sistema deve exibir mensagem clara.

**Why this priority**: Evita frustração e melhora confiabilidade.

**Independent Test**: Simular cancelamento no Google e verificar mensagem de erro amigável.

**Acceptance Scenarios**:

1. **Given** cancelamento no Google, **When** o usuário retorna ao sistema, **Then** deve ver mensagem "Login cancelado".

---

### Edge Cases

-  O que acontece se o e-mail do Google já existe no sistema? → Deve vincular à conta existente.
-  Como o sistema lida se o Google não retornar avatar? → Usar avatar padrão.
-  O que acontece se o token expirar durante o fluxo? → Redirecionar para login novamente.

---

## Requirements

### Functional Requirements

-  **FR-001**: Sistema deve permitir login via Google OAuth 2.0.
-  **FR-002**: Sistema deve criar conta automaticamente se e-mail não existir.
-  **FR-003**: Sistema deve vincular conta Google a usuário existente pelo e-mail.
-  **FR-004**: Sistema deve sincronizar nome, e-mail e avatar.
-  **FR-005**: Sistema deve registrar logs de autenticação e erros.

### Key Entities

-  **User**: atributos `id`, `tenant_id`, `email`, `password`, `is_active`, `logo`, `email_verified_at`, `remember_token`, `timestamps`, além dos novos campos planejados `google_id`, `avatar`.
-  **AuthSession**: representa sessão autenticada, vinculada ao usuário.

---

## Success Criteria

### Measurable Outcomes

-  **SC-001**: Usuário consegue logar com Google em menos de 3 cliques.
-  **SC-002**: 90% dos logins sociais concluídos sem erro.
-  **SC-003**: Taxa de abandono de cadastro reduzida em 20%.
-  **SC-004**: Nenhum dado sensível do Google armazenado indevidamente.

---
