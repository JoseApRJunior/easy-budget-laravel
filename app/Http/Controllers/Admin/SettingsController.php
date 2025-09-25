<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends BaseController
{
    public function __construct(ActivityService $activityService) { parent::__construct($activityService);
    }

    public function index(): View
    {
        return view( 'admin.settings.index' );
    }

    public function store( Request $request ): RedirectResponse
    {
        // Logic to store settings
        return redirect()->route( 'admin.settings.index' )->with( 'success', 'Configurações salvas com sucesso!' );
    }

}


