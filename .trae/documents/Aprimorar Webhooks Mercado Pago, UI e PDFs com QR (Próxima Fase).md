## Objetivos
- Resolver credenciais por tenant nos webhooks de planos (usar ProviderCredential e EncryptionService).
- Persistir idempotência de webhooks com histórico consultável.
- Cobrir fluxos críticos com testes de integração (webhooks, checkout de planos, PDFs com QR) usando sqlite in-memory.
- Habilitar QR nativo mPDF e ajustar QrCodeService para priorizar writer local.
- Aprimorar UI de integração com expiração amigável e feedback pós-refresh.

## Alterações Planejadas
- Webhook de planos: em `MercadoPagoWebhookService::resolveAccessTokenForPlan`, extrair `plan_subscription_id` do `external_reference`, carregar `PlanSubscription`, buscar `ProviderCredential` por `tenant_id`, descriptografar `access_token` e instanciar `PaymentClient` com o token do provider; fallback para `config/env` com `Log::warning`.
- Idempotência persistente: criar `WebhookRequest` (modelo + migração) com `request_id`, `type`, `payload_hash`, `received_at`, `processed_at`, `status` e registrar antes de enfileirar no `MercadoPagoWebhookController`; alternativa Redis com TTL maior.
- Testes de integração: configurar `.env.testing` com sqlite in-memory; usar `Queue::fake` e mocks de Mercado Pago; cobrir HMAC válido/inválido/duplicado, resolução de token por tenant, atualização de `PlanSubscription`, criação de preferência/redirect e presença de hash/QR nos PDFs.
- QR nativo mPDF: adicionar `mpdf/qrcode` e atualizar `QrCodeService` para usar `Mpdf\QrCode\QrCode` + `Output\Png` por padrão, mantendo fallback HTTP.
- UI de integração: converter `expires_in` para minutos/horas na view, mostrar feedback pós-refresh e desabilitar o botão quando desconectado.

## Critérios de Aceitação
- Planos: aprovado → `ACTIVE` com datas/valores; rejeitado/cancelado/refundado → `CANCELLED`; pagamento persistido.
- Webhooks: `X-Signature` validado; idempotência garantida e consultável; logs estruturados.
- PDFs: hash salvo e QR funciona para `documents.verify`.
- UI: expiração exibida e refresh funcional com feedback.
- Testes: executam com sqlite e fakes sem depender de MySQL.

## Cronograma
- Dia 1–2: Token por tenant nos webhooks + idempotência persistente.
- Dia 3–4: QR mPDF e ajustes no QrCodeService; testes PDFs.
- Dia 5–6: Testes webhooks/checkout; melhorias na UI.
- Dia 7: Revisão, endurecimento de segurança e limpeza.

Confirma para iniciar a execução desta fase?