<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ProviderFormRequest;
use App\Services\AddressService;
use App\Services\CommonDataService;
use App\Services\ContactService;
use App\Services\ProviderService;
use App\Services\UserService;
use App\Services\ActivityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Controlador para gerenciamento de prestadores.
 * Implementa operações tenant-aware para registro, perfil e configurações de prestadores.
 * Migração do sistema legacy app/controllers/ProviderController.php.
 *
 * @package App\Http\Controllers
 * @author IA
 */
class ProviderController extends BaseController
{
    /**
     * @var ProviderService
     */
    protected ProviderService $providerService;

    /**
     * @var UserService
     */
    protected UserService $userService;

    /**
     * @var CommonDataService
     */
    protected CommonDataService $commonDataService;

    /**
     * @var ContactService
     */
    protected ContactService $contactService;

    /**
     * @var AddressService
     */
    protected AddressService $addressService;

    /**
     * Construtor da classe ProviderController.
     *
     * @param ProviderService $providerService
     * @param UserService $userService
     * @param CommonDataService $commonDataService
     * @param ContactService $contactService
     * @param AddressService $addressService
     */
    public function __construct(
        ProviderService $providerService,
        UserService $userService,
        CommonDataService $commonDataService,
        ContactService $contactService,
        AddressService $addressService,
        ActivityService $activityService,
    ) {
        parent::__construct($activityService);
        $this->providerService   = $providerService;
        $this->userService       = $userService;
        $this->commonDataService = $commonDataService;
        $this->contactService    = $contactService;
        $this->addressService    = $addressService;
    }

    /**
     * Exibe o formulário de registro de prestador.
     *
     * @return View
     */
    public function create(): View
    {
        $this->logActivity(
            action: 'view_provider_registration',
            entity: 'providers',
            metadata: [ 'user_id' => $this->userId() ],
        );

        return $this->renderView( 'providers.register', [ 
            'areasOfActivity' => $this->commonDataService->getAreasOfActivity(),
            'professions'     => $this->commonDataService->getProfessions(),
            'states'          => $this->getBrazilianStates(),
            'banks'           => $this->getBrazilianBanks(),
            'userId'          => Auth::id()
        ] );
    }

    /**
     * Armazena um novo prestador no sistema.
     *
     * @param ProviderFormRequest $request
     * @return RedirectResponse
     */
    public function store( ProviderFormRequest $request ): RedirectResponse
    {
        $tenantId = $this->tenantId();
        $userId   = $this->userId();

        if ( !$tenantId || !$userId ) {
            return $this->errorRedirect( 'Usuário ou tenant não encontrado.' );
        }

        try {
            $providerData              = $request->validated();
            $providerData[ 'tenant_id' ] = $tenantId;
            $providerData[ 'user_id' ]   = $userId;
            $providerData[ 'status' ]    = 'pending'; // Status inicial pendente

            $result = $this->providerService->createProvider( $providerData );

            if ( $result->isSuccess() ) {
                $this->logActivity(
                    action: 'create_provider',
                    entity: 'providers',
                    entityId: $result->getEntityId(),
                    metadata: [ 
                        'tenant_id'     => $tenantId,
                        'user_id'       => $userId,
                        'provider_type' => $providerData[ 'provider_type' ]
                    ],
                );

                // Enviar email de confirmação
                $this->providerService->sendRegistrationConfirmation( $result->getEntityId() );

                return $this->successRedirect(
                    message: 'Registro realizado com sucesso! Aguarde a aprovação da sua conta.',
                    route: 'provider.dashboard',
                );
            }

            return $this->errorRedirect( $result->getError() ?? 'Erro ao criar prestador.' );

        } catch ( \Exception $e ) {
            return $this->errorRedirect( 'Erro interno ao processar registro: ' . $e->getMessage() );
        }
    }

    /**
     * Exibe o dashboard do prestador.
     *
     * @return View
     */
    public function dashboard(): View
    {
        $user = Auth::user();
        if ( !$user || !$user->provider ) {
            return $this->errorRedirect( 'Acesso negado. Você não é um prestador registrado.' );
        }

        $provider = $user->provider;
        $tenantId = $this->tenantId();

        $this->logActivity(
            action: 'view_provider_dashboard',
            entity: 'providers',
            entityId: $provider->id,
            metadata: [ 'tenant_id' => $tenantId ],
        );

        // Estatísticas do prestador
        $stats = [ 
            'total_budgets'    => $this->providerService->getProviderBudgetCount( $provider->id ),
            'pending_budgets'  => $this->providerService->getProviderPendingBudgetCount( $provider->id ),
            'total_invoices'   => $this->providerService->getProviderInvoiceCount( $provider->id ),
            'pending_invoices' => $this->providerService->getProviderPendingInvoiceCount( $provider->id ),
            'recent_activity'  => $this->providerService->getRecentProviderActivity( $provider->id, 10 )
        ];

        // Orçamentos recentes
        $recentBudgets = $this->providerService->getRecentBudgetsForProvider( $provider->id, 5 );

        // Faturas pendentes
        $pendingInvoices = $this->providerService->getPendingInvoicesForProvider( $provider->id, 5 );

        return $this->renderView( 'providers.dashboard', [ 
            'provider'        => $provider,
            'stats'           => $stats,
            'recentBudgets'   => $recentBudgets,
            'pendingInvoices' => $pendingInvoices,
            'tenantId'        => $tenantId
        ] );
    }

