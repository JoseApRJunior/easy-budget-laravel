# Feature Specification: Login com Google (OAuth 2.0)

**Feature Branch**: `001-login-google`
**Created**: 2025-10-21
**Status**: Draft
**Input**: User description: "Permitir login/cadastro via conta Google usando OAuth 2.0"

---

## User Scenarios & Testing _(mandatory)_

### User Story 1 - Login rápido sem cadastro manual (Priority: P1)

Usuário sem conta no sistema clica em "Entrar com Google" na página de login, autentica-se com sua conta Google e é automaticamente cadastrado e logado no sistema.

**Why this priority**: É o fluxo principal que reduz drasticamente o atrito no cadastro, eliminando formulários longos e aumentando a taxa de conversão de novos usuários em 40-60% (padrão da indústria).

**Independent Test**: Pode ser testado completamente acessando a página de login, clicando em "Entrar com Google", autenticando com uma conta Google não registrada e verificando se o usuário é criado e logado automaticamente no dashboard.

**Acceptance Scenarios**:

1. **Given** um usuário não registrado no sistema, **When** ele clica em "Entrar com Google" e autentica com sucesso, **Then** o sistema cria uma nova conta vinculada ao Google e redireciona para o dashboard.
2. **Given** a autenticação com Google bem-sucedida, **When** o sistema verifica o e-mail, **Then** deve criar um tenant automaticamente para o novo usuário.
3. **Given** criação de conta via Google, **When** o usuário acessa seu perfil, **Then** deve ver os dados básicos (nome, e-mail, avatar) já preenchidos.

---

### User Story 2 - Vinculação de conta existente (Priority: P2)

Usuário com conta no sistema (cadastrada por e-mail/senha) acessa "Entrar com Google" pela primeira vez e o sistema vincula automaticamente sua conta Google ao perfil existente usando o e-mail como identificador.

**Why this priority**: Permite que usuários existentes adotem o login social sem perder histórico e dados, melhorando UX e permitindo múltiplos métodos de autenticação.

**Independent Test**: Criar uma conta manualmente, fazer logout, clicar em "Entrar com Google" com o mesmo e-mail e verificar se o login é bem-sucedido e o perfil mantém todos os dados anteriores.

**Acceptance Scenarios**:

1. **Given** um usuário já registrado com e-mail "usuario@email.com", **When** ele autentica com Google usando o mesmo e-mail, **Then** o sistema vincula a conta Google ao usuário existente e faz login.
2. **Given** vinculação bem-sucedida, **When** o usuário faz login futuro, **Then** deve poder usar tanto Google quanto e-mail/senha.
3. **Given** tentativa de vinculação, **When** o e-mail Google corresponde a usuário inativo, **Then** deve exibir mensagem "Conta desativada. Contate o suporte".

---

### User Story 3 - Login futuro simplificado (Priority: P3)

Usuário que já vinculou sua conta Google pode fazer login com apenas um clique no botão "Entrar com Google", sem precisar inserir credenciais manualmente.

**Why this priority**: Melhora significativamente a experiência de retorno ao sistema, reduzindo tempo de login de ~30 segundos para ~3 segundos.

**Independent Test**: Após vinculação inicial, fazer logout e clicar em "Entrar com Google" para verificar login automático sem solicitação de credenciais.

**Acceptance Scenarios**:

1. **Given** usuário com conta Google vinculada, **When** ele clica em "Entrar com Google", **Then** deve ser autenticado automaticamente se já está logado no Google.
2. **Given** usuário não logado no Google, **When** ele clica em "Entrar com Google", **Then** deve ser redirecionado para página de login do Google.

---

### User Story 4 - Sincronização de dados (Priority: P4)

Após autenticação com Google, o sistema sincroniza automaticamente nome, e-mail e avatar do perfil Google para o perfil do usuário no sistema.

**Why this priority**: Mantém perfil atualizado e melhora personalização, mas não é crítico para funcionalidade básica de autenticação.

**Independent Test**: Fazer login com Google e verificar se nome, e-mail e avatar aparecem corretamente no perfil do usuário.

**Acceptance Scenarios**:

1. **Given** login com Google, **When** o sistema recebe dados do perfil, **Then** deve atualizar nome e e-mail do usuário.
2. **Given** Google retorna avatar, **When** o sistema processa dados, **Then** deve salvar URL do avatar no perfil.
3. **Given** Google não retorna avatar, **When** o sistema processa dados, **Then** deve usar avatar padrão do sistema.

---

### User Story 5 - Tratamento de erros e cancelamento (Priority: P5)

Se o usuário cancelar a autenticação Google ou ocorrer erro no processo, o sistema deve exibir mensagem clara e permitir que o usuário tente novamente.

**Why this priority**: Garante confiabilidade e evita frustração, mas é tratamento de exceção, não fluxo principal.

**Independent Test**: Simular cancelamento na tela do Google e verificar se mensagem de erro é exibida corretamente.

**Acceptance Scenarios**:

1. **Given** usuário clica em "Entrar com Google", **When** ele cancela na tela de autorização do Google, **Then** deve retornar à página de login com mensagem "Login cancelado. Tente novamente".
2. **Given** erro na comunicação com Google, **When** ocorre timeout ou falha de rede, **Then** deve exibir "Erro ao conectar com Google. Verifique sua conexão e tente novamente".
3. **Given** token inválido ou expirado, **When** sistema tenta validar autenticação, **Then** deve redirecionar para login com mensagem apropriada.

---

### Edge Cases

