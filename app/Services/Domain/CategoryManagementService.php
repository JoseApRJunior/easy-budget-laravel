<?php

declare(strict_types=1);

namespace App\Services\Domain;

use App\Models\Category;
use App\Support\ServiceResult;
use App\Enums\OperationStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Serviço para gerenciamento complexo de categorias
 *
 * Centraliza lógica de negócio que antes estava espalhada entre
 * Controller, Model e Pivot model.
 */
class CategoryManagementService
{
    /**
     * Define categoria como padrão para um tenant
     *
     * Garante que apenas UMA categoria seja default por tenant,
     * removendo o flag de todas as outras.
     *
     * @param Category $category Categoria a ser definida como padrão
     * @param int $tenantId ID do tenant
     * @return ServiceResult
     */
    public function setDefaultCategory(Category $category, int $tenantId): ServiceResult
    {
        try {
            DB::beginTransaction();

            // Remover default de todas as outras categorias deste tenant
            DB::table('category_tenant')
                ->where('tenant_id', $tenantId)
                ->where('category_id', '!=', $category->id)
                ->update([
                    'is_default' => false,
                    'updated_at' => now(),
                ]);

            // Definir esta como default
            // syncWithoutDetaching mantém outros tenants que já têm essa categoria
            $category->tenants()->syncWithoutDetaching([
                $tenantId => [
                    'is_default' => true,
                    'is_custom' => $category->isCustomFor($tenantId), // Preserva se é custom
                ],
            ]);

            DB::commit();

            Log::info('Default category set', [
                'category_id' => $category->id,
                'category_name' => $category->name,
                'tenant_id' => $tenantId,
            ]);

            return ServiceResult::success($category, 'Categoria definida como padrão');

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to set default category', [
                'category_id' => $category->id,
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return ServiceResult::error(
                OperationStatus::ERROR,
                'Erro ao definir categoria padrão: ' . $e->getMessage(),
                null,
                $e
            );
        }
    }

    /**
     * Verifica se categoria pode ser deletada
     *
     * Valida:
     * - Não possui serviços associados
     * - Não possui produtos associados
     * - Não possui subcategorias
     *
     * @param Category $category
     * @return ServiceResult
     */
    public function canDelete(Category $category): ServiceResult
    {
        // Verificar services
        if ($category->services()->exists()) {
            return ServiceResult::error(
                OperationStatus::VALIDATION_ERROR,
                'Categoria possui serviços associados'
            );
        }

        // Verificar products
        $hasProducts = DB::table('products')
            ->where('category_id', $category->id)
            ->whereNull('deleted_at') // Considerar apenas produtos não deletados
            ->exists();

        if ($hasProducts) {
            return ServiceResult::error(
                OperationStatus::VALIDATION_ERROR,
                'Categoria possui produtos associados'
            );
        }

        // Verificar subcategorias
        if ($category->hasChildren()) {
            return ServiceResult::error(
                OperationStatus::VALIDATION_ERROR,
                'Categoria possui subcategorias'
            );
        }

        return ServiceResult::success(true, 'Categoria pode ser deletada');
    }

