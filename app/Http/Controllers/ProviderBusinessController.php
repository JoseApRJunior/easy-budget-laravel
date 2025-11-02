<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Abstracts\Controller;
use App\Http\Requests\ProviderBusinessUpdateRequest;
use App\Services\Application\ProviderManagementService;
use Illuminate\Http\RedirectResponse;
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
        private ProviderManagementService $providerManagementService,
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

        // Adicionar arquivo de logo aos dados validados se fornecido
        if ( $request->hasFile( 'logo' ) ) {
            $validated[ 'logo' ] = $request->file( 'logo' );
        }

        // Usar o serviço para atualizar os dados empresariais
        $result = $this->providerManagementService->updateProvider( $validated );

        // Verificar resultado do serviço
        if ( !$result->isSuccess() ) {
            return redirect( '/provider/business/edit' )
                ->with( 'error', $result->getMessage() );
        }

        // Limpar sessões relacionadas
        Session::forget( 'checkPlan' );
        Session::forget( 'last_updated_session_provider' );

        return redirect( '/settings' )
            ->with( 'success', 'Dados empresariais atualizados com sucesso!' );
    }

}
