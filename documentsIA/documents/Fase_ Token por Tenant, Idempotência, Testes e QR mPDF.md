## Objetivos
- Resolver credenciais por tenant nos webhooks de planos, eliminando dependência de token global.
- Persistir idempotência de webhooks para rastreabilidade e evitar duplicidades.
- Cobrir fluxos críticos com testes de integração (webhooks, checkout de planos, PDFs com QR) usando sqlite in-memory.
- Habilitar QR nativo mPDF e ajustar serviço de QR para priorizar writer local.
- Aprimorar UI de integração com expiração amigável e feedback após refresh.

## Alterações Planejadas
### 1) Webhook de Planos: Access Token por Tenant
- Editar `app/Services/Infrastructure/Payment/MercadoPagoWebhookService.php`:
  - `resolveAccessTokenForPlan(paymentId)`: extrair `plan_subscription_id` via `parseExternalReference`, carregar `PlanSubscription`, buscar `ProviderCredential` por `tenant_id`, descriptografar `access_token` (EncryptionService) e retornar.
  - Logging de fallback quando credenciais ausentes.
- Critério: `PaymentClient` para planos usa token do provider do tenant correto.

### 2) Idempotência Persistente
- Adicionar `WebhookRequest` (modelo + migração) com campos: `request_id`, `type`, `payload_hash`, `received_at`, `processed_at`, `status`.
- Ajustar `MercadoPagoWebhookController`: registrar `request_id` antes de enfileirar; ignorar se existir.
- Alternativa: chave Redis/Tabelas com TTL > 10min.
- Critério: duplicados recebem `ignored`, com histórico consultável.

### 3) Testes de Integração (sqlite in-memory)
- Configurar ambiente de teste: `.env.testing` com `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`.
- Coberturas:
  - Webhook invoices: HMAC válido/ inválido/ duplicado, job enfileirado.
  - Webhook planos: token por tenant resolvido, `PlanSubscription` atualizado (approved/cancelled), `PaymentMercadoPagoPlan` persistido.
  - Preferência planos: criação de `PlanSubscription` pending e redirecionamento para `init_point` (mock MP).
  - PDFs: orçamento/serviço com hash e QR; fatura com QR quando `public_hash` existir.
- Critério: suíte passa sem depender de MySQL externo.

### 4) QR nativo mPDF
- Adicionar dependência `mpdf/qrcode`.
- Ajustar `app/Services/Infrastructure/QrCodeService.php` para usar `Mpdf\QrCode\QrCode` + `Output\Png` por padrão, mantendo fallback HTTP.
- Critério: QR gerado localmente nos PDFs.

### 5) UI de Integração Mercado Pago
- Converter `expires_in` para formato amigável (min/horas) na view.
- Mensagens de sucesso/erro após refresh; desabilitar botão se desconectado.
- Critério: usuário vê expiração e renova token com feedback claro.

## Aceitação
- Planos: pagamentos aprovados ativam assinatura; rejeições/cancelamentos/refundos cancelam; dados de transação persistidos.
- Webhooks: assinatura HMAC validada; idempotência persistente; logs estruturados.
- PDFs: hash salvo e QR funcional com link de verificação pública.
- UI: refresh tokens ok; expiração exibida corretamente.
- Testes: suíte executa e passa com sqlite + fakes.

## Cronograma
- Dia 1–2: Resolver token por tenant e idempotência persistente.
- Dia 3–4: QR mPDF e ajustes no QrCodeService; testes PDFs.
- Dia 5–6: Testes webhooks/checkout; melhorias na UI.
- Dia 7: Revisão e endurecimento de segurança.

Confirma para eu aplicar essas alterações e executar os testes?