    /**
     * Busca todos os descendentes de uma categoria recursivamente
     *
     * Usa Common Table Expression (CTE) recursiva para performance.
     * Muito mais eficiente que loops iterativos.
     *
     * @param int $categoryId
     * @return array Array de IDs dos descendentes
     */
    public function getDescendantIds(int $categoryId): array
    {
        $descendants = DB::select("
            WITH RECURSIVE category_tree AS (
                -- Anchor: filhos diretos
                SELECT id FROM categories
                WHERE parent_id = ?
                  AND deleted_at IS NULL

                UNION ALL

                -- Recursão: filhos dos filhos
                SELECT c.id
                FROM categories c
                INNER JOIN category_tree ct ON c.parent_id = ct.id
                WHERE c.deleted_at IS NULL
            )
            SELECT id FROM category_tree
        ", [$categoryId]);

        return array_column($descendants, 'id');
    }

    /**
     * Verifica se categoria ou seus descendentes estão em uso
     *
     * Útil para validar desativação em cascata.
     *
     * @param Category $category
     * @return bool
     */
    public function isInUse(Category $category): bool
    {
        $categoryIds = array_merge(
            [$category->id],
            $this->getDescendantIds($category->id)
        );

        // Verificar services
        $hasServices = DB::table('services')
            ->whereIn('category_id', $categoryIds)
            ->exists();

        if ($hasServices) {
            return true;
        }

        // Verificar products
        $hasProducts = DB::table('products')
            ->whereIn('category_id', $categoryIds)
            ->whereNull('deleted_at')
            ->exists();

        return $hasProducts;
    }

    /**
     * Anexa categoria a um tenant
     *
     * @param Category $category
     * @param int $tenantId
     * @param bool $isCustom Se é categoria custom do tenant
     * @param bool $isDefault Se deve ser definida como padrão
     * @return ServiceResult
     */
    public function attachToTenant(
        Category $category,
        int $tenantId,
        bool $isCustom = false,
        bool $isDefault = false
    ): ServiceResult {
        try {
            DB::beginTransaction();

            // Se deve ser default, remover flag de outras
            if ($isDefault) {
                DB::table('category_tenant')
                    ->where('tenant_id', $tenantId)
                    ->update(['is_default' => false]);
            }

            // Anexar categoria ao tenant
            $category->tenants()->syncWithoutDetaching([
                $tenantId => [
                    'is_custom' => $isCustom,
                    'is_default' => $isDefault,
                ],
            ]);

            DB::commit();

            Log::info('Category attached to tenant', [
                'category_id' => $category->id,
                'tenant_id' => $tenantId,
                'is_custom' => $isCustom,
                'is_default' => $isDefault,
            ]);

            return ServiceResult::success(true, 'Categoria vinculada ao tenant');

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to attach category to tenant', [
                'category_id' => $category->id,
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ]);

            return ServiceResult::error(
                OperationStatus::ERROR,
                'Erro ao vincular categoria: ' . $e->getMessage(),
                null,
                $e
            );
        }
    }

    /**
     * Remove vínculo de categoria com tenant
     *
     * @param Category $category
     * @param int $tenantId
     * @return ServiceResult
     */
    public function detachFromTenant(Category $category, int $tenantId): ServiceResult
    {
        try {
            // Verificar se categoria está em uso por este tenant
            $inUseByTenant = DB::table('services')
                ->where('category_id', $category->id)
                ->where('tenant_id', $tenantId)
                ->exists();

            if ($inUseByTenant) {
                return ServiceResult::error(
                    OperationStatus::VALIDATION_ERROR,
                    'Categoria está em uso por este tenant'
                );
            }

            $category->tenants()->detach($tenantId);

            Log::info('Category detached from tenant', [
                'category_id' => $category->id,
                'tenant_id' => $tenantId,
            ]);

            return ServiceResult::success(true, 'Categoria desvinculada do tenant');

        } catch (\Exception $e) {
            Log::error('Failed to detach category from tenant', [
                'category_id' => $category->id,
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ]);

            return ServiceResult::error(
                OperationStatus::ERROR,
                'Erro ao desvincular categoria',
                null,
                $e
            );
        }
    }
    /**
     * Cria uma nova categoria
     *
     * @param array $data Dados da categoria
     * @param int|null $tenantId ID do tenant (null se for admin/global)
     * @return ServiceResult
     */
    public function createCategory(array $data, ?int $tenantId = null): ServiceResult
    {
        try {
            return DB::transaction(function () use ($data, $tenantId) {
                // Criar categoria
                $category = Category::create([
                    'name' => $data['name'],
                    'slug' => \Illuminate\Support\Str::slug($data['name']), // Garantir slug atualizado
                    'parent_id' => $data['parent_id'] ?? null,
                    'is_active' => $data['is_active'] ?? true,
                ]);

                // Se for tenant, vincular como custom
                if ($tenantId !== null) {
                    $this->attachToTenant($category, $tenantId, true, false);
                }

                // Se for Admin (Global), não vinculamos a ninguém inicialmente.
                // Ela será visível para todos via scopeGlobalOnly/forTenant.

                Log::info('Category created', [
                    'category_id' => $category->id,
                    'tenant_id' => $tenantId,
                    'is_global' => $tenantId === null,
                ]);

                return ServiceResult::success($category, 'Categoria criada com sucesso');
            });
        } catch (\Exception $e) {
            Log::error('Failed to create category', [
                'data' => $data,
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ]);

            return ServiceResult::error(
                OperationStatus::ERROR,
                'Erro ao criar categoria: ' . $e->getMessage(),
                null,
                $e
            );
        }
    }

    /**
     * Atualiza uma categoria existente
     *
     * @param Category $category
     * @param array $data
     * @return ServiceResult
     */
    public function updateCategory(Category $category, array $data): ServiceResult
    {
        try {
            return DB::transaction(function () use ($category, $data) {
                // Verificar desativação
                if ($category->is_active && isset($data['is_active']) && !$data['is_active']) {
                    if ($this->isInUse($category)) {
                        return ServiceResult::error(
                            OperationStatus::VALIDATION_ERROR,
                            'Não é possível desativar: categoria ou subcategoria em uso.'
                        );
                    }
                }

                $category->update([
                    'name' => $data['name'],
                    'slug' => \Illuminate\Support\Str::slug($data['name']),
                    'parent_id' => $data['parent_id'] ?? null,
                    'is_active' => $data['is_active'] ?? $category->is_active,
                ]);

                Log::info('Category updated', [
                    'category_id' => $category->id,
                ]);

                return ServiceResult::success($category, 'Categoria atualizada com sucesso');
            });
        } catch (\Exception $e) {
            Log::error('Failed to update category', [
                'category_id' => $category->id,
                'error' => $e->getMessage(),
            ]);

            return ServiceResult::error(
                OperationStatus::ERROR,
                'Erro ao atualizar categoria: ' . $e->getMessage(),
                null,
                $e
            );
        }
    }

    /**
     * Deleta uma categoria
     *
     * @param Category $category
     * @return ServiceResult
     */
    public function deleteCategory(Category $category): ServiceResult
    {
        try {
            return DB::transaction(function () use ($category) {
                // Validar deleção
                $canDelete = $this->canDelete($category);
                if ($canDelete->isError()) {
                    return $canDelete;
                }

                $category->delete();

                Log::info('Category deleted', [
                    'category_id' => $category->id,
                ]);

                return ServiceResult::success(null, 'Categoria excluída com sucesso');
            });
        } catch (\Exception $e) {
            Log::error('Failed to delete category', [
                'category_id' => $category->id,
                'error' => $e->getMessage(),
            ]);

            return ServiceResult::error(
                OperationStatus::ERROR,
                'Erro ao excluir categoria: ' . $e->getMessage(),
                null,
                $e
            );
        }
    }
}
