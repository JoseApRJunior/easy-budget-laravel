<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Abstracts\Controller;
use App\Http\Requests\RegisterUserRequest;
use App\Services\Application\UserRegistrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class EnhancedRegisteredUserController extends Controller
{
    /**
     * Exibir a tela de registro aprimorada.
     */
    public function create(): View
    {
        return view( 'auth.enhanced-register' );
    }

    /**
     * Processar o registro de um novo usuário com estrutura completa.
     *
     * Controller fino que delega toda lógica de negócio para UserRegistrationService.
     * Usa RegisterUserRequest para validação robusta e automática.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store( RegisterUserRequest $request ): RedirectResponse
    {
        // Obter dados validados e preparados pelo FormRequest
        $userData = $request->getValidatedData();

        // Delegar para o service
        $registrationService = app( UserRegistrationService::class);
        $result              = $registrationService->registerUser( $userData );

        if ( !$result->isSuccess() ) {
            return back()
                ->withInput()
                ->withErrors( [ 'registration' => $result->getMessage() ] );
        }

        // Registro realizado com sucesso
        $data = $result->getData();

        // ✅ Login automático do usuário após registro bem-sucedido
        Auth::login( $data[ 'user' ] );

        // Redirecionar para dashboard com mensagem de sucesso
        return redirect()->route( 'provider.dashboard' )
            ->with( 'success', $data[ 'message' ] );
    }

}
