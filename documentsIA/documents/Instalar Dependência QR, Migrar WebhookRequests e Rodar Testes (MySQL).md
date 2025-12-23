## Objetivos
- Instalar `mpdf/qrcode` e garantir QR nativo nos PDFs.
- Aplicar migração `webhook_requests` para idempotência persistente.
- Executar testes de integração com MySQL (`.env.testing`).

## Passos de Execução
1) Instalação de dependência
- Executar `composer update mpdf/qrcode` ou `composer install` para sincronizar as dependências.
- Verificar autoload e presença de `Mpdf\QrCode` em runtime.

2) Migrações
- Rodar `php artisan migrate` para criar a tabela `webhook_requests` no ambiente de teste/produção conforme necessário.

3) Testes de Integração (MySQL)
- Confirmar `.env.testing` aponta para MySQL de teste.
- Rodar `php artisan test --testsuite=Feature --filter=MercadoPagoWebhookControllerTest`.
- Rodar `php artisan test --filter=MercadoPagoIntegrationRefreshTest`.

## Verificações
- PDFs: QR gerado localmente (sem chamadas externas) nos PDFs de orçamento, serviço e fatura.
- Webhooks: entrada registrada em `webhook_requests`; duplicada retorna `ignored`; logs de segurança presentes.
- UI: expiração exibida (min/h) e feedback após refresh.

## Resultados Esperados
- Dependências atualizadas sem conflitos.
- Migrações aplicadas com sucesso.
- Suíte de testes de integração passando com MySQL.

Confirma para eu executar esses comandos (instalação, migração e testes) agora?