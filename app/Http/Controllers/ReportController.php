<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Abstracts\Controller;
use App\Models\ReportDefinition;
use App\Models\ReportExecution;
use App\Models\ReportSchedule;
use App\Services\ExportService;
use App\Services\ReportGenerationService;
use App\Services\ReportSchedulerService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Controlador principal para gerenciamento de relatórios
 * Gerencia interface web e operações básicas
 */
class ReportController extends Controller
{

}
