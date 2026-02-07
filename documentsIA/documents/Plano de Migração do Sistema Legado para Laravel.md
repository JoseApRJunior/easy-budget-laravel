## Inventário Completo (documentsIA/old-sistem)
- `RELATORIO_ANALISE_MERCADOPAGO_CONTROLLER.md` — tipo: documentação técnica (pagamentos); prioridade: alta; complexidade: alta; destino: `app/Services/Infrastructure/Payments`, `app/Http/Controllers/Integrations/MercadoPagoController`, webhooks.
- `RELATORIO_ANALISE_WEBHOOK_CONTROLLER.md` — tipo: documentação técnica (webhooks MP); prioridade: alta; complexidade: alta; destino: `app/Http/Controllers/Webhooks/WebhookController`, serviços de planos e faturas.
- `RELATORIO_ANALISE_PAYMENT_CONTROLLER.md` — tipo: documentação técnica (processamento); prioridade: alta; complexidade: média/alta; destino: `app/Services/Domain/PaymentService`, repositórios e integração MP.
- `RELATORIO_ANALISE_PROVIDER_CONTROLLER.md` — tipo: documentação técnica (tenant/provider); prioridade: média; complexidade: média; destino: `app/Http/Controllers/Provider/*`, `app/Services/Domain/ProviderService`.
- `RELATORIO_ANALISE_PUBLIC_INVOICE_CONTROLLER.md` — tipo: documentação técnica (público); prioridade: média; complexidade: baixa; destino: `app/Http/Controllers/Public/InvoiceController`, views públicas `resources/views/pages/public/invoice/*`.
- `RELATORIO_ANALISE_SETTINGS_CONTROLLER.md` — tipo: documentação técnica (configurações); prioridade: média; complexidade: média; destino: `app/Http/Controllers/SettingsController`, `config/*`, `app/Services/Application/SettingsService`.
- `RELATORIO_ANALISE_SUPPORT_CONTROLLER.md` — tipo: documentação técnica (suporte/helpdesk); prioridade: baixa/média; complexidade: média; destino: `app/Http/Controllers/SupportController`, `app/Services/Domain/SupportService`.
- `RELATORIO_DOCUMENT_VERIFICATION_CONTROLLER.md` — tipo: documentação técnica (verificação documentos); prioridade: média; complexidade: baixa; destino: `app/Http/Controllers/Public/DocumentVerificationController`, `app/Services/Application/DocumentVerificationService`.
- `RELATORIO_HOME_CONTROLLER.md` — tipo: documentação técnica (landing pública); prioridade: alta; complexidade: baixa; destino: `app/Http/Controllers/HomeController`, views públicas `resources/views/pages/home/*`.
- `RELATORIO_INVOICES_CONTROLLER.md` — tipo: documentação técnica (geração faturas); prioridade: alta; complexidade: média; destino: `app/Http/Controllers/InvoiceController` (ampliação), `app/Services/Domain/InvoiceService`.
- `RELATORIO_MODEL_REPORT_CONTROLLER.md` — tipo: documentação técnica (logging relatórios); prioridade: baixa; complexidade: muito baixa; destino: eventos/listeners `app/Events/ReportGenerated`, `app/Listeners/LogReportGeneration`.
- `RELATORIO_QRCODE_CONTROLLER.md` — tipo: documentação técnica (QR Code); prioridade: baixa; complexidade: baixa; destino: `app/Http/Controllers/QrCodeController`, `app/Services/Infrastructure/QrCodeService`.
- `RELATORIO_UPLOAD_CONTROLLER.md` — tipo: documentação técnica (upload/imagem); prioridade: alta; complexidade: média; destino: `app/Http/Controllers/UploadController`, `app/Services/Infrastructure/ImageProcessingService`, `config/upload.php`.
- `RELATORIO_AJAX_CONTROLLER.md` — tipo: documentação técnica (endpoints AJAX); prioridade: média; complexidade: baixa; destino: `routes/api.php`, `app/Http/Controllers/Api/AjaxController`, `app/Services/Application/FilterService`.
- `RELATORIO_ANALISE_PUBLIC_INVOICE_CONTROLLER.md` — já listado; confirmar vínculo com MP.
- `WEBHOOK_IMPLEMENTATION_GUIDE.md` — tipo: guia; prioridade: alta (apoio); complexidade: baixa; destino: aplicar requisitos no `WebhookController` e serviços.

