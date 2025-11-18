<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

try {
    // Buscar o usuário correto do Tenant ID 3 (User ID 3)
    $user = User::find(3);
    if (!$user) {
        echo "Usuário ID 3 não encontrado\n";
        exit;
    }

    echo "Usuário encontrado: {$user->name} (ID: {$user->id}, Tenant: {$user->tenant_id})\n";

    // Autenticar o usuário
    auth()->login($user);
    echo "Usuário autenticado com sucesso\n";

    // Buscar o serviço
    $service = \App\Models\Service::withoutGlobalScopes()->where('code', 'ORC-20251112-0003-S003')->first();

    if (!$service) {
        echo "Serviço ORC-20251112-0003-S003 não encontrado\n";
        exit;
    }

    echo "Serviço encontrado:\n";
    echo "- Código: {$service->code}\n";
    echo "- ID: {$service->id}\n";
    echo "- Tenant ID: {$service->tenant_id}\n";
    echo "- Status: {$service->status->value}\n";

    // Verificar se o usuário tem acesso ao tenant do serviço
    if ($service->tenant_id !== $user->tenant_id) {
        echo "ERRO: Usuário (tenant: {$user->tenant_id}) não tem acesso ao serviço (tenant: {$service->tenant_id})\n";
    } else {
        echo "✓ Usuário tem acesso ao tenant do serviço\n";
    }

    // Testar ServiceService com tenant scoping correto
    echo "\nTestando ServiceService com tenant correto:\n";

    $serviceService = app(\App\Services\Domain\ServiceService::class);
    $result = $serviceService->findByCode('ORC-20251112-0003-S003', [
        'budget.customer.commonData',
        'budget.customer.contacts',
        'category',
        'serviceItems.product',
        'schedules' => function ($q) {
            $q->latest()->limit(1);
        }
    ]);

    if ($result->isSuccess()) {
        echo "✓ ServiceService encontrou o serviço com sucesso\n";
        $foundService = $result->getData();
        echo "- Serviço carregado: {$foundService->code}\n";
        echo "- Tenant do serviço: {$foundService->tenant_id}\n";
    } else {
        echo "✗ ServiceService falhou: " . $result->getMessage() . "\n";
    }

} catch (\Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}