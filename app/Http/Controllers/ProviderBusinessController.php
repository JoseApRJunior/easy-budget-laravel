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

            // Atualizar logo do usuário se fornecido
            if ( isset( $logoResult[ 'success' ] ) && $logoResult[ 'success' ] ) {
                // Remover logo antigo se existir
                if ( $user->logo && file_exists( public_path( $user->logo ) ) ) {
                    unlink( public_path( $user->logo ) );
                }
                $user->update( [ 'logo' => $logoResult[ 'paths' ][ 'original' ] ] );
            }

            // Atualizar CommonData
            if ( $user->provider && $user->provider->commonData ) {
                $commonDataUpdate = array_filter( [
                    'first_name'          => $validated[ 'first_name' ] ?? null,
                    'last_name'           => $validated[ 'last_name' ] ?? null,
                    'birth_date'          => !empty( $validated[ 'birth_date' ] ) ? \Carbon\Carbon::createFromFormat( 'd/m/Y', $validated[ 'birth_date' ] )->format( 'Y-m-d' ) : null,
                    'company_name'        => $validated[ 'company_name' ] ?? null,
                    'cnpj'                => $this->cleanDocumentNumber( $validated[ 'cnpj' ] ?? $user->provider->commonData->cnpj ),
                    'cpf'                 => $this->cleanDocumentNumber( $validated[ 'cpf' ] ?? $user->provider->commonData->cpf ),
                    'area_of_activity_id' => $validated[ 'area_of_activity_id' ] ?? null,
                    'profession_id'       => $validated[ 'profession_id' ] ?? null,
                    'description'         => $validated[ 'description' ] ?? null,
                ], fn( $value ) => $value !== null );

                $user->provider->commonData->update( $commonDataUpdate );
            }

            // Atualizar Contact
            if ( $user->provider && $user->provider->contact ) {
                $contactUpdate = array_filter( [
                    'email_personal' => $validated[ 'email_personal' ] ?? null,
                    'phone_personal' => $validated[ 'phone_personal' ] ?? null,
                    'email_business' => $validated[ 'email_business' ] ?? null,
                    'phone_business' => $validated[ 'phone_business' ] ?? null,
                    'website'        => $validated[ 'website' ] ?? null,
                ], fn( $value ) => $value !== null );

                $user->provider->contact->update( $contactUpdate );
            }

            // Atualizar Address
            if ( $user->provider && $user->provider->address ) {
                $addressUpdate = array_filter( [
                    'address'        => $validated[ 'address' ] ?? null,
                    'address_number' => $validated[ 'address_number' ] ?? null,
                    'neighborhood'   => $validated[ 'neighborhood' ] ?? null,
                    'city'           => $validated[ 'city' ] ?? null,
                    'state'          => $validated[ 'state' ] ?? null,
                    'cep'            => $validated[ 'cep' ] ?? null,
                ], fn( $value ) => $value !== null );

                $user->provider->address->update( $addressUpdate );
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
