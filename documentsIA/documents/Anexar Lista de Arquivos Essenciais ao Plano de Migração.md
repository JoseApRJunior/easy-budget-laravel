## Objetivo

Adicionar ao final do Plano de Migração uma lista prática dos arquivos essenciais por módulo, para orientar e evitar perdas de contexto durante a execução.

## Arquivos Essenciais por Módulo

### Integração Mercado Pago (OAuth/Preferências)

* `app/Http/Controllers/Integrations/MercadoPagoController.php` — UI/fluxos de conexão, refresh e desconexão

* `app/Services/Infrastructure/MercadoPagoOAuthService.php` — troca/renovação de tokens

* `app/Models/ProviderCredential.php` — armazenamento criptografado de credenciais

* `app/Services/Infrastructure/EncryptionService.php` — (de/en)criptação

* `app/Services/Infrastructure/PaymentMercadoPagoPlanService.php` — preferência de pagamento para planos

* `app/Http/Controllers/PlanController.php` — criação de `PlanSubscription` e redirect

* `config/services.php` — chaves de OAuth/segredo de webhook

### Webhooks Mercado Pago

* `app/Http/Controllers/MercadoPagoWebhookController.php` — endpoints e segurança (`X-Request-Id`, `X-Signature`)

* `app/Jobs/ProcessMercadoPagoWebhook.php` — processamento assíncrono e marcação de idempotência

* `app/Services/Infrastructure/Payment/MercadoPagoWebhookService.php` — lógica de resolução de token e atualização de estados

* `app/Models/PaymentMercadoPagoInvoice.php`, `app/Models/PaymentMercadoPagoPlan.php` — registros de pagamentos

* `app/Models/WebhookRequest.php` — idempotência persistente

* `database/migrations/2025_11_15_000001_create_webhook_requests_table.php` — tabela de idempotência

### PDFs + QR + Verificação

* `app/Services/Infrastructure/QrCodeService.php` — geração de QR (nativo mPDF com fallback)

* `app/Services/Infrastructure/BudgetPdfService.php` — PDF de orçamento (mPDF)

* `app/Services/Infrastructure/ServicePdfService.php` — PDF de serviço público (mPDF)

* `app/Services/Domain/InvoiceService.php` — PDF de fatura (mPDF), `public_hash` e token de confirmação

* `app/Http/Controllers/BudgetController.php` — geração/armazenamento do hash e re-render com QR

* `app/Http/Controllers/ServiceController.php` — PDF público com hash/QR

* `resources/views/budgets/pdf.blade.php` — rodapé com QR/link `documents.verify`

* `resources/views/invoices/pdf.blade.php` — QR público da fatura

* `resources/views/pages/service/public/print.blade.php` — QR e link de verificação

* `composer.json` — dependência `mpdf/qrcode`

### Relatórios (Reports)

* `app/Http/Controllers/ReportController.php` — navegação, geração PDF (mPDF) e download

* `app/Services/Domain/ReportService.php` — solicitação, download e estatísticas

* `app/Repositories/ReportRepository.php` — filtros, busca por hash, recentes

* `resources/views/pages/report/*` — páginas de filtros por módulo

* `resources/views/pages/report/budget/pdf_budget.blade.php` — PDF orçamento (corrigir layout para `layouts.pdf_base`)

* `app/Http/Controllers/Api/ReportApiController.php` — API avançada (ajustar `ExportService` para `Application`)

* `app/Services/Application/ExportService.php` — exportações PDF/Excel/CSV

### Uploads e AJAX/CEP

* `app/Services/Application/FileUploadService.php` — processamento de imagem e upload

* `app/Http/Controllers/UploadController.php` — endpoint de upload

* `app/Http/Controllers/Api/AjaxController.php` — filtros/buscas e CEP (BrasilAPI)

### Verificação de Documentos

* `app/Services/Domain/DocumentVerificationService.php` — verificação por hash

* `app/Http/Controllers/DocumentVerificationController.php` — página pública e integração

* `resources/views/pages/document/verify.blade.php` — UI pública de verificação

### Páginas Públicas de Fatura/Serviço

* `app/Http/Controllers/PublicInvoiceController.php` — visualização e redirect para pagamento

* `app/Services/Infrastructure/PaymentMercadoPagoInvoiceService.php` — preferência de fatura

### Modelos de Assinatura/Planos

* `app/Models/PlanSubscription.php`, `app/Models/Provider.php`

### Núcleo (Rotas/Controller Base)

* `routes/web.php` — grupos `reports.*`, `webhooks.*`, `integrations.*` e rotas públicas

* `routes/api.php` — rotas de AJAX autenticadas

* `app/Http/Controllers/Controller.php` — base (herança)

## Como Usar no Plano

* Anexar esta lista ao final do plano mestre em `\.trae\documents\Plano de Migração do Sistema Legado para Laravel.md` sob a seção “Arquivos Essenciais por Módulo”.

* Marcar cada módulo como concluído conforme migração, mantendo observações de pendências e apontando testes que serão feitos ao final.

## Próxima Ação

* Anexar esta lista ao plano e prosseguir para a migração das “Páginas Públicas de Planos (status)” e “Produtos (CRUD + Imagens)”, seguindo a ordem priorizada.

