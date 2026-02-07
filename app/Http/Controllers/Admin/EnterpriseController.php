<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Abstracts\Controller;
use App\Models\Tenant;
use App\Services\Admin\EnterpriseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class EnterpriseController extends Controller
{
    protected EnterpriseService $enterpriseService;

    public function __construct(EnterpriseService $enterpriseService)
    {
        $this->enterpriseService = $enterpriseService;
        $this->middleware(['auth', 'verified', 'role:admin']);
    }

    /**
     * Dashboard principal de gestão de empresas
     */
    public function index(Request $request): View
    {
        $filters = [
            'status' => $request->get('status'),
            'plan' => $request->get('plan'),
            'search' => $request->get('search'),
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
        ];

        $enterprises = $this->enterpriseService->getEnterprises($filters, 20);
        $statistics = $this->enterpriseService->getEnterpriseStatistics();
        $plans = $this->enterpriseService->getAvailablePlans();

        return view('admin.enterprises.index', compact('enterprises', 'statistics', 'plans', 'filters'));
    }

    /**
     * Dados JSON para tabela de empresas (AJAX)
     */
    public function data(Request $request): JsonResponse
    {
        $filters = [
            'status' => $request->get('status'),
            'plan' => $request->get('plan'),
            'search' => $request->get('search'),
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
        ];

        $enterprises = $this->enterpriseService->getEnterprises($filters, 100);

        return response()->json([
            'success' => true,
            'enterprises' => $enterprises,
        ]);
    }

    /**
     * Dados financeiros de uma empresa específica (AJAX)
     */
    public function financialData(string $tenantId): JsonResponse
    {
        $financialData = $this->enterpriseService->getMonthlyFinancialData($tenantId);

        return response()->json([
            'success' => true,
            'data' => $financialData,
        ]);
    }

    /**
     * Detalhes de uma empresa específica
     */
    public function show(string $id): View
    {
        $enterprise = $this->enterpriseService->getEnterpriseDetails($id);
        $financialSummary = $this->enterpriseService->getFinancialSummary($id);
        $users = $this->enterpriseService->getEnterpriseUsers($id);
        $activityLog = $this->enterpriseService->getActivityLog($id);
        $performanceMetrics = $this->enterpriseService->getPerformanceMetrics($id);

        return view('admin.enterprises.show', compact(
            'enterprise',
            'financialSummary',
            'users',
            'activityLog',
            'performanceMetrics'
        ));
    }

    /**
     * Formulário de criação de nova empresa
     */
    public function create(): View
    {
        $plans = $this->enterpriseService->getAvailablePlans();
        $countries = $this->enterpriseService->getCountries();
        $timezones = $this->enterpriseService->getTimezones();

        return view('admin.enterprises.create', compact('plans', 'countries', 'timezones'));
    }

    /**
     * Criar nova empresa
     */
    public function store(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:tenants,email',
            'document' => 'required|string|unique:tenants,document',
            'phone' => 'required|string',
            'address' => 'required|string',
            'city' => 'required|string',
            'state' => 'required|string',
            'country' => 'required|string',
            'postal_code' => 'required|string',
            'timezone' => 'required|string',
            'plan_id' => 'required|exists:plans,id',
            'admin_name' => 'required|string|max:255',
            'admin_email' => 'required|email|unique:users,email',
            'admin_phone' => 'required|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput()
                ->with('error', 'Por favor, corrija os erros no formulário.');
        }

        DB::beginTransaction();

        $enterprise = $this->enterpriseService->createEnterprise($request->all());

        DB::commit();

        Log::info('Nova empresa criada: '.$enterprise->name.' (ID: '.$enterprise->id.')');

        return redirect()->route('admin.enterprises.show', $enterprise->id)
            ->with('success', 'Empresa criada com sucesso!');
    }

    /**
     * Formulário de edição de empresa
     */
    public function edit(string $id): View
    {
        $enterprise = Tenant::findOrFail($id);
        $plans = $this->enterpriseService->getAvailablePlans();
        $countries = $this->enterpriseService->getCountries();
        $timezones = $this->enterpriseService->getTimezones();

        return view('admin.enterprises.edit', compact('enterprise', 'plans', 'countries', 'timezones'));
    }

    /**
     * Atualizar empresa
     */
    public function update(Request $request, string $id): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:tenants,email,'.$id,
            'document' => 'required|string|unique:tenants,document,'.$id,
            'phone' => 'required|string',
            'address' => 'required|string',
            'city' => 'required|string',
            'state' => 'required|string',
            'country' => 'required|string',
            'postal_code' => 'required|string',
            'timezone' => 'required|string',
            'plan_id' => 'required|exists:plans,id',
            'status' => 'required|in:active,inactive,suspended',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput()
                ->with('error', 'Por favor, corrija os erros no formulário.');
        }

        $enterprise = $this->enterpriseService->updateEnterprise($id, $request->all());

        Log::info('Empresa atualizada: '.$enterprise->name.' (ID: '.$enterprise->id.')');

        return redirect()->route('admin.enterprises.show', $enterprise->id)
            ->with('success', 'Empresa atualizada com sucesso!');
    }

    /**
     * Suspender empresa
     */
    public function suspend(string $id): RedirectResponse
    {
        $enterprise = $this->enterpriseService->suspendEnterprise($id);

        Log::warning('Empresa suspensa: '.$enterprise->name.' (ID: '.$enterprise->id.')');

        return redirect()->route('admin.enterprises.show', $id)
            ->with('warning', 'Empresa suspensa com sucesso.');
    }

    /**
     * Reativar empresa
     */
    public function reactivate(string $id): RedirectResponse
    {
        $enterprise = $this->enterpriseService->reactivateEnterprise($id);

        Log::info('Empresa reativada: '.$enterprise->name.' (ID: '.$enterprise->id.')');

        return redirect()->route('admin.enterprises.show', $id)
            ->with('success', 'Empresa reativada com sucesso.');
    }

    /**
     * Excluir empresa (soft delete)
     */
    public function destroy(string $id): RedirectResponse
    {
        $enterprise = $this->enterpriseService->deleteEnterprise($id);

        Log::warning('Empresa excluída: '.$enterprise->name.' (ID: '.$enterprise->id.')');

        return redirect()->route('admin.enterprises.index')
            ->with('success', 'Empresa excluída com sucesso.');
    }

    /**
     * Exportar dados da empresa
     */
    public function export(string $id): JsonResponse
    {
        $exportData = $this->enterpriseService->exportEnterpriseData($id);

        return response()->json($exportData, 200, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => 'attachment; filename="enterprise_'.$id.'_export.json"',
        ]);
    }

    /**
     * Obter dados para AJAX/DataTables
     */
    public function getData(Request $request): JsonResponse
    {
        $filters = $request->only(['status', 'plan', 'search', 'date_from', 'date_to']);
        $data = $this->enterpriseService->getEnterpriseDataTable($filters);

        return response()->json($data);
    }

    /**
     * Obter estatísticas para dashboard
     */
    public function getStatistics(): JsonResponse
    {
        $statistics = $this->enterpriseService->getEnterpriseStatistics();

        return response()->json($statistics);
    }
}
