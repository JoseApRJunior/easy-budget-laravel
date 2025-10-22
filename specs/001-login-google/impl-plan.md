# Implementation Plan: Login com Google (OAuth 2.0)

**Feature Branch**: `001-login-google`
**Created**: 2025-10-21
**Status**: Planning
**Phase**: 0 - Research & Analysis

---

## 📋 Technical Context

### 🎯 Current Architecture Analysis

**Sistema existente:**

-  Laravel 12 com arquitetura Controller → Services → Repositories → Models
-  Sistema multi-tenant implementado com isolamento por empresa
-  Sistema de autenticação atual baseado em Laravel Breeze/Sanctum
-  Sistema de e-mail avançado já implementado com MailerService
-  Sistema de auditoria completo com trait Auditable
-  Estrutura de usuários com campos: `id`, `tenant_id`, `email`, `password`, `is_active`, `logo`, `email_verified_at`, `remember_token`

### 🔧 Required Technical Components

**Novos campos necessários no modelo User:**

-  `google_id` (string, nullable) - ID único do usuário no Google
-  `avatar` (string, nullable) - URL do avatar do Google
-  `google_data` (json, nullable) - Dados adicionais do perfil Google

**Integrações necessárias:**

-  **Google OAuth 2.0** - Para autenticação via Google
-  **Google People API** - Para obter dados do perfil (nome, avatar)
-  **Configuração OAuth** - Client ID, Client Secret, Redirect URI

**Bibliotecas sugeridas:**

-  **laravel/socialite** - Para integração OAuth simplificada
-  **google/apiclient** - Para acesso à Google People API

### ❓ NEEDS CLARIFICATION

**NC-001**: Qual é a configuração atual do Laravel Socialite no projeto?
**NC-002**: Existe algum serviço de autenticação customizado que precisa ser integrado?
**NC-003**: Como o sistema deve lidar com usuários que já têm conta mas nunca usaram Google?
**NC-004**: Precisamos implementar refresh token para manter acesso aos dados do Google?
**NC-005**: Como integrar com o sistema de auditoria existente para logs de login social?
**NC-006**: O sistema precisa de configuração específica para ambiente de desenvolvimento vs produção?

### 🔗 Dependencies & Integration Points

**Dependências existentes a considerar:**

-  Sistema de e-mail (MailerService) - Para notificações de login
-  Sistema de auditoria (AuditLog) - Para rastrear logins sociais
-  Sistema multi-tenant - Para associar usuários ao tenant correto
-  Sistema de verificação de e-mail - Para marcar e-mail como verificado automaticamente

**Novas dependências necessárias:**

-  Google OAuth 2.0 credentials (Client ID, Client Secret)
-  Configuração de redirect URLs para desenvolvimento e produção

---

## 🏛️ Constitution Check

### 📜 Relevant Constitutional Principles

**Princípio 1: Arquitetura MVC com Service Layer**

-  ✅ **COMPATÍVEL**: Implementação seguirá padrão Controller → Service → Repository
-  🔄 **ADAPTAÇÃO**: Novo serviço GoogleAuthService para lógica de negócio OAuth

**Princípio 2: Multi-tenant Architecture**

-  ✅ **COMPATÍVEL**: Usuários Google serão associados ao tenant correto
-  🔄 **ADAPTAÇÃO**: Lógica para determinar tenant durante criação automática de conta

**Princípio 3: Sistema de Auditoria**

-  ✅ **COMPATÍVEL**: Todos os logins sociais serão auditados
-  🔄 **ADAPTAÇÃO**: Categoria específica "social_auth" para logs de OAuth

**Princípio 4: Tratamento de Erros Padronizado**

-  ✅ **COMPATÍVEL**: Uso de ServiceResult para respostas consistentes
-  🔄 **ADAPTAÇÃO**: Tratamento específico para erros OAuth (cancelamento, falha de API)

### 🚨 Gate Evaluation

**Gate 1: Arquitetura Compatibility**

-  ✅ **PASS**: Implementação compatível com arquitetura existente

**Gate 2: Security Standards**

-  ✅ **PASS**: OAuth 2.0 é padrão seguro da indústria
-  ⚠️ **WARNING**: Necessário configurar HTTPS em produção

**Gate 3: Multi-tenant Isolation**

-  ✅ **PASS**: Usuários Google respeitarão isolamento por tenant

**Gate 4: Audit Requirements**

-  ✅ **PASS**: Sistema de auditoria cobrirá logins sociais

**Gate 5: Error Handling**

-  ✅ **PASS**: Tratamento robusto de erros implementado

---

## 📋 Implementation Phases

### Phase 0: Research & Analysis (Current)

**Objetivo**: Resolver todas as incertezas técnicas identificadas

#### Research Tasks

**RT-001**: Análise da configuração atual do Laravel Socialite

-  Verificar se Socialite já está instalado
-  Identificar configurações existentes de OAuth
-  Documentar providers já configurados

**RT-002**: Estudo do fluxo de autenticação atual

-  Mapear AuthenticatedSessionController atual
-  Identificar pontos de integração necessários
-  Documentar fluxo de criação de usuários

