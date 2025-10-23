<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Entities\AddressEntity;
use App\Entities\CommonDataEntity;
use App\Entities\ContactEntity;
use App\Entities\ProviderEntity;
use App\Entities\UserEntity;
use App\Http\Controllers\Abstracts\Controller;
use App\Http\Requests\ChangePasswordRequest;
use App\Services\Application\FileUploadService;
use App\Services\Application\ProviderManagementService;
use App\Services\Domain\ActivityService;
use App\Services\Domain\AddressService;
use App\Services\Domain\CommonDataService;
use App\Services\Domain\ContactService;
use App\Services\Domain\UserService;
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
        private ActivityService $activityService,
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
     * Exibe formulário de atualização do provider.
     *
     * @return View|RedirectResponse
     */
    public function update(): View|RedirectResponse
    {
        try {
            $data = $this->providerService->getProviderForUpdate();

            return view( 'pages.provider.update', [
                'provider'          => $data[ 'provider' ],
                'areas_of_activity' => $data[ 'areas_of_activity' ],
                'professions'       => $data[ 'professions' ],
            ] );
        } catch ( \Exception $e ) {
            return redirect()->route( 'dashboard' )
                ->with( 'error', 'Provider não encontrado' );
        }
    }

    /**
     * Processa atualização dos dados do provider.
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function update_store( Request $request ): RedirectResponse
    {
        // Validar dados do formulário
        $validated = $request->validate( [
            'first_name'          => 'required|string|max:255',
            'last_name'           => 'required|string|max:255',
            'email'               => 'required|email|max:255',
            'phone'               => 'nullable|string|max:20',
            'document'            => 'nullable|string|max:20',
            'area_of_activity_id' => 'required|integer',
            'profession_id'       => 'required|integer',
            'logo'                => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ] );

        // Dados do formulário sanitizados
        $data = $request->all();

        // Verificar se email já existe usando UserService
        $checkResponse = $this->userService->findByEmail( $data[ 'email' ] );

        if ( $checkResponse->isSuccess() ) {
            /** @var UserEntity $existingUser */
            $existingUser = $checkResponse->data;
            if ( $existingUser->getId() != Auth::id() ) {
                return redirect( '/provider/update' )
                    ->with( 'error', 'Este e-mail já está registrado!' );
            }
        }

        // Processar upload de imagem
        $info = null;
        if ( $request->hasFile( 'logo' ) ) {
            $this->fileUpload->make( 'logo' )
                ->resize( 200, null, true )
                ->execute();
            $info           = $this->fileUpload->get_image_info();
            $data[ 'logo' ] = $info[ 'path' ];
        }

        // Buscar dados atuais do usuário usando UserService
        $userResponse = $this->userService->findByIdAndTenantId(
            Auth::id(),
            Auth::user()->tenant_id,
        );

        if ( !$userResponse->isSuccess() ) {
            return redirect( '/provider/update' )
                ->with( 'error', 'Usuário não encontrado' );
        }

        /** @var UserEntity $userData */
        $userData     = $userResponse->data;
        $originalData = $userData->toArray();

        // Gerenciar arquivo de logo usando Storage
        if ( isset( $info[ 'path' ] ) && $originalData[ 'logo' ] !== null && $info[ 'path' ] !== $originalData[ 'logo' ] ) {
            if ( file_exists( public_path( $originalData[ 'logo' ] ) ) ) {
                unlink( public_path( $originalData[ 'logo' ] ) );
            }
        }
        $data[ 'logo' ] = isset( $info[ 'path' ] ) ? $info[ 'path' ] : $originalData[ 'logo' ];

        // Criar UserEntity atualizada
        $userEntity = UserEntity::create( array_merge(
            array_diff_key( $originalData, array_flip( [ 'created_at', 'updated_at' ] ) ),
            $data,
        ) );

        // Atualizar usuário usando UserService
        if ( !empty( array_diff_assoc( $userData->toArray(), $userEntity->toArray() ) ) ) {
            $updateResponse = $this->userService->update( $userEntity, Auth::user()->tenant_id );

            if ( !$updateResponse->isSuccess() ) {
                return redirect( '/provider/update' )
                    ->with( 'error', 'Falha ao atualizar os dados do usuário: ' . $updateResponse->message );
            }
        }

        // Buscar dados atuais de CommonData usando CommonDataService
        $commonDataResponse = $this->commonDataService->findByIdAndTenantId(
            Auth::user()->common_data_id,
            Auth::user()->tenant_id,
        );

        if ( !$commonDataResponse->isSuccess() ) {
            return redirect( '/provider/update' )
                ->with( 'error', 'Dados comuns não encontrados' );
        }

        /** @var CommonDataEntity $commonDataData */
        $commonDataData = $commonDataResponse->data;
        $originalData   = $commonDataData->toArray();

        // Converter IDs para inteiros
        $data[ 'area_of_activity_id' ] = (int) $data[ 'area_of_activity_id' ];
        $data[ 'profession_id' ]       = (int) $data[ 'profession_id' ];

        // Criar CommonDataEntity atualizada
        $commonDataEntity = CommonDataEntity::create( array_merge(
            array_diff_key( $originalData, array_flip( [ 'created_at', 'updated_at' ] ) ),
            $data,
        ) );

        // Atualizar CommonData usando CommonDataService
        if ( !empty( array_diff_assoc( $commonDataData->toArray(), $commonDataEntity->toArray() ) ) ) {
            $updateResponse = $this->commonDataService->update( $commonDataEntity, Auth::user()->tenant_id );

            if ( !$updateResponse->isSuccess() ) {
                return redirect( '/provider/update' )
                    ->with( 'error', 'Falha ao atualizar dados comuns: ' . $updateResponse->message );
            }
        }

        // Buscar dados atuais de Contact usando ContactService
        $contactResponse = $this->contactService->findByIdAndTenantId(
            Auth::user()->contact_id,
            Auth::user()->tenant_id,
        );

        if ( !$contactResponse->isSuccess() ) {
            return redirect( '/provider/update' )
                ->with( 'error', 'Contato não encontrado' );
        }

        /** @var ContactEntity $contactData */
        $contactData  = $contactResponse->data;
        $originalData = $contactData->toArray();

        // Criar ContactEntity atualizada
        $contactEntity = ContactEntity::create( array_merge(
            array_diff_key( $originalData, array_flip( [ 'created_at', 'updated_at' ] ) ),
            $data,
        ) );

        // Atualizar Contact usando ContactService
        if ( !empty( array_diff_assoc( $contactData->toArray(), $contactEntity->toArray() ) ) ) {
            $updateResponse = $this->contactService->update( $contactEntity, Auth::user()->tenant_id );

            if ( !$updateResponse->isSuccess() ) {
                return redirect( '/provider/update' )
                    ->with( 'error', 'Falha ao atualizar contato: ' . $updateResponse->message );
            }
        }

        // Buscar dados atuais de Address usando AddressService
        $addressResponse = $this->addressService->findByIdAndTenantId(
            Auth::user()->address_id,
            Auth::user()->tenant_id,
        );

        if ( !$addressResponse->isSuccess() ) {
            return redirect( '/provider/update' )
                ->with( 'error', 'Endereço não encontrado' );
        }

        /** @var AddressEntity $addressData */
        $addressData  = $addressResponse->data;
        $originalData = $addressData->toArray();

        // Criar AddressEntity atualizada
        $addressEntity = AddressEntity::create( array_merge(
            array_diff_key( $originalData, array_flip( [ 'created_at', 'updated_at' ] ) ),
            $data,
        ) );

        // Atualizar Address usando AddressService
        if ( !empty( array_diff_assoc( $addressData->toArray(), $addressEntity->toArray() ) ) ) {
            $updateResponse = $this->addressService->update( $addressEntity, Auth::user()->tenant_id );

            if ( !$updateResponse->isSuccess() ) {
                return redirect( '/provider/update' )
                    ->with( 'error', 'Falha ao atualizar endereço: ' . $updateResponse->message );
            }
        }

        // Buscar dados atuais do provider usando ProviderService
        $providerResponse = $this->providerService->findByIdAndTenantId(
            Auth::id(),
            Auth::user()->tenant_id,
        );

        if ( !$providerResponse->isSuccess() ) {
            return redirect( '/provider/update' )
                ->with( 'error', 'Prestador não encontrado' );
        }

        /** @var ProviderEntity $providerData */
        $providerData = $providerResponse->data;

        // Criar array com dados do provider para compatibilidade
        $originalData = [
            'id'             => $providerData->getId(),
            'tenant_id'      => $providerData->getTenant()->getId(),
            'user_id'        => $providerData->getUser()->getId(),
            'terms_accepted' => $providerData->isTermsAccepted(),
        ];

        // Criar ProviderEntity atualizada
        $providerEntity = ProviderEntity::create( array_merge(
            array_diff_key( $originalData, array_flip( [ 'created_at', 'updated_at' ] ) ),
            $data,
        ) );

        // Atualizar Provider usando ProviderService
        if ( !empty( array_diff_assoc( $providerData->toArray(), $providerEntity->toArray() ) ) ) {
            $updateResponse = $this->providerService->update( $providerEntity, Auth::user()->tenant_id );

            if ( !$updateResponse->isSuccess() ) {
                return redirect( '/provider/update' )
                    ->with( 'error', 'Falha ao atualizar prestador: ' . $updateResponse->message );
            }
        }

        // Log da atividade usando ActivityService
        $this->activityService->logActivity(
            Auth::user()->tenant_id,
            Auth::id(),
            1, // provider_updated activity type id
            'provider',
            Auth::id(),
            "Prestador {$data[ 'first_name' ]} {$data[ 'last_name' ]} atualizado com sucesso!",
            $data,
        );

        // Limpar sessões relacionadas usando Session facade
        Session::forget( 'checkPlan' );
        Session::forget( 'last_updated_session_provider' );

        return redirect( '/settings' )
            ->with( 'success', 'Prestador atualizado com sucesso!' );
    }

    /**
     * Exibe formulário de alteração de senha.
     *
     * @return View
     */
    public function change_password(): View
    {
        return view( 'pages.provider.change_password' );
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

            return redirect()->route( 'settings.index' )
                ->with( 'success', 'Senha alterada com sucesso!' );
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
