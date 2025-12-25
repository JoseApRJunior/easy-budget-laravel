<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\AreaOfActivity;
use App\Repositories\Abstracts\AbstractTenantRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class AreaOfActivityRepository extends AbstractTenantRepository
{
    protected function makeModel(): Model
    {
        return new AreaOfActivity();
    }

    public function getActive(): Collection
    {
        return $this->model->where('is_active', true)->orderBy('name')->get();
    }
}
