<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\EnterpriseService;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class EnterpriseController extends Controller
{
    protected $enterpriseService;

    public function __construct(EnterpriseService $enterpriseService)
    {
        $this->enterpriseService = $enterpriseService;
        $this->middleware(['auth', 'verified', 'role:admin']);
    }

    /**
     * Dashboard principal de gestão de empresas
     */
    public function index(Request $request)
    {
        try {
            $filters = [
                'status' => $request->get('status'),
                'plan' => $request->get('plan'),
                'search' => $request->get('search'),
                'date_from' => $request->get('date_from'),
                'date_to' => $request->get('date_to')
            ];

            $enterprises = $this->enterpriseService->getEnterprises($filters, 20);
            $statistics = $this->enterpriseService->getEnterpriseStatistics();
            $plans = $this->enterpriseService->getAvailablePlans();

            return view('admin.enterprises.index', compact('enterprises', 'statistics', 'plans', 'filters'));
        } catch (\Exception $e) {
            Log::error('Erro ao carregar dashboard de empresas: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Erro ao carregar dados das empresas.');
        }
    }

    /**
     * Dados JSON para tabela de empresas (AJAX)
     */
    public function data(Request $request)
    {
        try {
            $filters = [
                'status' => $request->get('status'),
                'plan' => $request->get('plan'),
                'search' => $request->get('search'),
                'date_from' => $request->get('date_from'),
                'date_to' => $request->get('date_to')
            ];

            $enterprises = $this->enterpriseService->getEnterprises($filters, 100);
            
            return response()->json([
                'success' => true,
                'enterprises' => $enterprises
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao carregar dados de empresas: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro ao carregar dados das empresas',
                'enterprises' => []
            ], 500);
        }
    }

    /**
     * Dados financeiros de uma empresa específica (AJAX)
     */
    public function financialData($tenantId)
    {
        try {
            $financialData = $this->enterpriseService->getMonthlyFinancialData($tenantId);
            
            return response()->json([
                'success' => true,
                'data' => $financialData
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao carregar dados financeiros da empresa ' . $tenantId . ': ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro ao carregar dados financeiros',
                'data' => [
                    'monthly_revenue' => 0,
                    'last_month_revenue' => 0,
                    'monthly_costs' => 0,
                    'last_month_costs' => 0,
                    'customer_count' => 0,
                    'profit_margin' => 0,
                    'revenue_growth' => 0,
                ]
            ], 500);
        }
    }

    /**
     * Detalhes de uma empresa específica
     */
    public function show($id)
    {
        try {
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
        } catch (\Exception $e) {
            Log::error('Erro ao carregar detalhes da empresa ' . $id . ': ' . $e->getMessage());
            return redirect()->route('admin.enterprises.index')->with('error', 'Empresa não encontrada.');
        }
    }

    /**
     * Formulário de criação de nova empresa
     */
    public function create()
    {
        $plans = $this->enterpriseService->getAvailablePlans();
        $countries = $this->enterpriseService->getCountries();
        $timezones = $this->enterpriseService->getTimezones();

        return view('admin.enterprises.create', compact('plans', 'countries', 'timezones'));
    }

    /**
     * Criar nova empresa
     */
    public function store(Request $request)
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

        try {
            DB::beginTransaction();

            $enterprise = $this->enterpriseService->createEnterprise($request->all());
            
            DB::commit();

            Log::info('Nova empresa criada: ' . $enterprise->name . ' (ID: ' . $enterprise->id . ')');

            return redirect()->route('admin.enterprises.show', $enterprise->id)
                ->with('success', 'Empresa criada com sucesso!');

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Erro ao criar empresa: ' . $e->getMessage());
            
            return redirect()->back()
                ->withInput()
                ->with('error', 'Erro ao criar empresa. Por favor, tente novamente.');
        }
    }

    /**
     * Formulário de edição de empresa
     */
    public function edit($id)
    {
        try {
            $enterprise = Tenant::findOrFail($id);
            $plans = $this->enterpriseService->getAvailablePlans();
            $countries = $this->enterpriseService->getCountries();
            $timezones = $this->enterpriseService->getTimezones();

            return view('admin.enterprises.edit', compact('enterprise', 'plans', 'countries', 'timezones'));
        } catch (\Exception $e) {
            Log::error('Erro ao carregar formulário de edição: ' . $e->getMessage());
            return redirect()->route('admin.enterprises.index')->with('error', 'Empresa não encontrada.');
        }
    }

    /**
     * Atualizar empresa
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:tenants,email,' . $id,
            'document' => 'required|string|unique:tenants,document,' . $id,
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

        try {
            $enterprise = $this->enterpriseService->updateEnterprise($id, $request->all());

            Log::info('Empresa atualizada: ' . $enterprise->name . ' (ID: ' . $enterprise->id . ')');

            return redirect()->route('admin.enterprises.show', $enterprise->id)
                ->with('success', 'Empresa atualizada com sucesso!');

        } catch (\Exception $e) {
            Log::error('Erro ao atualizar empresa ' . $id . ': ' . $e->getMessage());
            
            return redirect()->back()
                ->withInput()
                ->with('error', 'Erro ao atualizar empresa. Por favor, tente novamente.');
        }
    }

    /**
     * Suspender empresa
     */
    public function suspend($id)
    {
        try {
            $enterprise = $this->enterpriseService->suspendEnterprise($id);
            
            Log::warning('Empresa suspensa: ' . $enterprise->name . ' (ID: ' . $enterprise->id . ')');

            return redirect()->route('admin.enterprises.show', $id)
                ->with('warning', 'Empresa suspensa com sucesso.');

        } catch (\Exception $e) {
            Log::error('Erro ao suspender empresa ' . $id . ': ' . $e->getMessage());
            return redirect()->back()->with('error', 'Erro ao suspender empresa.');
        }
    }

    /**
     * Reativar empresa
     */
    public function reactivate($id)
    {
        try {
            $enterprise = $this->enterpriseService->reactivateEnterprise($id);
            
            Log::info('Empresa reativada: ' . $enterprise->name . ' (ID: ' . $enterprise->id . ')');

            return redirect()->route('admin.enterprises.show', $id)
                ->with('success', 'Empresa reativada com sucesso.');

        } catch (\Exception $e) {
            Log::error('Erro ao reativar empresa ' . $id . ': ' . $e->getMessage());
            return redirect()->back()->with('error', 'Erro ao reativar empresa.');
        }
    }

    /**
     * Excluir empresa (soft delete)
     */
    public function destroy($id)
    {
        try {
            $enterprise = $this->enterpriseService->deleteEnterprise($id);
            
            Log::warning('Empresa excluída: ' . $enterprise->name . ' (ID: ' . $enterprise->id . ')');

            return redirect()->route('admin.enterprises.index')
                ->with('success', 'Empresa excluída com sucesso.');

        } catch (\Exception $e) {
            Log::error('Erro ao excluir empresa ' . $id . ': ' . $e->getMessage());
            return redirect()->back()->with('error', 'Erro ao excluir empresa.');
        }
    }

    /**
     * Exportar dados da empresa
     */
    public function export($id)
    {
        try {
            $exportData = $this->enterpriseService->exportEnterpriseData($id);
            
            return response()->json($exportData, 200, [
                'Content-Type' => 'application/json',
                'Content-Disposition' => 'attachment; filename="enterprise_' . $id . '_export.json"'
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao exportar empresa ' . $id . ': ' . $e->getMessage());
            return redirect()->back()->with('error', 'Erro ao exportar dados da empresa.');
        }
    }

    /**
     * Obter dados para AJAX/DataTables
     */
    public function getData(Request $request)
    {
        try {
            $filters = $request->only(['status', 'plan', 'search', 'date_from', 'date_to']);
            $data = $this->enterpriseService->getEnterpriseDataTable($filters);
            
            return response()->json($data);
        } catch (\Exception $e) {
            Log::error('Erro ao obter dados de empresas: ' . $e->getMessage());
            return response()->json(['error' => 'Erro ao obter dados'], 500);
        }
    }

    /**
     * Obter estatísticas para dashboard
     */
    public function getStatistics()
    {
        try {
            $statistics = $this->enterpriseService->getEnterpriseStatistics();
            return response()->json($statistics);
        } catch (\Exception $e) {
            Log::error('Erro ao obter estatísticas: ' . $e->getMessage());
            return response()->json(['error' => 'Erro ao obter estatísticas'], 500);
        }
    }
}