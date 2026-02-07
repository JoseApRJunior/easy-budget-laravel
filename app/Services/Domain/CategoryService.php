<?php

declare(strict_types=1);

namespace App\Services\Domain;

use App\Actions\Category\CreateCategoryAction;
use App\Actions\Category\UpdateCategoryAction;
use App\DTOs\Category\CategoryDTO;
use App\DTOs\Category\CategoryFilterDTO;
use App\Enums\OperationStatus;
use App\Models\Category;
use App\Repositories\CategoryRepository;
use App\Services\Core\Abstracts\AbstractBaseService;
use App\Support\ServiceResult;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Serviço para gerenciamento de categorias com arquitetura refinada.
 *
 * Categorias são isoladas por tenant - cada empresa gerencia suas próprias categorias.
 * Implementa a arquitetura padronizada com validação robusta e operações transacionais.
 *
 * @property CategoryRepository $repository
 */
class CategoryService extends AbstractBaseService
{
    public function __construct(
        CategoryRepository $repository,
        private CreateCategoryAction $createAction,
        private UpdateCategoryAction $updateAction,
    ) {
        parent::__construct($repository);
    }

    protected function getSupportedFilters(): array
    {
        return ['id', 'name', 'slug', 'is_active', 'parent_id', 'created_at', 'updated_at'];
    }

    /**
     * Valida dados da categoria.
     */
    public function validate(array $data, bool $isUpdate = false): ServiceResult
    {
        $validator = Validator::make($data, Category::businessRules());

        if ($validator->fails()) {
            return $this->error(OperationStatus::INVALID_DATA, implode(', ', $validator->errors()->all()));
        }

        return $this->success($data);
    }

    /**
     * Lista categorias do tenant com filtros e paginação opcional.
     */
    public function getFilteredCategories(CategoryFilterDTO $filterDto, bool $paginate = true): ServiceResult
    {
        return $this->safeExecute(function () use ($filterDto, $paginate) {
            if (! $this->tenantId()) {
                return $this->error(OperationStatus::ERROR, 'Tenant não identificado');
            }

            // Normalização padronizada via Trait usando o array do DTO
            $normalizedFilters = $this->normalizeFilters($filterDto->toFilterArray(), [
                'aliases' => ['active' => 'is_active'],
                'likes' => ['name', 'slug'],
            ]);

            $with = ['parent' => fn ($q) => $q->withTrashed()];

            if ($paginate) {
                $result = $this->repository->getPaginated(
                    $normalizedFilters,
                    $filterDto->per_page,
                    $with,
                );
                Log::info('Categorias carregadas (paginado)', ['total' => $result->total()]);
            } else {
                $result = $this->repository->getAllByTenant(
                    $normalizedFilters,
                    null, // order by can be added if needed
                );
                // getAllByTenant doesn't load relations by default in AbstractTenantRepository
                // but we can load them if needed.
                if (! empty($with)) {
                    $result->load($with);
                }
                Log::info('Categorias carregadas (coleção)', ['total' => $result->count()]);
            }

            return $result;
        }, 'Erro ao carregar categorias.');
    }

    /**
     * Obtém categorias pai ativas para uso em formulários.
     */
    public function getParentCategories(): ServiceResult
    {
        return $this->safeExecute(function () {
            $parents = $this->repository->listParents();

            return $this->success($parents, 'Categorias pai carregadas com sucesso.');
        }, 'Erro ao carregar categorias pai.');
    }

