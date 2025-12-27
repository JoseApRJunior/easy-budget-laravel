<?php

declare(strict_types=1);

namespace App\Repositories;

use App\DTOs\User\UserDTO;
use App\Models\User;
use App\Repositories\Abstracts\AbstractTenantRepository;
use App\Repositories\Traits\RepositoryFiltersTrait;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Repositório para operações de usuário tenant-aware
 *
 * Implementa métodos específicos para gerenciamento de usuários
 * com isolamento automático por tenant_id
 */
class UserRepository extends AbstractTenantRepository
{
    use RepositoryFiltersTrait;

    /**
     * Define o Model a ser utilizado pelo Repositório.
     */
    protected function makeModel(): Model
    {
        return new User;
    }

    /**
     * Cria um novo usuário a partir de um DTO.
     */
    public function createFromDTO(UserDTO $dto): User
    {
        return $this->model->newQuery()->create($dto->toArray());
    }

    /**
     * Atualiza um usuário a partir de um DTO.
     */
    public function updateFromDTO(int $id, UserDTO $dto): ?Model
    {
        return $this->update($id, $dto->toArrayWithoutNulls());
    }

    /**
     * Encontra usuário por email independentemente do tenant (busca global).
     *
     * @param  string  $email  Email do usuário
     * @return User|null Usuário encontrado ou null
     */
    public function findByEmail(string $email): ?User
    {
        return $this->model->withoutTenant()->where('email', $email)->first();
    }

    /**
     * Encontra usuários ativos dentro do tenant atual.
     *
     * @return Collection<User> Coleção de usuários ativos
     */
    public function findActive(): Collection
    {
        return $this->model->newQuery()->where('is_active', true)->get();
    }

    /**
     * Valida se email é único dentro do tenant atual.
     *
     * @param  string  $email  Email a ser verificado
     * @param  int|null  $excludeId  ID do usuário a ser excluído da verificação
     * @return bool True se é único, false caso contrário
     */
    public function isEmailUnique(string $email, ?int $excludeId = null): bool
    {
        return ! $this->model->newQuery()
            ->where('email', $email)
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->exists();
    }

    /**
     * Conta administradores dentro do tenant atual.
     *
     * @return int Número de administradores
     */
    public function countAdmins(): int
    {
        return $this->model->newQuery()->where('role', 'admin')->count();
    }

    /**
     * Verifica se slug existe dentro do tenant atual.
     *
     * @param  string  $slug  Slug a ser verificado
     * @param  int|null  $excludeId  ID do usuário a ser excluído da verificação
     * @return bool True se existe, false caso contrário
     */
    public function existsBySlug(string $slug, ?int $excludeId = null): bool
    {
        return $this->model->newQuery()
            ->where('slug', $slug)
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->exists();
    }

    /**
     * Encontra o primeiro usuário dentro do tenant atual.
     *
     * @return User|null Primeiro usuário ou null
     */
    public function first(): ?User
    {
        return $this->model->newQuery()->first();
    }

    /**
     * Encontra o último usuário dentro do tenant atual.
     *
     * @return User|null Último usuário ou null
     */
    public function last(): ?User
    {
        return $this->model->newQuery()->latest()->first();
    }
}
