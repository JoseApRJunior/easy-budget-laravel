## Objetivos
- Resolver uso de `access_token` por tenant nos webhooks de planos (credenciais do provider → PaymentClient).
- Persistir idempotência de webhooks com histórico consultável.
- Cobrir fluxos críticos com testes de integração (webhooks, checkout de planos, PDFs com QR) usando sqlite in-memory e fakes.
- Habilitar QR nativo mPDF e ajustar `QrCodeService` para priorizar writer local.
- Aprimorar UI de integração com expiração amigável e feedback pós-refresh.

## Alterações Planejadas
### 1) Webhook de Planos – Token por Tenant
- Em `MercadoPagoWebhookService`:
  - `resolveAccessTokenForPlan`: extrair `plan_subscription_id` via `external_reference`, carregar `PlanSubscription`, buscar `ProviderCredential` por `tenant_id`, descriptografar `access_token` (EncryptionService), instanciar `PaymentClient` com token do provider.
  - Fallback para `config/env` somente se credencial ausente, com logging claro.

### 2) Idempotência Persistente
- Criar `WebhookRequest` (modelo + migração) com: `request_id`, `type`, `payload_hash`, `received_at`, `processed_at`, `status`.
- No `MercadoPagoWebhookController`: registrar antes de enfileirar; ignorar se existir.
- Alternativa Redis com TTL maior.

### 3) Testes de Integração (sqlite in-memory)
- `.env.testing`: `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`.
- Fakes: `Queue::fake`, clients/HTTP mocks para Mercado Pago.
- Casos:
  - Webhook invoices: HMAC válido/ inválido/ duplicado; job enfileirado.
  - Webhook planos: token por tenant resolvido; `PlanSubscription` atualizado (approved/cancelled); `PaymentMercadoPagoPlan` persistido.
  - Preferência planos: criação de assinatura `pending` e redirecionamento para `init_point` (mock MP).
  - PDFs: orçamento/serviço com hash e QR; fatura com QR quando `public_hash` existir.

### 4) QR nativo mPDF
- Adicionar `mpdf/qrcode` no `composer.json`.
- Ajustar `QrCodeService` para usar `Mpdf\QrCode\QrCode` + `Output\Png` por padrão, mantendo fallback HTTP.

### 5) UI de Integração
- Converter `expires_in` para minutos/horas; mensagens pós-refresh (sucesso/erro); desabilitar botão quando desconectado.

## Aceitação
- Planos: approved → ACTIVE com datas/valores; rejected/cancelled/refunded → CANCELLED; pagamento persistido.
- Webhooks: `X-Signature` validado; idempotência garantida e consultável; logs estruturados.
- PDFs: hash salvo; QR abre `documents.verify` com sucesso.
- UI: expiração exibida, refresh funcional com feedback.
- Testes: passam localmente sem MySQL.

## Cronograma
- Dia 1–2: Token por tenant e idempotência persistente.
- Dia 3–4: QR mPDF e ajustes no QrCodeService; testes PDFs.
- Dia 5–6: Testes webhooks/checkout; melhorias de UI.
- Dia 7: Revisão de segurança e limpeza.

Confirma para iniciar a execução desta fase?