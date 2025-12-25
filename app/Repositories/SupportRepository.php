<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Support;
use App\Repositories\Abstracts\AbstractTenantRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Repositório para operações de suporte tenant-aware
 *
 * Implementa métodos específicos para gerenciamento de tickets de suporte
 * com isolamento automático por tenant_id
 */
class SupportRepository extends AbstractTenantRepository
{
    /**
     * Define o Model a ser utilizado pelo Repositório.
     */
    protected function makeModel(): Model
    {
        return new Support();
    }

    /**
     * Encontra tickets de suporte por email dentro do tenant atual.
     *
     * @param string $email Email do usuário
     * @return Collection<Support> Coleção de tickets encontrados
     */
    public function findByEmail(string $email): Collection
    {
        return $this->getAllByTenant(['email' => $email]);
    }

    /**
     * Encontra tickets de suporte por status dentro do tenant atual.
     *
     * @param string $status Status do ticket
     * @return Collection<Support> Coleção de tickets com o status especificado
     */
    public function findByStatus(string $status): Collection
    {
        return $this->getAllByTenant(['status' => $status]);
    }

    /**
     * Encontra tickets de suporte abertos dentro do tenant atual.
     *
     * @return Collection<Support> Coleção de tickets abertos
     */
    public function findOpen(): Collection
    {
        return $this->findByStatus(Support::STATUS_ABERTO);
    }

    /**
     * Encontra tickets de suporte resolvidos dentro do tenant atual.
     *
     * @return Collection<Support> Coleção de tickets resolvidos
     */
    public function findResolved(): Collection
    {
        return $this->findByStatus(Support::STATUS_RESOLVIDO);
    }

    /**
     * Conta tickets de suporte por status dentro do tenant atual.
     *
     * @param string $status Status do ticket
     * @return int Número de tickets com o status especificado
     */
    public function countByStatus(string $status): int
    {
        return $this->model->where('status', $status)->count();
    }

    /**
     * Busca tickets de suporte por assunto (busca parcial) dentro do tenant atual.
     *
     * @param string $subject Assunto ou parte do assunto
     * @return Collection<Support> Coleção de tickets encontrados
     */
    public function searchBySubject(string $subject): Collection
    {
        return $this->model->where('subject', 'LIKE', "%{$subject}%")->get();
    }

    /**
     * Busca tickets de suporte por conteúdo da mensagem dentro do tenant atual.
     *
     * @param string $message Conteúdo ou parte da mensagem
     * @return Collection<Support> Coleção de tickets encontrados
     */
    public function searchByMessage(string $message): Collection
    {
        return $this->model->where('message', 'LIKE', "%{$message}%")->get();
    }

    /**
     * Cria um novo ticket de suporte a partir de um DTO.
     */
    public function createFromDTO(\App\DTOs\Support\SupportDTO $dto): Model
    {
        return $this->create($dto->toArrayWithoutNulls());
    }

    /**
     * Atualiza um ticket de suporte a partir de um DTO.
     */
    public function updateFromDTO(int $id, \App\DTOs\Support\SupportUpdateDTO $dto): ?Model
    {
        return $this->update($id, $dto->toArrayWithoutNulls());
    }

    /**
     * Obtém estatísticas de tickets por status dentro do tenant atual.
     *
     * @return array<string, int> Array com contadores por status
     */
    public function getStatusStats(): array
    {
        return [
            'aberto' => $this->countByStatus(Support::STATUS_ABERTO),
            'respondido' => $this->countByStatus(Support::STATUS_RESPONDIDO),
            'resolvido' => $this->countByStatus(Support::STATUS_RESOLVIDO),
            'fechado' => $this->countByStatus(Support::STATUS_FECHADO),
        ];
    }
}