    /**
     * Cria nova categoria para o tenant.
     */
    public function createCategory(CategoryDTO $dto): ServiceResult
    {
        return $this->safeExecute(function () use ($dto) {
            $tenantId = (int) $this->tenantId();
            $data = $dto->toArray();

            // Validações de negócio pré-action
            if (! empty($data['slug']) && ! Category::validateUniqueSlug($data['slug'], $tenantId)) {
                return $this->error(OperationStatus::INVALID_DATA, 'Slug já existe neste tenant');
            }

            if (! empty($data['parent_id'])) {
                $parentResult = $this->validateAndGetParent((int) $data['parent_id'], $tenantId);
                if ($parentResult->isError()) {
                    return $parentResult;
                }

                $parent = $parentResult->getData();
                if (! $parent->is_active && ($data['is_active'] ?? true)) {
                    return $this->error(OperationStatus::INVALID_DATA, 'Não é possível criar uma subcategoria ativa sob uma categoria pai inativa.');
                }

                if ((new Category(['parent_id' => $data['parent_id']]))->wouldCreateCircularReference((int) $data['parent_id'])) {
                    return $this->error(OperationStatus::INVALID_DATA, 'Não é possível criar referência circular');
                }
            }

            // Delega a criação para a Action
            $category = $this->createAction->execute($dto, $tenantId);

            return $this->success($category, 'Categoria criada com sucesso.');
        }, 'Erro ao criar categoria.');
    }

    /**
     * Atualiza categoria.
     */
    public function updateCategory(int $id, CategoryDTO $dto): ServiceResult
    {
        return $this->safeExecute(function () use ($id, $dto) {
            $tenantId = (int) $this->tenantId();
            $ownerResult = $this->findAndVerifyOwnership($id);
            if ($ownerResult->isError()) {
                return $ownerResult;
            }

            $category = $ownerResult->getData();
            $data = $dto->toArray();

            // Validações de negócio pré-action
            if (! empty($data['slug']) && ! Category::validateUniqueSlug($data['slug'], $tenantId, $id)) {
                return $this->error(OperationStatus::INVALID_DATA, 'Slug já existe neste tenant');
            }

            if (! empty($data['parent_id'])) {
                if ($data['parent_id'] == $id) {
                    return $this->error(OperationStatus::INVALID_DATA, 'Categoria não pode ser pai de si mesma');
                }

                $parentResult = $this->validateAndGetParent((int) $data['parent_id'], $tenantId);
                if ($parentResult->isError()) {
                    return $parentResult;
                }

                if ($category->wouldCreateCircularReference((int) $data['parent_id'])) {
                    return $this->error(OperationStatus::INVALID_DATA, 'Não é possível criar referência circular');
                }
            }

            // Regras de negócio para ativação/desativação
            if (isset($data['is_active'])) {
                $newStatus = (bool) $data['is_active'];
                $oldStatus = (bool) $category->is_active;

                if ($newStatus !== $oldStatus && $newStatus === true) {
                    $parentId = $data['parent_id'] ?? $category->parent_id;
                    if ($parentId) {
                        $parent = Category::find($parentId);
                        if ($parent && ! $parent->is_active) {
                            return $this->error(OperationStatus::INVALID_DATA, "Não é possível ativar a subcategoria porque a categoria pai '{$parent->name}' está inativa.");
                        }
                    }
                }
            }

            // Delega a atualização para a Action
            $updatedCategory = $this->updateAction->execute($category, $dto, $tenantId);

            return $this->success($updatedCategory, 'Categoria atualizada com sucesso.');
        }, 'Erro ao atualizar categoria.');
    }

    /**
     * Remove categoria.
     */
    public function deleteCategory(int $id): ServiceResult
    {
        return $this->safeExecute(function () use ($id) {
            $ownerResult = $this->findAndVerifyOwnership($id);
            if ($ownerResult->isError()) {
                return $ownerResult;
            }

            $category = $ownerResult->getData();

            // Verificações de "em uso" antes de deletar
            if ($category->hasChildren()) {
                return $this->error(OperationStatus::INVALID_DATA, 'Não é possível excluir uma categoria que possui subcategorias.');
            }

            if ($category->services()->exists()) {
                return $this->error(OperationStatus::INVALID_DATA, 'Não é possível excluir uma categoria que possui serviços vinculados.');
            }

            if ($category->products()->exists()) {
                return $this->error(OperationStatus::INVALID_DATA, 'Não é possível excluir uma categoria que possui produtos vinculados.');
            }

            return $this->delete($id);
        }, 'Erro ao remover categoria.');
    }

