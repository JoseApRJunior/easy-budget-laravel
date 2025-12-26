<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Profession;
use App\Repositories\Abstracts\AbstractGlobalRepository;
use App\Repositories\Traits\RepositoryFiltersTrait;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class ProfessionRepository extends AbstractGlobalRepository
{
    use RepositoryFiltersTrait;

    protected function makeModel(): Model
    {
        return new Profession;
    }

    public function getActive(): Collection
    {
        return $this->model->newQuery()->where('is_active', true)->orderBy('name')->get();
    }
}
