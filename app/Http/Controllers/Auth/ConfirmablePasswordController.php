<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ConfirmablePasswordController extends Controller
{
    /**
     * Show the confirm password view.
     */
    public function show(): View|RedirectResponse
    {
        return view( 'auth.confirm-password' );
    }

    /**
     * Confirm the user's password.
     */
    public function store( Request $request ): RedirectResponse
    {
        $request->validate( [
            'password' => 'required|password',
        ] );

        $request->session()->passwordConfirmed();

        return redirect()->intended();
    }

}
