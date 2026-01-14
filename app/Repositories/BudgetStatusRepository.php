<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Enums\BudgetStatus;
use App\Repositories\Contracts\GlobalRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use LogicException;

/**
 * Repositório para gerenciamento de status de orçamentos usando enums.
 *
 * Esta classe implementa GlobalRepositoryInterface para fornecer operações
 * compatíveis com BudgetStatus, que substitui o modelo BudgetStatus.
 * Como os status agora são enums, algumas operações não são aplicáveis.
 */
class BudgetStatusRepository implements GlobalRepositoryInterface
{
    /**
     * Busca status de orçamento por slug.
     *
     * @param  string  $slug  Slug único do status
     * @return BudgetStatus|null Status encontrado ou null se não existir
     */
    public function findBySlug(string $slug): ?BudgetStatus
    {
        return BudgetStatus::tryFrom($slug);
    }

    /**
     * Busca status ativos ordenados por order_index.
     *
     * @param  array|null  $orderBy  Ordenação personalizada (opcional)
     * @param  int|null  $limit  Limite de registros (opcional)
     * @return array<BudgetStatus> Lista de status ativos
     */
    public function findActive(?array $orderBy = null, ?int $limit = null): array
    {
        $activeStatuses = array_filter(
            BudgetStatus::cases(),
            fn (BudgetStatus $status) => $status->isActive()
        );

        // Ordena por order_index se não especificado
        $orderBy = $orderBy ?? ['order_index' => 'asc'];

        if ($orderBy['order_index'] === 'asc') {
            usort($activeStatuses, fn ($a, $b) => $a->getOrderIndex() <=> $b->getOrderIndex());
        } else {
            usort($activeStatuses, fn ($a, $b) => $b->getOrderIndex() <=> $a->getOrderIndex());
        }

        if ($limit) {
            $activeStatuses = array_slice($activeStatuses, 0, $limit);
        }

        return array_values($activeStatuses);
    }

    /**
     * Busca status ordenados por um campo específico.
     *
     * @param  string  $field  Campo para ordenação
     * @param  string  $direction  Direção da ordenação (asc/desc)
     * @param  int|null  $limit  Limite de registros (opcional)
     * @return array<BudgetStatus> Lista ordenada de status
     */
    public function findOrderedBy(string $field, string $direction = 'asc', ?int $limit = null): array
    {
        $allStatuses = BudgetStatus::cases();

        if ($field === 'order_index') {
            if ($direction === 'asc') {
                usort($allStatuses, fn ($a, $b) => $a->getOrderIndex() <=> $b->getOrderIndex());
            } else {
                usort($allStatuses, fn ($a, $b) => $b->getOrderIndex() <=> $a->getOrderIndex());
            }
        } elseif ($field === 'name') {
            if ($direction === 'asc') {
                usort($allStatuses, fn ($a, $b) => $a->getName() <=> $b->getName());
            } else {
                usort($allStatuses, fn ($a, $b) => $b->getName() <=> $a->getName());
            }
        }

        if ($limit) {
            $allStatuses = array_slice($allStatuses, 0, $limit);
        }

        return array_values($allStatuses);
    }

    /**
     * Busca status por nome.
     *
     * @param  string  $name  Nome do status
     * @return BudgetStatus|null Status encontrado ou null se não existir
     */
    public function findByName(string $name): ?BudgetStatus
    {
        foreach (BudgetStatus::cases() as $status) {
            if ($status->getName() === $name) {
                return $status;
            }
        }

        return null;
    }

    /**
     * Verifica se existe status com slug específico.
     *
     * @param  string  $slug  Slug para verificação
     * @return bool True se existe, false caso contrário
     */
    public function existsBySlug(string $slug): bool
    {
        return BudgetStatus::tryFrom($slug) !== null;
    }

    /**
     * Conta total de status ativos.
     *
     * @return int Total de status ativos
     */
    public function countActive(): int
    {
        return count(array_filter(
            BudgetStatus::cases(),
            fn (BudgetStatus $status) => $status->isActive()
        ));
    }

    /**
     * Busca status por cor específica.
     *
     * @param  string  $color  Cor do status
     * @return array<BudgetStatus> Lista de status com a cor especificada
     */
    public function findByColor(string $color): array
    {
        return array_filter(
            BudgetStatus::cases(),
            fn (BudgetStatus $status) => $status->getColor() === $color
        );
    }

    /**
     * Busca status dentro de um range de order_index.
     *
     * @param  int  $minOrderIndex  Mínimo order_index
     * @param  int  $maxOrderIndex  Máximo order_index
     * @return array<BudgetStatus> Lista de status no range especificado
     */
    public function findByOrderIndexRange(int $minOrderIndex, int $maxOrderIndex): array
    {
        return array_filter(
            BudgetStatus::cases(),
            fn (BudgetStatus $status) => $status->getOrderIndex() >= $minOrderIndex &&
            $status->getOrderIndex() <= $maxOrderIndex
        );
    }