**RT-003**: Análise de requisitos de segurança OAuth

-  Definir configurações de produção vs desenvolvimento
-  Identificar requisitos de HTTPS e domínios
-  Documentar políticas de segurança necessárias

**RT-004**: Estudo de integração com sistema de auditoria

-  Identificar categorias de log apropriadas
-  Definir formato de logs para autenticação social
-  Documentar eventos personalizados necessários

### Phase 1: Design & Contracts (Next)

**Objetivo**: Criar especificações técnicas detalhadas

#### Design Deliverables

**DD-001**: Modelo de dados atualizado (User)

-  Campos adicionais: `google_id`, `avatar`, `google_data`
-  Relacionamentos necessários
-  Índices de performance

**DD-002**: Contratos de API (OpenAPI/Swagger)

-  Endpoint `/auth/google` - Iniciar autenticação
-  Endpoint `/auth/google/callback` - Processar retorno
-  Endpoint `/auth/google/unlink` - Desvincular conta

**DD-003**: Diagramas de fluxo OAuth

-  Fluxo de autenticação bem-sucedida
-  Fluxo de criação de nova conta
-  Fluxo de vinculação de conta existente
-  Fluxo de tratamento de erros

### Phase 2: Implementation (Future)

**Objetivo**: Implementar funcionalidade completa

#### Implementation Tasks

**IT-001**: Configuração Google OAuth 2.0

-  Configurar Google Cloud Console
-  Obter Client ID e Client Secret
-  Configurar redirect URLs

**IT-002**: Instalação e configuração Laravel Socialite

-  Instalar package via Composer
-  Configurar provider Google
-  Testar configuração básica

**IT-003**: Implementação do serviço de autenticação Google

-  Criar GoogleAuthService
-  Implementar lógica de criação/vinculação de usuários
-  Integrar com sistema de auditoria

**IT-004**: Desenvolvimento dos controllers

-  GoogleAuthController para endpoints OAuth
-  Integração com AuthenticatedSessionController existente
-  Tratamento de erros e redirecionamentos

**IT-005**: Atualização do modelo User

-  Adicionar campos necessários para Google
-  Implementar lógica de sincronização de dados
-  Criar métodos auxiliares para OAuth

**IT-006**: Implementação das views

-  Botão "Entrar com Google" na página de login
-  Página de callback OAuth
-  Mensagens de erro amigáveis

**IT-007**: Testes automatizados

-  Testes de integração OAuth
-  Testes de criação de usuários
-  Testes de vinculação de contas
-  Testes de tratamento de erros

**IT-008**: Documentação e configuração

-  Documentar configuração OAuth
-  Criar guia de setup para produção
-  Documentar troubleshooting

---

## 🎯 Success Metrics

### Implementation Success Criteria

**SC-001**: Usuário consegue autenticar com Google em menos de 10 segundos
**SC-002**: 100% das autenticações sociais são auditadas
**SC-003**: Taxa de sucesso de login social > 95%
**SC-004**: Zero dados sensíveis armazenados indevidamente
**SC-005**: Tempo de implementação < 2 semanas

### Quality Gates

**QG-001**: Todos os testes automatizados passando
**QG-002**: Cobertura de testes > 80%
**QG-003**: Zero vulnerabilidades de segurança identificadas
**QG-004**: Performance impact < 5% no tempo de resposta
**QG-005**: Documentação completa e atualizada

---

## 🚧 Risk Assessment

### Technical Risks

**Risco 1: Problemas de configuração OAuth**

-  **Impacto**: Alto - Usuários não conseguem fazer login
-  **Probabilidade**: Média
-  **Mitigação**: Documentação detalhada e ambiente de teste

**Risco 2: Problemas de compatibilidade com autenticação existente**

-  **Impacto**: Alto - Pode quebrar login atual
-  **Probabilidade**: Baixa
-  **Mitigação**: Testes abrangentes e implementação gradual

**Risco 3: Vazamento de dados do Google**

-  **Impacto**: Crítico - Violação de privacidade
-  **Probabilidade**: Baixa
-  **Mitigação**: Revisão de segurança e auditoria de código

### Business Risks

**Risco 1: Baixa adoção do login social**

-  **Impacto**: Médio - Menos redução de atrito no cadastro
-  **Probabilidade**: Média
-  **Mitigação**: UX otimizada e promoção da funcionalidade

**Risco 2: Problemas com contas duplicadas**

-  **Impacto**: Alto - Confusão para usuários
-  **Probabilidade**: Média
-  **Mitigação**: Lógica robusta de vinculação de contas

---

## 📚 References

-  [Laravel Socialite Documentation](https://laravel.com/docs/socialite)
-  [Google OAuth 2.0 Documentation](https://developers.google.com/identity/protocols/oauth2)
-  [Google People API](https://developers.google.com/people/api/rest/v1/people/get)
-  Sistema de autenticação atual: `app/Http/Controllers/Auth/`
-  Sistema de auditoria: `app/Traits/Auditable.php`
-  Sistema de e-mail: `app/Services/Infrastructure/MailerService.php`

---

_Este documento será atualizado conforme o progresso da implementação_
