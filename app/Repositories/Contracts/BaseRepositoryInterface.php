<?php
declare(strict_types=1);

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

interface BaseRepositoryInterface
{
    public function find( int $id ): ?Model;
    public function getAll(): Collection;
    public function create( array $data ): Model;
    public function update( int $id, array $data ): ?Model;
    public function delete( int $id ): bool;
}
