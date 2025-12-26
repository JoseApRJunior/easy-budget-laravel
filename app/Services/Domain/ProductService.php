<?php

declare(strict_types=1);

namespace App\Services\Domain;

use App\DTOs\Product\ProductDTO;
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
        private ProductInventoryRepository $inventoryRepository
    ) {
        parent::__construct($repository);
    }

    protected function getSupportedFilters(): array
    {
        return ['id', 'name', 'sku', 'price', 'active', 'category_id', 'created_at', 'updated_at'];
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

    public function findBySku(string $sku, array $with = [], bool $withTrashed = false): ServiceResult
    {
        return $this->safeExecute(function () use ($sku, $with, $withTrashed) {
            $product = $this->repository->findBySku($sku, $with, $withTrashed);

            if (! $product) {
                return $this->error(
                    OperationStatus::NOT_FOUND,
                    "Produto com SKU {$sku} não encontrado",
                );
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
            $product = DB::transaction(function () use ($dto) {
                // 1. Persistência usando DTO
                $product = $this->repository->createFromDTO($dto);

                // 2. Upload image
                if ($dto->image instanceof \Illuminate\Http\UploadedFile) {
                    $imagePath = $this->uploadProductImage($dto->image);
                    $this->repository->update($product->id, ['image' => $imagePath]);
                    $product->refresh();
                }

                // 3. Inventário Inicial
                $this->inventoryRepository->initialize($product->id);

                return $product;
            });

            return $this->success($product, 'Produto criado com sucesso');
        }, 'Erro ao criar produto.');
    }

    /**
     * Atualiza produto buscando por SKU.
     */
    public function updateProductBySku(string $sku, ProductDTO $dto, ?bool $removeImage = false): ServiceResult
    {
        return $this->safeExecute(function () use ($sku, $dto, $removeImage) {
            $product = DB::transaction(function () use ($sku, $dto, $removeImage) {
                $product = $this->repository->findBySku($sku);

                if (! $product) {
                    throw new Exception("Produto com SKU {$sku} não encontrado");
                }

                $data = $dto->toDatabaseArray();

                // 1. Gerenciar Imagem
                if ($removeImage && $product->image) {
                    $this->deletePhysicalImage($product->image);
                    $data['image'] = null;
                } elseif (isset($data['image']) && $data['image'] instanceof \Illuminate\Http\UploadedFile) {
                    if ($product->image) {
                        $this->deletePhysicalImage($product->image);
                    }
                    $data['image'] = $this->uploadProductImage($data['image']);
                } else {
                    // Mantém a imagem atual se nada foi enviado
                    unset($data['image']);
                }

                // 2. Atualizar
                $this->repository->update($product->id, $data);

                return $product->fresh();
            });

            return $this->success($product, 'Produto atualizado com sucesso');
        }, 'Erro ao atualizar produto.');
    }

    public function toggleProductStatus(string $sku): ServiceResult
    {
        return $this->safeExecute(function () use ($sku) {
            $product = DB::transaction(function () use ($sku) {
                $product = $this->repository->findBySku($sku);

                if (! $product) {
                    throw new Exception("Produto com SKU {$sku} não encontrado");
                }

                if (! $this->repository->canBeDeactivatedOrDeleted($product->id)) {
                    throw new Exception('Produto não pode ser desativado/ativado pois está em uso em serviços.');
                }

                $newStatus = ! $product->active;
                $this->repository->updateStatus($product->id, $newStatus);

                return $product->fresh();
            });

            $message = $product->active ? 'Produto ativado com sucesso' : 'Produto desativado com sucesso';

            return $this->success($product, $message);
        }, 'Erro ao alterar status do produto.');
    }

    public function deleteProductBySku(string $sku): ServiceResult
    {
        return $this->safeExecute(function () use ($sku) {
            DB::transaction(function () use ($sku) {
                $product = $this->repository->findBySku($sku);

                if (! $product) {
                    throw new Exception("Produto com SKU {$sku} não encontrado");
                }

                if (! $this->repository->canBeDeactivatedOrDeleted($product->id)) {
                    throw new Exception('Produto não pode ser excluído pois está em uso em serviços.');
                }

                // Deletar imagem física se existir
                $this->deletePhysicalImage($product->image);

                $this->repository->delete($product->id);
            });

            return $this->success(null, 'Produto excluído com sucesso');
        }, 'Erro ao excluir produto.');
    }

    public function getDeleted(array $filters = [], array $with = [], int $perPage = 15): ServiceResult
    {
        return $this->safeExecute(function () use ($filters, $with, $perPage) {
            $filters['deleted'] = 'only';
            $paginator = $this->repository->getPaginated(
                $filters,
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

            $this->repository->restore($product->id);

            return $this->success($product, 'Produto restaurado com sucesso');
        }, 'Erro ao restaurar produto.');
    }

    /**
     * Restaura múltiplos produtos pelos seus IDs.
     */
    public function restoreProducts(array $ids): ServiceResult
    {
        return $this->safeExecute(function () use ($ids) {
            $count = 0;
            foreach ($ids as $id) {
                if ($this->repository->restore((int) $id)) {
                    $count++;
                }
            }

            return $this->success(null, "{$count} produtos restaurados com sucesso");
        }, 'Erro ao restaurar produtos.');
    }

    public function getFilteredProducts(array $filters = [], array $with = [], int $perPage = 15): ServiceResult
    {
        return $this->safeExecute(function () use ($filters, $with, $perPage) {
            $paginator = $this->repository->getPaginated(
                $filters,
                $perPage,
                $with,
            );

            Log::info('Produtos carregados', ['total' => $paginator->total()]);

            return $paginator;
        }, 'Erro ao carregar produtos.');
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
