<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Abstracts\Controller;
use App\Http\Requests\ProviderBusinessUpdateRequest;
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
 * Controller para gerenciamento dos dados empresariais do provider.
 *
 * Este controller gerencia apenas dados empresariais (dados da empresa, contato,
 * endereço, logo), separado do ProfileController que gerencia dados pessoais.
 */
class ProviderBusinessController extends Controller
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
     * Exibe formulário de atualização dos dados empresariais.
     *
     * @return View|RedirectResponse
     */
    public function edit(): View|RedirectResponse
    {
        $user     = Auth::user();
        $provider = $user->provider;

        if ( !$provider ) {
            return redirect( '/provider' )
                ->with( 'error', 'Provider não encontrado' );
        }

        // Carregar relacionamentos necessários
        $provider->load( [ 'commonData', 'contact', 'address' ] );

        return view( 'pages.provider.business.edit', [
            'provider'          => $provider,
            'areas_of_activity' => \App\Models\AreaOfActivity::all(),
            'professions'       => \App\Models\Profession::all(),
        ] );
    }

    /**
     * Processa atualização dos dados empresariais.
     *
     * @param ProviderBusinessUpdateRequest $request
     * @return RedirectResponse
     */
    public function update( ProviderBusinessUpdateRequest $request ): RedirectResponse
    {
        $validated = $request->validated();
        $user      = Auth::user();

        try {
            // Mapear cnpj e cpf para document se necessário
            if ( !empty( $validated[ 'cnpj' ] ) ) {
                $validated[ 'document' ] = $validated[ 'cnpj' ];
            } elseif ( !empty( $validated[ 'cpf' ] ) ) {
                $validated[ 'document' ] = $validated[ 'cpf' ];
            }

            // Processar upload de logo se fornecido
            if ( $request->hasFile( 'logo' ) ) {
                $logoFile   = $request->file( 'logo' );
                $logoResult = $this->fileUpload->uploadCompanyLogo( $logoFile, $user->tenant_id );
                if ( $logoResult[ 'success' ] ) {
                    $validated[ 'logo' ] = $logoResult[ 'paths' ][ 'original' ];
                }
            }

            // Buscar dados atuais do usuário
            $userData = $this->userService->findById( $user->id );
            if ( !$userData->isSuccess() ) {
                return redirect( '/provider/business/edit' )
                    ->with( 'error', 'Usuário não encontrado' );
            }

            $userModel    = $userData->getData();
            $originalData = $userModel->toArray();

            // Gerenciar arquivo de logo usando Storage
            if ( isset( $logoResult[ 'success' ] ) && $logoResult[ 'success' ] && $originalData[ 'logo' ] !== null && $logoResult[ 'paths' ][ 'original' ] !== $originalData[ 'logo' ] ) {
                if ( file_exists( public_path( $originalData[ 'logo' ] ) ) ) {
                    unlink( public_path( $originalData[ 'logo' ] ) );
                }
            }
            $validated[ 'logo' ] = isset( $logoResult[ 'success' ] ) && $logoResult[ 'success' ] ? $logoResult[ 'paths' ][ 'original' ] : $originalData[ 'logo' ];

            // Atualizar dados do usuário (logo)
            if ( !empty( array_diff_assoc( $userModel->toArray(), [ 'logo' => $validated[ 'logo' ] ] ) ) ) {
                $this->userService->update( $user->id, [ 'logo' => $validated[ 'logo' ] ] );
            }

            // Verificar se o usuário tem os IDs necessários
            if ( !$user->provider()->commonData()->id ) {
                return redirect( '/provider/business/edit' )
                    ->with( 'error', 'Dados comuns não configurados para este usuário' );
            }

            // Buscar dados atuais de CommonData
            $commonDataData = $this->commonDataService->findById( $user->provider()->commonData()->id );
            if ( !$commonDataData->isSuccess() ) {
                return redirect( '/provider/business/edit' )
                    ->with( 'error', 'Dados comuns não encontrados' );
            }

            $commonDataModel = $commonDataData->getData();

            // Converter IDs para inteiros
            $validated[ 'area_of_activity_id' ] = (int) $validated[ 'area_of_activity_id' ];
            $validated[ 'profession_id' ]       = (int) $validated[ 'profession_id' ];

            // Preparar dados para CommonData (incluindo campos pessoais)
            $commonDataFields = [
                'first_name',
                'last_name',
                'birth_date',
                'company_name',
                'cnpj',
                'cpf',
                'area_of_activity_id',
                'profession_id',
                'description'
            ];
            $commonDataUpdate = array_intersect_key( $validated, array_flip( $commonDataFields ) );

            // Atualizar CommonData
            if ( !empty( array_diff_assoc( $commonDataModel->toArray(), $commonDataUpdate ) ) ) {
                $this->commonDataService->update( $commonDataModel->id, $commonDataUpdate );
            }

            // Verificar se o usuário tem contact_id antes de buscar
            if ( $user->provider()->contact()->id ) {
                // Buscar dados atuais de Contact
                $contactData = $this->contactService->findById( $user->provider()->contact()->id );
                if ( !$contactData->isSuccess() ) {
                    return redirect( '/provider/business/edit' )
                        ->with( 'error', 'Contato não encontrado' );
                }

                $contactModel = $contactData->getData();

                // Preparar dados para Contact (incluindo campos pessoais)
                $contactFields = [
                    'email_personal' => 'email',
                    'phone_personal' => 'phone',
                    'email_business',
                    'phone_business',
                    'website'
                ];
                $contactUpdate = [];
                foreach ( $contactFields as $formField => $dbField ) {
                    if ( is_numeric( $formField ) ) {
                        $formField = $dbField; // Para campos sem mapeamento
                    }
                    if ( isset( $validated[ $formField ] ) ) {
                        $contactUpdate[ $dbField ] = $validated[ $formField ];
                    }
                }

                // Atualizar Contact
                if ( !empty( array_diff_assoc( $contactModel->toArray(), $contactUpdate ) ) ) {
                    $this->contactService->update( $contactModel->id, $contactUpdate );
                }
            }

            // Verificar se o usuário tem address_id antes de buscar
            if ( $user->provider()->address()->id ) {
                // Buscar dados atuais de Address
                $addressData = $this->addressService->findById( $user->provider()->address()->id );
                if ( !$addressData->isSuccess() ) {
                    return redirect( '/provider/business/edit' )
                        ->with( 'error', 'Endereço não encontrado' );
                }

                $addressModel = $addressData->getData();

                // Atualizar Address
                if ( !empty( array_diff_assoc( $addressModel->toArray(), $validated ) ) ) {
                    $this->addressService->update( $addressModel->id, $validated );
                }
            }

            // Atualizar Provider
            $updateResponse = $this->providerService->updateProvider( $validated );

            if ( !$updateResponse->isSuccess() ) {
                return redirect( '/provider/business/edit' )
                    ->with( 'error', $updateResponse->getMessage() );
            }

            // Limpar sessões relacionadas
            Session::forget( 'checkPlan' );
            Session::forget( 'last_updated_session_provider' );

            return redirect( '/settings' )
                ->with( 'success', 'Dados empresariais atualizados com sucesso!' );

        } catch ( \Exception $e ) {
            return redirect( '/provider/business/edit' )
                ->with( 'error', 'Erro ao atualizar dados empresariais: ' . $e->getMessage() );
        }
    }

}
