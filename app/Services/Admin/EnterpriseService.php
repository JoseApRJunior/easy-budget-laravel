<?php

namespace App\Services\Admin;

use App\Models\AuditLog;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\PlanSubscription;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class EnterpriseService
{
    /**
     * Obter lista de empresas com filtros
     */
    public function getEnterprises(array $filters = [], int $perPage = 20)
    {
        $query = Tenant::with(['planSubscriptions.plan', 'users']);

        // Aplicar filtros
        if (! empty($filters['status'])) {
            // TODO: Implement status on Tenant if needed, currently using is_active
            // $query->where('status', $filters['status']);
        }

        if (! empty($filters['plan'])) {
            $query->whereHas('planSubscriptions', function ($q) use ($filters) {
                $q->where('plan_id', $filters['plan']);
            });
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('document', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
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
        // $activeEnterprises = Tenant::where('status', 'active')->count();
        // $suspendedEnterprises = Tenant::where('status', 'suspended')->count();
        $activeEnterprises = Tenant::where('is_active', true)->count();
        $suspendedEnterprises = Tenant::where('is_active', false)->count();
        $newThisMonth = Tenant::whereMonth('created_at', Carbon::now()->month)->count();

        $revenueThisMonth = Payment::whereMonth('created_at', Carbon::now()->month)
            ->where('status', 'approved')
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
            'planSubscriptions.plan',
            'users',
            'invoices' => function ($query) {
                $query->latest()->limit(10);
            },
            'activities' => function ($query) {
                $query->latest()->limit(20);
            },
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
            // 'email' => $data['email'], // Not in fillable/schema
            // 'document' => $data['document'], // Not in fillable/schema
            // 'phone' => $data['phone'], // Not in fillable/schema
            // 'address' => $data['address'], // Not in fillable/schema
            // 'city' => $data['city'], // Not in fillable/schema
            // 'state' => $data['state'], // Not in fillable/schema
            // 'country' => $data['country'], // Not in fillable/schema
            // 'postal_code' => $data['postal_code'], // Not in fillable/schema
            // 'timezone' => $data['timezone'], // Not in fillable/schema
            // 'plan_id' => $data['plan_id'], // Not in fillable/schema
            // 'status' => 'active', // Not in fillable/schema
        ]);

        // Criar domínio para o tenant (TODO: Implement domain logic if model exists)
        /*
        $tenant->domains()->create([
            'domain' => $this->generateSubdomain($data['name']),
        ]);
        */

        // Criar administrador da empresa
        $adminUser = User::create([
            'name' => $data['admin_name'],
            'email' => $data['admin_email'],
            'phone' => $data['admin_phone'] ?? null,
            'password' => Hash::make($this->generateTemporaryPassword()),
            'tenant_id' => $tenant->id,
            'email_verified_at' => now(),
        ]);

        // Atribuir role de admin
        // $adminUser->assignRole('provider'); // Role might be different or not implemented

        // Criar subscription
        PlanSubscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $data['plan_id'],
            // 'status' => 'active', // Enum?
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);

        // Registrar atividade
        AuditLog::create([
            'tenant_id' => $tenant->id,
            'user_id' => auth()->id(),
            'action' => 'enterprise_created',
            // 'description' => 'Empresa criada através do painel administrativo',
            'details' => [ // metadata -> details
                'enterprise_name' => $tenant->name,
                'admin_email' => $adminUser->email,
            ],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
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
            // Update other fields if schema supports
        ]);

        // Registrar atividade
        AuditLog::create([
            'tenant_id' => $tenant->id,
            'user_id' => auth()->id(),
            'action' => 'enterprise_updated',
            // 'description' => 'Dados da empresa atualizados',
            'details' => [
                'changes' => array_keys($data),
            ],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return $tenant;
    }

    /**
     * Suspender empresa
     */
    public function suspendEnterprise(int $id): Tenant
    {
        $tenant = Tenant::findOrFail($id);

        $tenant->update(['is_active' => false]);

        // Suspender subscription
        $tenant->planSubscriptions()->update(['status' => 'suspended']); // Ensure status field exists or use logic

        // Suspender todos os usuários
        $tenant->users()->update(['is_active' => false]);

        // Registrar atividade
        AuditLog::create([
            'tenant_id' => $tenant->id,
            'user_id' => auth()->id(),
            'action' => 'enterprise_suspended',
            // 'description' => 'Empresa suspensa',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return $tenant;
    }

    /**
     * Reativar empresa
     */
    public function reactivateEnterprise(int $id): Tenant
    {
        $tenant = Tenant::findOrFail($id);

        $tenant->update(['is_active' => true]);

        // Reativar subscription
        $tenant->planSubscriptions()->update(['status' => 'active']);

        // Reativar todos os usuários
        $tenant->users()->update(['is_active' => true]);

        // Registrar atividade
        AuditLog::create([
            'tenant_id' => $tenant->id,
            'user_id' => auth()->id(),
            'action' => 'enterprise_reactivated',
            // 'description' => 'Empresa reativada',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
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
        // $this->backupEnterpriseData($tenant); // Disabled for now

        // Excluir (soft delete)
        $tenant->delete();

        // Registrar atividade
        AuditLog::create([
            'tenant_id' => $tenant->id,
            'user_id' => auth()->id(),
            'action' => 'enterprise_deleted',
            // 'description' => 'Empresa excluída',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return $tenant;
    }

    /**
     * Obter resumo financeiro da empresa
     */
    public function getFinancialSummary(int $id): array
    {
        $tenant = Tenant::findOrFail($id);

        $totalRevenue = Payment::where('tenant_id', $id)
            ->where('status', 'approved')
            ->sum('amount');

        $totalExpenses = 0; // FinancialRecord::where('tenant_id', $id)->where('type', 'expense')->sum('amount');

        $monthlyRevenue = Payment::where('tenant_id', $id)
            ->where('status', 'approved')
            ->whereMonth('created_at', Carbon::now()->month)
            ->sum('amount');

        $monthlyExpenses = 0; // FinancialRecord::where('tenant_id', $id)->where('type', 'expense')->whereMonth('created_at', Carbon::now()->month)->sum('amount');

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
            ->with(['roles']) // permissions removed as it is not a relation
            ->orderBy('created_at', 'desc')
            ->paginate(20);
    }

    /**
     * Obter log de atividades da empresa
     */
    public function getActivityLog(int $id)
    {
        return AuditLog::where('tenant_id', $id)
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
        $activeUsers = User::where('tenant_id', $id)->where('last_login_at', '>=', Carbon::now()->subDays(30))->count(); // last_login -> last_login_at?

        // Métricas de uso
        $lastActivity = AuditLog::where('tenant_id', $id)->latest()->first();
        $activitiesThisMonth = AuditLog::where('tenant_id', $id)
            ->whereMonth('created_at', Carbon::now()->month)
            ->count();

        // Status da subscription
        $subscription = $tenant->planSubscriptions()->latest()->first();
        $daysUntilExpiration = $subscription ? Carbon::parse($subscription->ends_at)->diffInDays(Carbon::now()) : 0;

        return [
            'total_users' => $totalUsers,
            'active_users' => $activeUsers,
            'user_activation_rate' => $totalUsers > 0 ? ($activeUsers / $totalUsers) * 100 : 0,
            'activities_this_month' => $activitiesThisMonth,
            'last_activity' => $lastActivity ? $lastActivity->created_at : null,
            'subscription_status' => $subscription ? $subscription->status : 'inactive', // status?
            'days_until_expiration' => $daysUntilExpiration,
            'plan_name' => $subscription && $subscription->plan ? $subscription->plan->name : 'Nenhum',
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
            'financial_records' => [], // FinancialRecord::where('tenant_id', $id)->get()->toArray(),
            'activity_logs' => AuditLog::where('tenant_id', $id)->limit(1000)->get()->toArray(),
            'subscription' => $tenant->planSubscriptions()->latest()->first()?->toArray(),
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
            $plan = $enterprise->planSubscriptions->first()?->plan;
            $data[] = [
                'id' => $enterprise->id,
                'name' => $enterprise->name,
                // 'email' => $enterprise->email,
                'plan' => $plan ? $plan->name : 'Nenhum',
                // 'status' => $enterprise->status,
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

        /*
        $counter = 1;
        while (Tenant::whereHas('domains', function ($query) use ($subdomain) {
            $query->where('domain', $subdomain.'.easybudget.net.br');
        })->exists()) {
            $subdomain = substr($base, 0, 15).$counter;
            $counter++;
        }
        */

        return $subdomain.'.easybudget.net.br';
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
        $backupPath = storage_path('app/backups/enterprises/'.$tenant->id.'_'.time().'.json');
        file_put_contents($backupPath, json_encode($backupData, JSON_PRETTY_PRINT));

        Log::info('Backup criado para empresa '.$tenant->id.' em '.$backupPath);
    }

    /**
     * Gerar ações para DataTables
     */
    private function generateActions(Tenant $enterprise): string
    {
        $actions = '<div class="btn-group" role="group">';

        $actions .= '<a href="'.route('admin.enterprises.show', $enterprise->id).'" class="btn btn-sm btn-primary" title="Ver">
                        <i class="bi bi-eye"></i>
                    </a>';

        $actions .= '<a href="'.route('admin.enterprises.edit', $enterprise->id).'" class="btn btn-sm btn-warning" title="Editar">
                        <i class="bi bi-pencil"></i>
                    </a>';

        if ($enterprise->is_active) {
            $actions .= '<button onclick="suspendEnterprise('.$enterprise->id.')" class="btn btn-sm btn-danger" title="Suspender">
                            <i class="bi bi-pause"></i>
                        </button>';
        } else {
            $actions .= '<button onclick="reactivateEnterprise('.$enterprise->id.')" class="btn btn-sm btn-success" title="Reativar">
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
            ->sum('amount'); // Removed ?? 0

        // Receita do mês passado
        $lastMonthRevenue = \DB::table('payments')
            ->where('tenant_id', $tenantId)
            ->where('status', 'approved')
            ->whereMonth('created_at', $lastMonth->month)
            ->whereYear('created_at', $lastMonth->year)
            ->sum('amount'); // Removed ?? 0

        // Custos do mês atual (assinatura + taxas)
        $subscriptionCost = 0;
        $tenant = Tenant::with(['planSubscriptions.plan'])->find($tenantId);

        if ($tenant && $tenant->planSubscriptions->first() && $tenant->planSubscriptions->first()->plan) {
            $subscriptionCost = $tenant->planSubscriptions->first()->plan->price ?? 0;
        }

        $paymentProcessingFees = \DB::table('payments')
            ->where('tenant_id', $tenantId)
            ->where('status', 'approved')
            ->whereMonth('created_at', $currentMonth->month)
            ->whereYear('created_at', $currentMonth->year)
            ->sum(\DB::raw('amount * 0.029')); // Removed ?? 0

        $currentMonthCosts = $subscriptionCost + $paymentProcessingFees;

        // Custos do mês passado
        $lastMonthPaymentFees = \DB::table('payments')
            ->where('tenant_id', $tenantId)
            ->where('status', 'approved')
            ->whereMonth('created_at', $lastMonth->month)
            ->whereYear('created_at', $lastMonth->year)
            ->sum(\DB::raw('amount * 0.029')); // Removed ?? 0

        $lastMonthCosts = $subscriptionCost + $lastMonthPaymentFees;

        // Número de clientes
        $customerCount = \DB::table('customers')
            ->where('tenant_id', $tenantId)
            ->count(); // Removed ?? 0

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
