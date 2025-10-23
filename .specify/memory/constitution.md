## Use sempre brach `dev-junior`

# Easy Budget Laravel Constitution

## Core Principles

### I. Arquitetura Padronizada

Todas as funcionalidades devem seguir a arquitetura MVC com Service Layer e Repository Pattern. As camadas obrigatórias são: Controller → Service → Repository → Model → View. Traits `TenantScoped` e `Auditable` devem ser aplicadas sempre que necessário. O sistema é multi-tenant por padrão, com isolamento completo por `tenant_id`.

### II. Segurança em Primeiro Lugar

Autenticação via Google OAuth 2.0 deve seguir as melhores práticas usando Laravel Socialite. Tokens são criptografados e expiram automaticamente. Proteções contra CSRF, XSS e SQL Injection são obrigatórias. Auditoria completa deve ser aplicada em todas as ações sensíveis, com severidade, categoria e metadados.

### III. Testes Obrigatórios (NON-NEGOTIABLE)

Toda funcionalidade deve ter testes unitários e de integração. Cobertura mínima futura: 80%. Testes obrigatórios para login, reset de senha, verificação de e-mail, permissões e middleware de trial. Testes devem validar fluxos completos e garantir estabilidade antes de produção.

### IV. Evolução Guiada por Especificações

Toda nova lógica deve iniciar com `/speckit.specify`, seguida por `/speckit.plan`, `/speckit.tasks`, `/speckit.implement` e `/speckit.analyze`. Esse fluxo garante consistência, rastreabilidade e alinhamento técnico com os padrões do projeto.

### V. Observabilidade e Simplicidade

Logs estruturados devem ser implementados em todas as operações críticas. Métricas de performance e segurança devem ser monitoradas continuamente. A arquitetura deve permanecer simples e escalável, evitando complexidade desnecessária. Dashboards e relatórios devem ser otimizados para decisões rápidas.

---

## Requisitos Técnicos e de Segurança

-  Laravel 12, PHP 8.3+, MySQL 8.0+, Redis 7.0+
-  Blade + Bootstrap 5.3 + jQuery 3.7
-  Autenticação via Socialite + Sanctum
-  Rate limiting por IP e tenant
-  Auditoria com severidade e categoria
-  Conformidade com LGPD/GDPR e OWASP Top 10
-  Tokens nunca armazenados em texto puro
-  APP_DEBUG sempre desativado em produção

---

## Fluxo de Desenvolvimento

-  Pull Requests exigem revisão obrigatória
-  Testes automatizados devem passar antes do merge
-  Deploy somente após aprovação em ambiente de staging
-  Checklist de segurança e arquitetura deve ser validado
-  Documentação atualizada em `DesignPatterns/README-GERAL.md`
-  Novos modelos devem seguir o fluxo completo: Model → Repository → Service → Controller → View
-  Permissões RBAC devem ser registradas via seeder e validadas com `@can` ou `PermissionService`

---

## Governance

Esta constituição tem prioridade sobre outras práticas. Alterações exigem documentação, aprovação em revisão e plano de migração. Complexidade deve ser sempre justificada. Todos os PRs e revisões devem verificar conformidade com esta constituição. Use os artefatos Speckit como guia de runtime para desenvolvimento.

**Version**: 2.0.0 | **Ratified**: 23/10/2025 | **Last Amended**: 23/10/2025

---

Se quiser, posso salvar esse artefato como base para futuras especificações ou gerar um `/speckit.specify` para a próxima funcionalidade. Deseja que eu faça isso agora?
