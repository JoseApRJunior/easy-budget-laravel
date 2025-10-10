<?php

declare(strict_types=1);

namespace App\Services\Domain;

use App\Enums\OperationStatus;
use App\Interfaces\ServiceInterface;
use App\Models\Category;
use App\Repositories\CategoryRepository;
use App\Services\Domain\Abstracts\BaseService;
use App\Support\ServiceResult;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CategoryService extends AbstractBaseService
{

}
