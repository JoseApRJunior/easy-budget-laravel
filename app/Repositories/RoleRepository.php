<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Role;

class RoleRepository extends AbstractNoTenantRepository
{
    protected string $modelClass = Role::class;

    public function __construct()
    {
        parent::__construct();
    }

    public function findByName( string $name ): ?Role
    {
        return $this->findOneBy( [ 'name' => $name ] );
    }

    public function findActive(): array
    {
        return $this->findBy( [ 'status' => 'active' ] );
    }

    public function findOrderedByName( string $direction = 'asc' ): array
    {
        return $this->findOrderedBy( 'name', $direction );
    }

}
