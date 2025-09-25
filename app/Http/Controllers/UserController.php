<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\UserCreateFormRequest;
use App\Services\ActivityService;
use App\Services\UserRegistrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct(
        private readonly UserRegistrationService $userRegistrationService,
        private readonly ActivityService $activityService,
    ) {}

    public function showRegisterForm(): View
    {
        return view( 'pages.user.register' );
    }

    public function register( UserCreateFormRequest $request ): RedirectResponse
    {
        try {
            $tenantId = $request->tenant_id ?? 1;
            $user     = $this->userRegistrationService->registerProvider( $request->validated() + [ 'tenant_id' => $tenantId ] );

            // Log activity
            $this->activityService->logActivity( $user->id, 'user_registered', $request->ip(), [ 
                'email'     => $request->email,
                'tenant_id' => $tenantId
            ] );

            return redirect()->route( 'login' )->with( 'success', 'Registro realizado com sucesso. Verifique seu email para confirmação.' );
        } catch ( ValidationException $e ) {
            return back()->withErrors( $e->errors() )->withInput();
        } catch ( \Exception $e ) {
            return back()->with( 'error', 'Erro ao registrar usuário: ' . $e->getMessage() )->withInput();
        }
    }

    public function showConfirmAccountForm( Request $request ): View
    {
        return view( 'pages.user.confirm-account', [ 'token' => $request->token ] );
    }

    public function confirmAccount( Request $request ): RedirectResponse
    {
        $request->validate( [ 'token' => 'required|string' ] );

        try {
            $success = $this->userRegistrationService->confirmAccount( $request->token );

            if ( $success ) {
                $this->activityService->logActivity( null, 'account_confirmed', $request->ip(), [ 'token' => $request->token ] );
                return redirect()->route( 'login' )->with( 'success', 'Conta confirmada com sucesso. Você pode fazer login.' );
            }

            return back()->with( 'error', 'Token inválido ou expirado.' );
        } catch ( ValidationException $e ) {
            return back()->withErrors( $e->errors() );
        } catch ( \Exception $e ) {
            return back()->with( 'error', 'Erro ao confirmar conta: ' . $e->getMessage() );
        }
    }

    public function resendConfirmation( Request $request ): RedirectResponse
    {
        $request->validate( [ 'email' => 'required|email' ] );

        $user = User::where( 'email', $request->email )->first();

        if ( !$user || $user->status === 'active' ) {
            return back()->with( 'error', 'Usuário não encontrado ou já confirmado.' );
        }

        try {
            $this->userRegistrationService->resendConfirmation( $user->id );
            return back()->with( 'success', 'Email de confirmação reenviado.' );
        } catch ( \Exception $e ) {
            return back()->with( 'error', 'Erro ao reenviar confirmação: ' . $e->getMessage() );
        }
    }

    public function showPasswordResetForm(): View
    {
        return view( 'pages.user.change-password' );
    }

    public function changePassword( Request $request ): RedirectResponse
    {
        $request->validate( [ 
            'current_password' => 'required',
            'new_password'     => 'required|min:8|confirmed',
        ] );

        $user = Auth::user();

        if ( !Hash::check( $request->current_password, $user->password ) ) {
            return back()->withErrors( [ 'current_password' => 'Senha atual incorreta.' ] );
        }

        try {
            $user->update( [ 
                'password' => Hash::make( $request->new_password ),
            ] );

            $this->activityService->logActivity( $user->id, 'password_changed', $request->ip() );

            return redirect()->route( 'dashboard' )->with( 'success', 'Senha alterada com sucesso.' );
        } catch ( \Exception $e ) {
            return back()->with( 'error', 'Erro ao alterar senha: ' . $e->getMessage() );
        }
    }

    public function showProfile(): View
    {
        $user = Auth::user();
        return view( 'pages.user.profile', compact( 'user' ) );
    }

    public function updateProfile( Request $request ): RedirectResponse
    {
        $request->validate( [ 
            'name'  => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . Auth::id(),
            'phone' => 'nullable|string',
        ] );

        try {
            $user = Auth::user();
            $user->update( $request->validated() );

            $this->activityService->logActivity( $user->id, 'profile_updated', $request->ip() );

            return back()->with( 'success', 'Perfil atualizado com sucesso.' );
        } catch ( ValidationException $e ) {
            return back()->withErrors( $e->errors() );
        } catch ( \Exception $e ) {
            return back()->with( 'error', 'Erro ao atualizar perfil: ' . $e->getMessage() );
        }
    }

    public function blockAccount( int $userId ): RedirectResponse
    {
        if ( !Auth::user()->isAdmin() ) {
            abort( 403 );
        }

        $request->validate( [ 'reason' => 'required|string' ] );

        try {
            $this->userRegistrationService->blockAccount( $userId, $request->reason );

            $this->activityService->logActivity( Auth::id(), 'user_blocked', $request->ip(), [ 
                'blocked_user_id' => $userId,
                'reason'          => $request->reason
            ] );

            return redirect()->route( 'admin.users' )->with( 'success', 'Usuário bloqueado com sucesso.' );
        } catch ( \Exception $e ) {
            return back()->with( 'error', 'Erro ao bloquear usuário: ' . $e->getMessage() );
        }
    }

}
