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
 * Cria 5 budgets por provider, cada um com 5 services em status diferentes,
 * cada service com 5 products, e services finalizados com faturas geradas.
 */
class BudgetTestSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info( '🏢 Criando dados de teste de budgets...' );

        // Buscar todos os providers criados pelo ProviderTestSeeder
        $providers = Provider::with( 'tenant' )->get();

        if ( $providers->isEmpty() ) {
            $this->command->warn( '⚠️  Nenhum provider encontrado. Execute ProviderTestSeeder primeiro.' );
            return;
        }

        foreach ( $providers as $provider ) {
            $this->createBudgetsForProvider( $provider );
        }

        $this->command->info( '✅ Dados de teste de budgets criados com sucesso!' );
    }

    private function createBudgetsForProvider( Provider $provider ): void
    {
        $tenant    = $provider->tenant;
        $customers = Customer::where( 'tenant_id', $tenant->id )->get();

        if ( $customers->isEmpty() ) {
            $this->command->warn( "⚠️  Nenhum cliente encontrado para o tenant {$tenant->id}" );
            return;
        }

        // Criar 5 budgets por provider
        for ( $i = 1; $i <= 5; $i++ ) {
            $customer = $customers->random();
            $this->createBudgetWithServices( $provider, $tenant, $customer, $i );
        }

        $this->command->info( "   ✓ 5 budgets criados para provider {$provider->id} ({$tenant->name})" );
    }

    private function createBudgetWithServices( Provider $provider, Tenant $tenant, Customer $customer, int $budgetIndex ): void
    {
        DB::transaction( function () use ($provider, $tenant, $customer, $budgetIndex) {
            // Criar budget
            $budget = Budget::create( [
                'tenant_id'   => $tenant->id,
                'customer_id' => $customer->id,
                'code'        => "BUD-{$tenant->id}-{$budgetIndex}",
                'total'       => 0, // Será calculado depois
                'discount'    => 0,
                'status'      => BudgetStatus::DRAFT->value,
                'due_date'    => now()->addDays( rand( 7, 30 ) ),
            ] );

            $totalBudget = 0;

            // Criar 5 services com status diferentes
            $serviceStatuses = [
                ServiceStatus::SCHEDULED,
                ServiceStatus::IN_PROGRESS,
                ServiceStatus::COMPLETED,
                ServiceStatus::APPROVED,
                ServiceStatus::CANCELLED,
            ];

            $categories = Category::all(); // Categorias são globais, não por tenant
            if ( $categories->isEmpty() ) {
                // Criar uma categoria se não existir nenhuma
                $category   = Category::create( [
                    'name' => 'Categoria Teste',
                    'slug' => 'categoria-teste',
                ] );
                $categories = collect( [ $category ] );
            }

            foreach ( $serviceStatuses as $index => $status ) {
                $serviceTotal  = $this->createServiceWithItems( $budget, $tenant, $categories->random(), $status, $index + 1 );
                $totalBudget  += $serviceTotal;

                // Para services COMPLETED ou APPROVED, criar fatura
                if ( in_array( $status, [ ServiceStatus::COMPLETED, ServiceStatus::APPROVED ] ) ) {
                    $this->createInvoiceForService( $budget, $tenant, $customer, $budget->services()->latest()->first() );
                }
            }

            // Atualizar total do budget
            $budget->update( [ 'total' => $totalBudget ] );
        } );
    }

    private function createServiceWithItems( Budget $budget, Tenant $tenant, Category $category, ServiceStatus $status, int $serviceIndex ): float
    {
        // Criar service
        $service = Service::create( [
            'tenant_id'   => $tenant->id,
            'budget_id'   => $budget->id,
            'category_id' => $category->id,
            'code'        => "SRV-{$budget->id}-{$serviceIndex}",
            'description' => "Serviço de teste {$serviceIndex} para orçamento {$budget->code}",
            'status'      => $status->value,
            'discount'    => 0,
            'total'       => 0, // Será calculado
            'due_date'    => now()->addDays( rand( 1, 30 ) ),
        ] );

        $totalService = 0;

        // Criar 5 produtos para este service
        $products = Product::where( 'tenant_id', $tenant->id )->inRandomOrder()->limit( 5 )->get();

        // Se não há produtos suficientes, criar alguns
        if ( $products->count() < 5 ) {
            for ( $i = $products->count(); $i < 5; $i++ ) {
                $product = Product::create( [
                    'tenant_id'   => $tenant->id,
                    'category_id' => $category->id,
                    'name'        => "Produto Teste {$i}",
                    'description' => "Produto criado para teste",
                    'sku'         => "TEST-{$tenant->id}-{$i}",
                    'price'       => rand( 10, 500 ),
                    'unit'        => 'un',
                    'active'      => true,
                ] );
                $products->push( $product );
            }
        }

        foreach ( $products as $index => $product ) {
            $quantity  = rand( 1, 10 );
            $unitValue = (float) $product->price;
            $total     = $quantity * $unitValue;

            ServiceItem::create( [
                'tenant_id'  => $tenant->id,
                'service_id' => $service->id,
                'product_id' => $product->id,
                'unit_value' => $unitValue,
                'quantity'   => $quantity,
                'total'      => $total,
            ] );

            $totalService  += $total;
        }

        // Atualizar total do service
        $service->update( [ 'total' => $totalService ] );

        return $totalService;
    }

    private function createInvoiceForService( Budget $budget, Tenant $tenant, Customer $customer, Service $service ): void
    {
        Invoice::create( [
            'tenant_id'      => $tenant->id,
            'service_id'     => $service->id,
            'customer_id'    => $customer->id,
            'code'           => "INV-{$service->id}",
            'subtotal'       => $service->total,
            'discount'       => 0,
            'total'          => $service->total,
            'due_date'       => now()->addDays( rand( 7, 30 ) ),
            'payment_method' => collect( [ 'pix', 'boleto', 'cartao' ] )->random(),
            'status'         => InvoiceStatus::PENDING->value,
        ] );
    }

}
