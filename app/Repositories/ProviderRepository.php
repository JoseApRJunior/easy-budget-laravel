<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Provider;
use App\Repositories\Abstracts\AbstractTenantRepository;
use Illuminate\Database\Eloquent\Model;

/**
 * Repositório para gerenciamento de provedores.
 *
 * Estende AbstractTenantRepository para operações tenant-aware
 * com isolamento automático de dados por empresa.
 */
class ProviderRepository extends AbstractTenantRepository
{
    /**
     * Define o Model a ser utilizado pelo Repositório.
     */
    protected function makeModel(): Model
    {
        return new Provider();
    }

    /**
     * Busca provedor por ID de usuário dentro do tenant atual.
     *
     * @param int $userId
     * @return Provider|null
     */
    public function findByUserId(int $userId): ?Provider
    {
        return $this->model->where('user_id', $userId)->first();
    }

    /**
     * Busca provedor por slug dentro do tenant atual.
     *
     * @param string $slug
     * @return Provider|null
     */
    public function findBySlug(string $slug): ?Provider
    {
        return $this->findByTenantAndSlug($slug);
    }
}
