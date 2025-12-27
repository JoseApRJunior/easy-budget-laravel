<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Abstracts\Controller;
use App\Http\Requests\ProfileUpdateRequest;
use App\Services\Application\FileUploadService;
use App\Services\Domain\SettingsService;
use App\Services\Domain\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Controller para gerenciamento do perfil pessoal do usuário.
 *
 * Este controller gerencia apenas dados pessoais do usuário (dados básicos + redes sociais),
 * separado do ProviderController que gerencia dados empresariais.
 */
class ProfileController extends Controller
{
    public function __construct(
        private UserService $userService,
        private SettingsService $settingsService,
        private FileUploadService $fileUploadService,
    ) {}

    /**
     * Exibe formulário de edição do perfil pessoal.
     */
    public function edit(): View|RedirectResponse
    {
        $profileData = $this->userService->getProfileData(Auth::user()->tenant_id);

        if (! $profileData->isSuccess()) {
            return redirect()->route('settings.index')
                ->with('error', 'Erro ao carregar dados do perfil');
        }

        return view('pages.profile.edit', [
            'user' => $profileData->getData()['user'],
            'settings' => $profileData->getData()['settings'],
        ]);
    }

    /**
     * Atualiza dados do perfil pessoal.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $tenantId = Auth::user()->tenant_id;

        // Processar upload de avatar se fornecido
        if ($request->hasFile('avatar')) {
            $avatarFile = $request->file('avatar');
            $avatarResult = $this->fileUploadService->uploadAvatar($avatarFile, Auth::id(), $tenantId);
            if ($avatarResult['success']) {
                $validated['avatar'] = $avatarResult['paths']['original'];
            }
        }

        // Atualizar dados pessoais
        $result = $this->userService->updatePersonalData($validated, $tenantId);

        if (! $result->isSuccess()) {
            return redirect()->route('settings.profile.edit')
                ->with('error', $result->getMessage());
        }

        return redirect()->route('settings.index')
            ->with('success', 'Perfil atualizado com sucesso!');
    }

    /**
     * Remove avatar do usuário.
     */
    public function destroy(): RedirectResponse
    {
        $result = $this->settingsService->removeAvatar();

        if (! $result['success']) {
            return redirect()->route('settings.profile.edit')
                ->with('error', $result['message']);
        }

        return redirect()->route('settings.index')
            ->with('success', 'Avatar removido com sucesso!');
    }
}
