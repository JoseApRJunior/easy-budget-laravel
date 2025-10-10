<?php

declare(strict_types=1);

namespace App\Services\Domain;

use App\Enums\OperationStatus;
use App\Repositories\RoleRepository;
use App\Services\Domain\Abstracts\BaseNoTenantService;
use App\Support\ServiceResult;
use App\Traits\SlugGenerator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class RoleService extends AbstractBaseService
{

}