    /**
     * Exibe o perfil do prestador.
     *
     * @return View
     */
    public function profile(): View
    {
        $user = Auth::user();
        if ( !$user || !$user->provider ) {
            return $this->errorRedirect( 'Acesso negado. Você não é um prestador registrado.' );
        }

        $provider = $user->provider;
        $tenantId = $this->tenantId();

        $this->logActivity(
            action: 'view_provider_profile',
            entity: 'providers',
            entityId: $provider->id,
            metadata: [ 'tenant_id' => $tenantId ],
        );

        // Carregar dados relacionados
        $commonData = $this->commonDataService->getCommonDataById( $provider->common_data_id, $tenantId );
        $contact    = $this->contactService->getContactById( $provider->contact_id, $tenantId );
        $address    = $this->addressService->getAddressById( $provider->address_id, $tenantId );

        return $this->renderView( 'providers.profile', [ 
            'provider'        => $provider,
            'commonData'      => $commonData,
            'contact'         => $contact,
            'address'         => $address,
            'tenantId'        => $tenantId,
            'areasOfActivity' => $this->commonDataService->getAreasOfActivity(),
            'professions'     => $this->commonDataService->getProfessions(),
            'states'          => $this->getBrazilianStates()
        ] );
    }

    /**
     * Atualiza o perfil do prestador.
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function updateProfile( Request $request ): RedirectResponse
    {
        $user = Auth::user();
        if ( !$user || !$user->provider ) {
            return $this->errorRedirect( 'Acesso negado.' );
        }

        $provider = $user->provider;
        $tenantId = $this->tenantId();

        $request->validate( [ 
            'company_name'   => 'nullable|string|max:255',
            'description'    => 'nullable|string|max:1000',
            'phone'          => 'nullable|string|max:15',
            'email_business' => 'nullable|email|max:255',
            'website'        => 'nullable|url|max:255',
            'address'        => 'nullable|string|max:255',
            'address_number' => 'nullable|string|max:10',
            'neighborhood'   => 'nullable|string|max:100',
            'city'           => 'nullable|string|max:100',
            'state'          => 'nullable|size:2|in:AC,AL,AP,AM,BA,CE,DF,ES,GO,MA,MT,MS,MG,PA,PB,PR,PE,PI,RJ,RN,RS,RO,RR,SC,SP,SE,TO',
            'cep'            => 'nullable|string|size:9|regex:/^[0-9]{5}-[0-9]{3}$/',
            'profile_image'  => 'nullable|image|mimes:jpeg,png,jpg|max:2048'
        ] );

        try {
            $updateData = $request->only( [ 
                'company_name',
                'description',
                'phone',
                'email_business',
                'website',
                'address',
                'address_number',
                'neighborhood',
                'city',
                'state',
                'cep'
            ] );

            $updateData[ 'tenant_id' ] = $tenantId;

            // Atualizar dados relacionados se fornecidos
            if (
                $request->has( 'address' ) || $request->has( 'address_number' ) || $request->has( 'neighborhood' ) ||
                $request->has( 'city' ) || $request->has( 'state' ) || $request->has( 'cep' )
            ) {

                $addressData              = $request->only( [ 'address', 'address_number', 'neighborhood', 'city', 'state', 'cep' ] );
                $addressData[ 'tenant_id' ] = $tenantId;

                $addressResult = $this->addressService->updateAddress( $provider->address_id, $addressData );

                if ( !$addressResult->isSuccess() ) {
                    return $this->errorRedirect( 'Erro ao atualizar endereço: ' . $addressResult->getError() );
                }
            }

            if ( $request->has( 'phone' ) || $request->has( 'email_business' ) || $request->has( 'website' ) ) {
                $contactData              = $request->only( [ 'phone', 'email_business', 'website' ] );
                $contactData[ 'tenant_id' ] = $tenantId;

                $contactResult = $this->contactService->updateContact( $provider->contact_id, $contactData );

                if ( !$contactResult->isSuccess() ) {
                    return $this->errorRedirect( 'Erro ao atualizar contato: ' . $contactResult->getError() );
                }
            }

            // Atualizar dados comuns
            if ( $request->has( 'company_name' ) || $request->has( 'description' ) ) {
                $commonDataResult = $this->commonDataService->updateCommonData( $provider->common_data_id, $updateData );

                if ( !$commonDataResult->isSuccess() ) {
                    return $this->errorRedirect( 'Erro ao atualizar dados da empresa: ' . $commonDataResult->getError() );
                }
            }

            // Upload de imagem de perfil
            if ( $request->hasFile( 'profile_image' ) ) {
                $imagePath                   = $request->file( 'profile_image' )->store( 'provider_profiles', 'public' );
                $updateData[ 'profile_image' ] = $imagePath;

                $this->providerService->updateProviderProfileImage( $provider->id, $imagePath );
            }

            $result = $this->providerService->updateProvider( $provider->id, $updateData );

            if ( $result->isSuccess() ) {
                $this->logActivity(
                    action: 'update_provider_profile',
                    entity: 'providers',
                    entityId: $provider->id,
                    metadata: [ 'tenant_id' => $tenantId ],
                );

                return $this->successRedirect(
                    message: 'Perfil atualizado com sucesso.',
                    route: 'provider.profile',
                );
            }

            return $this->errorRedirect( $result->getError() ?? 'Erro ao atualizar perfil.' );

        } catch ( \Exception $e ) {
            return $this->errorRedirect( 'Erro interno ao atualizar perfil: ' . $e->getMessage() );
        }
    }

    /**
     * Exibe as configurações do prestador.
     *
     * @return View
     */
    public function settings(): View
    {
        $user = Auth::user();
        if ( !$user || !$user->provider ) {
            return $this->errorRedirect( 'Acesso negado.' );
        }

        $provider = $user->provider;
        $tenantId = $this->tenantId();

        $this->logActivity(
            action: 'view_provider_settings',
            entity: 'providers',
            entityId: $provider->id,
            metadata: [ 'tenant_id' => $tenantId ],
        );

        $settings = $this->providerService->getProviderSettings( $provider->id );
        $plans    = $this->providerService->getAvailablePlans();

        return $this->renderView( 'providers.settings', [ 
            'provider' => $provider,
            'settings' => $settings,
            'plans'    => $plans,
            'tenantId' => $tenantId,
            'banks'    => $this->getBrazilianBanks()
        ] );
    }