Obs.: Os arquivos no diretório são relatórios (.md) que mapeiam módulos do sistema legado (Twig + DoctrineDBAL). A migração consiste em implementar esses módulos em Laravel conforme arquitetura definida no memory-bank.

## Estratégia de Migração (alinhada a documentsIA/migrate laravel)
- Sequência por dependências técnicas
  - Fase 1 (base e público): `HomeController`, `UploadController`, `InvoicesController` (integração ao `InvoiceController` existente).
  - Fase 2 (infra de APIs): `AjaxController`, `DocumentVerificationController`.
  - Fase 3 (complementos): `QrCodeController`, eventos de `ModelReportController`.
  - Fase 4 (pagamentos): `MercadoPagoController`, `WebhookController`, `PaymentService`, `PublicInvoiceController` (URLs públicas e status), `SettingsController`/`ProviderController` (credenciais, OAuth, multi-tenant).
- Padrões de nomenclatura
  - Controllers: `App\Http\Controllers\{Context}\{Nome}Controller` (ex.: `Public\InvoiceController`, `Webhooks\WebhookController`).
  - Services: `App\Services\{Domain|Application|Infrastructure}\{Nome}Service`.
  - Repositories: `App\Repositories\{Nome}Repository` estendendo `AbstractTenantRepository`.
  - Models: singular (`Product`, `Invoice`); tabelas plural (`products`, `invoices`); códigos únicos normalizados (`{{UNIQUE_CODE_FIELD}}` como `code`/`sku` conforme módulo).
  - Views: `resources/views/pages/{modulo}/{index|create|edit|show}.blade.php` e públicas em `resources/views/pages/public/*`.
- Estrutura de diretórios de destino
  - Conforme `memory-bank/architecture.md`: Controllers → Services → Repositories → Models; views modulares em `resources/views/pages/*`; traits multi-tenant e middleware de tenant.
  - Integração Mercado Pago: `app/Http/Controllers/Integrations/MercadoPagoController`, `app/Http/Controllers/Webhooks/WebhookController`, `app/Services/Infrastructure/Payments/*`, credenciais em models `ProviderCredential`.
- Alinhamento com `documentsIA/migrate laravel`
  - Reutilizar relatórios `RELATORIO_ANALISE_*` já existentes (Budget, Customer, Invoice, Plan, Product, Report, Service).
  - Aplicar o `PATTERN_PROMPTS_MIGRACAO_MODULO.md` para novos módulos (Ajax, Upload, QrCode, Webhook, MercadoPago, PublicInvoice, Settings, Provider, Support, DocumentVerification, Invoices, Home).

## Plano dos Próximos Prompts (documentsIA/migrate laravel/PROMPTS)
- Template para novos prompts
  - Usar `PATTERN_PROMPTS_MIGRACAO_MODULO.md` com tokens: `{{MODULE_NAME}}`, `{{Module}}`, `{{Repository}}`, `{{Service}}`, `{{TABLE_NAME}}`, `{{UNIQUE_CODE_FIELD}}`, `{{RELATIONS}}`.
  - Estruturar prompts em grupos: (1) Migration/Model/Factory; (2) Repository (`getPaginated`, `findByCode`); (3) Services (Domínio/Aplicação/Infra); (4) Controller; (5) Views & Rotas; (6) Testes.
- Nomes dos arquivos de prompt
  - `PROMPTS_DETALHADOS_MIGRACAO_AJAX.md`, `..._UPLOAD.md`, `..._QRCODE.md`, `..._WEBHOOK.md`, `..._MERCADOPAGO.md`, `..._PUBLIC_INVOICE.md`, `..._SETTINGS.md`, `..._PROVIDER.md`, `..._SUPPORT.md`, `..._DOCUMENT_VERIFICATION.md`, `..._INVOICES.md`, `..._HOME.md`.
- Critérios de priorização
  - Impacto no fluxo core (faturas, pagamentos, upload).
  - Dependências entre módulos (webhook depende de serviços de pagamento e de fatura; público de fatura depende de MP e de invoices).
  - Complexidade técnica e risco (OAuth/assinaturas, idempotência, segurança).
  - Cobertura de testes e esforço.
