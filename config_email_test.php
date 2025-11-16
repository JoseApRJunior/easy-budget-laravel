<?php
/**
 * Configuração de Email para Testes com Mercado Pago
 * Configura o email de teste juniorklan.ju@gmail.com
 */

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\Config;

// Configurar email de teste
Config::set('mail.from.address', 'juniorklan.ju@gmail.com');
Config::set('mail.from.name', 'Easy Budget - Teste');
Config::set('app.url', 'https://dev.easybudget.net.br');

// Configurações adicionais para teste
Config::set('mail.mailers.smtp.host', 'live.smtp.mailtrap.io');
Config::set('mail.mailers.smtp.port', 587);
Config::set('mail.mailers.smtp.encryption', 'tls');
Config::set('mail.mailers.smtp.username', 'smtp@mailtrap.io');
Config::set('mail.mailers.smtp.password', '07cd6c814de9d7fb1b18248565682dce');

echo "✅ Configurações de email atualizadas para: juniorklan.ju@gmail.com\n";
echo "📧 Host: live.smtp.mailtrap.io\n";
echo "🔐 Porta: 587 (TLS)\n";
echo "🌐 URL do sistema: https://dev.easybudget.net.br\n\n";

// Verificar configurações do Mercado Pago
$mpConfig = config('services.mercadopago');
echo "💳 Configurações Mercado Pago:\n";
echo "- Access Token: " . substr($mpConfig['access_token'] ?? 'N/A', 0, 20) . "...\n";
echo "- App ID: " . ($mpConfig['app_id'] ?? 'N/A') . "\n";
echo "- Webhook Secret: " . substr($mpConfig['webhook_secret'] ?? 'N/A', 0, 20) . "...\n\n";

// Verificar rotas de webhook
echo "🔗 Rotas de Webhook:\n";
echo "- Planos: " . route('webhooks.mercadopago.plans') . "\n";
echo "- Faturas: " . route('webhooks.mercadopago.invoices') . "\n\n";

echo "✅ Configurações prontas para teste!\n";