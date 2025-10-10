<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

// ESTENDE A BASE para herdar todos os métodos CRUD
interface TenantRepositoryInterface extends BaseRepositoryInterface
{
    // Não precisa reescrever find(), getAll(), etc., pois já foram herdados.

    // Você só adicionaria métodos ESPECÍFICOS DE TENANT aqui, se necessário.
    // Exemplo:
    // public function findByTenantAndSlug(string $slug): ?Model;
}