-  **E-mail Google já existe no sistema?** → Deve vincular automaticamente à conta existente pelo e-mail.
-  **Google não retorna avatar?** → Usar avatar padrão do sistema.
-  **Token expira durante o fluxo?** → Redirecionar para página de login com mensagem de expiração.
-  **Usuário existente está inativo?** → Bloquear login e exibir mensagem "Conta desativada. Contate o suporte".
-  **E-mail Google já vinculado a outro tenant?** → Não permitir vinculação (manter isolamento multi-tenant).
-  **Dados obrigatórios ausentes na resposta do Google?** → Requisitar preenchimento manual após login.
-  **Revogação de acesso no Google?** → Remover vinculação e notificar usuário no próximo acesso.

---

## Requirements _(mandatory)_

### Functional Requirements

-  **FR-001**: Sistema DEVE permitir autenticação via Google OAuth 2.0 através de botão claramente identificado na página de login.
-  **FR-002**: Sistema DEVE criar automaticamente nova conta de usuário quando e-mail Google não existir no sistema.
-  **FR-003**: Sistema DEVE criar automaticamente um tenant para cada novo usuário registrado via Google.
-  **FR-004**: Sistema DEVE vincular conta Google a usuário existente quando e-mail já está cadastrado.
-  **FR-005**: Sistema DEVE armazenar identificador único do Google (google_id) para cada usuário autenticado via Google.
-  **FR-006**: Sistema DEVE sincronizar nome, e-mail e avatar do perfil Google no primeiro login.
-  **FR-007**: Sistema DEVE permitir login futuro com apenas um clique quando usuário já está autenticado no Google.
-  **FR-008**: Sistema DEVE respeitar isolamento multi-tenant (e-mail único por tenant).
-  **FR-009**: Sistema DEVE registrar todas as autenticações sociais no log de auditoria.
-  **FR-010**: Sistema DEVE validar se usuário está ativo antes de permitir login.
-  **FR-011**: Sistema DEVE tratar cancelamento de autenticação com mensagem clara ao usuário.
-  **FR-012**: Sistema DEVE tratar erros de comunicação com Google de forma apropriada.
-  **FR-013**: Sistema DEVE permitir que usuários com conta Google vinculada continuem usando e-mail/senha se desejarem.
-  **FR-014**: Sistema DEVE aplicar rate limiting para prevenir abuso de tentativas de autenticação.
-  **FR-015**: Sistema DEVE validar tokens OAuth recebidos do Google antes de processar autenticação.

### Assumptions

As seguintes premissas foram adotadas para preencher lacunas na descrição da feature:

-  **A1**: Usuários criados via Google receberão role padrão "provider" (conforme arquitetura do sistema).
-  **A2**: Senha será opcional para contas criadas via Google (podem optar por definir senha posteriormente).
-  **A3**: E-mail retornado pelo Google será considerado automaticamente verificado.
-  **A4**: Avatar do Google será salvo como URL externa (não download/armazenamento local).
-  **A5**: Isolamento multi-tenant será mantido (mesmo e-mail pode existir em diferentes tenants).
-  **A6**: Sistema usará Laravel Socialite como biblioteca padrão para OAuth (seguindo padrões Laravel).
-  **A7**: Autenticação com Google seguirá mesmo fluxo de sessão do sistema atual.
-  **A8**: Dados sensíveis do Google (tokens de acesso) não serão armazenados permanentemente.
-  **A9**: Usuário poderá desvincular conta Google posteriormente através de configurações.
-  **A10**: Mesmo rate limiting do login tradicional será aplicado (60 requisições por minuto).

### Key Entities

-  **User**: Usuário do sistema. Novos atributos planejados: `google_id` (VARCHAR 255, NULLABLE, UNIQUE), `avatar` (VARCHAR 255, NULLABLE para URL do avatar do Google). Relacionamento com Tenant mantido.
-  **Tenant**: Empresa no sistema multi-tenant. Cada novo usuário via Google cria automaticamente um tenant.
-  **AuthSession**: Sessão de autenticação. Registra método de login (e-mail/senha ou Google) para auditoria.
-  **AuditLog**: Log de auditoria. Registra todas as tentativas de login social (sucesso, falha, cancelamento).

---

## Success Criteria _(mandatory)_

### Measurable Outcomes

-  **SC-001**: Usuário consegue fazer login com Google em menos de 5 segundos (do clique até dashboard).
-  **SC-002**: 95% das autenticações sociais são concluídas sem erro.
-  **SC-003**: Taxa de conversão de cadastro aumenta em 30% com login social disponível.
-  **SC-004**: Nenhum dado sensível do OAuth (access tokens, refresh tokens) é armazenado permanentemente no banco.
-  **SC-005**: Tempo médio de cadastro reduzido de 2 minutos para 10 segundos.
-  **SC-006**: 100% dos logins sociais são registrados em log de auditoria.
-  **SC-007**: Isolamento multi-tenant mantido com 100% de eficácia (e-mail único por tenant).
-  **SC-008**: Usuários conseguem vincular conta Google a perfil existente em uma única tentativa.

---

## Assumptions

-  Sistema usa Laravel Socialite para integração OAuth 2.0
-  Credenciais do Google OAuth (Client ID, Client Secret) serão configuradas via variáveis de ambiente
-  Callback URL será `{APP_URL}/auth/google/callback`
-  Sistema mantém compatibilidade com autenticação tradicional (e-mail/senha)
-  Auditoria segue padrões existentes do sistema
-  Interface usa Bootstrap 5.3 existente
-  Isolamento multi-tenant é mantido conforme arquitetura atual

---

## Notes

-  Feature segue arquitetura estabelecida: Controller → Services → Repositories → Models
-  Integração deve respeitar sistema de auditoria existente (trait Auditable)
-  Compatível com sistema de verificação de e-mail atual (contas Google já verificadas)
-  Não requer migração de dados existentes (novos campos são NULLABLE)
-  Preparado para expansão futura (outros provedores OAuth: Facebook, Microsoft)
