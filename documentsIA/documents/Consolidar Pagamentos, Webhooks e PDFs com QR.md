## Objetivos
- Finalizar e validar fluxos de pagamento (faturas e planos) via Mercado Pago.
- Fortalecer segurança dos webhooks com resolução correta de credenciais por tenant.
- Padronizar geração de PDFs com hash e QR de verificação em orçamento, serviço e fatura.
- Cobrir funcionalidades com testes de integração essenciais.

## Escopo Atual
- Integração Mercado Pago: tokens renováveis, UI com expiração, desconectar (app/Http/Controllers/Integrations/MercadoPagoController.php:17,83; routes/web.php:399; resources/views/pages/mercadopago/index.blade.php:31).
- Preferência planos: criação por tenant, external_reference completo (app/Services/Infrastructure/PaymentMercadoPagoPlanService.php:31) e redirecionamento (app/Http/Controllers/PlanController.php:191).
- Webhooks: assinatura HMAC (`X-Signature`) e idempotência por `X-Request-Id` (app/Http/Controllers/MercadoPagoWebhookController.php:63); atualização de `PlanSubscription` e persistência em `PaymentMercadoPagoPlan` (app/Services/Infrastructure/Payment/MercadoPagoWebhookService.php:61); token planos via config/env (app/Services/Infrastructure/Payment/MercadoPagoWebhookService.php:143).
- PDFs com QR: orçamento (BudgetController + budgets/pdf.blade.php), serviço (ServicePdfService + ServiceController + pages/service/public/print.blade.php), fatura (InvoiceService + invoices/pdf.blade.php). Fatura agora gera `public_hash` e token de verificação para links públicos (app/Services/Domain/InvoiceService.php:97,242).

## Implementações Planejadas
### 1) Webhooks de Planos – Resolver Access Token por Tenant
- Parsear `plan_subscription_id` do `external_reference` (já suportado).
- Carregar `ProviderCredential` do `tenant_id` da assinatura e desencriptar `access_token` para instanciar `PaymentClient` (similar a invoices).
- Fallback apenas se credencial ausente (config/env), com logging explícito.
- Critério: pagamentos de planos consultados sempre com o token do provider do tenant correto.

### 2) Testes de Integração Essenciais
- Webhook invoices: validar HMAC, idempotência e enfileiramento do job.
- Webhook planos: validar processamento com `ProviderCredential` resolvido por tenant; atualizar `PlanSubscription` status/datas/valores.
- Preferência planos: criar `PlanSubscription` pendente, gerar preferência e redirecionar.
- PDFs: garantir presença de hash e QR nas views de orçamento e serviço; fatura inclui QR quando `public_hash` existir.
- Critério: suíte de testes roda em ambiente de teste sem dependência de MySQL real (usar sqlite in-memory ou mocks).

### 3) UI da Integração Mercado Pago
- Exibir expiração em tempo amigável (minutos/hora) e feedback após refresh.
- Tratar erros de refresh (mensagens amigáveis na UI) e desabilitar botão quando não conectado.
- Critério: usuário consegue renovar tokens e ver expiração claramente.

### 4) QR Code Nativo do mPDF
- Adicionar dependência `mpdf/qrcode` e ajustar `QrCodeService` para priorizar o writer nativo (PNG/SVG/HTML), mantendo fallback HTTP.
- Critério: QR gerado localmente (sem chamadas externas) em todos os PDFs.

### 5) Fatura – Página Pública e Fluxo de Pagamento
- Confirmar view pública (invoices.public.view-status) e estados; assegurar redirecionamento seguro (PublicInvoiceController) e mensagens de erro/sucesso.
- Critério: clientes acessam fatura pública via link e conseguem iniciar pagamento.

### 6) Segurança e Idempotência
- Persistir idempotência dos webhooks (cache atual → opcionalmente tabela ou chave Redis) com janela maior.
- Validar origem de IP (lista da MP) opcionalmente; reforçar logs estruturados.
- Critério: eventos duplicados ignorados; eventos inválidos rejeitados com rastreabilidade.

## Verificações e Aceitação
- Pagamentos: aprovação altera estados corretamente (Invoice → PAID; PlanSubscription → ACTIVE) e persiste transação.
- PDFs: hash SHA-256 salvo e QR referencia `documents.verify` funcionando.
- UI: refresh funciona e expiração exibida corretamente.
- Testes: suíte passa localmente usando DB de teste (sqlite in-memory) e fakes para queue.

## Cronograma
- Semana 1: Resolver token webhook de planos por tenant, ajustar logs; testes para webhooks invoices/planos.
- Semana 2: QR nativo mPDF, reforço UI de integração, testes de PDFs.
- Semana 3: Idempotência persistente de webhooks, testes finais, revisão de segurança.

## Riscos e Mitigações
- Dependência de token global: mitigar resolvendo por tenant e adicionando fallback com alerta.
- Falhas nos webhooks: ampliar logs, tempo de retry do job e validação de assinatura.
- Geração QR externa: remover com `mpdf/qrcode` e testes de renderização.

## Entregáveis
- Código ajustado com resolução de token por tenant para webhooks de planos.
- Tests de integração cobrindo webhooks, preferências e PDFs.
- UI de integração com expiração e renovação funcional.
- QR nativo em PDFs com hash e verificação pública.

Confirma para iniciar execução destes itens?