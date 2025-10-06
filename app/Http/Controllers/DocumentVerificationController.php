<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controller para verificação de documentos
 */
class DocumentVerificationController extends Controller
{
    /**
     * Verifica autenticidade de documento através de hash.
     */
    public function verify( string $hash ): View
    {
        // TODO: Implementar lógica de verificação de documentos
        // Por ora, retorna view simples
        return view( 'documents.verify', compact( 'hash' ) );
    }

}
