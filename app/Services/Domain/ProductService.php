<?php

declare(strict_types=1);

namespace App\Services\Domain;

use App\Actions\Product\CreateProductAction;
use App\Actions\Product\DeleteProductAction;
use App\Actions\Product\RestoreProductAction;
use App\Actions\Product\ToggleProductStatusAction;
use App\Actions\Product\UpdateProductAction;
use App\DTOs\Product\ProductDTO;
use App\DTOs\Product\ProductFilterDTO;
use App\Enums\OperationStatus;
use App\Models\Product;
use App\Repositories\ProductInventoryRepository;
use App\Repositories\ProductRepository;
use App\Services\Core\Abstracts\AbstractBaseService;
use App\Support\ServiceResult;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Serviço de domínio para gerenciamento de produtos.
 *
 * Centraliza a regra de negócios e delega a persistência ao ProductRepository.
 * Mantém isolamento entre Controller e Model/Database.
 * Implementa a arquitetura padronizada com validação robusta e operações transacionais.
 *
 * @property ProductRepository $repository
 */
class ProductService extends AbstractBaseService
{
    public function __construct(
        ProductRepository $repository,
        private ProductInventoryRepository $inventoryRepository,
        private CreateProductAction $createAction,
        private UpdateProductAction $updateAction,
        private ToggleProductStatusAction $toggleStatusAction,
        private DeleteProductAction $deleteAction,
        private RestoreProductAction $restoreAction,
    ) {
        parent::__construct($repository);
    }

    protected function getSupportedFilters(): array
    {
        return ['id', 'name', 'sku', 'price', 'active', 'category', 'created_at', 'updated_at'];
    }

    /**
     * Dashboard de Produtos.
     */
    public function getDashboardData(): ServiceResult
    {
        return $this->safeExecute(function () {
            $stats = $this->repository->getDashboardStats();

            return $this->success($stats, 'Estatísticas de produtos obtidas com sucesso');
        }, 'Erro ao obter estatísticas de produtos.');
    }

    /**
     * Valida dados do produto.
     */
    public function validate(array $data, bool $isUpdate = false): ServiceResult
    {
        $validator = Validator::make($data, Product::businessRules());

        if ($validator->fails()) {
            return $this->error(OperationStatus::INVALID_DATA, implode(', ', $validator->errors()->all()));
        }

        return $this->success($data);
    }

    public function findBySku(string $sku, array $with = [], bool $withTrashed = false, array $loadCounts = []): ServiceResult
    {
        return $this->safeExecute(function () use ($sku, $with, $withTrashed, $loadCounts) {
            $product = $this->repository->findBySku($sku, $with, $withTrashed);

            if (! $product) {
                return $this->error(
                    OperationStatus::NOT_FOUND,
                    "Produto com SKU {$sku} não encontrado",
                );
            }

            if (! empty($with)) {
                $product->load($with);
            }

            if (! empty($loadCounts)) {
                $product->loadCount($loadCounts);
            }

            return $this->success($product, 'Produto encontrado');
        }, 'Erro ao buscar produto.');
    }

    /**
     * Cria um novo produto.
     */
    public function createProduct(ProductDTO $dto): ServiceResult
    {
        return $this->safeExecute(function () use ($dto) {
            // Delega a criação atômica para a Action
            $product = $this->createAction->execute($dto);

            // Gerenciamento de arquivo (Responsabilidade do Service)
            if ($dto->image instanceof \Illuminate\Http\UploadedFile) {
                try {
                    $imagePath = $this->uploadProductImage($dto->image);
                    $this->repository->update($product->id, ['image' => $imagePath]);
                    $product->refresh();
                } catch (Exception $e) {
                    Log::error('Erro ao fazer upload de imagem do produto: '.$e->getMessage());
                    // Não falha a criação do produto se apenas o upload falhar, 
                    // mas poderíamos lançar exceção se fosse crítico.
                }
            }

            return $this->success($product, 'Produto criado com sucesso');
        }, 'Erro ao criar produto.');
    }

    /**
     * Atualiza produto buscando por SKU.
     */
    public function updateProductBySku(string $sku, ProductDTO $dto, ?bool $removeImage = false): ServiceResult
    {
        return $this->safeExecute(function () use ($sku, $dto, $removeImage) {
            $product = $this->repository->findBySku($sku);

            if (! $product) {
                return $this->error(OperationStatus::NOT_FOUND, "Produto com SKU {$sku} não encontrado");
            }

            // 1. Gerenciar Imagem (Lógica de arquivo no Service)
            $imageData = [];
            if ($removeImage && $product->image) {
                $this->deletePhysicalImage($product->image);
                $imageData['image'] = null;
            } elseif ($dto->image instanceof \Illuminate\Http\UploadedFile) {
                if ($product->image) {
                    $this->deletePhysicalImage($product->image);
                }
                $imageData['image'] = $this->uploadProductImage($dto->image);
            }

            // 2. Executar atualização via Action
            $updatedProduct = $this->updateAction->execute($product, $dto);

            // 3. Se houver nova imagem, atualiza o registro
            if (array_key_exists('image', $imageData)) {
                $this->repository->update($updatedProduct->id, ['image' => $imageData['image']]);
                $updatedProduct->refresh();
            }

            return $this->success($updatedProduct, 'Produto atualizado com sucesso');
        }, 'Erro ao atualizar produto.');
    }

