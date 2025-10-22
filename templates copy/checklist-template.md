# ✅ Quality Checklist: Login com Google (OAuth 2.0)

**Purpose**: Validar se todos os requisitos funcionais, técnicos e de segurança do Login com Google foram implementados corretamente.
**Created**: 2025-10-21
**Feature**: `/specs/001-login-google/spec.md`

**Note**: Este checklist foi gerado com base no spec, plan e tasks da feature.

---

## 🔑 Funcionalidade

-  [ ] **CHK001** Usuário consegue iniciar login via rota `/auth/google`.
-  [ ] **CHK002** Usuário sem conta existente tem cadastro automático criado.
-  [ ] **CHK003** Usuário com conta existente (mesmo e-mail) tem conta vinculada corretamente.
-  [ ] **CHK004** Após login, usuário é redirecionado para `/dashboard`.

---

## 🔒 Segurança

-  [ ] **CHK005** Implementado uso de **state token** para prevenir CSRF.
-  [ ] **CHK006** Tokens de acesso **não são armazenados em texto puro**.
-  [ ] **CHK007** Apenas dados mínimos (nome, e-mail, avatar, google_id) são persistidos.
-  [ ] **CHK008** Logs estruturados de falhas de autenticação estão ativos.
-  [ ] **CHK009** Conformidade com **LGPD/GDPR** validada.

---

## 🧪 Testes

-  [ ] **CHK010** Testes unitários para `GoogleController` (redirect e callback) implementados.
-  [ ] **CHK011** Testes de integração para fluxo completo de login executados com sucesso.
-  [ ] **CHK012** Testes de erro (cancelamento, token inválido, falha de rede) cobertos.
-  [ ] **CHK013** Testes de sincronização de dados (nome, e-mail, avatar) validados.

---

## 🎨 Experiência do Usuário

-  [ ] **CHK014** Login concluído em até **3 cliques**.
-  [ ] **CHK015** Mensagens de erro claras exibidas em caso de falha/cancelamento.
-  [ ] **CHK016** Avatar padrão exibido quando Google não retorna imagem.

---

## 📂 Arquitetura & Código

-  [ ] **CHK017** Dependência `laravel/socialite` instalada e configurada.
-  [ ] **CHK018** Rotas `/auth/google` e `/auth/google/callback` criadas.
-  [ ] **CHK019** `GoogleController` implementado com métodos `redirect()` e `callback()`.
-  [ ] **CHK020** `SocialAuthenticationService` criado para encapsular lógica de autenticação.
-  [ ] **CHK021** Model `User` atualizado com campos `google_id` e `avatar`.

---

## 📊 Métricas de Sucesso

-  [ ] **CHK022** Taxa de abandono de cadastro reduzida em pelo menos **20%**.
-  [ ] **CHK023** 90% dos logins sociais concluídos sem erro.
-  [ ] **CHK024** Nenhum dado sensível do Google armazenado indevidamente.

---

## Notes

-  Marque os itens concluídos com `[x]`.
-  Adicione comentários ou achados inline.
-  Use este checklist como guia de QA antes de mover a feature do ambiente **DEV** (`https://dev.easybudget.net.br`) para produção.
