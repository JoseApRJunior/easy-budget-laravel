<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Abstracts\Controller;
use App\Http\Requests\InvoiceRequest;
use App\Models\Budget;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceStatus;
use App\Services\InvoiceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controller para gestão de faturas - Interface Web
 *
 * Gerencia todas as operações relacionadas a faturas através
 * da interface web, incluindo CRUD, busca e filtros.
 */
class InvoiceController extends Controller
{

}
