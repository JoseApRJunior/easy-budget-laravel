<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Enums\ServiceStatus;
use App\Repositories\Contracts\GlobalRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use LogicException;

class ServiceStatusRepository implements GlobalRepositoryInterface
{
    /**
     * Busca status por slug
     */
    public function findBySlug(string $slug): ?ServiceStatus
    {
        return ServiceStatus::tryFrom($slug);
    }

    /**
     * Busca status ativos
     */
    public function findActive(?array $orderBy = null, ?int $limit = null): array
    {
        $activeStatuses = array_filter(
            ServiceStatus::cases(),
            fn (ServiceStatus $status) => $status->isActive()
        );

        // Ordena por order_index se não especificado
        $orderBy = $orderBy ?? ['order_index' => 'asc'];

        if ($orderBy['order_index'] === 'asc') {
            // usort( $activeStatuses, fn( $a, $b ) => $a->getPriorityOrder() <=> $b->getPriorityOrder() );
        } else {
            // usort( $activeStatuses, fn( $a, $b ) => $b->getPriorityOrder() <=> $a->getPriorityOrder() );
        }

        if ($limit) {
            $activeStatuses = array_slice($activeStatuses, 0, $limit);
        }

        return array_values($activeStatuses);
    }

    /**
     * Busca todos os status ordenados
     */
    public function findOrderedBy(string $field, string $direction = 'asc', ?int $limit = null): array
    {
        $allStatuses = ServiceStatus::cases();

        if ($field === 'order_index') {
            usort($allStatuses, function ($a, $b) use ($direction) {
                $valA = $a->order_index;
                $valB = $b->order_index;

                return $direction === 'asc' ? $valA <=> $valB : $valB <=> $valA;
            });
        } elseif ($field === 'name') {
            usort($allStatuses, function ($a, $b) use ($direction) {
                $valA = $a->getDescription();
                $valB = $b->getDescription();

                return $direction === 'asc' ? $valA <=> $valB : $valB <=> $valA;
            });
        }

        if ($limit) {
            $allStatuses = array_slice($allStatuses, 0, $limit);
        }

        return array_values($allStatuses);
    }

    /**
     * Busca status por valor (slug)
     */
    public function findById(string $status): ?ServiceStatus
    {
        return ServiceStatus::tryFrom($status);
    }

    /**
     * Retorna todos os status disponíveis
     */
    public function findAll(): array
    {
        return ServiceStatus::cases();
    }

    /**
     * Busca status por múltiplos critérios (implementação básica para compatibilidade interna)
     */
    protected function internalFindBy(array $criteria, ?array $orderBy = null, ?int $limit = null): array
    {
        $results = [];

        foreach (ServiceStatus::cases() as $status) {
            $matches = true;

            foreach ($criteria as $field => $value) {
                switch ($field) {
                    case 'is_active':
                    case 'active':
                        if ($status->isActive() !== $value) {
                            $matches = false;
                        }
                        break;
                    case 'slug':
                        if ($status->value !== $value) {
                            $matches = false;
                        }
                        break;
                }

                if (! $matches) {
                    break;
                }
            }

            if ($matches) {
                $results[] = $status;
            }
        }

        // Aplica ordenação
        if ($orderBy) {
            foreach ($orderBy as $field => $direction) {
                if ($field === 'order_index') {
                    usort($results, function ($a, $b) use ($direction) {
                        // Compara a propriedade order_index de cada objeto
                        if ($direction === 'asc') {
                            return $a->order_index <=> $b->order_index;
                        } else {
                            return $b->order_index <=> $a->order_index;
                        }
                    });
                }
            }
        }

        if ($limit) {
            $results = array_slice($results, 0, $limit);
        }

        return $results;
    }

    /**
     * {@inheritdoc}
     */
    public function findBy(string|array $field, mixed $value = null): Collection
    {
        $criteria = is_array($field) ? $field : [$field => $value];
        $results = $this->internalFindBy($criteria);

        $models = collect();
        foreach ($results as $status) {
            $models->push($this->enumToModel($status));
        }

        return $models;
    }

    /**
     * {@inheritdoc}
     */
    public function findOneBy(string|array $field, mixed $value = null, array $with = [], bool $withTrashed = false): ?Model
    {
        $criteria = is_array($field) ? $field : [$field => $value];
        $results = $this->internalFindBy($criteria, null, 1);

        return isset($results[0]) ? $this->enumToModel($results[0]) : null;
    }

    /**
     * Conta status por critérios (implementação básica)
     */
    public function countBy(array $criteria): int
    {
        return count($this->internalFindBy($criteria));
    }

