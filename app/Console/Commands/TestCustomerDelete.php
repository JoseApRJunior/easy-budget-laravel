<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\OperationStatus;
use App\Models\Customer;
use App\Services\Domain\CustomerService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;

class TestCustomerDelete extends Command
{
    protected $signature   = 'test:customer-delete {customer_id?}';
    protected $description = 'Testa a funcionalidade de exclusão de clientes com logs detalhados';

    public function handle( CustomerService $customerService ): int
    {
        try {
            $customerId = $this->argument( 'customer_id' );

            // Para testes, vamos usar o usuário autenticado atual (se houver)
            $user = Auth::user();
            if ( !$user ) {
                $this->error( 'Usuário não autenticado. Execute como usuário logado.' );
                return Command::FAILURE;
            }

            $tenantId = $user->tenant_id;
            $this->info( "Testando exclusão do cliente ID: {$customerId} para tenant: {$tenantId}" );

            if ( $customerId ) {
                // Teste específico de um cliente
                return $this->testSpecificCustomer( $customerService, $customerId, $tenantId );
            } else {
                // Teste geral - lista primeiros clientes
                return $this->testGeneralCustomers( $customerService, $tenantId );
            }

        } catch ( \Exception $e ) {
            $this->error( "Erro no teste: " . $e->getMessage() );
            $this->error( $e->getTraceAsString() );
            return Command::FAILURE;
        }
    }

    private function testSpecificCustomer( CustomerService $customerService, int $customerId, int $tenantId ): int
    {
        $this->info( "=== Testando Cliente Específico ===" );

        // 1. Verificar se o cliente pode ser excluído
        $this->info( "1. Verificando se cliente pode ser excluído..." );
        $canDeleteResult = $customerService->canDeleteCustomer( $customerId, $tenantId );

        if ( !$canDeleteResult->isSuccess() ) {
            $this->error( "Erro ao verificar possibilidade de exclusão: " . $canDeleteResult->getMessage() );
            return Command::FAILURE;
        }

        $canDeleteData = $canDeleteResult->getData();
        $this->info( "Resultado da verificação:" );
        $this->info( "  - Pode excluir: " . ( $canDeleteData[ 'can_delete' ] ? 'SIM' : 'NÃO' ) );
        $this->info( "  - Motivo: " . ( $canDeleteData[ 'reason' ] ?? 'N/A' ) );
        $this->info( "  - Orçamentos: " . $canDeleteData[ 'budgets_count' ] );
        $this->info( "  - Serviços: " . $canDeleteData[ 'services_count' ] );
        $this->info( "  - Faturas: " . $canDeleteData[ 'invoices_count' ] );
        $this->info( "  - Interações: " . $canDeleteData[ 'interactions_count' ] );
        $this->info( "  - Total de relacionamentos: " . $canDeleteData[ 'total_relations' ] );

        if ( !$canDeleteData[ 'can_delete' ] ) {
            $this->warn( "Cliente NÃO pode ser excluído. Teste concluído." );
            return Command::SUCCESS;
        }

        // 2. Tentar excluir
        $this->info( "\n2. Tentando excluir cliente..." );
        $deleteResult = $customerService->deleteCustomer( $customerId, $tenantId );

        if ( $deleteResult->isSuccess() ) {
            $this->info( "✅ Cliente excluído com sucesso: " . $deleteResult->getMessage() );
            return Command::SUCCESS;
        } else {
            $this->error( "❌ Erro ao excluir cliente: " . $deleteResult->getMessage() );
            return Command::FAILURE;
        }
    }

    private function testGeneralCustomers( CustomerService $customerService, int $tenantId ): int
    {
        $this->info( "=== Testando Clientes Gerais ===" );

        // Buscar alguns clientes para testar
        $customers = Customer::where( 'tenant_id', $tenantId )
            ->withCount( [ 'budgets', 'services', 'invoices' ] )
            ->limit( 5 )
            ->get();

        if ( $customers->isEmpty() ) {
            $this->warn( "Nenhum cliente encontrado para teste." );
            return Command::SUCCESS;
        }

        $this->info( "Encontrados {$customers->count()} clientes para teste." );

        foreach ( $customers as $customer ) {
            $this->info( "\n--- Cliente ID: {$customer->id} ---" );
            $this->info( "  Nome: " . ( $customer->getNameAttribute() ?? 'N/A' ) );
            $this->info( "  Orçamentos: {$customer->budgets_count}" );
            $this->info( "  Serviços: {$customer->services_count}" );
            $this->info( "  Faturas: {$customer->invoices_count}" );

            $canDeleteResult = $customerService->canDeleteCustomer( $customer->id, $tenantId );

            if ( $canDeleteResult->isSuccess() ) {
                $canDeleteData = $canDeleteResult->getData();
                $canDelete     = $canDeleteData[ 'can_delete' ];
                $this->info( "  Pode excluir: " . ( $canDelete ? 'SIM' : 'NÃO' ) );
                if ( !$canDelete ) {
                    $this->info( "  Motivo: " . ( $canDeleteData[ 'reason' ] ?? 'N/A' ) );
                }
            } else {
                $this->error( "  Erro ao verificar: " . $canDeleteResult->getMessage() );
            }
        }

        $this->info( "\n=== Teste Concluído ===" );
        return Command::SUCCESS;
    }

}
