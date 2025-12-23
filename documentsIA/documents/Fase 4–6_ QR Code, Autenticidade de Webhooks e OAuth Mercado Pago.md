## Objetivos
- Implementar QR Code (geração/base64 e link para verificação de documento).
- Endurecer webhooks: validação de autenticidade (X-Signature), idempotência e resiliência.
- Finalizar fluxo OAuth Mercado Pago (troca de code→token, refresh, armazenamento seguro) e integração completa na UI.
- Adicionar testes de unidade e integração cobrindo os novos fluxos.

## Escopo Técnico
- QR Code:
  - Service: `App/Services/Infrastructure/QrCodeService` com métodos `generate(text, options)` e `read(file)` (opcional).
  - Controller: `App/Http/Controllers/QrCodeController` (API e Web) com `generate`, `handle`.
  - Views: incluir QR Code em PDFs gerados (budget/service/invoice) com link para `documents.verify`.
  - Dependência: `endroid/qr-code` para geração; leitor opcional (apenas se necessário).

- Webhooks Mercado Pago:
  - Autenticidade: validar header `X-Signature` e `X-Request-Id` conforme guia; rejeitar quando inválido.
  - Idempotência: persistir `X-Request-Id` e `payment_id` processados; checar antes de reprocessar; transações por operação.
  - Resiliência: retries na fila; timeouts adequados; logs estruturados; métricas básicas.
  - Mapeamento: manter status MP→local (pending/approved/rejected/cancelled/refunded) já aplicado; garantir consistência nos modelos.

- OAuth Mercado Pago (Provider):
  - Controller `Integrations/MercadoPagoController`:
    - `index`: mostrar estado de conexão e `authorization_url` oficial.
    - `callback`: trocar `code` por `access_token`/`refresh_token` via OAuth endpoint; persistir criptografado (`EncryptionService`).
    - `disconnect`: remover credenciais e invalidar sessão de integração.
  - Service: `App/Services/Infrastructure/MercadoPagoOAuthService` encapsulando troca e refresh; validações de expiração e escopo.
  - Config: endpoints e client_id/secret via `config/services.php` e `.env`.

- Preferências de Pagamento (Fatura/Plano):
  - Fatura: já criado método; refatorar para aceitar `payer` mais rico (nome/email) e `back_urls`/`auto_return`.
  - Plano: adicionar método equivalente com credenciais globais; external_reference inclui `plan_subscription_id`.

- Público de Faturas:
  - Garantir geração de `public_hash` e `userConfirmationToken` no fluxo de criação; exibir status, imprimir e link de pagamento.
  - SEO e segurança básica nas páginas públicas; rate limiting leve.

- Testes
  - Unit: `QrCodeService`, `MercadoPagoOAuthService`, `EncryptionService`.
  - Integração: `MercadoPagoWebhookController` (assinatura/idempotência), `PublicInvoiceController` (redirect a `init_point`), `DocumentVerificationController`.
  - Fila: testar `ProcessMercadoPagoWebhook` com mocks de cliente MP.

- PROMPTS (documentsIA/migrate laravel/PROMPTS)
  - Adicionar: `PROMPTS_DETALHADOS_MIGRACAO_QRCODE.md`, `PROMPTS_DETALHADOS_MIGRACAO_WEBHOOK.md`, `PROMPTS_DETALHADOS_MIGRACAO_MERCADOPAGO.md` (OAuth/preferências/credenciais), `PROMPTS_DETALHADOS_MIGRACAO_PUBLIC_INVOICE.md`.
  - Seguir `PATTERN_PROMPTS_MIGRACAO_MODULO.md` com tokens e passos (Migration/Model/Factory → Repository → Services → Controller → Views/Rotas → Testes).

## Critérios de Aceitação
- QR Code: gerar base64 estável, embed em PDFs, link funcional para verificação.
- Webhooks: validação `X-Signature` e `X-Request-Id`; idempotência; atualização correta de status em faturas/planos.
- OAuth: conexão e desconexão funcionais; tokens criptografados; refresh automático; UI mostra estado.
- Preferências: `init_point` válido; `notification_url` configurada; redirecionamento público funciona.
- Testes: cobertura mínima 60% controllers, 70% services; casos de erro cobertos.

## Cronograma
- Semana 1: QR Code (service/controller), embed em PDFs; testes.
- Semana 2: Autenticidade/Idempotência de Webhooks; fila e processamento; testes de integração.
- Semana 3: OAuth completo (troca e refresh), UI de integração; preferências robustas; testes.
- Semana 4: Público de fatura aperfeiçoado (hash/token/print); rate limiting; testes.
- Semana 5: Finalizações, PROMPTS e documentação técnica de uso.

## Segurança e Qualidade
- Criptografia com `EncryptionService`, sem logs de segredos; variáveis `.env` para credenciais.
- Rate limiting para páginas públicas e endpoints sensíveis; validação rigorosa de inputs.
- Logs estruturados por categoria/severidade; auditoria de eventos críticos.
- PSR-12 e análise estática; revisões de arquitetura conforme memory-bank.