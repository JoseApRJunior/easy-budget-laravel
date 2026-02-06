<?php

declare(strict_types=1);

// Verifica se já estamos dentro do Laravel (via rota) ou se é execução direta (CLI)
if (!defined('LARAVEL_START')) {
    require __DIR__.'/vendor/autoload.php';
    $app = require_once __DIR__.'/bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
}

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

echo "--- DIAGNÓSTICO DE E-MAIL (PRODUÇÃO) ---\n";
echo 'Data: '.date('Y-m-d H:i:s')."\n";
echo 'Ambiente (APP_ENV): '.app()->environment()."\n";
echo 'Mailer Default: '.Config::get('mail.default')."\n";

$defaultMailer = Config::get('mail.default');
$activeConfig = Config::get("mail.mailers.{$defaultMailer}");
echo "Configuração do Mailer Ativo ($defaultMailer):\n";
echo '  Transport: '.($activeConfig['transport'] ?? 'N/A')."\n";
echo '  Host: '.($activeConfig['host'] ?? 'N/A')."\n";
echo '  Port: '.($activeConfig['port'] ?? 'N/A')."\n";
echo '  User: '.($activeConfig['username'] ?? 'N/A')."\n";
echo '  Encryption: '.($activeConfig['encryption'] ?? $activeConfig['scheme'] ?? 'N/A')."\n";
echo '  From Address: '.Config::get('mail.from.address')."\n";
echo '  From Name: '.Config::get('mail.from.name')."\n";

echo "\nTentando enviar e-mail de teste...\n";

try {
    $result = Mail::raw('Este é um teste de diagnóstico de e-mail do Easy Budget.', function ($message) {
        $message->to('juniorklan.ju@gmail.com')
            ->subject('Diagnóstico SMTP - Easy Budget');
    });

    echo "SUCESSO: O Laravel reportou que o e-mail foi enviado.\n";
    echo "Verifique a caixa de entrada (incluindo SPAM).\n";
} catch (\Throwable $e) {
    echo "ERRO FATAL: Falha ao enviar e-mail.\n";
    echo 'Mensagem: '.$e->getMessage()."\n";
    echo 'Arquivo: '.$e->getFile().' na linha '.$e->getLine()."\n";

    Log::error('Erro no diagnóstico de e-mail: '.$e->getMessage(), [
        'exception' => $e,
    ]);
}

echo "\n--- FIM DO DIAGNÓSTICO ---\n";
