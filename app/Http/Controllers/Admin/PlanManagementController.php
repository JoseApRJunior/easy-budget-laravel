<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Abstracts\Controller;
use App\Models\AuditLog;
use App\Models\Plan;
use App\Models\PlanSubscription;
use App\Models\Tenant;
use App\Services\Domain\PlanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PlanManagementController extends Controller
{
    public function __construct(
        private PlanService $planService,
    ) {}

    /**
     * Display plans management dashboard
     */
    public function index(Request $request): View
    {
        $search = $request->get('search');
        $status = $request->get('status');
        $sort = $request->get('sort', 'name');
        $direction = $request->get('direction', 'asc');

        $query = Plan::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        $plans = $query->orderBy($sort, $direction)
            ->paginate(15)
            ->appends($request->query());

        $stats = $this->getPlanStats();

        return view('pages.admin.plan.index', [
            'plans' => $plans,
            'stats' => $stats,
            'search' => $search,
            'status' => $status,
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    /**
     * Show create plan form
     */
    public function create(): View
    {
        return view('pages.admin.plan.create', [
            'features' => $this->getAvailableFeatures(),
        ]);
    }

    /**
     * Store new plan
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50|unique:plans',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'status' => 'required|boolean',
            'max_budgets' => 'required|integer|min:1',
            'max_clients' => 'required|integer|min:1',
            'features' => 'nullable|array',
        ]);

        DB::transaction(function () use ($validated) {
            $plan = Plan::create([
                'name' => $validated['name'],
                'slug' => \Str::slug($validated['name']),
                'description' => $validated['description'] ?? null,
                'price' => $validated['price'],
                'status' => $validated['status'],
                'max_budgets' => $validated['max_budgets'],
                'max_clients' => $validated['max_clients'],
                'features' => $validated['features'] ?? null,
            ]);

            // Log activity
            AuditLog::log(
                action: 'created',
                model: $plan,
                metadata: ['description' => 'Plano criado: '.$plan->name]
            );
        });

        Cache::forget('plans.active');

        return redirect()->route('admin.plans.index')
            ->with('success', 'Plano criado com sucesso!');
    }

    /**
     * Show plan details
     */
    public function show(Plan $plan): View
    {
        // Admin global precisa ver assinaturas de todos os tenants
        $subscriptions = PlanSubscription::with(['tenant', 'provider'])
            ->withoutGlobalScope(\App\Models\Traits\TenantScope::class)
            ->where('plan_id', $plan->id)
            ->latest()
            ->paginate(10);

        $stats = $this->getPlanDetailedStats($plan, $subscriptions);

        return view('pages.admin.plan.show', [
            'plan' => $plan,
            'subscriptions' => $subscriptions,
            'stats' => $stats,
        ]);
    }

    /**
     * Show edit plan form
     */
    public function edit(Plan $plan): View
    {
        return view('pages.admin.plan.edit', [
            'plan' => $plan,
            'features' => $this->getAvailableFeatures(),
        ]);
    }

    /**
     * Update plan
     */
    public function update(Request $request, Plan $plan): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50|unique:plans,name,'.$plan->id,
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'status' => 'required|boolean',
            'max_budgets' => 'required|integer|min:1',
            'max_clients' => 'required|integer|min:1',
            'features' => 'nullable|array',
        ]);

        DB::transaction(function () use ($validated, $plan) {
            $plan->update([
                'name' => $validated['name'],
                'slug' => \Str::slug($validated['name']),
                'description' => $validated['description'] ?? null,
                'price' => $validated['price'],
                'status' => $validated['status'],
                'max_budgets' => $validated['max_budgets'],
                'max_clients' => $validated['max_clients'],
                'features' => $validated['features'] ?? null,
            ]);

            // Log activity
            AuditLog::log(
                action: 'updated',
                model: $plan,
                metadata: ['description' => 'Plano atualizado: '.$plan->name]
            );
        });

        Cache::forget('plans.active');

        return redirect()->route('admin.plans.index')
            ->with('success', 'Plano atualizado com sucesso!');
    }

    /**
     * Delete plan
     */
    public function destroy(Plan $plan): RedirectResponse
    {
        if ($plan->planSubscriptions()->exists()) {
            return redirect()->back()
                ->with('error', 'Não é possível excluir um plano com assinaturas ativas.');
        }

        DB::transaction(function () use ($plan) {
            // Log activity before deletion
            AuditLog::log(
                action: 'deleted',
                model: $plan,
                metadata: ['description' => 'Plano excluído: '.$plan->name]
            );

            $plan->delete();
        });

        Cache::forget('plans.active');

        return redirect()->route('admin.plans.index')
            ->with('success', 'Plano excluído com sucesso!');
    }

    /**
     * Toggle plan status
     */
    public function toggleStatus(Plan $plan): RedirectResponse
    {
        $plan->update([
            'status' => $plan->status === 'active' ? 'inactive' : 'active',
        ]);

        AuditLog::log(
            action: 'updated',
            model: $plan,
            metadata: ['description' => 'Status do plano alterado: '.$plan->name.' - '.$plan->status]
        );

        Cache::forget('plans.active');

        return redirect()->back()
            ->with('success', 'Status do plano alterado com sucesso!');
    }

    /**
     * Duplicate plan
     */
    public function duplicate(Plan $plan): RedirectResponse
    {
        $newPlan = $plan->replicate();
        $newPlan->name = $plan->name.' (Cópia)';
        $newPlan->status = 'draft';
        $newPlan->save();

        AuditLog::log(
            action: 'created',
            model: $newPlan,
            metadata: ['description' => 'Plano duplicado: '.$plan->name.' -> '.$newPlan->name]
        );

        return redirect()->route('admin.plans.edit', $newPlan)
            ->with('success', 'Plano duplicado com sucesso!');
    }

    /**
     * Show plan subscribers
     */
    public function subscribers(Plan $plan, Request $request): View
    {
        $search = $request->get('search');
        $status = $request->get('status');

        // Admin global precisa ver assinaturas de todos os tenants
        $query = PlanSubscription::with(['tenant', 'provider'])
            ->withoutGlobalScope(\App\Models\Traits\TenantScope::class)
            ->where('plan_id', $plan->id);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('tenant', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                })
                    ->orWhereHas('user', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        $subscribers = $query->latest()->paginate(20);

        return view('pages.admin.plan.subscribers', [
            'plan' => $plan,
            'subscribers' => $subscribers,
            'search' => $search,
            'status' => $status,
        ]);
    }

    /**
     * Show plan upgrade/downgrade history
     */
    public function history(Plan $plan): View
    {
        // Admin global precisa ver histórico de todos os tenants
        $history = PlanSubscription::with(['tenant', 'provider'])
            ->withoutGlobalScope(\App\Models\Traits\TenantScope::class)
            ->where('plan_id', $plan->id)
            ->latest()
            ->paginate(20);

        return view('pages.admin.plan.history', [
            'plan' => $plan,
            'history' => $history,
        ]);
    }

    /**
     * Show plan analytics
     */
    public function analytics(Plan $plan): View
    {
        $analytics = $this->getPlanAnalytics($plan);

        return view('pages.admin.plan.analytics', [
            'plan' => $plan,
            'analytics' => $analytics,
        ]);
    }

    /**
     * Export plan data
     */
    public function export(Request $request)
    {
        $format = $request->get('format', 'csv');
        $plans = Plan::with(['planSubscriptions'])->get();

        return match ($format) {
            'csv' => $this->exportCsv($plans),
            'json' => $this->exportJson($plans),
            'pdf' => $this->exportPdf($plans),
            default => $this->exportCsv($plans),
        };
    }

    /**
     * Get plan statistics
     */
    private function getPlanStats(): array
    {
        return [
            'total' => Plan::count(),
            'active' => Plan::where('status', 'active')->count(),
            'inactive' => Plan::where('status', 'inactive')->count(),
            'draft' => Plan::where('status', 'draft')->count(),
            'featured' => 0, // Não há campo is_featured na tabela plans
            // Admin global precisa de estatísticas de todos os tenants
            'total_subscriptions' => PlanSubscription::withoutGlobalScope(\App\Models\Traits\TenantScope::class)->count(),
            'active_subscriptions' => PlanSubscription::withoutGlobalScope(\App\Models\Traits\TenantScope::class)
                ->where('status', 'active')->count(),
            'monthly_revenue' => PlanSubscription::withoutGlobalScope(\App\Models\Traits\TenantScope::class)
                ->where('status', 'active')
                ->whereMonth('created_at', now()->month)
                ->sum('transaction_amount'),
            'yearly_revenue' => PlanSubscription::withoutGlobalScope(\App\Models\Traits\TenantScope::class)
                ->where('status', 'active')
                ->whereYear('created_at', now()->year)
                ->sum('transaction_amount'),
        ];
    }

    /**
     * Get detailed plan statistics
     */
    private function getPlanDetailedStats(Plan $plan, $subscriptions = null): array
    {
        // Se temos as subscriptions já carregadas, usamos elas para calcular estatísticas
        if ($subscriptions && method_exists($subscriptions, 'get')) {
            $allSubscriptions = $subscriptions->get();
        } else {
            // Fallback para query de todas as subscriptions (sem tenant scope para admin)
            $allSubscriptions = PlanSubscription::withoutGlobalScope(\App\Models\Traits\TenantScope::class)
                ->where('plan_id', $plan->id)
                ->get();
        }

        $activeSubscriptions = $allSubscriptions->where('status', 'active');
        $cancelledSubscriptions = $allSubscriptions->where('status', 'cancelled');
        $pendingSubscriptions = $allSubscriptions->where('status', 'pending');
        $expiredSubscriptions = $allSubscriptions->where('status', 'expired');
        $trialSubscriptions = $allSubscriptions->where('payment_method', 'trial')->where('transaction_amount', 0);
        $thisMonthActive = $activeSubscriptions->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()]);

        return [
            'total_subscriptions' => $allSubscriptions->count(),
            'active_subscriptions' => $activeSubscriptions->count(),
            'cancelled_subscriptions' => $cancelledSubscriptions->count(),
            'pending_subscriptions' => $pendingSubscriptions->count(),
            'expired_subscriptions' => $expiredSubscriptions->count(),
            'trial_subscriptions' => $trialSubscriptions->count(),
            'total_revenue' => $allSubscriptions->sum('transaction_amount'),
            'monthly_revenue' => $thisMonthActive->sum('transaction_amount'),
            'churn_rate' => $this->calculateChurnRate($allSubscriptions),
            'conversion_rate' => $this->calculateConversionRate($allSubscriptions),
        ];
    }

    /**
     * Get plan analytics
     */
    private function getPlanAnalytics(Plan $plan): array
    {
        $subscriptions = $plan->planSubscriptions()
            ->where('created_at', '>=', now()->subYear())
            ->get();

        $monthlyData = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i)->startOfMonth();
            $monthlyData[] = [
                'month' => $date->format('M Y'),
                'new_subscriptions' => $subscriptions->whereBetween('created_at', [
                    $date->copy(),
                    $date->copy()->endOfMonth(),
                ])->count(),
                'cancelled_subscriptions' => $subscriptions->whereBetween('updated_at', [
                    $date->copy(),
                    $date->copy()->endOfMonth(),
                ])->where('status', 'cancelled')->count(),
                'revenue' => $subscriptions->whereBetween('created_at', [
                    $date->copy(),
                    $date->copy()->endOfMonth(),
                ])->sum('transaction_amount'),
            ];
        }

        return [
            'monthly_data' => $monthlyData,
            'growth_rate' => $this->calculateGrowthRate($plan),
            'retention_rate' => $this->calculateRetentionRate($plan),
        ];
    }

    /**
     * Calculate churn rate using already loaded subscriptions
     */
    private function calculateChurnRate(Collection $subscriptions): float
    {
        $totalSubscriptions = $subscriptions->count();
        $cancelledSubscriptions = $subscriptions->where('status', 'cancelled')->count();

        return $totalSubscriptions > 0 ? ($cancelledSubscriptions / $totalSubscriptions) * 100 : 0;
    }

    /**
     * Calculate conversion rate using already loaded subscriptions
     * (pending to active conversion rate)
     */
    private function calculateConversionRate(Collection $subscriptions): float
    {
        $pendingSubscriptions = $subscriptions->where('status', 'pending')->count();
        $activeSubscriptions = $subscriptions->where('status', 'active')->count();

        return $pendingSubscriptions > 0 ? ($activeSubscriptions / $pendingSubscriptions) * 100 : 0;
    }

    /**
     * Calculate growth rate
     */
    private function calculateGrowthRate(Plan $plan): float
    {
        $lastMonth = $plan->planSubscriptions()
            ->whereBetween('created_at', [
                now()->subMonths(2)->startOfMonth(),
                now()->subMonth()->endOfMonth(),
            ])
            ->count();

        $thisMonth = $plan->planSubscriptions()
            ->whereBetween('created_at', [
                now()->startOfMonth(),
                now()->endOfMonth(),
            ])
            ->count();

        return $lastMonth > 0 ? (($thisMonth - $lastMonth) / $lastMonth) * 100 : 0;
    }

    /**
     * Calculate retention rate
     */
    private function calculateRetentionRate(Plan $plan): float
    {
        $totalSubscriptions = $plan->planSubscriptions()->count();
        $activeSubscriptions = $plan->planSubscriptions()->where('status', 'active')->count();

        return $totalSubscriptions > 0 ? ($activeSubscriptions / $totalSubscriptions) * 100 : 0;
    }

    /**
     * Get available features
     */
    private function getAvailableFeatures(): array
    {
        return [
            'unlimited_customers' => 'Clientes Ilimitados',
            'unlimited_invoices' => 'Faturas Ilimitadas',
            'unlimited_budgets' => 'Orçamentos Ilimitados',
            'unlimited_products' => 'Produtos Ilimitados',
            'unlimited_services' => 'Serviços Ilimitados',
            'unlimited_storage' => 'Armazenamento Ilimitado',
            'advanced_reports' => 'Relatórios Avançados',
            'custom_branding' => 'Marca Personalizada',
            'api_access' => 'Acesso à API',
            'priority_support' => 'Suporte Prioritário',
            'team_members' => 'Membros da Equipe',
            'multi_language' => 'Multi-idioma',
            'advanced_analytics' => 'Analytics Avançado',
            'ai_features' => 'Recursos de IA',
            'custom_integrations' => 'Integrações Customizadas',
            'white_label' => 'White Label',
            'dedicated_server' => 'Servidor Dedicado',
        ];
    }

    /**
     * Export plans to CSV
     */
    private function exportCsv($plans)
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="plans-'.date('Y-m-d').'.csv"',
        ];

        $callback = function () use ($plans) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Nome', 'Descrição', 'Preço', 'Ciclo', 'Status', 'Assinaturas Ativas', 'Receita Total']);

            foreach ($plans as $plan) {
                fputcsv($file, [
                    $plan->name,
                    $plan->description,
                    $plan->price,
                    'N/A', // Não há campo billing_cycle no plano
                    $plan->status,
                    $plan->planSubscriptions()->where('status', 'active')->count(),
                    $plan->planSubscriptions()->sum('transaction_amount'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export plans to JSON
     */
    private function exportJson($plans)
    {
        return response()->json($plans->toArray(), 200, [
            'Content-Disposition' => 'attachment; filename="plans-'.date('Y-m-d').'.json"',
        ]);
    }

    /**
     * Export plans to PDF
     */
    private function exportPdf($plans)
    {
        // Implementation for PDF export would go here
        // This would typically use a PDF library like DomPDF or mPDF
        return response()->json(['message' => 'PDF export not implemented yet'], 501);
    }
}
