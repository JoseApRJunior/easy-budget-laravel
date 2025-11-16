<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/bootstrap/app.php';

use Illuminate\Foundation\Application;
use Illuminate\Contracts\Console\Kernel;

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

// Criar arquivo de log
$logFile = __DIR__ . '/mercadopago_test_results.log';
$log = fopen($logFile, 'w');

function writeLog($message, $file) {
    fwrite($file, $message . "\n");
    echo $message . "\n";
}

writeLog("=== TESTE DE INTEGRAÇÃO MERCADO PAGO ===", $log);
writeLog("Data: " . date('Y-m-d H:i:s'), $log);
writeLog("Email de teste: juniorklan.ju@gmail.com", $log);
writeLog("", $log);

// Teste 1: Configuração do Mercado Pago
writeLog("1. Testando configuração do Mercado Pago...", $log);
$accessToken = env('MERCADOPAGO_ACCESS_TOKEN');
if ($accessToken) {
    writeLog("✓ Access Token configurado: " . substr($accessToken, 0, 10) . "...", $log);
} else {
    writeLog("✗ Access Token não configurado", $log);
}

// Teste 2: Configuração de Email
writeLog("", $log);
writeLog("2. Testando configuração de email...", $log);
$mailHost = env('MAIL_HOST');
$mailPort = env('MAIL_PORT');
$mailUsername = env('MAIL_USERNAME');
if ($mailHost && $mailPort && $mailUsername) {
    writeLog("✓ Email configurado: {$mailUsername} via {$mailHost}:{$mailPort}", $log);
} else {
    writeLog("✗ Configuração de email incompleta", $log);
}

// Teste 3: Buscar usuário de teste
writeLog("", $log);
writeLog("3. Buscando usuário de teste...", $log);
$user = \App\Models\User::where('email', 'juniorklan.ju@gmail.com')->first();
if ($user) {
    writeLog("✓ Usuário encontrado: {$user->name} (ID: {$user->id})", $log);
    writeLog("✓ Tenant ID: {$user->tenant_id}", $log);
    
    // Teste 4: Buscar credenciais Mercado Pago do provider
    writeLog("", $log);
    writeLog("4. Buscando credenciais Mercado Pago...", $log);
    $credentials = \App\Models\ProviderMercadoPagoCredential::where('tenant_id', $user->tenant_id)->first();
    if ($credentials) {
        writeLog("✓ Credenciais encontradas", $log);
        writeLog("✓ Access Token: " . substr($credentials->access_token, 0, 10) . "...", $log);
        writeLog("✓ Public Key: " . substr($credentials->public_key, 0, 10) . "...", $log);
    } else {
        writeLog("✗ Nenhuma credencial Mercado Pago encontrada", $log);
    }
    
    // Teste 5: Buscar planos disponíveis
    writeLog("", $log);
    writeLog("5. Buscando planos disponíveis...", $log);
    $plans = \App\Models\Plan::where('active', true)->get();
    if ($plans->count() > 0) {
        writeLog("✓ {$plans->count()} planos encontrados:", $log);
        foreach ($plans as $plan) {
            writeLog("   - {$plan->name}: R$ " . number_format($plan->price, 2, ',', '.'), $log);
        }
    } else {
        writeLog("✗ Nenhum plano ativo encontrado", $log);
    }
    
    // Teste 6: Buscar faturas
    writeLog("", $log);
    writeLog("6. Buscando faturas do usuário...", $log);
    $invoices = \App\Models\Invoice::where('tenant_id', $user->tenant_id)->get();
    if ($invoices->count() > 0) {
        writeLog("✓ {$invoices->count()} faturas encontradas", $log);
        foreach ($invoices->take(3) as $invoice) {
            writeLog("   - Fatura #{$invoice->id}: R$ " . number_format($invoice->total, 2, ',', '.') . " ({$invoice->status})", $log);
        }
    } else {
        writeLog("✗ Nenhuma fatura encontrada", $log);
    }
    
    // Teste 7: Testar criação de preferência de pagamento para plano
    writeLog("", $log);
    writeLog("7. Testando criação de preferência de pagamento para plano...", $log);
    try {
        $planService = app(\App\Services\Infrastructure\PaymentMercadoPagoPlanService::class);
        if ($plans->first() && $credentials) {
            $preference = $planService->createPaymentPreference($plans->first(), $user, $credentials);
            if ($preference && isset($preference['id'])) {
                writeLog("✓ Preferência criada com sucesso!", $log);
                writeLog("✓ ID: {$preference['id']}", $log);
                writeLog("✓ Link de pagamento: {$preference['init_point']}", $log);
                writeLog("✓ External Reference: {$preference['external_reference']}", $log);
            } else {
                writeLog("✗ Erro ao criar preferência", $log);
            }
        }
    } catch (Exception $e) {
        writeLog("✗ Erro: " . $e->getMessage(), $log);
    }
    
    // Teste 8: Testar criação de preferência de pagamento para fatura
    writeLog("", $log);
    writeLog("8. Testando criação de preferência de pagamento para fatura...", $log);
    try {
        $invoiceService = app(\App\Services\Infrastructure\PaymentMercadoPagoInvoiceService::class);
        if ($invoices->first() && $credentials) {
            $preference = $invoiceService->createPaymentPreference($invoices->first(), $credentials);
            if ($preference && isset($preference['id'])) {
                writeLog("✓ Preferência criada com sucesso!", $log);
                writeLog("✓ ID: {$preference['id']}", $log);
                writeLog("✓ Link de pagamento: {$preference['init_point']}", $log);
                writeLog("✓ External Reference: {$preference['external_reference']}", $log);
            } else {
                writeLog("✗ Erro ao criar preferência", $log);
            }
        }
    } catch (Exception $e) {
        writeLog("✗ Erro: " . $e->getMessage(), $log);
    }
    
} else {
    writeLog("✗ Usuário não encontrado", $log);
}

writeLog("", $log);
writeLog("=== TESTE FINALIZADO ===", $log);
writeLog("Verifique os resultados acima e no arquivo: {$logFile}", $log);
writeLog("Email de teste configurado: juniorklan.ju@gmail.com", $log);

fclose($log);

echo "\nResultados salvos em: {$logFile}\n";