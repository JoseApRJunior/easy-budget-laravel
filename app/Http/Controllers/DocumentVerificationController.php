<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Abstracts\Controller;
use App\Services\Domain\DocumentVerificationService;
use Illuminate\View\View;

class DocumentVerificationController extends Controller
{
    public function __construct(private DocumentVerificationService $verificationService)
    {
    }

    public function verify(string $hash): View
    {
        $result = $this->verificationService->verifyDocument($hash);

        return view('pages.document.verify', [
            'found' => $result->isSuccess(),
            'document' => $result->getData()['document'] ?? null,
            'type' => $result->getData()['type'] ?? 'Desconhecido',
            'hash' => $hash,
            'verified_at' => now(),
        ]);
    }
}
