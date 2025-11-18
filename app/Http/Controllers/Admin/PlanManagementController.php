<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Abstracts\Controller;
use App\Models\Plan;
use App\Models\PlanSubscription;
use App\Models\Tenant;
use App\Services\Domain\PlanService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class PlanManagementController extends Controller
{
    public function __construct(
        private PlanService $planService
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

        return view('admin.plans.index', [
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
        return view('admin.plans.create', [
            'features' => $this->getAvailableFeatures(),
        ]);
    }

    /**
     * Store new plan
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:plans',
            'description' => 'required|string|max:1000',
            'price' => 'required|numeric|min:0',
            'billing_cycle' => 'required|string|in:monthly,quarterly,yearly',
            'trial_days' => 'required|integer|min:0|max:365',
            'max_customers' => 'required|integer|min:1',
            'max_invoices' => 'required|integer|min:1',
            'max_budgets' => 'required|integer|min:1',
            'max_products' => 'required|integer|min:1',
            'max_services' => 'required|integer|min:1',
            'storage_limit' => 'required|integer|min:1',
            'features' => 'array',
            'features.*' => 'string',
            'is_featured' => 'boolean',
            'sort_order' => 'integer|min:0',
            'status' => 'required|string|in:active,inactive,draft',
            'color' => 'nullable|string|max:7',
            'icon' => 'nullable|string|max:50',
        ]);

        DB::transaction(function () use ($validated) {
            $plan = Plan::create([
                'name' => $validated['name'],
                'description' => $validated['description'],
                'price' => $validated['price'],
                'billing_cycle' => $validated['billing_cycle'],
                'trial_days' => $validated['trial_days'],
                'max_customers' => $validated['max_customers'],
                'max_invoices' => $validated['max_invoices'],
                'max_budgets' => $validated['max_budgets'],
                'max_products' => $validated['max_products'],
                'max_services' => $validated['max_services'],
                'storage_limit' => $validated['storage_limit'],
                'features' => json_encode($validated['features'] ?? []),
                'is_featured' => $validated['is_featured'] ?? false,
                'sort_order' => $validated['sort_order'] ?? 0,
                'status' => $validated['status'],
                'color' => $validated['color'] ?? '#4e73df',
                'icon' => $validated['icon'] ?? 'bi-box',
            ]);

            // Log activity
            activity()
                ->causedBy(auth()->user())
                ->performedOn($plan)
                ->log('Plano criado: ' . $plan->name);
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
        $subscriptions = PlanSubscription::with(['tenant', 'user'])
                                       ->where('plan_id', $plan->id)
                                       ->latest()
                                       ->paginate(10);

        $stats = $this->getPlanDetailedStats($plan);

        return view('admin.plans.show', [
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
        return view('admin.plans.edit', [
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
            'name' => 'required|string|max:255|unique:plans,name,' . $plan->id,
            'description' => 'required|string|max:1000',
            'price' => 'required|numeric|min:0',
            'billing_cycle' => 'required|string|in:monthly,quarterly,yearly',
            'trial_days' => 'required|integer|min:0|max:365',
            'max_customers' => 'required|integer|min:1',
            'max_invoices' => 'required|integer|min:1',
            'max_budgets' => 'required|integer|min:1',
            'max_products' => 'required|integer|min:1',
            'max_services' => 'required|integer|min:1',
            'storage_limit' => 'required|integer|min:1',
            'features' => 'array',
            'features.*' => 'string',
            'is_featured' => 'boolean',
            'sort_order' => 'integer|min:0',
            'status' => 'required|string|in:active,inactive,draft',
            'color' => 'nullable|string|max:7',
            'icon' => 'nullable|string|max:50',
        ]);

        DB::transaction(function () use ($validated, $plan) {
            $plan->update([
                'name' => $validated['name'],
                'description' => $validated['description'],
                'price' => $validated['price'],
                'billing_cycle' => $validated['billing_cycle'],
                'trial_days' => $validated['trial_days'],
                'max_customers' => $validated['max_customers'],
                'max_invoices' => $validated['max_invoices'],
                'max_budgets' => $validated['max_budgets'],
                'max_products' => $validated['max_products'],
                'max_services' => $validated['max_services'],
                'storage_limit' => $validated['storage_limit'],
                'features' => json_encode($validated['features'] ?? []),
                'is_featured' => $validated['is_featured'] ?? false,
                'sort_order' => $validated['sort_order'] ?? 0,
                'status' => $validated['status'],
                'color' => $validated['color'] ?? '#4e73df',
                'icon' => $validated['icon'] ?? 'bi-box',
            ]);

            // Log activity
            activity()
                ->causedBy(auth()->user())
                ->performedOn($plan)
                ->log('Plano atualizado: ' . $plan->name);
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
            activity()
                ->causedBy(auth()->user())
                ->performedOn($plan)
                ->log('Plano excluído: ' . $plan->name);

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
            'status' => $plan->status === 'active' ? 'inactive' : 'active'
        ]);

        activity()
            ->causedBy(auth()->user())
            ->performedOn($plan)
            ->log('Status do plano alterado: ' . $plan->name . ' - ' . $plan->status);

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
        $newPlan->name = $plan->name . ' (Cópia)';
        $newPlan->status = 'draft';
        $newPlan->save();

        activity()
            ->causedBy(auth()->user())
            ->performedOn($newPlan)
            ->log('Plano duplicado: ' . $plan->name . ' -> ' . $newPlan->name);

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

        $query = PlanSubscription::with(['tenant', 'user'])
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

        return view('admin.plans.subscribers', [
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
        $history = PlanSubscription::with(['tenant', 'user'])
                                  ->where('plan_id', $plan->id)
                                  ->whereNotNull('previous_plan_id')
                                  ->latest()
                                  ->paginate(20);

        return view('admin.plans.history', [
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

        return view('admin.plans.analytics', [
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
        $plans = Plan::with(['subscriptions'])->get();

        return match($format) {
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
            'featured' => Plan::where('is_featured', true)->count(),
            'total_subscriptions' => PlanSubscription::count(),
            'active_subscriptions' => PlanSubscription::where('status', 'active')->count(),
            'monthly_revenue' => PlanSubscription::where('status', 'active')
                                              ->where('billing_cycle', 'monthly')
                                              ->sum('amount'),
            'yearly_revenue' => PlanSubscription::where('status', 'active')
                                               ->where('billing_cycle', 'yearly')
                                               ->sum('amount'),
        ];
    }

    /**
     * Get detailed plan statistics
     */
    private function getPlanDetailedStats(Plan $plan): array
    {
        return [
            'total_subscriptions' => $plan->planSubscriptions()->count(),
            'active_subscriptions' => $plan->planSubscriptions()->where('status', 'active')->count(),
            'cancelled_subscriptions' => $plan->planSubscriptions()->where('status', 'cancelled')->count(),
            'trial_subscriptions' => $plan->planSubscriptions()->where('status', 'trial')->count(),
            'total_revenue' => $plan->planSubscriptions()->sum('transaction_amount'),
            'monthly_revenue' => $plan->planSubscriptions()
                                    ->where('status', 'active')
                                    ->where('billing_cycle', 'monthly')
                                    ->sum('transaction_amount'),
            'churn_rate' => $this->calculateChurnRate($plan),
            'conversion_rate' => $this->calculateConversionRate($plan),
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
                    $date->copy()->endOfMonth()
                ])->count(),
                'cancelled_subscriptions' => $subscriptions->whereBetween('updated_at', [
                    $date->copy(),
                    $date->copy()->endOfMonth()
                ])->where('status', 'cancelled')->count(),
                'revenue' => $subscriptions->whereBetween('created_at', [
                    $date->copy(),
                    $date->copy()->endOfMonth()
                ])->sum('amount'),
            ];
        }

        return [
            'monthly_data' => $monthlyData,
            'growth_rate' => $this->calculateGrowthRate($plan),
            'retention_rate' => $this->calculateRetentionRate($plan),
        ];
    }

    /**
     * Calculate churn rate
     */
    private function calculateChurnRate(Plan $plan): float
    {
        $totalSubscriptions = $plan->planSubscriptions()->count();
        $cancelledSubscriptions = $plan->planSubscriptions()->where('status', 'cancelled')->count();

        return $totalSubscriptions > 0 ? ($cancelledSubscriptions / $totalSubscriptions) * 100 : 0;
    }

    /**
     * Calculate conversion rate
     */
    private function calculateConversionRate(Plan $plan): float
    {
        $trialSubscriptions = $plan->planSubscriptions()->where('status', 'trial')->count();
        $activeSubscriptions = $plan->planSubscriptions()->where('status', 'active')->count();

        return $trialSubscriptions > 0 ? ($activeSubscriptions / $trialSubscriptions) * 100 : 0;
    }

    /**
     * Calculate growth rate
     */
    private function calculateGrowthRate(Plan $plan): float
    {
        $lastMonth = $plan->planSubscriptions()
                          ->whereBetween('created_at', [
                              now()->subMonths(2)->startOfMonth(),
                              now()->subMonth()->endOfMonth()
                          ])
                          ->count();

        $thisMonth = $plan->planSubscriptions()
                          ->whereBetween('created_at', [
                              now()->startOfMonth(),
                              now()->endOfMonth()
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
            'Content-Disposition' => 'attachment; filename="plans-' . date('Y-m-d') . '.csv"',
        ];

        $callback = function () use ($plans) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Nome', 'Descrição', 'Preço', 'Ciclo', 'Status', 'Assinaturas Ativas', 'Receita Total']);

            foreach ($plans as $plan) {
                fputcsv($file, [
                    $plan->name,
                    $plan->description,
                    $plan->price,
                    $plan->billing_cycle,
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
            'Content-Disposition' => 'attachment; filename="plans-' . date('Y-m-d') . '.json"',
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