    public function toggleProductStatus(string $sku): ServiceResult
    {
        return $this->safeExecute(function () use ($sku) {
            $product = $this->repository->findBySku($sku);

            if (! $product) {
                return $this->error(OperationStatus::NOT_FOUND, "Produto com SKU {$sku} não encontrado");
            }

            $updatedProduct = $this->toggleStatusAction->execute($product);

            $message = $updatedProduct->active ? 'Produto ativado com sucesso' : 'Produto desativado com sucesso';

            return $this->success($updatedProduct, $message);
        }, 'Erro ao alterar status do produto.');
    }

    public function deleteProductBySku(string $sku): ServiceResult
    {
        return $this->safeExecute(function () use ($sku) {
            $product = $this->repository->findBySku($sku);

            if (! $product) {
                return $this->error(OperationStatus::NOT_FOUND, "Produto com SKU {$sku} não encontrado");
            }

            // 1. Gerenciar arquivos (Service)
            if ($product->image) {
                $this->deletePhysicalImage($product->image);
            }

            // 2. Executar exclusão (Action)
            $this->deleteAction->execute($product);

            return $this->success(null, 'Produto excluído com sucesso');
        }, 'Erro ao excluir produto.');
    }

    public function getDeleted(array $filters = [], array $with = [], int $perPage = 15): ServiceResult
    {
        return $this->safeExecute(function () use ($filters, $with, $perPage) {
            $filters['deleted'] = 'only';
            $normalizedFilters = $this->normalizeFilters($filters);

            $paginator = $this->repository->getPaginated(
                $normalizedFilters,
                $perPage,
                $with,
            );

            return $paginator;
        }, 'Erro ao carregar produtos deletados.');
    }

    public function restoreProductBySku(string $sku): ServiceResult
    {
        return $this->safeExecute(function () use ($sku) {
            $product = $this->repository->findBySku($sku, [], true);

            if (! $product || ! $product->trashed()) {
                return $this->error(OperationStatus::NOT_FOUND, 'Produto não encontrado ou não está excluído');
            }

            $this->restoreAction->execute($product);

            return $this->success($product, 'Produto restaurado com sucesso');
        }, 'Erro ao restaurar produto.');
    }

    /**
     * Restaura múltiplos produtos pelos seus IDs.
     */
    public function restoreProducts(array $ids): ServiceResult
    {
        return $this->safeExecute(function () use ($ids) {
            return DB::transaction(function () use ($ids) {
                $count = 0;
                foreach ($ids as $id) {
                    $product = $this->repository->findOneBy('id', (int) $id, [], true);
                    if ($product && $product->trashed() && $this->restoreAction->execute($product)) {
                        $count++;
                    }
                }

                return $this->success(null, "{$count} produtos restaurados com sucesso");
            });
        }, 'Erro ao restaurar produtos.');
    }

    /**
     * Retorna produtos filtrados e paginados opcionalmente via DTO.
     */
    public function getFilteredProducts(ProductFilterDTO $filterDto, array $with = [], bool $paginate = true): ServiceResult
    {
        return $this->safeExecute(function () use ($filterDto, $with, $paginate) {
            // Normalização padronizada via Trait
            $normalizedFilters = $this->normalizeFilters($filterDto->toFilterArray());

            if ($paginate) {
                $result = $this->repository->getPaginated(
                    $normalizedFilters,
                    $filterDto->per_page,
                    $with,
                );
                Log::info('Produtos carregados (paginado)', ['total' => $result->total()]);
            } else {
                $result = $this->repository->getAllByTenant(
                    $normalizedFilters,
                );
                if (!empty($with)) {
                    $result->load($with);
                }
                Log::info('Produtos carregados (coleção)', ['total' => $result->count()]);
            }

            return $result;
        }, 'Erro ao carregar produtos.');
    }

    /**
     * Gera o próximo SKU disponível.
     */
    public function generateNextSku(): ServiceResult
    {
        return $this->safeExecute(function () {
            return $this->repository->generateUniqueSku();
        });
    }

    // --- Auxiliares Privados ---

    private function uploadProductImage($imageFile): ?string
    {
        if (! $imageFile) {
            return null;
        }

        $tenantId = $this->tenantId();
        $path = 'products/'.($tenantId ?? 'unknown');
        $filename = Str::random(40).'.'.$imageFile->getClientOriginalExtension();

        if (! Storage::disk('public')->exists($path)) {
            Storage::disk('public')->makeDirectory($path);
        }

        $imageFile->storePubliclyAs($path, $filename, 'public');

        return $path.'/'.$filename;
    }

    private function resolveRelativeStoragePath(?string $image): ?string
    {
        if (empty($image)) {
            return null;
        }
        $trimmed = ltrim($image, '/');
        if (Str::startsWith($trimmed, 'storage/')) {
            return Str::after($trimmed, 'storage/');
        }

        return $trimmed;
    }

    private function deletePhysicalImage(?string $image): void
    {
        if (! $image) {
            return;
        }

        $relative = $this->resolveRelativeStoragePath($image);
        if ($relative && Storage::disk('public')->exists($relative)) {
            Storage::disk('public')->delete($relative);
        }
    }
}
