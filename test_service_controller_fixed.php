<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Services\Domain\ServiceService;
use Illuminate\Support\Facades\Log;

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

    // Simular o comportamento do ServiceController com relacionamentos corrigidos
    echo "\n=== Simulando ServiceController (CORRIGIDO) ===\n";

    $serviceCode = 'ORC-20251112-0003-S003';

    // Verificar se o código do serviço segue o padrão esperado
    if ( empty( $serviceCode ) || strlen( $serviceCode ) < 3 ) {
        echo "✗ Código de serviço inválido: {$serviceCode}\n";
        exit;
    }
    echo "✓ Código de serviço válido: {$serviceCode}\n";

    // Buscar o serviço usando o ServiceService com relacionamentos corrigidos
    $serviceService = app(ServiceService::class);
    $result = $serviceService->findByCode( $serviceCode, [
        'budget.customer.commonData',
        'budget.customer.contact',  // CORRIGIDO: contact (singular)
        'category',
        'serviceItems.product',
        'schedules' => function ( $q ) {
            $q->latest()->limit( 1 );
        }
    ] );

    if ( !$result->isSuccess() ) {
        echo "✗ ServiceService falhou: " . $result->getMessage() . "\n";
        if ($result->getError()) {
            echo "  - Erro: " . $result->getError()->getMessage() . "\n";
        }
        exit;
    }

    echo "✓ ServiceService encontrou o serviço\n";

    $service = $result->getData();

    // Verificar se o serviço pertence ao tenant do usuário
    $userTenantId = auth()->user()->tenant_id ?? null;
    if ( $service->tenant_id !== $userTenantId ) {
        echo "✗ Tentativa de acessar serviço de outro tenant\n";
        echo "  - Tenant do serviço: {$service->tenant_id}\n";
        echo "  - Tenant do usuário: {$userTenantId}\n";
        exit;
    }

    echo "✓ Usuário tem acesso ao tenant do serviço\n";
    echo "✓ SERVIÇO CARREGADO COM SUCESSO!\n";
    echo "\nDetalhes do serviço:\n";
    echo "- Código: {$service->code}\n";
    echo "- ID: {$service->id}\n";
    echo "- Tenant: {$service->tenant_id}\n";
    echo "- Status: {$service->status->value}\n";
    echo "- Categoria: " . ($service->category ? $service->category->name : "Sem categoria") . "\n";
    echo "- Itens de serviço: " . $service->serviceItems->count() . "\n";
    echo "- Agendamentos: " . $service->schedules->count() . "\n";
    echo "- Budget: " . ($service->budget ? "Sim" : "Não") . "\n";

    if ($service->budget && $service->budget->customer) {
        echo "  - Cliente: {$service->budget->customer->name}\n";
        echo "  - Contato: " . ($service->budget->customer->contact ? "Sim" : "Não") . "\n";
        if ($service->budget->customer->commonData) {
            echo "  - Dados comuns: Sim\n";
        }
    }

} catch (\Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}