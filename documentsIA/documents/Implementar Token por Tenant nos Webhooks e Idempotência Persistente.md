## Objetivos Imediatos
- Resolver `access_token` por tenant nos webhooks de planos, evitando uso de token global.
- Persistir idempotência de webhooks com histórico consultável e janela ampliada.

## Alterações Planejadas
### 1) Token por Tenant nos Webhooks de Planos
- Atualizar `app/Services/Infrastructure/Payment/MercadoPagoWebhookService.php`:
  - Em `resolveAccessTokenForPlan(string $paymentId)`, extrair `plan_subscription_id` via `parseExternalReference($externalRef,'plan_subscription_id')`.
  - Carregar `PlanSubscription` pelo ID e obter `tenant_id`.
  - Buscar `ProviderCredential` por `tenant_id` e `payment_gateway='mercadopago'`.
  - Descriptografar `access_token_encrypted` com `EncryptionService::decryptStringLaravel`.
  - Instanciar `PaymentClient` com este token.
  - Fallback para `config('services.mercadopago.access_token')`/`env('MERCADOPAGO_GLOBAL_ACCESS_TOKEN')` apenas se credenciais ausentes, com `Log::warning`.

### 2) Idempotência Persistente de Webhooks
- Adicionar modelo/migração `WebhookRequest` com campos: `request_id`, `type`, `payload_hash`, `received_at`, `processed_at`, `status`.
- Em `app/Http/Controllers/MercadoPagoWebhookController.php`:
  - Registrar a entrada com `X-Request-Id` e `payload_hash` antes de enfileirar.
  - Ignorar e responder `ignored` se já existir.
  - Atualizar `processed_at` quando o job concluir.
- Alternativa com Redis: chave `mp:webhook:{request_id}` com TTL de 1h.

## Testes de Verificação
- Configurar `.env.testing` com `DB_CONNECTION='sqlite'` e `DB_DATABASE=':memory:'`.
- Casos de teste:
  - Webhook planos: cria `ProviderCredential` e `PlanSubscription`, envia webhook com HMAC válido, verifica que `PaymentClient` usa token do provider e `PlanSubscription` atualiza para `ACTIVE`.
  - Idempotência: dupla chamada com mesmo `X-Request-Id` retorna `ignored` e apenas um job é enfileirado.

## Critérios de Aceitação
- Consultas de pagamento de planos usam o token do provider do tenant correto.
- Duplicidades de webhook são ignoradas com histórico disponível.
- Testes passando em sqlite in-memory com fakes de fila e cliente MP.

## Timeline
- Dia 1: Implementar resolução de token por tenant e logs.
- Dia 2: Idempotência persistente e testes de ambos.

Confirma para eu aplicar estas mudanças e executar os testes?