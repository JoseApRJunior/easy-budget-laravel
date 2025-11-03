<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Abstracts\Controller;
use App\Http\Requests\ChangePasswordRequest;
use App\Services\Application\FileUploadService;
use App\Services\Application\ProviderManagementService;

use App\Services\Domain\AddressService;
use App\Services\Domain\CommonDataService;
use App\Services\Domain\ContactService;
use App\Services\Domain\UserService;
use App\Support\ServiceResult;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;

/**
 * Controller moderno para gerenciar operações relacionadas aos providers.
 *
 * Este controller utiliza Eloquent, Form Requests e injeção de dependências
 * seguindo os padrões Laravel modernos.
 *
 * Funcionalidades:
 * - Dashboard do provider
 * - Atualização de dados do provider
 * - Alteração de senha
 * - Upload de imagens
 */
class ProviderController extends Controller
{
    public function __construct(
        private ProviderManagementService $providerService,
        private UserService $userService,
        private CommonDataService $commonDataService,
        private ContactService $contactService,
        private AddressService $addressService,

        private FileUploadService $fileUpload,
    ) {}

    /**
     * Dashboard do provider com resumo de orçamentos, atividades e financeiro.
     *
     * @return View
     */
    public function index(): View
    {
        $dashboardData = $this->providerService->getDashboardData(
            Auth::user()->tenant_id,
        );

        return view( 'pages.provider.index', [
            'budgets'           => $dashboardData[ 'budgets' ],
            'activities'        => $dashboardData[ 'activities' ],
            'financial_summary' => $dashboardData[ 'financial_summary' ],
            'total_activities'  => count( $dashboardData[ 'activities' ] ),
        ] );

    }

    /**
     * Exibe formulário de atualização do provider (legacy - redireciona para nova estrutura).
     *
     * @return RedirectResponse
     */
    public function update(): RedirectResponse
    {
        return redirect()->route( 'provider.business.edit' )
            ->with( 'info', 'Use a nova interface separada para atualizar seus dados.' );
    }

    /**
     * Processa atualização dos dados do provider (legacy - redireciona para nova estrutura).
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function update_store( Request $request ): RedirectResponse
    {
        return redirect()->route( 'provider.business.edit' )
            ->with( 'info', 'Use a nova interface separada para atualizar seus dados.' );
    }

    /**
     * Exibe formulário de alteração de senha.
     *
     * @return View
     */
    public function change_password(): View
    {
        $user         = Auth::user();
        $isGoogleUser = is_null( $user->password );

        return view( 'pages.provider.change_password', [
            'isGoogleUser' => $isGoogleUser,
            'userEmail'    => $user->email,
        ] );
    }

    /**
     * Processa alteração de senha.
     *
     * @return RedirectResponse
     */
    public function change_password_store( ChangePasswordRequest $request ): RedirectResponse
    {
        try {
            $this->providerService->changePassword( $request->validated()[ 'password' ] );

            $user           = Auth::user();
            $isGoogleUser   = is_null( $user->password );
            $successMessage = $isGoogleUser ? 'Senha definida com sucesso!' : 'Senha alterada com sucesso!';

            return redirect()->route( 'settings.index' )
                ->with( 'success', $successMessage );
        } catch ( \Exception $e ) {
            return redirect()->route( 'provider.change_password' )
                ->with( 'error', 'Erro ao atualizar senha: ' . $e->getMessage() );
        }
    }

    /**
     * Exibe relatórios financeiros do provider.
     *
     * @return View
     */
    public function financial_reports(): View
    {
        $financialData = $this->providerService->getFinancialReports(
            Auth::user()->tenant_id,
        );

        return view( 'pages.provider.reports.financial', [
            'financial_summary' => $financialData[ 'financial_summary' ],
            'monthly_revenue'   => $financialData[ 'monthly_revenue' ],
            'pending_budgets'   => $financialData[ 'pending_budgets' ],
            'overdue_payments'  => $financialData[ 'overdue_payments' ],
        ] );
    }

    /**
     * Exibe relatórios de orçamentos do provider.
     *
     * @return View
     */
    public function budget_reports(): View
    {
        $budgetData = $this->providerService->getBudgetReports(
            Auth::user()->tenant_id,
        );

        return view( 'pages.provider.reports.budgets', [
            'budgets'      => $budgetData[ 'budgets' ],
            'budget_stats' => $budgetData[ 'budget_stats' ],
            'period'       => $budgetData[ 'period' ],
        ] );
    }

    /**
     * Exporta relatórios de orçamentos em Excel.
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function budget_reports_excel()
    {
        $budgetData = $this->providerService->getBudgetReports(
            Auth::user()->tenant_id,
        );

        // Implementar lógica de exportação Excel
        // Por enquanto retorna uma resposta simples
        return response()->json( [
            'success' => true,
            'message' => 'Exportação Excel será implementada',
            'data'    => $budgetData,
        ] );
    }

    /**
     * Exporta relatórios de orçamentos em PDF.
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function budget_reports_pdf()
    {
        $budgetData = $this->providerService->getBudgetReports(
            Auth::user()->tenant_id,
        );

        // Implementar lógica de exportação PDF
        // Por enquanto retorna uma resposta simples
        return response()->json( [
            'success' => true,
            'message' => 'Exportação PDF será implementada',
            'data'    => $budgetData,
        ] );
    }

    /**
     * Exibe relatórios de serviços do provider.
     *
     * @return View
     */
    public function service_reports(): View
    {
        $serviceData = $this->providerService->getServiceReports(
            Auth::user()->tenant_id,
        );

        return view( 'pages.provider.reports.services', [
            'services'      => $serviceData[ 'services' ],
            'service_stats' => $serviceData[ 'service_stats' ],
            'period'        => $serviceData[ 'period' ],
        ] );
    }

    /**
     * Exibe relatórios de clientes do provider.
     *
     * @return View
     */
    public function customer_reports(): View
    {
        $customerData = $this->providerService->getCustomerReports(
            Auth::user()->tenant_id,
        );

        return view( 'pages.provider.reports.customers', [
            'customers'      => $customerData[ 'customers' ],
            'customer_stats' => $customerData[ 'customer_stats' ],
            'period'         => $customerData[ 'period' ],
        ] );
    }

}
