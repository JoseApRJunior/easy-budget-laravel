<?php

declare(strict_types=1);

namespace App\Actions\User;

use App\Models\User;
use App\Models\Tenant;
use App\Repositories\UserRepository;
use App\DTOs\User\UserDTO;
use Illuminate\Support\Facades\Hash;

class CreateUserAction
{
    public function __construct(
        private UserRepository $userRepository
    ) {}

    /**
     * Cria um novo usuário vinculado a um tenant.
     *
     * @param array $userData
     * @param Tenant $tenant
     * @return User
     */
    public function execute(array $userData, Tenant $tenant): User
    {
        return $this->userRepository->createFromDTO(new UserDTO(
            name: $userData['first_name'] . ' ' . $userData['last_name'],
            email: $userData['email'],
            password: $userData['password'] ?? null,
            is_active: true,
            tenant_id: $tenant->id
        ));
    }
}
