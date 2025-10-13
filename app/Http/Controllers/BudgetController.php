<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Abstracts\Controller;
use App\Models\Budget;
use App\Models\BudgetItemCategory;
use App\Models\BudgetTemplate;
use App\Services\BudgetCalculationService;
use App\Services\BudgetPdfService;
use App\Services\BudgetTemplateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BudgetController extends Controller
{

}
