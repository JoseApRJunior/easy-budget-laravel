## Objetivos
- Habilitar QR nativo mPDF em todos os PDFs (orçamento, serviço, fatura) e remover dependência de chamadas externas.
- Aprimorar UI da integração Mercado Pago com expiração amigável e feedback após renovação.
- Adicionar testes de integração cobrindo PDFs com QR e rotas de integração (refresh/desconectar).

## Alterações Planejadas
### 1) QR nativo mPDF
- Adicionar `mpdf/qrcode` no `composer.json` (require).
- Atualizar `app/Services/Infrastructure/QrCodeService.php` para priorizar `Mpdf\QrCode\QrCode + Output\Png` e manter fallback HTTP caso lib ausente.
- Verificar geração de QR em:
  - Orçamento: `BudgetController::print` e `resources/views/budgets/pdf.blade.php`.
  - Serviço: `ServiceController::print` e `resources/views/pages/service/public/print.blade.php`.
  - Fatura: `InvoiceService::generateInvoicePdf` e `resources/views/invoices/pdf.blade.php`.

### 2) UI de Expiração Mercado Pago
- Controller `Integrations/MercadoPagoController@index`: calcular `expires_readable` (minutos/horas) a partir de `expires_in` e enviar à view.
- View `pages/mercadopago/index.blade.php`: exibir `expires_readable`; mostrar mensagens de feedback (sucesso/erro) após `refresh`; desabilitar botão quando desconectado.
- Controller `refresh`: manter logs e mensagens amigáveis; atualizar `expires_in` junto aos tokens.

### 3) Testes
- PDFs: criar testes que geram HTML/PDF e afirmam presença de QR (match por `data:image/png;base64` ou marcação SVG quando aplicável).
- Integração: testes para `refresh` (mock Http), confirmação de atualização de tokens e feedback na UI.
- Manter execução com MySQL de testes conforme seu `.env` de teste atual.

## Critérios de Aceitação
- PDFs: todos incluem QR gerado localmente e hash/links válidos de verificação.
- UI: expiração exibida de forma amigável, renovação com feedback visível.
- Testes: passam no ambiente de testes configurado (MySQL).

## Cronograma
- Dia 1: Adicionar `mpdf/qrcode` e ajustar `QrCodeService`; validar PDFs.
- Dia 2: Ajustes de UI (`expires_readable`, feedback) e testes de integração.
- Dia 3: Revisão final e limpeza.

Confirma para iniciar a execução desta fase?