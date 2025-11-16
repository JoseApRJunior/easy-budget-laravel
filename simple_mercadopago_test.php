<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/bootstrap/app.php';

use Illuminate\Foundation\Application;
use Illuminate\Contracts\Console\Kernel;

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

echo "=== TESTE DE INTEGRAÇÃO MERCADO PAGO ===\n";
echo "Email de teste: juniorklan.ju@gmail.com\n\n";

// Teste 1: Configuração do Mercado Pago
echo "1. Testando configuração do Mercado Pago...\n";
$accessToken = env('MERCADOPAGO_ACCESS_TOKEN');
if ($accessToken) {
    echo "✓ Access Token configurado: " . substr($accessToken, 0, 10) . "...\n";
} else {
    echo "✗ Access Token não configurado\n";
}

// Teste 2: Configuração de Email
echo "\n2. Testando configuração de email...\n";
$mailHost = env('MAIL_HOST');
$mailPort = env('MAIL_PORT');
$mailUsername = env('MAIL_USERNAME');
if ($mailHost && $mailPort && $mailUsername) {
    echo "✓ Email configurado: {$mailUsername} via {$mailHost}:{$mailPort}\n";
} else {
    echo "✗ Configuração de email incompleta\n";
}

// Teste 3: Buscar usuário de teste
echo "\n3. Buscando usuário de teste...\n";
$user = \App\Models\User::where('email', 'juniorklan.ju@gmail.com')->first();
if ($user) {
    echo "✓ Usuário encontrado: {$user->name} (ID: {$user->id})\n";
    echo "✓ Tenant ID: {$user->tenant_id}\n";
    
    // Teste 4: Buscar credenciais Mercado Pago do provider
    echo "\n4. Buscando credenciais Mercado Pago...\n";
    $credentials = \App\Models\ProviderMercadoPagoCredential::where('tenant_id', $user->tenant_id)->first();
    if ($credentials) {
        echo "✓ Credenciais encontradas\n";
        echo "✓ Access Token: " . substr($credentials->access_token, 0, 10) . "...\n";
        echo "✓ Public Key: " . substr($credentials->public_key, 0, 10) . "...\n";
    } else {
        echo "✗ Nenhuma credencial Mercado Pago encontrada\n";
    }
    
    // Teste 5: Buscar planos disponíveis
    echo "\n5. Buscando planos disponíveis...\n";
    $plans = \App\Models\Plan::where('active', true)->get();
    if ($plans->count() > 0) {
        echo "✓ {$plans->count()} planos encontrados:\n";
        foreach ($plans as $plan) {
            echo "   - {$plan->name}: R$ " . number_format($plan->price, 2, ',', '.') . "\n";
        }
    } else {
        echo "✗ Nenhum plano ativo encontrado\n";
    }
    
    // Teste 6: Buscar faturas
    echo "\n6. Buscando faturas do usuário...\n";
    $invoices = \App\Models\Invoice::where('tenant_id', $user->tenant_id)->get();
    if ($invoices->count() > 0) {
        echo "✓ {$invoices->count()} faturas encontradas\n";
        foreach ($invoices->take(3) as $invoice) {
            echo "   - Fatura #{$invoice->id}: R$ " . number_format($invoice->total, 2, ',', '.') . " ({$invoice->status})\n";
        }
    } else {
        echo "✗ Nenhuma fatura encontrada\n";
    }
    
    // Teste 7: Testar criação de preferência de pagamento para plano
    echo "\n7. Testando criação de preferência de pagamento para plano...\n";
    try {
        $planService = app(\App\Services\Infrastructure\PaymentMercadoPagoPlanService::class);
        if ($plans->first() && $credentials) {
            $preference = $planService->createPaymentPreference($plans->first(), $user, $credentials);
            if ($preference && isset($preference['id'])) {
                echo "✓ Preferência criada com sucesso!\n";
                echo "✓ ID: {$preference['id']}\n";
                echo "✓ Link de pagamento: {$preference['init_point']}\n";
                echo "✓ External Reference: {$preference['external_reference']}\n";
            } else {
                echo "✗ Erro ao criar preferência\n";
            }
        }
    } catch (Exception $e) {
        echo "✗ Erro: " . $e->getMessage() . "\n";
    }
    
    // Teste 8: Testar criação de preferência de pagamento para fatura
    echo "\n8. Testando criação de preferência de pagamento para fatura...\n";
    try {
        $invoiceService = app(\App\Services\Infrastructure\PaymentMercadoPagoInvoiceService::class);
        if ($invoices->first() && $credentials) {
            $preference = $invoiceService->createPaymentPreference($invoices->first(), $credentials);
            if ($preference && isset($preference['id'])) {
                echo "✓ Preferência criada com sucesso!\n";
                echo "✓ ID: {$preference['id']}\n";
                echo "✓ Link de pagamento: {$preference['init_point']}\n";
                echo "✓ External Reference: {$preference['external_reference']}\n";
            } else {
                echo "✗ Erro ao criar preferência\n";
            }
        }
    } catch (Exception $e) {
        echo "✗ Erro: " . $e->getMessage() . "\n";
    }
    
} else {
    echo "✗ Usuário não encontrado\n";
}

echo "\n=== TESTE FINALIZADO ===\n";
echo "Verifique os resultados acima para validar a integração.\n";
echo "Email de teste configurado: juniorklan.ju@gmail.com\n";