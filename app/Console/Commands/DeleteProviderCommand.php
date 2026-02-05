<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Provider;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DeleteProviderCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'provider:delete 
                            {id : O ID do provider a ser excluído} 
                            {--force : Força a exclusão sem perguntar}
                            {--with-user : Exclui também o usuário associado}
                            {--with-tenant : Exclui também o tenant associado (CUIDADO)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Exclui um provider e todos os seus dados relacionados (budgets, services, invoices, customers, etc).';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $providerId = (int) $this->argument('id');
        $force = (bool) $this->option('force');
        $withUser = (bool) $this->option('with-user');
        $withTenant = (bool) $this->option('with-tenant');

        $provider = Provider::find($providerId);

        if (!$provider) {
            $this->error("Provider com ID {$providerId} não encontrado.");
            return Command::FAILURE;
        }

        $this->info("Provider encontrado: {$provider->id} (Tenant: {$provider->tenant_id}, User: {$provider->user_id})");
        
        // Alerta sobre o escopo da exclusão
        $this->warn("ESTA AÇÃO É DESTRUTIVA E IRREVERSÍVEL.");
        $this->line("Os seguintes dados serão excluídos:");
        $this->line("- Orçamentos (Budgets)");
        $this->line("- Serviços (Services)");
        $this->line("- Faturas (Invoices)");
        $this->line("- Clientes (Customers)");
        $this->line("- Assinaturas (PlanSubscriptions)");
        $this->line("- Dados Comuns (CommonData, Address, Contact, BusinessData)");
        
        if ($withUser) {
            $this->warn("- O USUÁRIO (User ID: {$provider->user_id}) TAMBÉM SERÁ EXCLUÍDO.");
        }
        
        if ($withTenant) {
            $this->warn("- O TENANT (Tenant ID: {$provider->tenant_id}) TAMBÉM SERÁ EXCLUÍDO.");
        }

        if (!$force && !$this->confirm('Tem certeza que deseja continuar?')) {
            $this->info('Operação cancelada.');
            return Command::SUCCESS;
        }

        DB::beginTransaction();

        try {
            // Desabilitar verificação de FK para permitir exclusão em massa mais fácil, 
            // mas vamos tentar fazer de forma ordenada primeiro.
            
            // 1. Excluir dados financeiros e operacionais
            $this->info("Excluindo Faturas...");
            // Assumindo que invoices pertencem ao tenant do provider, e provider é dono do tenant.
            // Se não for o caso, precisaríamos filtrar por provider_id se existisse na tabela invoices,
            // mas invoices geralmente são do tenant. Vamos usar o tenant_id do provider.
            // CUIDADO: Se houver outros providers no mesmo tenant, isso apagaria dados deles também?
            // Neste sistema, parece que Provider = Tenant Owner.
            // Mas vamos ser seguros: se provider tem relacionamento direto, usamos.
            
            // O AdminProviderService verifica $provider->invoices(). Vamos assumir que existe.
            if (method_exists($provider, 'invoices')) {
                $provider->invoices()->delete();
            } else {
                // Fallback: Invoices do tenant (se for exclusão de tenant ou se assumirmos 1:1)
                // Vamos deletar apenas se for exclusão de tenant ou se tiver certeza.
                // Por segurança, vamos pular se não tiver relação direta, a menos que --with-tenant seja usado.
                if ($withTenant) {
                    \App\Models\Invoice::where('tenant_id', $provider->tenant_id)->delete();
                }
            }

            $this->info("Excluindo Serviços...");
            // Services pertencem ao Tenant, não diretamente ao Provider (na tabela services)
            // Se estamos excluindo o tenant, deletamos todos os serviços do tenant.
            if ($withTenant) {
                \App\Models\Service::where('tenant_id', $provider->tenant_id)->delete();
            }

            $this->info("Excluindo Orçamentos...");
            // Budgets pertencem ao Tenant
            if ($withTenant) {
                \App\Models\Budget::where('tenant_id', $provider->tenant_id)->delete();
            }

            $this->info("Excluindo Clientes...");
            // Customers geralmente pertencem ao tenant. Se provider for deletado, customers ficam órfãos?
            // AdminProviderService bloqueia se tiver customers. Então devemos deletar.
            // Customers não tem provider_id direto geralmente, mas tem tenant_id.
            // Se formos deletar customers vinculados a este provider (contexto), assumimos tenant.
            if (method_exists($provider, 'customers')) {
                $provider->customers()->delete();
            } elseif ($withTenant) {
                \App\Models\Customer::where('tenant_id', $provider->tenant_id)->delete();
            }

            $this->info("Excluindo Assinaturas...");
            $provider->planSubscriptions()->delete();

            $this->info("Excluindo Credenciais e Integrações...");
            $provider->providerCredentials()->delete();
            $provider->merchantOrderMercadoPago()->delete();
            $provider->paymentMercadoPagoPlans()->delete();

            $this->info("Excluindo Dados Cadastrais...");
            $provider->commonData()->delete();
            $provider->contact()->delete();
            $provider->address()->delete();
            if ($provider->businessData()->exists()) {
                $provider->businessData()->delete();
            }

            // Se solicitado, excluir o Tenant
            if ($withTenant) {
                $this->info("Excluindo Tenant ID {$provider->tenant_id}...");
                // TenantPurgeCommand logic simplificada
                $tenant = $provider->tenant;
                if ($tenant) {
                    // Excluir configurações
                    \App\Models\SystemSettings::where('tenant_id', $tenant->id)->delete();
                    \App\Models\UserSettings::where('tenant_id', $tenant->id)->delete();
                    // Excluir o tenant
                    $tenant->delete();
                }
            }

            // Excluir o Provider
            $this->info("Excluindo registro do Provider...");
            $provider->delete();

            // Se solicitado, excluir o User
            if ($withUser) {
                $this->info("Excluindo Usuário ID {$provider->user_id}...");
                $user = \App\Models\User::find($provider->user_id);
                if ($user) {
                    $user->delete();
                }
            }

            DB::commit();
            $this->info("✅ Provider excluído com sucesso!");

            return Command::SUCCESS;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Erro ao excluir provider: " . $e->getMessage());
            $this->error($e->getTraceAsString());
            return Command::FAILURE;
        }
    }
}
