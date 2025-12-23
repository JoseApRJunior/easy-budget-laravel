<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Abstracts\Controller;

/**
 * Controller para páginas de erro personalizadas
 */
class ErrorController extends Controller
{
    /**
     * Página de acesso não permitido.
     */
    public function notAllowed(): View
    {
        return view('errors.not-allowed');
    }

    /**
     * Página de não encontrado.
     */
    public function notFound(): View
    {
        return view('errors.not-found');
    }

    /**
     * Página de erro interno.
     */
    public function internal(): View
    {
        return view('errors.internal');
    }
}
