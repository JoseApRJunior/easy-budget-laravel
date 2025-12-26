<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\DTOs\Provider\ProviderUpdateDTO;
use App\Http\Controllers\Abstracts\Controller;
use App\Http\Requests\ProviderBusinessUpdateRequest;
use App\Models\Provider;
use App\Services\Application\ProviderManagementService;
use Illuminate\Http\RedirectResponse;
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
     */
    public function edit(): View|RedirectResponse
    {
        $result = $this->providerManagementService->getProviderForUpdate();

        if (! $result->isSuccess()) {
            return redirect('/provider')
                ->with('error', $result->getMessage());
        }

        $data = $result->getData();

        return view('pages.provider.business.edit', [
            'provider' => $data['provider'],
            'areas_of_activity' => $data['areas_of_activity'],
            'professions' => $data['professions'],
        ]);
    }

    /**
     * Processa atualização dos dados empresariais.
     */
    public function update(ProviderBusinessUpdateRequest $request): RedirectResponse
    {
        // Criar DTO a partir do request
        $dto = ProviderUpdateDTO::fromRequest($request);

        // Usar o serviço para atualizar os dados empresariais
        $result = $this->providerManagementService->updateProvider($dto);

        // Verificar resultado do serviço
        if (! $result->isSuccess()) {
            return redirect('/provider/business/edit')
                ->with('error', $result->getMessage());
        }

        // Limpar sessões relacionadas
        Session::forget('checkPlan');
        Session::forget('last_updated_session_provider');

        return redirect('/settings')
            ->with('success', 'Dados empresariais atualizados com sucesso!');
    }
}
