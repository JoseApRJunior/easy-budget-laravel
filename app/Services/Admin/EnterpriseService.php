<?php

namespace App\Services\Admin;

use App\Models\Tenant;
use App\Models\Plan;
use App\Models\User;
use App\Models\Subscription;
use App\Models\FinancialRecord;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class EnterpriseService
{
    /**
     * Obter lista de empresas com filtros
     */
    public function getEnterprises(array $filters = [], int $perPage = 20)
    {
        $query = Tenant::with(['plan', 'subscription', 'adminUser']);

        // Aplicar filtros
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['plan'])) {
            $query->where('plan_id', $filters['plan']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('document', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Obter estatísticas gerais das empresas
     */
    public function getEnterpriseStatistics(): array
    {
        $totalEnterprises = Tenant::count();
        $activeEnterprises = Tenant::where('status', 'active')->count();
        $suspendedEnterprises = Tenant::where('status', 'suspended')->count();
        $newThisMonth = Tenant::whereMonth('created_at', Carbon::now()->month)->count();

        $revenueThisMonth = FinancialRecord::whereMonth('created_at', Carbon::now()->month)
            ->where('type', 'revenue')
            ->sum('amount');

        $avgRevenuePerEnterprise = $totalEnterprises > 0 ? $revenueThisMonth / $totalEnterprises : 0;

        return [
            'total_enterprises' => $totalEnterprises,
            'active_enterprises' => $activeEnterprises,
            'suspended_enterprises' => $suspendedEnterprises,
            'new_this_month' => $newThisMonth,
            'revenue_this_month' => $revenueThisMonth,
            'avg_revenue_per_enterprise' => $avgRevenuePerEnterprise,
            'activation_rate' => $totalEnterprises > 0 ? ($activeEnterprises / $totalEnterprises) * 100 : 0,
        ];
    }

    /**
     * Obter detalhes completos de uma empresa
     */
    public function getEnterpriseDetails(int $id): Tenant
    {
        return Tenant::with([
            'plan',
            'subscription',
            'adminUser',
            'users',
            'financialRecords' => function($query) {
                $query->latest()->limit(10);
            },
            'activityLogs' => function($query) {
                $query->latest()->limit(20);
            }
        ])->findOrFail($id);
    }

    /**
     * Criar nova empresa com administrador
     */
    public function createEnterprise(array $data): Tenant
    {
        // Criar tenant
        $tenant = Tenant::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'document' => $data['document'],
            'phone' => $data['phone'],
            'address' => $data['address'],
            'city' => $data['city'],
            'state' => $data['state'],
            'country' => $data['country'],
            'postal_code' => $data['postal_code'],
            'timezone' => $data['timezone'],
            'plan_id' => $data['plan_id'],
            'status' => 'active',
        ]);

        // Criar domínio para o tenant
        $tenant->domains()->create([
            'domain' => $this->generateSubdomain($data['name']),
        ]);

        // Criar administrador da empresa
        $adminUser = User::create([
            'name' => $data['admin_name'],
            'email' => $data['admin_email'],
            'phone' => $data['admin_phone'],
            'password' => Hash::make($this->generateTemporaryPassword()),
            'tenant_id' => $tenant->id,
            'email_verified_at' => now(),
        ]);

        // Atribuir role de admin
        $adminUser->assignRole('provider');

        // Criar subscription
        Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $data['plan_id'],
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);

        // Registrar atividade
        ActivityLog::create([
            'tenant_id' => $tenant->id,
            'user_id' => auth()->id(),
            'action' => 'enterprise_created',
            'description' => 'Empresa criada através do painel administrativo',
            'metadata' => [
                'enterprise_name' => $tenant->name,
                'admin_email' => $adminUser->email,
            ],
        ]);

        return $tenant;
    }

    /**
     * Atualizar empresa
     */
    public function updateEnterprise(int $id, array $data): Tenant
    {
        $tenant = Tenant::findOrFail($id);
        
        $tenant->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'document' => $data['document'],
            'phone' => $data['phone'],
            'address' => $data['address'],
            'city' => $data['city'],
            'state' => $data['state'],
            'country' => $data['country'],
            'postal_code' => $data['postal_code'],
            'timezone' => $data['timezone'],
            'plan_id' => $data['plan_id'],
            'status' => $data['status'],
        ]);

        // Registrar atividade
        ActivityLog::create([
            'tenant_id' => $tenant->id,
            'user_id' => auth()->id(),
            'action' => 'enterprise_updated',
            'description' => 'Dados da empresa atualizados',
            'metadata' => [
                'changes' => array_keys($data),
            ],
        ]);

        return $tenant;
    }

    /**
     * Suspender empresa
     */
    public function suspendEnterprise(int $id): Tenant
    {
        $tenant = Tenant::findOrFail($id);
        
        $tenant->update(['status' => 'suspended']);

        // Suspender subscription
        $tenant->subscription()->update(['status' => 'suspended']);

        // Suspender todos os usuários
        $tenant->users()->update(['status' => 'suspended']);

        // Registrar atividade
        ActivityLog::create([
            'tenant_id' => $tenant->id,
            'user_id' => auth()->id(),
            'action' => 'enterprise_suspended',
            'description' => 'Empresa suspensa',
        ]);

        return $tenant;
    }

    /**
     * Reativar empresa
     */
    public function reactivateEnterprise(int $id): Tenant
    {
        $tenant = Tenant::findOrFail($id);
        
        $tenant->update(['status' => 'active']);

        // Reativar subscription
        $tenant->subscription()->update(['status' => 'active']);

        // Reativar todos os usuários
        $tenant->users()->update(['status' => 'active']);

        // Registrar atividade
        ActivityLog::create([
            'tenant_id' => $tenant->id,
            'user_id' => auth()->id(),
            'action' => 'enterprise_reactivated',
            'description' => 'Empresa reativada',
        ]);

        return $tenant;
    }

    /**
     * Excluir empresa (soft delete)
     */
    public function deleteEnterprise(int $id): Tenant
    {
        $tenant = Tenant::findOrFail($id);
        
        // Backup antes de excluir
        $this->backupEnterpriseData($tenant);

        // Excluir (soft delete)
        $tenant->delete();

        // Registrar atividade
        ActivityLog::create([
            'tenant_id' => $tenant->id,
            'user_id' => auth()->id(),
            'action' => 'enterprise_deleted',
            'description' => 'Empresa excluída',
        ]);

        return $tenant;
    }

    /**
     * Obter resumo financeiro da empresa
     */
    public function getFinancialSummary(int $id): array
    {
        $tenant = Tenant::findOrFail($id);

        $totalRevenue = FinancialRecord::where('tenant_id', $id)
            ->where('type', 'revenue')
            ->sum('amount');

        $totalExpenses = FinancialRecord::where('tenant_id', $id)
            ->where('type', 'expense')
            ->sum('amount');

        $monthlyRevenue = FinancialRecord::where('tenant_id', $id)
            ->where('type', 'revenue')
            ->whereMonth('created_at', Carbon::now()->month)
            ->sum('amount');

        $monthlyExpenses = FinancialRecord::where('tenant_id', $id)
            ->where('type', 'expense')
            ->whereMonth('created_at', Carbon::now()->month)
            ->sum('amount');

        return [
            'total_revenue' => $totalRevenue,
            'total_expenses' => $totalExpenses,
            'net_profit' => $totalRevenue - $totalExpenses,
            'monthly_revenue' => $monthlyRevenue,
            'monthly_expenses' => $monthlyExpenses,
            'monthly_profit' => $monthlyRevenue - $monthlyExpenses,
            'profit_margin' => $totalRevenue > 0 ? (($totalRevenue - $totalExpenses) / $totalRevenue) * 100 : 0,
        ];
    }

    /**
     * Obter usuários da empresa
     */
    public function getEnterpriseUsers(int $id)
    {
        return User::where('tenant_id', $id)
            ->with(['roles', 'permissions'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);
    }

    /**
     * Obter log de atividades da empresa
     */
    public function getActivityLog(int $id)
    {
        return ActivityLog::where('tenant_id', $id)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(20);
    }

    /**
     * Obter métricas de performance da empresa
     */
    public function getPerformanceMetrics(int $id): array
    {
        $tenant = Tenant::findOrFail($id);

        // Métricas de usuários
        $totalUsers = User::where('tenant_id', $id)->count();
        $activeUsers = User::where('tenant_id', $id)->where('last_login', '>=', Carbon::now()->subDays(30))->count();

        // Métricas de uso
        $lastActivity = ActivityLog::where('tenant_id', $id)->latest()->first();
        $activitiesThisMonth = ActivityLog::where('tenant_id', $id)
            ->whereMonth('created_at', Carbon::now()->month)
            ->count();

        // Status da subscription
        $subscription = $tenant->subscription;
        $daysUntilExpiration = $subscription ? Carbon::parse($subscription->ends_at)->diffInDays(Carbon::now()) : 0;

        return [
            'total_users' => $totalUsers,
            'active_users' => $activeUsers,
            'user_activation_rate' => $totalUsers > 0 ? ($activeUsers / $totalUsers) * 100 : 0,
            'activities_this_month' => $activitiesThisMonth,
            'last_activity' => $lastActivity ? $lastActivity->created_at : null,
            'subscription_status' => $subscription ? $subscription->status : 'inactive',
            'days_until_expiration' => $daysUntilExpiration,
            'plan_name' => $tenant->plan ? $tenant->plan->name : 'Nenhum',
        ];
    }

    /**
     * Exportar dados da empresa
     */
    public function exportEnterpriseData(int $id): array
    {
        $tenant = Tenant::findOrFail($id);

        return [
            'enterprise' => $tenant->toArray(),
            'users' => User::where('tenant_id', $id)->get()->toArray(),
            'financial_records' => FinancialRecord::where('tenant_id', $id)->get()->toArray(),
            'activity_logs' => ActivityLog::where('tenant_id', $id)->limit(1000)->get()->toArray(),
            'subscription' => $tenant->subscription ? $tenant->subscription->toArray() : null,
            'exported_at' => Carbon::now()->toDateTimeString(),
        ];
    }

    /**
     * Obter dados para DataTables
     */
    public function getEnterpriseDataTable(array $filters = []): array
    {
        $enterprises = $this->getEnterprises($filters, 100);

        $data = [];
        foreach ($enterprises as $enterprise) {
            $data[] = [
                'id' => $enterprise->id,
                'name' => $enterprise->name,
                'email' => $enterprise->email,
                'plan' => $enterprise->plan ? $enterprise->plan->name : 'Nenhum',
                'status' => $enterprise->status,
                'created_at' => $enterprise->created_at->format('d/m/Y'),
                'actions' => $this->generateActions($enterprise),
            ];
        }

        return [
            'data' => $data,
            'recordsTotal' => Tenant::count(),
            'recordsFiltered' => $enterprises->total(),
        ];
    }

    /**
     * Obter planos disponíveis
     */
    public function getAvailablePlans()
    {
        return Plan::where('status', 'active')->get();
    }

    /**
     * Obter países
     */
    public function getCountries()
    {
        return [
            'BR' => 'Brasil',
            'US' => 'Estados Unidos',
            'PT' => 'Portugal',
            'ES' => 'Espanha',
        ];
    }

    /**
     * Obter fusos horários
     */
    public function getTimezones()
    {
        return [
            'America/Sao_Paulo' => 'Brasília (UTC-3)',
            'America/New_York' => 'Nova York (UTC-5/-4)',
            'Europe/Lisbon' => 'Lisboa (UTC+0/+1)',
            'Europe/Madrid' => 'Madrid (UTC+1/+2)',
        ];
    }

    /**
     * Gerar subdomínio único
     */
    private function generateSubdomain(string $name): string
    {
        $base = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $name));
        $subdomain = substr($base, 0, 20);
        
        $counter = 1;
        while (Tenant::whereHas('domains', function($query) use ($subdomain) {
            $query->where('domain', $subdomain . '.easybudget.net.br');
        })->exists()) {
            $subdomain = substr($base, 0, 15) . $counter;
            $counter++;
        }

        return $subdomain . '.easybudget.net.br';
    }

    /**
     * Gerar senha temporária
     */
    private function generateTemporaryPassword(): string
    {
        return substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 12);
    }

    /**
     * Fazer backup dos dados da empresa
     */
    private function backupEnterpriseData(Tenant $tenant): void
    {
        $backupData = $this->exportEnterpriseData($tenant->id);
        
        // Salvar backup em arquivo
        $backupPath = storage_path('app/backups/enterprises/' . $tenant->id . '_' . time() . '.json');
        file_put_contents($backupPath, json_encode($backupData, JSON_PRETTY_PRINT));
        
        Log::info('Backup criado para empresa ' . $tenant->id . ' em ' . $backupPath);
    }

    /**
     * Gerar ações para DataTables
     */
    private function generateActions(Tenant $enterprise): string
    {
        $actions = '<div class="btn-group" role="group">';
        
        $actions .= '<a href="' . route('admin.enterprises.show', $enterprise->id) . '" class="btn btn-sm btn-primary" title="Ver">
                        <i class="bi bi-eye"></i>
                    </a>';
        
        $actions .= '<a href="' . route('admin.enterprises.edit', $enterprise->id) . '" class="btn btn-sm btn-warning" title="Editar">
                        <i class="bi bi-pencil"></i>
                    </a>';
        
        if ($enterprise->status === 'active') {
            $actions .= '<button onclick="suspendEnterprise(' . $enterprise->id . ')" class="btn btn-sm btn-danger" title="Suspender">
                            <i class="bi bi-pause"></i>
                        </button>';
        } else {
            $actions .= '<button onclick="reactivateEnterprise(' . $enterprise->id . ')" class="btn btn-sm btn-success" title="Reativar">
                            <i class="bi bi-play"></i>
                        </button>';
        }
        
        $actions .= '</div>';
        
        return $actions;
    }

    /**
     * Obter dados financeiros mensais para DataTables
     */
    public function getMonthlyFinancialData(int $tenantId): array
    {
        $currentMonth = Carbon::now();
        $lastMonth = Carbon::now()->subMonth();

        // Receita do mês atual
        $currentMonthRevenue = \DB::table('payments')
            ->where('tenant_id', $tenantId)
            ->where('status', 'approved')
            ->whereMonth('created_at', $currentMonth->month)
            ->whereYear('created_at', $currentMonth->year)
            ->sum('amount') ?? 0;

        // Receita do mês passado
        $lastMonthRevenue = \DB::table('payments')
            ->where('tenant_id', $tenantId)
            ->where('status', 'approved')
            ->whereMonth('created_at', $lastMonth->month)
            ->whereYear('created_at', $lastMonth->year)
            ->sum('amount') ?? 0;

        // Custos do mês atual (assinatura + taxas)
        $subscriptionCost = 0;
        $tenant = Tenant::with(['planSubscription.plan'])->find($tenantId);
        
        if ($tenant && $tenant->planSubscription && $tenant->planSubscription->plan) {
            $subscriptionCost = $tenant->planSubscription->plan->price ?? 0;
        }

        $paymentProcessingFees = \DB::table('payments')
            ->where('tenant_id', $tenantId)
            ->where('status', 'approved')
            ->whereMonth('created_at', $currentMonth->month)
            ->whereYear('created_at', $currentMonth->year)
            ->sum(\DB::raw('amount * 0.029')) ?? 0;

        $currentMonthCosts = $subscriptionCost + $paymentProcessingFees;

        // Custos do mês passado
        $lastMonthPaymentFees = \DB::table('payments')
            ->where('tenant_id', $tenantId)
            ->where('status', 'approved')
            ->whereMonth('created_at', $lastMonth->month)
            ->whereYear('created_at', $lastMonth->year)
            ->sum(\DB::raw('amount * 0.029')) ?? 0;

        $lastMonthCosts = $subscriptionCost + $lastMonthPaymentFees;

        // Número de clientes
        $customerCount = \DB::table('customers')
            ->where('tenant_id', $tenantId)
            ->count() ?? 0;

        // Taxa de margem de lucro
        $profitMargin = $currentMonthRevenue > 0 ? 
            (($currentMonthRevenue - $currentMonthCosts) / $currentMonthRevenue) * 100 : 0;

        return [
            'monthly_revenue' => $currentMonthRevenue,
            'last_month_revenue' => $lastMonthRevenue,
            'monthly_costs' => $currentMonthCosts,
            'last_month_costs' => $lastMonthCosts,
            'customer_count' => $customerCount,
            'profit_margin' => $profitMargin,
            'revenue_growth' => $lastMonthRevenue > 0 ? 
                (($currentMonthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100 : 0,
        ];
    }
}