- Metodologia de teste e validação
  - Unit tests: repositories (filtros, `findByCode`), services (regras de negócio, idempotência).
  - Integration tests: controllers (rotas, middleware `tenant`, autenticação), views (renderização), webhooks (assinatura `X-Signature`).
  - Sandbox MP: simular `approved`, `pending`, `in_process`, `cancelled`, `refunded` e validar mapeamentos.
  - Upload/Imagem: testar MIME real, redimensionamento, watermark, limites de tamanho, storage link.
  - APIs públicas: rate limiting, validação de entradas, sanitização.
  - Qualidade: PSR-12, Larastan/PHPStan, logs/auditoria, análise de segurança.

## Cronograma Estimado, Marcos e Critérios de Aceitação
- Semana 1 — Base Pública e Upload
  - Entregas: `HomeController` + views; `UploadController` + `ImageProcessingService` + `config/upload.php`.
  - Critérios: rotas públicas funcionais; upload com resize e watermark; storage configurado; testes de controller e service passando; PSR-12 e análise estática ok.
- Semana 2 — Faturas e Geração
  - Entregas: ampliar `InvoiceController` com criação completa/parcial; número de fatura; validações de saldo.
  - Critérios: criação de fatura a partir de orçamento; regras de negócio validadas; testes de serviço e controller; auditoria de atividades.
- Semana 3 — AJAX e Verificação
  - Entregas: `AjaxController` (filtros e CEP), `DocumentVerificationController` (hash público).
  - Critérios: endpoints protegidos com `auth:sanctum` + `tenant`; rate limiting; CEP via HTTP client; verificação de documentos em múltiplas tabelas; testes e cobertura mínima.
- Semana 4 — QR Code e Eventos
  - Entregas: `QrCodeController` + `QrCodeService`; eventos `ReportGenerated` + listener `LogReportGeneration`.
  - Critérios: geração/leitura de QR; integração com PDFs onde aplicável; event-driven funcionando; testes unitários.
- Semana 5 — Pagamentos (Parte 1: Integração)
  - Entregas: `MercadoPagoController` (index/callback/disconnect), credenciais de provider; OAuth completo.
  - Critérios: fluxo OAuth concluído; tokens criptografados; credenciais válidas por tenant; testes de integração com sandbox.
- Semana 6 — Pagamentos (Parte 2: Webhooks & Público)
  - Entregas: `WebhookController` (invoices/plans), serviços `PaymentMercadoPagoInvoiceService` e `PaymentMercadoPagoPlanService`, `PublicInvoiceController`.
  - Critérios: idempotência, validação `X-Signature`, transações, atualização de `invoices`/`plan_subscriptions`; páginas de status público e redirecionamento; cobertura de testes robusta.
- Semana 7 — Settings/Provider/Support
  - Entregas: `SettingsController` (config do sistema), `ProviderController` (gestão tenant/credenciais), `SupportController`.
  - Critérios: CRUDs estáveis; permissões/roles aplicadas; auditoria; testes e compliance.

## Verificações de Qualidade por Fase
- Padrões: PSR-12, documentação mínima em docblocks, nomes consistentes.
- Segurança: middleware `tenant`, validações de inputs, MIME real no upload, OAuth seguro, webhook com assinatura e idempotência, rate limiting.
- Observabilidade: logs estruturados (categoria/severidade), auditoria por operação, métricas básicas.
- Testes: cobertura mínima por módulo (services >70%, controllers >60%), testes para casos de erro.
- Revisão: checklist de arquitetura (Controller → Service → Repository), consistência de views, rotas e nomenclatura.

## Notas de Consistência com o Projeto
- Seguir estrutura e padrões descritos em `.kilocode/rules/memory-bank/architecture.md` e `brief.md`.
- Usar o padrão de prompts e análises já presente em `documentsIA/migrate laravel/PROMPTS` e `RELATORIO_ANALISE_*`.
- Consolidar códigos únicos (ex.: `PROD000001`) para campos `code`/`sku` conforme módulo, com unicidade por `tenant_id`.
- Manter multi-tenant em todas as entidades (trait `TenantScoped`, escopos, middleware).
- Validar cada entrega com testes, análise estática e auditoria de logs.