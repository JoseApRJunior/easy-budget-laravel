<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Service;
use App\Models\User;

try {
    // Buscar o usuário que está logado (User ID 2)
    $user = User::find(2);
    if (!$user) {
        echo "Usuário ID 2 não encontrado\n";
        exit;
    }

    echo "Usuário encontrado: {$user->name} (ID: {$user->id}, Tenant: {$user->tenant_id})\n";

    // Autenticar o usuário
    auth()->login($user);
    echo "Usuário autenticado com sucesso\n";

    // Buscar o serviço
    $service = Service::withoutGlobalScopes()->where('code', 'ORC-20251112-0003-S003')->first();

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

    // Testar carregamento de relacionamentos
    echo "\nTestando carregamento de relacionamentos:\n";

    try {
        $service->load(['budget.customer.commonData', 'budget.customer.contacts', 'category', 'serviceItems.product', 'schedules']);
        echo "✓ Todos os relacionamentos carregados com sucesso\n";

        echo "- Budget: " . ($service->budget ? "Sim" : "Não") . "\n";
        echo "- Category: " . ($service->category ? $service->category->name : "Não") . "\n";
        echo "- Service Items: " . $service->serviceItems->count() . " itens\n";
        echo "- Schedules: " . $service->schedules->count() . " agendamentos\n";

    } catch (\Exception $e) {
        echo "ERRO ao carregar relacionamentos: " . $e->getMessage() . "\n";
        echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    }

    echo "\nTestando ServiceService:\n";

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
    } else {
        echo "✗ ServiceService falhou: " . $result->getMessage() . "\n";
    }

} catch (\Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
