<?php

declare(strict_types=1);

namespace App\Services\Domain;

use App\Enums\OperationStatus;
use App\Events\CategoryCreated;
use App\Events\CategoryDeleted;
use App\Events\CategoryUpdated;
use App\Events\DefaultCategoryChanged;
use App\Models\Category;
use App\Support\ServiceResult;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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
    public function setDefaultCategory( Category $category, int $tenantId ): ServiceResult
    {
        try {
            DB::beginTransaction();

            // Remover default de todas as outras categorias deste tenant
            DB::table( 'category_tenant' )
                ->where( 'tenant_id', $tenantId )
                ->where( 'category_id', '!=', $category->id )
                ->update( [
                    'is_default' => false,
                    'updated_at' => now(),
                ] );

            // Definir esta como default
            // syncWithoutDetaching mantém outros tenants que já têm essa categoria
            $category->tenants()->syncWithoutDetaching( [
                $tenantId => [
                    'is_default' => true,
                    'is_custom'  => $category->isCustomFor( $tenantId ), // Preserva se é custom
                ],
            ] );

            DB::commit();

            Log::info( 'Default category set', [
                'category_id'   => $category->id,
                'category_name' => $category->name,
                'tenant_id'     => $tenantId,
            ] );

            if ( class_exists( DefaultCategoryChanged::class) ) {
                event( new DefaultCategoryChanged( $category->id, $tenantId ) );
            }

            return ServiceResult::success( $category, 'Categoria definida como padrão' );
        } catch ( \Exception $e ) {
            DB::rollBack();

            Log::error( 'Failed to set default category', [
                'category_id' => $category->id,
                'tenant_id'   => $tenantId,
                'error'       => $e->getMessage(),
                'trace'       => $e->getTraceAsString(),
            ] );

            return ServiceResult::error(
                OperationStatus::ERROR,
                'Erro ao definir categoria padrão: ' . $e->getMessage(),
                null,
                $e,
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
    public function canDelete( Category $category ): ServiceResult
    {
        // Verificar services
        if ( $category->services()->exists() ) {
            return ServiceResult::error(
                OperationStatus::VALIDATION_ERROR,
                'Categoria possui serviços associados',
            );
        }

        // Verificar products
        $hasProducts = DB::table( 'products' )
            ->where( 'category_id', $category->id )
            ->whereNull( 'deleted_at' ) // Considerar apenas produtos não deletados
            ->exists();

        if ( $hasProducts ) {
            return ServiceResult::error(
                OperationStatus::VALIDATION_ERROR,
                'Categoria possui produtos associados',
            );
        }

        // Verificar subcategorias
        if ( $category->hasChildren() ) {
            return ServiceResult::error(
                OperationStatus::VALIDATION_ERROR,
                'Categoria possui subcategorias',
            );
        }

        return ServiceResult::success( true, 'Categoria pode ser deletada' );
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
    public function getDescendantIds( int $categoryId ): array
    {
        $all   = [];
        $queue = [ $categoryId ];
        while ( !empty( $queue ) ) {
            $children = \App\Models\Category::query()
                ->whereIn( 'parent_id', $queue )
                ->whereNull( 'deleted_at' )
                ->pluck( 'id' )
                ->all();
            $children = array_values( array_diff( $children, $all ) );
            if ( empty( $children ) ) {
                break;
            }
            $all   = array_merge( $all, $children );
            $queue = $children;
        }
        return $all;
    }

    /**
     * Verifica se categoria ou seus descendentes estão em uso
     *
     * Útil para validar desativação em cascata.
     *
     * @param Category $category
     * @return bool
     */
    public function isInUse( Category $category ): bool
    {
        $categoryIds = array_merge(
            [ $category->id ],
            $this->getDescendantIds( $category->id ),
        );

        // Verificar services
        $hasServices = DB::table( 'services' )
            ->whereIn( 'category_id', $categoryIds )
            ->exists();

        if ( $hasServices ) {
            return true;
        }

        // Verificar products
        $hasProducts = DB::table( 'products' )
            ->whereIn( 'category_id', $categoryIds )
            ->whereNull( 'deleted_at' )
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
        bool $isDefault = false,
    ): ServiceResult {
        try {
            DB::beginTransaction();

            // Se deve ser default, remover flag de outras
            if ( $isDefault ) {
                DB::table( 'category_tenant' )
                    ->where( 'tenant_id', $tenantId )
                    ->update( [ 'is_default' => false ] );
            }

            // Anexar categoria ao tenant
            $category->tenants()->syncWithoutDetaching( [
                $tenantId => [
                    'is_custom'  => $isCustom,
                    'is_default' => $isDefault,
                ],
            ] );

            DB::commit();

            Log::info( 'Category attached to tenant', [
                'category_id' => $category->id,
                'tenant_id'   => $tenantId,
                'is_custom'   => $isCustom,
                'is_default'  => $isDefault,
            ] );

            return ServiceResult::success( true, 'Categoria vinculada ao tenant' );
        } catch ( \Exception $e ) {
            DB::rollBack();

            Log::error( 'Failed to attach category to tenant', [
                'category_id' => $category->id,
                'tenant_id'   => $tenantId,
                'error'       => $e->getMessage(),
            ] );

            return ServiceResult::error(
                OperationStatus::ERROR,
                'Erro ao vincular categoria: ' . $e->getMessage(),
                null,
                $e,
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
    public function detachFromTenant( Category $category, int $tenantId ): ServiceResult
    {
        try {
            // Verificar se categoria está em uso por este tenant
            $inUseByTenant = DB::table( 'services' )
                ->where( 'category_id', $category->id )
                ->where( 'tenant_id', $tenantId )
                ->exists();

            if ( $inUseByTenant ) {
                return ServiceResult::error(
                    OperationStatus::VALIDATION_ERROR,
                    'Categoria está em uso por este tenant',
                );
            }

            $category->tenants()->detach( $tenantId );

            Log::info( 'Category detached from tenant', [
                'category_id' => $category->id,
                'tenant_id'   => $tenantId,
            ] );

            return ServiceResult::success( true, 'Categoria desvinculada do tenant' );
        } catch ( \Exception $e ) {
            Log::error( 'Failed to detach category from tenant', [
                'category_id' => $category->id,
                'tenant_id'   => $tenantId,
                'error'       => $e->getMessage(),
            ] );

            return ServiceResult::error(
                OperationStatus::ERROR,
                'Erro ao desvincular categoria',
                null,
                $e,
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
    public function createCategory( array $data, ?int $tenantId = null ): ServiceResult
    {
        try {
            return DB::transaction( function () use ($data, $tenantId) {
                // Criar categoria
                $category = Category::create( [
                    'name'      => $data[ 'name' ],
                    'slug'      => \Illuminate\Support\Str::slug( $data[ 'name' ] ), // Garantir slug atualizado
                    'parent_id' => $data[ 'parent_id' ] ?? null,
                    'is_active' => $data[ 'is_active' ] ?? true,
                ] );

                // Se for tenant, vincular como custom
                if ( $tenantId !== null ) {
                    $this->attachToTenant( $category, $tenantId, true, false );
                }

                // Se for Admin (Global), não vinculamos a ninguém inicialmente.
                // Ela será visível para todos via scopeGlobalOnly/forTenant.

                Log::info( 'Category created', [
                    'category_id' => $category->id,
                    'tenant_id'   => $tenantId,
                    'is_global'   => $tenantId === null,
                ] );

                if ( class_exists( CategoryCreated::class) ) {
                    event( new CategoryCreated( $category->id ) );
                }

                return ServiceResult::success( $category, 'Categoria criada com sucesso' );
            } );
        } catch ( \Exception $e ) {
            Log::error( 'Failed to create category', [
                'data'      => $data,
                'tenant_id' => $tenantId,
                'error'     => $e->getMessage(),
            ] );

            return ServiceResult::error(
                OperationStatus::ERROR,
                'Erro ao criar categoria: ' . $e->getMessage(),
                null,
                $e,
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
    public function updateCategory( Category $category, array $data ): ServiceResult
    {
        try {
            return DB::transaction( function () use ($category, $data) {
                if ( $category->is_active && array_key_exists( 'is_active', $data ) && $data[ 'is_active' ] === false ) {
                    if ( $category->hasChildren() ) {
                        return ServiceResult::error(
                            OperationStatus::VALIDATION_ERROR,
                            'Não é possível desativar: categoria possui subcategorias.',
                        );
                    }
                    if ( $this->isInUse( $category ) ) {
                        return ServiceResult::error(
                            OperationStatus::VALIDATION_ERROR,
                            'Não é possível desativar: categoria ou subcategoria em uso.',
                        );
                    }
                }

                $updates = [];
                if ( isset( $data[ 'name' ] ) ) {
                    $updates[ 'name' ] = $data[ 'name' ];
                    // Gerar slug automaticamente baseado no novo nome
                    $updates[ 'slug' ] = Str::slug( $data[ 'name' ] );
                }
                if ( array_key_exists( 'slug', $data ) && !empty( $data[ 'slug' ] ) ) {
                    // Permite customizar slug se fornecido explicitamente
                    $updates[ 'slug' ] = $data[ 'slug' ];
                }
                if ( array_key_exists( 'parent_id', $data ) ) {
                    $updates[ 'parent_id' ] = $data[ 'parent_id' ] ?? null;
                }
                if ( array_key_exists( 'is_active', $data ) ) {
                    $updates[ 'is_active' ] = (bool) $data[ 'is_active' ];
                }

                if ( !empty( $updates ) ) {
                    $category->update( $updates );
                }

                Log::info( 'Category updated', [
                    'category_id' => $category->id,
                ] );

                if ( class_exists( CategoryUpdated::class) ) {
                    event( new CategoryUpdated( $category->id ) );
                }

                return ServiceResult::success( $category, 'Categoria atualizada com sucesso' );
            } );
        } catch ( \Exception $e ) {
            Log::error( 'Failed to update category', [
                'category_id' => $category->id,
                'error'       => $e->getMessage(),
            ] );

            return ServiceResult::error(
                OperationStatus::ERROR,
                'Erro ao atualizar categoria: ' . $e->getMessage(),
                null,
                $e,
            );
        }
    }

    /**
     * Deleta uma categoria
     *
     * @param Category $category
     * @return ServiceResult
     */
    public function deleteCategory( Category $category ): ServiceResult
    {
        try {
            return DB::transaction( function () use ($category) {
                // Validar deleção
                $canDelete = $this->canDelete( $category );
                if ( $canDelete->isError() ) {
                    return $canDelete;
                }

                $category->delete();

                Log::info( 'Category deleted', [
                    'category_id' => $category->id,
                ] );

                if ( class_exists( CategoryDeleted::class) ) {
                    event( new CategoryDeleted( $category->id ) );
                }

                return ServiceResult::success( null, 'Categoria excluída com sucesso' );
            } );
        } catch ( \Exception $e ) {
            Log::error( 'Failed to delete category', [
                'category_id' => $category->id,
                'error'       => $e->getMessage(),
            ] );

            return ServiceResult::error(
                OperationStatus::ERROR,
                'Erro ao excluir categoria: ' . $e->getMessage(),
                null,
                $e,
            );
        }
    }

}