    /**
     * Busca status por ID (mantido para compatibilidade)
     */
    public function findById(int $id): ?BudgetStatus
    {
        // Mapeia IDs antigos para enum values (compatibilidade com código legado)
        $idMapping = [
            1 => 'draft',
            2 => 'sent',      // Corrigido: era 'pending', agora 'sent'
            3 => 'approved',
            4 => 'completed', // Adicionado: COMPLETED
            5 => 'rejected',
            6 => 'expired',   // Adicionado: EXPIRED
            7 => 'cancelled',
            8 => 'revised',   // Adicionado: REVISED
        ];

        $slug = $idMapping[$id] ?? null;

        return $slug ? BudgetStatus::tryFrom($slug) : null;
    }

    /**
     * Retorna todos os status disponíveis
     */
    public function findAll(): array
    {
        return BudgetStatus::cases();
    }

    /**
     * Busca status por múltiplos critérios
     */
    public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null): array
    {
        $results = [];

        foreach (BudgetStatus::cases() as $status) {
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
                    case 'name':
                        if ($status->getName() !== $value) {
                            $matches = false;
                        }
                        break;
                    case 'color':
                        if ($status->getColor() !== $value) {
                            $matches = false;
                        }
                        break;
                    case 'order_index':
                        if (is_array($value) && count($value) === 3) {
                            [$operator, $min, $max] = $value;
                            $orderIndex = $status->getOrderIndex();
                            if ($operator === '>=' && $orderIndex < $min) {
                                $matches = false;
                            } elseif ($operator === '<=' && $orderIndex > $max) {
                                $matches = false;
                            }
                        } else {
                            if ($status->getOrderIndex() !== $value) {
                                $matches = false;
                            }
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
                    if ($direction === 'asc') {
                        usort($results, fn ($a, $b) => $a->getOrderIndex() <=> $b->getOrderIndex());
                    } else {
                        usort($results, fn ($a, $b) => $b->getOrderIndex() <=> $a->getOrderIndex());
                    }
                }
            }
        }

        if ($limit) {
            $results = array_slice($results, 0, $limit);
        }

        return $results;
    }

    /**
     * Busca um status por critérios
     */
    public function findOneBy(array $criteria): ?BudgetStatus
    {
        $results = $this->findBy($criteria, null, 1);

        return $results[0] ?? null;
    }

    /**
     * Conta status por critérios
     */
    public function countBy(array $criteria): int
    {
        return count($this->findBy($criteria));
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
        $statuses = BudgetStatus::cases();
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
        throw new LogicException('Cannot create new BudgetStatus - it is now an enum');
    }

    /**
     * Atualiza um registro (não aplicável para enums)
     */
    public function update(int $id, array $data): ?Model
    {
        throw new LogicException('Cannot update BudgetStatus - it is now an enum');
    }

    /**
     * Remove um registro (não aplicável para enums)
     */
    public function delete(int $id): bool
    {
        throw new LogicException('Cannot delete BudgetStatus - it is now an enum');
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
        $results = $this->findBy($criteria, $orderBy, $limit);

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
        throw new LogicException('Cannot create new BudgetStatus - it is now an enum');
    }

    /**
     * Atualiza registro global (não aplicável para enums)
     */
    public function updateGlobal(int $id, array $data): ?Model
    {
        throw new LogicException('Cannot update BudgetStatus - it is now an enum');
    }

    /**
     * Remove registro global (não aplicável para enums)
     */
    public function deleteGlobal(int $id): bool
    {
        throw new LogicException('Cannot delete BudgetStatus - it is now an enum');
    }

    /**
     * Paginação global (adaptada para enum)
     */
    public function paginateGlobal(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $allStatuses = $this->findBy($filters);
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
     * Cria uma instância do modelo (não aplicável para enums)
     */
    protected function makeModel(): Model
    {
        throw new LogicException('Cannot create BudgetStatus model instance - it is now an enum');
    }

    /**
     * Converte enum para um objeto Model-like para compatibilidade
     */
    private function enumToModel(BudgetStatus $status): Model
    {
        return new class($status) extends Model
        {
            public BudgetStatus $enum;

            public function __construct(BudgetStatus $enum)
            {
                $this->enum = $enum;
            }

            public function getKey()
            {
                return $this->enum->getOrderIndex();
            }

            public function getKeyName()
            {
                return 'id';
            }

            public function __get($key)
            {
                return match ($key) {
                    'id' => $this->enum->getOrderIndex(),
                    'slug' => $this->enum->value,
                    'name' => $this->enum->getName(),
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
                    'id' => $this->enum->getOrderIndex(),
                    'slug' => $this->enum->value,
                    'name' => $this->enum->getName(),
                    'color' => $this->enum->getColor(),
                    'icon' => $this->enum->getIcon(),
                    'order_index' => $this->enum->getOrderIndex(),
                    'is_active' => $this->enum->isActive(),
                ];
            }
        };
    }
}
