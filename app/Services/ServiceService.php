<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Interfaces\ActivatableInterface;
use App\Contracts\Interfaces\PaginatableInterface;
use App\Contracts\Interfaces\ServiceInterface;
use App\Contracts\Interfaces\SlugableInterface;
use App\Enums\OperationStatus;
use App\Models\Service;
use App\Repositories\ServiceRepository;
use App\Services\Abstracts\BaseTenantService;
use App\Support\ServiceResult;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ServiceService extends AbstractBaseService
{

}