    // Implementações da BaseRepositoryInterface

    /**
     * Encontra um registro pelo ID (adaptado para enum)
     */
    public function find(int $id): ?Model
    {
        $status = $this->findById($id);

        return $status ? $this->enumToModel($status) : null;
    }

    /**
     * Retorna todos os registros (adaptado para enum)
     */
    public function getAll(): Collection
    {
        $statuses = ServiceStatus::cases();
        $models = collect();

        foreach ($statuses as $status) {
            $models->push($this->enumToModel($status));
        }

        return $models;
    }

    /**
     * Cria um novo registro (não aplicável para enums)
     */
    public function create(array $data): Model
    {
        throw new LogicException('Cannot create new ServiceStatus - it is now an enum');
    }

    /**
     * Atualiza um registro (não aplicável para enums)
     */
    public function update(int $id, array $data): ?Model
    {
        throw new LogicException('Cannot update ServiceStatus - it is now an enum');
    }

    /**
     * Remove um registro (não aplicável para enums)
     */
    public function delete(int $id): bool
    {
        throw new LogicException('Cannot delete ServiceStatus - it is now an enum');
    }

    // Implementações da GlobalRepositoryInterface

    /**
     * Busca registros globais com filtros
     */
    public function getAllGlobal(
        array $criteria = [],
        ?array $orderBy = null,
        ?int $limit = null,
        ?int $offset = null,
    ): Collection {
        $results = $this->internalFindBy($criteria, $orderBy, $limit);

        if ($offset) {
            $results = array_slice($results, $offset);
        }

        $models = collect();
        foreach ($results as $status) {
            $models->push($this->enumToModel($status));
        }

        return $models;
    }

    /**
     * Busca registro global por ID
     */
    public function findGlobal(int $id): ?Model
    {
        return $this->find($id);
    }

    /**
     * Cria registro global (não aplicável para enums)
     */
    public function createGlobal(array $data): Model
    {
        throw new LogicException('Cannot create new ServiceStatus - it is now an enum');
    }

    /**
     * Atualiza registro global (não aplicável para enums)
     */
    public function updateGlobal(int $id, array $data): ?Model
    {
        throw new LogicException('Cannot update ServiceStatus - it is now an enum');
    }

    /**
     * Remove registro global (não aplicável para enums)
     */
    public function deleteGlobal(int $id): bool
    {
        throw new LogicException('Cannot delete ServiceStatus - it is now an enum');
    }

    /**
     * Paginação global (adaptada para enum)
     */
    public function paginateGlobal(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $allStatuses = $this->internalFindBy($filters);
        $total = count($allStatuses);

        // Simula paginação para enums
        $page = request('page', 1);
        $offset = ($page - 1) * $perPage;
        $items = array_slice($allStatuses, $offset, $perPage);

        $models = collect();
        foreach ($items as $status) {
            $models->push($this->enumToModel($status));
        }

        return new LengthAwarePaginator(
            $models,
            $total,
            $perPage,
            $page,
            ['path' => request()->url(), 'pageName' => 'page'],
        );
    }

    /**
     * Conta registros globais
     */
    public function countGlobal(array $filters = []): int
    {
        return $this->countBy($filters);
    }

    /**
     * Converte enum para um objeto Model-like para compatibilidade
     */
    private function enumToModel(ServiceStatus $status): Model
    {
        // Cria um objeto anônimo que se comporta como Model
        return new class($status) extends Model
        {
            public ServiceStatus $enum;

            public function __construct(ServiceStatus $enum)
            {
                $this->enum = $enum;
            }

            public function getKey()
            {
                return $this->enum->value;
            }

            public function getKeyName()
            {
                return 'slug'; // Alterado para slug pois é o valor único do enum
            }

            public function __get($key)
            {
                return match ($key) {
                    'id' => $this->enum->value, // ID agora é o value (slug)
                    'slug' => $this->enum->value,
                    'name' => $this->enum->getDescription(),
                    'color' => $this->enum->getColor(),
                    'icon' => $this->enum->getIcon(),
                    'order_index' => $this->enum->getOrderIndex(),
                    'is_active' => $this->enum->isActive(),
                    default => null,
                };
            }

            public function toArray(): array
            {
                return [
                    'id' => $this->enum->value,
                    'slug' => $this->enum->value,
                    'name' => $this->enum->getDescription(),
                    'color' => $this->enum->getColor(),
                    'icon' => $this->enum->getIcon(),
                    'order_index' => $this->enum->getOrderIndex(),
                    'is_active' => $this->enum->isActive(),
                ];
            }
        };
    }
}
