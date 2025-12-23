## Objetivos
- Aprimorar a UI de integração para exibir expiração dos tokens de forma amigável e feedback após renovação.
- Consolidar resolução de credenciais por tenant em webhooks de planos, removendo dependência de token global.
- Fortalecer idempotência com persistência e ampliar cobertura com testes de integração (webhooks/checkout/PDFs com QR).

## Alterações Planejadas
### 1) UI da Integração Mercado Pago
- Controller (`Integrations/MercadoPagoController@index`): calcular expiração amigável (minutos/horas) a partir de `expires_in` e expor `expires_readable`.
- View (`pages/mercadopago/index.blade.php`): exibir `expires_readable`, feedback de sucesso/erro após `refresh`, desabilitar botão quando desconectado.
- Controller (`refresh`): tratar erros de renovação (mensagem amigável, logs), atualizar `expires_in` e tokens.

### 2) Webhooks de Planos: Token por Tenant
- Serviço (`Payment/MercadoPagoWebhookService`): em `resolveAccessTokenForPlan`, extrair `plan_subscription_id` do `external_reference`, carregar `PlanSubscription`, buscar `ProviderCredential` do `tenant_id`, descriptografar `access_token_encrypted` e usar no `PaymentClient`. Fallback para `config/env` apenas quando credenciais ausentes, com logging.

### 3) Idempotência Persistente
- Persistência `WebhookRequest` (modelo/migração) com `request_id`, `type`, `payload_hash`, `received_at`, `processed_at`, `status`.
- Controller (`MercadoPagoWebhookController`): registrar entrada antes de enfileirar; ignorar duplicados; marcar `processed_at` na conclusão do job.
- Alternativa Redis com TTL ampliado.

### 4) Testes de Integração (sqlite in-memory)
- Configuração `.env.testing` com `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`.
- Casos: HMAC válido/inválido/duplicado, token por tenant em planos (approved/cancelled), criação de preferência e redirect, PDFs de orçamento/serviço/fatura com hash e QR.
- Fakes: Queue e cliente Mercado Pago.

### 5) QR nativo mPDF
- Adicionar `mpdf/qrcode` e adaptar `QrCodeService` para priorizar writer nativo (PNG/SVG/HTML), mantendo fallback HTTP.

## Critérios de Aceitação
- UI: expiração amigável e feedback de renovação visível.
- Webhooks: token por tenant resolvido corretamente; assinatura `X-Signature` validada; duplicidades ignoradas e registradas.
- PDFs: hash salvo e QR funcional para `documents.verify`.
- Testes: passam localmente com sqlite e fakes, sem MySQL.

## Cronograma
- Dia 1–2: Ajustes UI (expiração/feedback) e token por tenant nos webhooks.
- Dia 3–4: Idempotência persistente e testes webhooks.
- Dia 5–6: QR nativo mPDF e testes de PDFs/checkout.
- Dia 7: Revisão final, endurecimento de segurança e limpeza.

Confirma para iniciar a execução destas mudanças?