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
                $this->fileUpload->make( 'logo' )
                    ->resize( 200, null, true )
                    ->execute();
                $logoInfo            = $this->fileUpload->get_image_info();
                $validated[ 'logo' ] = $logoInfo[ 'path' ];
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
            if ( isset( $logoInfo[ 'path' ] ) && $originalData[ 'logo' ] !== null && $logoInfo[ 'path' ] !== $originalData[ 'logo' ] ) {
                if ( file_exists( public_path( $originalData[ 'logo' ] ) ) ) {
                    unlink( public_path( $originalData[ 'logo' ] ) );
                }
            }
            $validated[ 'logo' ] = isset( $logoInfo[ 'path' ] ) ? $logoInfo[ 'path' ] : $originalData[ 'logo' ];

            // Atualizar dados do usuário (logo)
            if ( !empty( array_diff_assoc( $userModel->toArray(), [ 'logo' => $validated[ 'logo' ] ] ) ) ) {
                $this->userService->update( $user->id, [ 'logo' => $validated[ 'logo' ] ] );
            }

            // Buscar dados atuais de CommonData
            $commonDataData = $this->commonDataService->findById( $user->common_data_id );
            if ( !$commonDataData->isSuccess() ) {
                return redirect( '/provider/business/edit' )
                    ->with( 'error', 'Dados comuns não encontrados' );
            }

            $commonDataModel = $commonDataData->getData();

            // Converter IDs para inteiros
            $validated[ 'area_of_activity_id' ] = (int) $validated[ 'area_of_activity_id' ];
            $validated[ 'profession_id' ]       = (int) $validated[ 'profession_id' ];

            // Atualizar CommonData
            if ( !empty( array_diff_assoc( $commonDataModel->toArray(), $validated ) ) ) {
                $this->commonDataService->update( $commonDataModel->id, $validated );
            }

            // Buscar dados atuais de Contact
            $contactData = $this->contactService->findById( $user->contact_id );
            if ( !$contactData->isSuccess() ) {
                return redirect( '/provider/business/edit' )
                    ->with( 'error', 'Contato não encontrado' );
            }

            $contactModel = $contactData->getData();

            // Atualizar Contact
            if ( !empty( array_diff_assoc( $contactModel->toArray(), $validated ) ) ) {
                $this->contactService->update( $contactModel->id, $validated );
            }

            // Buscar dados atuais de Address
            $addressData = $this->addressService->findById( $user->address_id );
            if ( !$addressData->isSuccess() ) {
                return redirect( '/provider/business/edit' )
                    ->with( 'error', 'Endereço não encontrado' );
            }

            $addressModel = $addressData->getData();

            // Atualizar Address
            if ( !empty( array_diff_assoc( $addressModel->toArray(), $validated ) ) ) {
                $this->addressService->update( $addressModel->id, $validated );
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