    /**
     * Busca categorias por nome/descrição com pesquisa parcial.
     */
    public function searchCategories(
        string $search,
        array $filters = [],
        ?array $orderBy = null,
        ?int $limit = null,
    ): ServiceResult {
        return $this->safeExecute(
            fn () => $this->repository->search($search, $this->normalizeFilters($filters), $orderBy, $limit),
            'Erro ao buscar categorias.'
        );
    }

    /**
     * Busca categorias ativas (não deletadas) do tenant.
     */
    public function getActive(
        array $filters = [],
        ?array $orderBy = null,
        ?int $limit = null,
    ): ServiceResult {
        return $this->safeExecute(
            fn () => $this->repository->getActive($this->normalizeFilters($filters), $orderBy, $limit),
            'Erro ao buscar categorias ativas.'
        );
    }

    /**
     * Busca categorias deletadas (soft delete) do tenant.
     */
    public function getDeleted(
        array $filters = [],
        ?array $orderBy = null,
        ?int $limit = null,
    ): ServiceResult {
        return $this->safeExecute(function () use ($filters, $orderBy, $limit) {
            $filters['deleted'] = 'only';

            return $this->repository->getDeleted($this->normalizeFilters($filters), $orderBy, $limit);
        }, 'Erro ao buscar categorias deletadas.');
    }

    /**
     * Restaura categorias deletadas (soft delete) por IDs.
     */
    public function restoreCategories(array $ids): ServiceResult
    {
        return $this->safeExecute(function () use ($ids) {
            foreach ($ids as $id) {
                $category = $this->repository->findOneBy('id', (int) $id, [], true);
                if ($category && $category->parent_id) {
                    $parent = $this->repository->findOneBy('id', $category->parent_id, [], true);
                    if ($parent) {
                        if ($parent->trashed()) {
                            return $this->error(
                                OperationStatus::INVALID_DATA,
                                "Não é possível restaurar a subcategoria '{$category->name}' porque a categoria pai ({$parent->name}) está na lixeira. Restaure o pai primeiro."
                            );
                        }
                        if (! $parent->is_active && $category->is_active) {
                            return $this->error(
                                OperationStatus::INVALID_DATA,
                                "Não é possível restaurar a subcategoria '{$category->name}' como ativa porque a categoria pai ({$parent->name}) está inativa."
                            );
                        }
                    }
                }
            }

            return $this->repository->restoreMany($ids);
        }, 'Erro ao restaurar categorias.');
    }

    /**
     * Restaura categoria deletada (soft delete) por slug.
     */
    public function restoreCategoriesBySlug(string $slug): ServiceResult
    {
        return $this->safeExecute(function () use ($slug) {
            $category = $this->repository->findBySlug($slug, true);

            if (! $category) {
                return $this->error(OperationStatus::NOT_FOUND, 'Categoria não encontrada ou não está excluída');
            }

            // Validação: Não permitir restaurar filho se o pai estiver deletado ou inativo
            if ($category->parent_id) {
                $parent = $this->repository->findOneBy('id', $category->parent_id, [], true);
                if ($parent) {
                    if ($parent->trashed()) {
                        return $this->error(
                            OperationStatus::INVALID_DATA,
                            "Não é possível restaurar esta subcategoria porque a categoria pai ({$parent->name}) está na lixeira. Restaure o pai primeiro."
                        );
                    }
                    if (! $parent->is_active && $category->is_active) {
                        return $this->error(
                            OperationStatus::INVALID_DATA,
                            "Não é possível restaurar esta subcategoria como ativa porque a categoria pai ({$parent->name}) está inativa."
                        );
                    }
                }
            }

            $success = $this->repository->restoreBySlug($slug);

            if (! $success) {
                return $this->error(OperationStatus::ERROR, 'Erro ao restaurar a categoria');
            }

            return $this->success(null, 'Categoria restaurada com sucesso');
        }, 'Erro ao restaurar categoria.');
    }

