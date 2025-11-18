<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Auth;

try {
    // Buscar o usuário correto do Tenant ID 3 (User ID 3)
    $user = User::find(3);
    if (!$user) {
        echo "Usuário ID 3 não encontrado\n";
        exit;
    }
    
    echo "Usuário encontrado: {$user->name} (ID: {$user->id}, Tenant: {$user->tenant_id})\n";
    echo "Email: {$user->email}\n";
    
    // Autenticar o usuário manualmente
    Auth::login($user);
    
    // Gerar sessão
    session()->regenerate();
    
    echo "\n✓ Usuário autenticado com sucesso\n";
    echo "Session ID: " . session()->getId() . "\n";
    echo "Auth check: " . (Auth::check() ? 'Sim' : 'Não') . "\n";
    echo "User ID: " . Auth::id() . "\n";
    echo "Tenant ID: " . Auth::user()->tenant_id . "\n";
    
    // Criar arquivo com informações da sessão para teste
    $sessionInfo = [
        'session_id' => session()->getId(),
        'user_id' => Auth::id(),
        'tenant_id' => Auth::user()->tenant_id,
        'email' => Auth::user()->email,
        'name' => Auth::user()->name,
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    file_put_contents('storage/logs/session_test.json', json_encode($sessionInfo, JSON_PRETTY_PRINT));
    echo "\n✓ Informações da sessão salvas em storage/logs/session_test.json\n";
    
    // Testar acesso ao serviço
    echo "\n=== Testando acesso ao serviço ===\n";
    
    $service = \App\Models\Service::withoutGlobalScopes()->where('code', 'ORC-20251112-0003-S003')->first();
    
    if ($service) {
        echo "✓ Serviço encontrado: {$service->code}\n";
        echo "  - Tenant do serviço: {$service->tenant_id}\n";
        echo "  - Tenant do usuário: " . Auth::user()->tenant_id . "\n";
        
        if ($service->tenant_id === Auth::user()->tenant_id) {
            echo "✓ Usuário tem acesso ao tenant do serviço\n";
        } else {
            echo "✗ Usuário NÃO tem acesso ao tenant do serviço\n";
        }
    } else {
        echo "✗ Serviço não encontrado\n";
    }
    
} catch (\Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}