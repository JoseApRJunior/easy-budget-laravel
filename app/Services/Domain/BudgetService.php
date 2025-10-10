<?php

declare(strict_types=1);

namespace App\Services\Domain;

use App\Contracts\Interfaces\ActivatableInterface;
use App\Contracts\Interfaces\PaginatableInterface;
use App\Contracts\Interfaces\SlugableInterface;
use App\Enums\OperationStatus;
use App\Models\Budget;
use App\Repositories\BudgetRepository;
use App\Repositories\CategoryRepository;
use App\Repositories\CustomerRepository;
use App\Services\Domain\Abstracts\BaseTenantService;
use App\Services\NotificationService;
use App\Services\PdfService;
use App\Support\ServiceResult;
use App\Traits\SlugGenerator;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class BudgetService extends AbstractBaseService
{
}