    /**
     * Busca categoria por slug dentro do tenant com relacionamentos e contagens.
     */
    public function findBySlug(string $slug, array $with = [], bool $withTrashed = true, array $loadCounts = []): ServiceResult
    {
        return $this->safeExecute(function () use ($slug, $with, $withTrashed, $loadCounts) {
            $entity = $this->repository->findBySlug($slug, $withTrashed);

            if (! $entity) {
                return $this->error(OperationStatus::NOT_FOUND, 'Categoria não encontrada');
            }

            if (! empty($with)) {
                $entity->load($with);
            }

            if (! empty($loadCounts)) {
                $entity->loadCount($loadCounts);
            }

            return $entity;
        }, 'Erro ao buscar categoria.');
    }

    /**
     * Alterna status (ativo/inativo) de uma categoria via slug.
     */
    public function toggleCategoryStatus(string $slug): ServiceResult
    {
        return $this->safeExecute(function () use ($slug) {
            $ownerResult = $this->findBySlug($slug);
            if ($ownerResult->isError()) {
                return $ownerResult;
            }

            $category = $ownerResult->getData();
            $newStatus = ! $category->is_active;

            // Regras de negócio para ativação/desativação
            if ($newStatus === true && $category->parent_id && $category->parent_id !== $category->id) {
                // Ao ativar um filho, o pai deve estar ativo e não pode estar na lixeira
                $parent = Category::withTrashed()->find($category->parent_id);
                if ($parent && (! $parent->is_active || $parent->trashed())) {
                    $reason = $parent->trashed() ? 'está na lixeira' : 'está inativa';

                    return $this->error(OperationStatus::INVALID_DATA, "Não é possível ativar a subcategoria '{$category->name}' porque a categoria pai '{$parent->name}' {$reason}.");
                }
            }

            return DB::transaction(function () use ($category, $newStatus) {
                // Se estiver desativando um pai, desativa todos os filhos em cascata
                if ($newStatus === false) {
                    Category::where('parent_id', $category->id)
                        ->where('tenant_id', $this->tenantId())
                        ->update(['is_active' => false]);
                }

                $category->update(['is_active' => $newStatus]);

                $message = $newStatus ? 'Categoria ativada com sucesso' : 'Categoria desativada com sucesso';

                return $this->success($category->fresh(), $message);
            });
        }, 'Erro ao alterar status da categoria.');
    }

    /**
     * Lista todas as categorias do tenant ordenadas por nome.
     */
    public function listAll(): ServiceResult
    {
        return $this->safeExecute(fn () => $this->repository->findOrderedByName('asc'), 'Erro ao listar categorias.');
    }

    /**
     * Busca categoria por ID dentro do tenant com verificações.
     */
    public function getCategoryById(int $id): ServiceResult
    {
        return $this->findAndVerifyOwnership($id);
    }

    /**
     * Dashboard de categorias com estatísticas.
     */
    public function getDashboardData(): ServiceResult
    {
        return $this->safeExecute(function () {
            $total = $this->repository->countByTenant();
            $active = $this->repository->countActiveByTenant();
            $deleted = $this->repository->countDeletedByTenant();
            $recentCategories = $this->repository->getRecentByTenant(5);

            $stats = [
                'total_categories' => $total,
                'active_categories' => $active,
                'inactive_categories' => max(0, $total - $active),
                'deleted_categories' => $deleted,
                'recent_categories' => $recentCategories,
            ];

            return $this->success($stats, 'Estatísticas obtidas com sucesso');
        }, 'Erro ao obter estatísticas de categorias.');
    }

    private function findAndVerifyOwnership(int $id): ServiceResult
    {
        $result = $this->findById($id);
        if ($result->isError()) {
            return $result;
        }

        $category = $result->getData();
        if ($category->tenant_id !== $this->tenantId()) {
            return $this->error(OperationStatus::UNAUTHORIZED, 'Categoria não pertence ao tenant atual');
        }

        return $this->success($category);
    }

    private function validateAndGetParent(int $parentId, int $tenantId): ServiceResult
    {
        $parent = Category::find($parentId);
        if (! $parent || $parent->tenant_id !== $tenantId) {
            return $this->error(OperationStatus::INVALID_DATA, 'Categoria pai inválida');
        }

        return $this->success($parent);
    }
}
