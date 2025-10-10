<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Abstracts\Controller;
use App\Models\Invoice;
use App\Services\InvoiceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controller para visualização pública de faturas
 *
 * Permite que clientes visualizem e paguem faturas através de links públicos seguros.
 */
class PublicInvoiceController extends Controller
{

}