    /**
     * Atualiza configurações do prestador.
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function updateSettings( Request $request ): RedirectResponse
    {
        $user = Auth::user();
        if ( !$user || !$user->provider ) {
            return $this->errorRedirect( 'Acesso negado.' );
        }

        $provider = $user->provider;
        $tenantId = $this->tenantId();

        $request->validate( [ 
            'bank_account'                   => 'nullable|array',
            'bank_account.bank'              => 'nullable|string|max:50',
            'bank_account.agency'            => 'nullable|string|max:20',
            'bank_account.account'           => 'nullable|string|max:20',
            'bank_account.account_type'      => 'nullable|in:checking,savings',
            'tax_regime'                     => 'nullable|in:simples_nacional,lucro_presumido,lucro_real,none',
            'notification_preferences'       => 'nullable|array',
            'notification_preferences.email' => 'nullable|boolean',
            'notification_preferences.sms'   => 'nullable|boolean',
            'notification_preferences.push'  => 'nullable|boolean',
            'plan_id'                        => 'nullable|exists:plans,id',
            'subscription_auto_renew'        => 'nullable|boolean'
        ] );

        try {
            $settingsData                = $request->only( [ 
                'bank_account',
                'tax_regime',
                'notification_preferences',
                'plan_id',
                'subscription_auto_renew'
            ] );
            $settingsData[ 'tenant_id' ]   = $tenantId;
            $settingsData[ 'provider_id' ] = $provider->id;

            $result = $this->providerService->updateProviderSettings( $settingsData );

            if ( $result->isSuccess() ) {
                $this->logActivity(
                    action: 'update_provider_settings',
                    entity: 'providers',
                    entityId: $provider->id,
                    metadata: [ 'tenant_id' => $tenantId ],
                );

                return $this->successRedirect(
                    message: 'Configurações atualizadas com sucesso.',
                    route: 'provider.settings',
                );
            }

            return $this->errorRedirect( $result->getError() ?? 'Erro ao atualizar configurações.' );

        } catch ( \Exception $e ) {
            return $this->errorRedirect( 'Erro interno ao atualizar configurações: ' . $e->getMessage() );
        }
    }

    /**
     * Lista os orçamentos do prestador.
     *
     * @param Request $request
     * @return View
     */
    public function budgets( Request $request ): View
    {
        $user = Auth::user();
        if ( !$user || !$user->provider ) {
            return $this->errorRedirect( 'Acesso negado.' );
        }

        $provider = $user->provider;
        $tenantId = $this->tenantId();

        $filters = [ 
            'status'          => $request->get( 'status' ),
            'date_from'       => $request->get( 'date_from' ),
            'date_to'         => $request->get( 'date_to' ),
            'customer_search' => $request->get( 'customer_search' ),
            'search'          => $request->get( 'search' )
        ];

        $budgets = $this->providerService->getBudgetsForProvider(
            providerId: $provider->id,
            filters: $filters,
            perPage: 15,
        );

        $stats = $this->providerService->getProviderBudgetStats( $provider->id );

        return $this->renderView( 'providers.budgets', [ 
            'provider' => $provider,
            'budgets'  => $budgets,
            'filters'  => $filters,
            'stats'    => $stats,
            'tenantId' => $tenantId,
            'statuses' => [ 'draft', 'sent', 'approved', 'rejected', 'completed', 'cancelled' ]
        ] );
    }

