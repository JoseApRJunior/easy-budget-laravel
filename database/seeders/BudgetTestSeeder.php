<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\BudgetStatus;
use App\Enums\InvoiceStatus;
use App\Enums\ServiceStatus;
use App\Models\Budget;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Provider;
use App\Models\Service;
use App\Models\ServiceItem;
use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeder para criar dados de teste de budgets com services e invoices.
 *
 * Cria 3 budgets por provider, cada um com 3 services em status diferentes,
 * cada service com 3 products, e services finalizados com faturas geradas.
 */
class BudgetTestSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🏢 Criando dados de teste de budgets...');

        // Buscar apenas providers dos tenants a partir do ID 3
        $providers = Provider::with('tenant')->where('tenant_id', '>=', 3)->get();

        if ($providers->isEmpty()) {
            $this->command->warn('⚠️  Nenhum provider encontrado. Execute ProviderTestSeeder primeiro.');
            return;
        }

        // Contadores globais para códigos únicos
        $globalBudgetCounter  = 1;
        $globalInvoiceCounter = 1;

        foreach ($providers as $provider) {
            $this->createBudgetsForProvider($provider, $globalBudgetCounter, $globalInvoiceCounter);
        }

        $this->command->info('✅ Dados de teste de budgets criados com sucesso!');
    }

    private function createBudgetsForProvider(Provider $provider, int &$globalBudgetCounter, int &$globalInvoiceCounter): void
    {
        $tenant    = $provider->tenant;
        $customers = Customer::where('tenant_id', $tenant->id)->get();

        if ($customers->isEmpty()) {
            $this->command->warn("⚠️  Nenhum cliente encontrado para o tenant {$tenant->id}");
            return;
        }

        // Criar 2 budgets por provider
        for ($i = 1; $i <= 2; $i++) {
            $customer = $customers->random();
            $this->createBudgetWithServices($provider, $tenant, $customer, $i, $globalBudgetCounter, $globalInvoiceCounter);
        }

        $this->command->info("   ✓ 2 budgets criados para provider {$provider->id} ({$tenant->name})");
    }

    private function createBudgetWithServices(Provider $provider, Tenant $tenant, Customer $customer, int $budgetIndex, int &$globalBudgetCounter, int &$globalInvoiceCounter): void
    {
        DB::transaction(function () use ($provider, $tenant, $customer, $budgetIndex, &$globalBudgetCounter, &$globalInvoiceCounter) {
            // Usar globalBudgetCounter para código sequencial único
            $budgetDate       = now()->format('Ymd');
            $budgetSequential = str_pad((string) $globalBudgetCounter, 4, '0', STR_PAD_LEFT);
            $budgetCode       = "ORC-{$budgetDate}-{$budgetSequential}";

            // Criar budget
            $budget = Budget::create([
                'tenant_id'   => $tenant->id,
                'customer_id' => $customer->id,
                'code'        => $budgetCode,
                'total'       => 0, // Será calculado depois
                'discount'    => 0,
                'status'      => BudgetStatus::DRAFT->value,
                'due_date'    => now()->addDays(rand(7, 30)),
            ]);

            $totalBudget = 0;

            // Criar 2 services: um COMPLETED e um APPROVED
            $serviceStatuses = [
                ServiceStatus::COMPLETED,
                ServiceStatus::APPROVED,
            ];

            $categories = Category::all(); // Categorias são globais
            if ($categories->isEmpty()) {
                // Criar uma categoria global se não existir nenhuma
                $category   = Category::create([
                    'name' => 'Categoria Teste',
                    'slug' => 'categoria-teste',
                    'is_active' => true,
                ]);
                $categories = collect([$category]);
            }

            foreach ($serviceStatuses as $index => $status) {
                $serviceTotal  = $this->createServiceWithItems($budget, $tenant, $categories->random(), $status, $index + 1, $budgetCode);
                $totalBudget  += $serviceTotal;
            }

            $serviceCompleted = $budget->services()->where('status', ServiceStatus::COMPLETED->value)->first();
            $serviceApproved  = $budget->services()->where('status', ServiceStatus::APPROVED->value)->first();

            if ($serviceCompleted) {
                $this->createInvoiceForService($budget, $tenant, $customer, $serviceCompleted, $budgetDate, $globalInvoiceCounter, true);
                $globalInvoiceCounter++;
            }

            if ($serviceApproved) {
                $this->createInvoiceForService($budget, $tenant, $customer, $serviceApproved, $budgetDate, $globalInvoiceCounter, false);
                $globalInvoiceCounter++;
            }

            // Atualizar total do budget
            $budget->update(['total' => $totalBudget]);
            $globalBudgetCounter++; // Incrementar contador de budget
        });
    }

    private function createServiceWithItems(Budget $budget, Tenant $tenant, Category $category, ServiceStatus $status, int $serviceIndex, string $budgetCode): float
    {
        // Gerar código de serviço seguindo padrão YYYYMMDD-0001-S001
        $serviceCode = "{$budgetCode}-S" . str_pad((string) $serviceIndex, 3, '0', STR_PAD_LEFT);

        // Criar service
        $service = Service::create([
            'tenant_id'   => $tenant->id,
            'budget_id'   => $budget->id,
            'category_id' => $category->id,
            'code'        => $serviceCode,
            'description' => "Serviço de teste {$serviceIndex} para orçamento {$budget->code}",
            'status'      => $status->value,
            'discount'    => 0,
            'total'       => 0, // Será calculado
            'due_date'    => now()->addDays(rand(1, 30)),
        ]);

        $totalService = 0;

        // Criar 3 produtos para este service
        $products = Product::where('tenant_id', $tenant->id)->inRandomOrder()->limit(3)->get();

        // Se não há produtos suficientes, criar alguns
        if ($products->count() < 3) {
            for ($i = $products->count(); $i < 3; $i++) {
                $product = Product::create([
                    'tenant_id'   => $tenant->id,
                    'category_id' => $category->id,
                    'name'        => "Produto Teste {$i}",
                    'description' => "Produto criado para teste",
                    'sku'         => "PRD-{$tenant->id}-{$i}",
                    'price'       => rand(10, 500),
                    'unit'        => 'un',
                    'active'      => true,
                ]);
                $products->push($product);
            }
        }

        foreach ($products as $index => $product) {
            $quantity  = rand(1, 10);
            $unitValue = (float) $product->price;
            $total     = $quantity * $unitValue;

            ServiceItem::create([
                'tenant_id'  => $tenant->id,
                'service_id' => $service->id,
                'product_id' => $product->id,
                'unit_value' => $unitValue,
                'quantity'   => $quantity,
                'total'      => $total,
            ]);

            $totalService  += $total;
        }

        // Atualizar total do service
        $service->update(['total' => $totalService]);

        return $totalService;
    }

    private function createInvoiceForService(Budget $budget, Tenant $tenant, Customer $customer, Service $service, string $budgetDate, int $invoiceCounter, bool $isPartial): void
    {
        // Gerar código de fatura seguindo padrão FAT-YYYYMMDD-0001
        $invoiceSequential = str_pad((string) $invoiceCounter, 4, '0', STR_PAD_LEFT);
        $invoiceCode       = "FAT-{$budgetDate}-{$invoiceSequential}";

        $subtotal = $service->total;
        $discount = $isPartial ? $service->total * 0.5 : 0;
        $total    = $subtotal - $discount;

        Invoice::create([
            'tenant_id'      => $tenant->id,
            'service_id'     => $service->id,
            'customer_id'    => $customer->id,
            'code'           => $invoiceCode,
            'subtotal'       => $subtotal,
            'discount'       => $discount,
            'total'          => $total,
            'due_date'       => now()->addDays(rand(7, 30)),
            'payment_method' => collect(['pix', 'boleto', 'cartao'])->random(),
            'status'         => InvoiceStatus::PENDING->value,
        ]);
    }
}
