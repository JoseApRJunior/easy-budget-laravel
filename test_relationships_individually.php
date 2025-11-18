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

    echo "\n=== Testando relacionamentos individualmente ===\n";

    $serviceService = app(ServiceService::class);

    // Testar cada relacionamento separadamente
    $relationships = [
        'category' => 'Categoria',
        'serviceItems' => 'Itens de serviço',
        'serviceItems.product' => 'Itens de serviço com produto',
        'budget' => 'Budget',
        'budget.customer' => 'Budget com cliente',
        'budget.customer.commonData' => 'Budget com cliente e dados comuns',
        'budget.customer.contacts' => 'Budget com cliente e contatos',
        'schedules' => 'Agendamentos'
    ];

    foreach ($relationships as $relation => $description) {
        echo "\nTestando: {$description} ({$relation})\n";
        try {
            $result = $serviceService->findByCode('ORC-20251112-0003-S003', [$relation]);

            if ($result->isSuccess()) {
                echo "✓ Sucesso - {$description}\n";
                $service = $result->getData();

                // Verificar dados carregados
                switch ($relation) {
                    case 'category':
                        echo "  - Categoria: " . ($service->category ? $service->category->name : "Nenhuma") . "\n";
                        break;
                    case 'serviceItems':
                        echo "  - Quantidade: " . $service->serviceItems->count() . "\n";
                        break;
                    case 'serviceItems.product':
                        echo "  - Quantidade: " . $service->serviceItems->count() . "\n";
                        if ($service->serviceItems->count() > 0) {
                            foreach ($service->serviceItems as $item) {
                                echo "    - Item: " . ($item->product ? $item->product->name : "Sem produto") . "\n";
                            }
                        }
                        break;
                    case 'budget':
                        echo "  - Budget existe: " . ($service->budget ? "Sim" : "Não") . "\n";
                        break;
                    case 'budget.customer':
                        echo "  - Cliente: " . ($service->budget && $service->budget->customer ? $service->budget->customer->name : "Sem cliente") . "\n";
                        break;
                    case 'budget.customer.commonData':
                        echo "  - Cliente: " . ($service->budget && $service->budget->customer ? $service->budget->customer->name : "Sem cliente") . "\n";
                        if ($service->budget && $service->budget->customer && $service->budget->customer->commonData) {
                            echo "  - Dados comuns carregados\n";
                        }
                        break;
                    case 'budget.customer.contacts':
                        echo "  - Cliente: " . ($service->budget && $service->budget->customer ? $service->budget->customer->name : "Sem cliente") . "\n";
                        echo "  - Contatos: " . ($service->budget && $service->budget->customer ? $service->budget->customer->contacts->count() : 0) . "\n";
                        break;
                    case 'schedules':
                        echo "  - Agendamentos: " . $service->schedules->count() . "\n";
                        break;
                }
            } else {
                echo "✗ Falha - {$description}\n";
                echo "  - Mensagem: " . $result->getMessage() . "\n";
                if ($result->getError()) {
                    echo "  - Erro: " . $result->getError()->getMessage() . "\n";
                }
            }
        } catch (\Exception $e) {
            echo "✗ Exceção - {$description}\n";
            echo "  - Erro: " . $e->getMessage() . "\n";
        }
    }

    echo "\n=== Testando combinações ===\n";

    // Testar combinações que funcionam
    $combinations = [
        ['category', 'serviceItems.product'],
        ['budget.customer', 'category'],
        ['budget.customer.contacts', 'category', 'serviceItems.product']
    ];

    foreach ($combinations as $combo) {
        echo "\nTestando combinação: " . implode(', ', $combo) . "\n";
        try {
            $result = $serviceService->findByCode('ORC-20251112-0003-S003', $combo);

            if ($result->isSuccess()) {
                echo "✓ Sucesso\n";
            } else {
                echo "✗ Falha: " . $result->getMessage() . "\n";
                if ($result->getError()) {
                    echo "  - Erro: " . $result->getError()->getMessage() . "\n";
                }
            }
        } catch (\Exception $e) {
            echo "✗ Exceção: " . $e->getMessage() . "\n";
        }
    }

} catch (\Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}