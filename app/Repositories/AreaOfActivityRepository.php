<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\AreaOfActivity;
use App\Repositories\Abstracts\AbstractGlobalRepository;
use App\Repositories\Traits\RepositoryFiltersTrait;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class AreaOfActivityRepository extends AbstractGlobalRepository
{
    use RepositoryFiltersTrait;

    protected function makeModel(): Model
    {
        return new AreaOfActivity;
    }

    public function getActive(): Collection
    {
        return $this->model->newQuery()->where('is_active', true)->orderBy('name')->get();
    }

    /**
     * Busca uma área de atuação pelo slug.
     */
    public function findBySlug(string $slug): ?AreaOfActivity
    {
        return $this->model->newQuery()->where('slug', $slug)->first();
    }
}