    /**
     * Obtém lista de estados brasileiros.
     *
     * @return array
     */
    private function getBrazilianStates(): array
    {
        return [ 
            'AC' => 'Acre',
            'AL' => 'Alagoas',
            'AP' => 'Amapá',
            'AM' => 'Amazonas',
            'BA' => 'Bahia',
            'CE' => 'Ceará',
            'DF' => 'Distrito Federal',
            'ES' => 'Espírito Santo',
            'GO' => 'Goiás',
            'MA' => 'Maranhão',
            'MT' => 'Mato Grosso',
            'MS' => 'Mato Grosso do Sul',
            'MG' => 'Minas Gerais',
            'PA' => 'Pará',
            'PB' => 'Paraíba',
            'PR' => 'Paraná',
            'PE' => 'Pernambuco',
            'PI' => 'Piauí',
            'RJ' => 'Rio de Janeiro',
            'RN' => 'Rio Grande do Norte',
            'RS' => 'Rio Grande do Sul',
            'RO' => 'Rondônia',
            'RR' => 'Roraima',
            'SC' => 'Santa Catarina',
            'SP' => 'São Paulo',
            'SE' => 'Sergipe',
            'TO' => 'Tocantins'
        ];
    }

    /**
     * Obtém lista de bancos brasileiros.
     *
     * @return array
     */
    private function getBrazilianBanks(): array
    {
        return [ 
            '001' => 'Banco do Brasil',
            '003' => 'Banco da Amazônia',
            '004' => 'Banco do Nordeste',
            '011' => 'Banco Central do Brasil',
            '012' => 'Banco de Brasília',
            '021' => 'Banco BANESTES',
            '024' => 'Banco de Pernambuco',
            '025' => 'Banco Aliança do Brasil',
            '029' => 'Banco Itaú BBA',
            '033' => 'Banco da Amazônia',
            '034' => 'Banco Econômico',
            '035' => 'Banco do Estado do Rio Grande do Sul',
            '041' => 'Banco do Estado de Rondônia',
            '047' => 'Banco do Estado de Santa Catarina',
            '070' => 'Banco da Amazônia',
            '104' => 'Banco Central do Brasil',
            '105' => 'Banco do Brasil',
            '107' => 'Banco do Brasil',
            '133' => 'Banco do Estado do Rio Grande do Sul',
            '134' => 'Banco Rendimento',
            '153' => 'Banco Sudameris Brasil',
            '154' => 'Banco Santander Brasil',
            '156' => 'Banco Interamericano de Desenvolvimento',
            '157' => 'Banco Inter',
            '158' => 'Banco Ourinvest',
            '159' => 'Banco Votorantim',
            '160' => 'Banco Bradesco',
            '183' => 'Banco Itaú Unibanco',
            '184' => 'Banco de Desenvolvimento do Espírito Santo',
            '237' => 'Banco Bradesco',
            '260' => 'Nubank',
            '290' => 'Banco Caixa Econômica Federal',
            '336' => 'Banco C6',
            '389' => 'Banco Inter',
            '410' => 'Banco C6',
            '604' => 'Banco Itaú Unibanco',
            '637' => 'Banco Bradesco',
            '748' => 'Banco Cooperativo Sicredi',
            '756' => 'Banco Cooperativo Sicoob'
        ];
    }

}
