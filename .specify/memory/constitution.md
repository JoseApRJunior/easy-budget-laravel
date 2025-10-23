# Easy Budget Laravel – Constitution

manter no branch dev-junior

## Core Principles

### I. Segurança em Primeiro Lugar

Todas as implementações devem priorizar a proteção de dados do usuário. Autenticação via Google OAuth 2.0 deve seguir as melhores práticas de segurança, incluindo uso de HTTPS, tokens expiram automaticamente e proteção contra CSRF.

### II. Experiência de Usuário Fluida

O login social deve ser simples, rápido e intuitivo. O usuário deve conseguir se cadastrar ou logar com um clique, sem fricção desnecessária.

### III. Testes Obrigatórios (NON-NEGOTIABLE)

Cada funcionalidade de autenticação deve ser coberta por testes unitários e de integração. Fluxos de login, callback e vinculação de contas precisam ser validados antes de produção.

### IV. Integração Confiável

A integração com o Google deve ser resiliente a falhas externas. Devemos implementar tratamento de erros, fallback e logs estruturados para monitorar problemas.

### V. Simplicidade e Escalabilidade

A arquitetura deve ser simples, baseada em Laravel Socialite, mas preparada para expansão futura (ex.: Facebook, Apple ID). Evitar complexidade desnecessária.

## Requisitos de Segurança

-  Seguir OWASP Top 10.
-  Armazenar apenas os dados mínimos necessários (nome, e-mail, avatar, Google ID).
-  Tokens de acesso nunca devem ser persistidos em texto puro.
-  Conformidade com LGPD/GDPR.

## Fluxo de Desenvolvimento

-  Pull Requests exigem revisão obrigatória.
-  Testes automatizados devem passar antes do merge.
-  Checklist de segurança deve ser validada em cada entrega.
-  Deploy só após aprovação em ambiente de staging.

## Governance

-  Esta constituição tem prioridade sobre outras práticas.
-  Alterações exigem documentação, aprovação em revisão e plano de migração.
-  Complexidade deve ser sempre justificada.

**Version**: 1.0.0 | **Ratified**: 2025-10-21 | **Last Amended**: 2025-10-21
