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

        // Carregar relacionamento provider para evitar N+1 queries
        $user->load( 'provider' );

        // Verificar se provider existe e tem common_data_id configurado
        if ( !$user->provider || !$user->provider->common_data_id ) {
            return redirect( '/provider/business/edit' )
                ->with( 'error', 'Dados comuns não configurados para este usuário' );
        }

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

            // Usar o serviço para atualizar os dados empresariais
            $result = $this->providerService->updateProviderBusinessData( $validated );

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

    /**
     * Clean document number (CNPJ/CPF) by removing formatting.
     */
    private function cleanDocumentNumber( ?string $documentNumber ): ?string
    {
        if ( empty( $documentNumber ) ) {
            return null;
        }

        // Remove all non-digit characters (points, hyphens, slashes)
        $cleaned = preg_replace( '/[^0-9]/', '', $documentNumber );

        // Ensure it's exactly the expected length
        if ( strlen( $cleaned ) === 14 || strlen( $cleaned ) === 11 ) {
            return $cleaned;
        }

        // Return null if invalid length
        return null;
    }

}
