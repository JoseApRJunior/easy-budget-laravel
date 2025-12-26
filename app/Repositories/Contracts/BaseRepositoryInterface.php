<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Interface BaseRepositoryInterface
 *
 * Contrato fundamental para operações básicas de acesso a dados (CRUD).
 * Define o contrato mínimo que todos os repositórios devem implementar.
 *
 * Esta interface serve como base para especializações como GlobalRepositoryInterface
 * e TenantRepositoryInterface, seguindo o princípio da Segregação de Interfaces (ISP).
 */
interface BaseRepositoryInterface
{
    /**
     * Encontra um registro pelo seu identificador único.
     *
     * @param  int  $id  Identificador único do registro.
     * @return Model|null Retorna o modelo encontrado ou null se não existir.
     *
     * @example
     * $user = $userRepository->find(1);
     * if ($user) {
     *     echo $user->name;
     * }
     */
    public function find(int $id): ?Model;

    /**
     * Recupera todos os registros disponíveis.
     *
     * @return Collection<Model> Coleção de todos os modelos.
     *
     * @example
     * $users = $userRepository->getAll();
     * foreach ($users as $user) {
     *     echo $user->name;
     * }
     */
    public function getAll(): Collection;

    /**
     * Cria um novo registro com os dados fornecidos.
     *
     * @param  array<string, mixed>  $data  Dados para criação do registro.
     * @return Model Modelo criado com os dados persistidos.
     *
     * @throws \Illuminate\Database\QueryException Em caso de violação de constraints.
     *
     * @example
     * $userData = [
     *     'name' => 'João Silva',
     *     'email' => 'joao@example.com',
     *     'tenant_id' => 1
     * ];
     * $user = $userRepository->create($userData);
     */
    public function create(array $data): Model;

    /**
     * Atualiza um registro existente pelo seu ID.
     *
     * @param  int  $id  Identificador único do registro a ser atualizado.
     * @param  array<string, mixed>  $data  Dados para atualização.
     * @return Model|null Retorna o modelo atualizado ou null se não encontrado.
     *
     * @example
     * $updatedUser = $userRepository->update(1, ['name' => 'João Atualizado']);
     */
    public function update(int $id, array $data): ?Model;

    /**
     * Remove um registro pelo seu ID.
     *
     * @param  int  $id  Identificador único do registro a ser removido.
     * @return bool Retorna true se removido com sucesso, false caso contrário.
     *
     * @example
     * $deleted = $userRepository->delete(1);
     * if ($deleted) {
     *     echo "Usuário removido com sucesso";
     * }
     */
    public function delete(int $id): bool;
}
