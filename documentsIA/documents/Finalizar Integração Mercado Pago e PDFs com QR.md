## Objetivos
- Resolver credenciais por tenant nos webhooks de planos, removendo dependência de token global.
- Cobrir pagamentos com testes robustos (webhooks/checkout) sem depender de MySQL externo.
- Padronizar QR nativo em PDFs e fortalecer a verificação pública.
- Aprimorar UI de integração com expiração e feedback.
- Persistir idempotência de webhooks em armazenamento confiável.

## Escopo de Pesquisa (Estado Atual)
- UI de integração exibe `expires_in` e provê refresh/desconectar (controller + view + rota).
- Preferência de planos criada com `external_reference` completo; redirecionamento implementado.
- Webhooks validam `X-Signature` e possuem idempotência via cache; atualizam `PlanSubscription` e persistem pagamento.
- PDFs com QR e hash ativos para orçamento/serviço/fatura.

## Plano de Implementação
### 1) Webhook de Planos: Access Token por Tenant
- Parsear `plan_subscription_id` do `external_reference` (já suportado).
- Carregar `PlanSubscription` → obter `tenant_id` → buscar `ProviderCredential` do tenant.
- Descriptografar `access_token` com `EncryptionService` e instanciar `PaymentClient` com este token.
- Fallback apenas se credencial ausente (config/env) com `Log::warning` e métrica.
- Critérios: todos pagamentos de planos consultados com token do provider correto.

### 2) Idempotência Persistente
- Criar persistência `webhook_requests` (campos: `request_id`, `type`, `received_at`, `processed_at`, `status`) ou usar Redis com TTL ampliado.
- Gravar antes de enfileirar; ignorar se encontrado.
- Critérios: duplicidades ignoradas com histórico consultável.

### 3) Testes de Integração
- Configurar sqlite in-memory para testes (env de test, `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`).
- Fakes: Queue::fake, HTTP mocks para Mercado Pago.
- Coberturas:
  - Webhook invoices: HMAC válido, inválido, duplicado; job enfileirado.
  - Webhook planos: resolução de token por tenant; update de `PlanSubscription` (approved/cancelled) e persistência em pagamento.
  - Preferência planos: criação de `PlanSubscription` pending + redirecionamento para `init_point`.
  - PDFs: orçamento/serviço com hash e QR; fatura com QR quando `public_hash` existir.
- Critérios: suíte passa localmente sem MySQL.

### 4) QR nativo mPDF
- Adicionar `mpdf/qrcode` e ajustar `QrCodeService` para priorizar writer nativo (PNG/SVG/HTML), mantendo fallback HTTP.
- Critérios: QR renderizado localmente em todos os PDFs.

### 5) UI de Integração
- Exibir expiração amigável (minutos/hora) e mensagem pós-refresh; desabilitar botão quando não conectado.
- Manter rotas nomeadas e CSRF.
- Critérios: usuário enxerga expiração e consegue renovar tokens com feedback claro.

## Aceitação
- Planos: pagamentos aprovados mudam assinatura para ACTIVE; rejeições/cancelamentos/refundos mudam para CANCELLED; dados de transação persistidos.
- Webhooks: `X-Signature` validado, idempotência persistente, logs estruturados.
- PDFs: hash salvo e QR funcional; verificação pública retorna sucesso.
- UI: refresh tokens ok e expiração exibida.
- Testes: suite de integração roda com sqlite e fakes e passa.

## Cronograma
- Dia 1–2: Resolver token por tenant nos webhooks; idempotência persistente; logs.
- Dia 3–4: Adicionar `mpdf/qrcode`; ajustar `QrCodeService`; testes PDFs.
- Dia 5–6: Testes webhooks/checkout com sqlite; melhorias UI e feedback.
- Dia 7: Revisão, endurecimento de segurança, documentação técnica no código.

Confirma para iniciar execução e aplicar as mudanças acima?