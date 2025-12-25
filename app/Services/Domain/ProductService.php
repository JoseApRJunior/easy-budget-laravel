<?php

declare(strict_types=1);

namespace App\Services\Domain;

use App\DTOs\Product\ProductDTO;
use App\Enums\OperationStatus;
use App\Models\Product;
use App\Repositories\ProductRepository;
use App\Services\Core\Abstracts\AbstractBaseService;
use App\Support\ServiceResult;
use Exception;
use Illuminate\Database\Eloquent\Collection;
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
    public function __construct(ProductRepository $repository)
    {
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
            $tenantId = $this->ensureTenantId();
            $stats = $this->repository->getDashboardStats($tenantId);
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

    /**
     * Normaliza filtros do request para formato aceito pelo repository.
     */
    private function normalizeFilters(array $filters): array
    {
        $normalized = [];

        // Status filter
        if (isset($filters['active']) && $filters['active'] !== '' && $filters['active'] !== null) {
            $normalized['active'] = (string) $filters['active'] === '1' || $filters['active'] === 1;
        }

        // Search filters
        if (!empty($filters['search'])) {
            $normalized['search'] = (string) $filters['search'];
        }

        if (!empty($filters['name'])) {
            $normalized['name'] = ['operator' => 'like', 'value' => '%' . $filters['name'] . '%'];
        }

        if (!empty($filters['sku'])) {
            $normalized['sku'] = ['operator' => 'like', 'value' => '%' . $filters['sku'] . '%'];
        }

        // Price filters (mantidos para funcionalidade específica de produtos)
        if (isset($filters['min_price']) && $filters['min_price'] !== null) {
            $normalized['min_price'] = $this->normalizePrice($filters['min_price']);
        }

        if (isset($filters['max_price']) && $filters['max_price'] !== null) {
            $normalized['max_price'] = $this->normalizePrice($filters['max_price']);
        }

        // Category filter (mantido para funcionalidade específica de produtos)
        if (!empty($filters['category_id'])) {
            $normalized['category_id'] = $filters['category_id'];
        }

        // Soft delete filter
        if (array_key_exists('deleted', $filters)) {
            $normalized['deleted'] = match ($filters['deleted']) {
                'only', '1'    => 'only',
                'current', '0' => 'current',
                default        => '',
            };
        }

        return $normalized;
    }

    /**
     * Retorna o total de produtos do tenant autenticado.
     */
    public function getTotalCount(): int
    {
        return $this->repository->countByTenant();
    }

    /**
     * Retorna o total de produtos ativos do tenant autenticado.
     */
    public function getActiveCount(): int
    {
        return $this->repository->countActiveByTenant();
    }

    /**
     * Retorna uma coleção de produtos recentes com relacionamentos opcionais.
     */
    public function getRecentProducts(int $limit = 5, array $with = []): Collection
    {
        // Obtém produtos recentes do repositório
        // Nota: O método do repositório já aplica ordenação por created_at desc
        $products = $this->repository->getRecentByTenant($limit);

        // Carrega relacionamentos se solicitados
        if (!empty($with)) {
            $products->load($with);
        }

        return $products;
    }

    /**
     * Retorna produtos ativos.
     */
    public function getActive(): ServiceResult
    {
        return $this->safeExecute(function () {
            return $this->repository->getAllByTenant(
                ['active' => true],
                ['name' => 'asc'],
            );
        }, 'Erro ao buscar produtos ativos.');
    }

    public function findBySku(string $sku, array $with = []): ServiceResult
    {
        try {
            $product = $this->repository->findBySku($sku, $with);

            if (!$product) {
                return $this->error(
                    OperationStatus::NOT_FOUND,
                    "Produto com SKU {$sku} não encontrado",
                );
            }

            return $this->success($product, 'Produto encontrado');
        } catch (Exception $e) {
            return $this->error(
                OperationStatus::ERROR,
                'Erro ao buscar produto',
                null,
                $e,
            );
        }
    }

    /**
     * Cria um novo produto.
     */
    public function createProduct(ProductDTO $dto): ServiceResult
    {
        return $this->safeExecute(function () use ($dto) {
            $tenantId = $this->ensureTenantId();

            $product = DB::transaction(function () use ($dto, $tenantId) {
                $data = $dto->toDatabaseArray();
                $data['tenant_id'] = $tenantId;

                // 1. SKU Generation if empty
                if (empty($data['sku'])) {
                    $data['sku'] = $this->repository->generateUniqueSku((int) $tenantId);
                }

                // 2. Upload image
                if (isset($data['image']) && $data['image'] instanceof \Illuminate\Http\UploadedFile) {
                    $data['image'] = $this->uploadProductImage($data['image']);
                }

                // 3. Persistência
                $product = $this->repository->create($data);

                // 4. Inventário Inicial
                \App\Models\ProductInventory::create([
                    'tenant_id'    => $product->tenant_id,
                    'product_id'   => $product->id,
                    'quantity'     => 0,
                    'min_quantity' => 0,
                    'max_quantity' => null,
                ]);

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
                $ownerResult = $this->findAndVerifyOwnership($sku);
                if ($ownerResult->isError()) {
                    throw new Exception($ownerResult->getMessage());
                }

                /** @var Product $product */
                $product = $ownerResult->getData();
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
                $ownerResult = $this->findAndVerifyOwnership($sku);
                if ($ownerResult->isError()) {
                    throw new Exception($ownerResult->getMessage());
                }

                $product = $ownerResult->getData();

                if (!$this->repository->canBeDeactivatedOrDeleted($product->id)) {
                    throw new Exception('Produto não pode ser desativado/ativado pois está em uso em serviços.');
                }

                $newStatus = !$product->active;

                $dto = new ProductDTO(
                    name: $product->name,
                    price: (float) $product->price,
                    category_id: $product->category_id,
                    description: $product->description,
                    sku: $product->sku,
                    unit: $product->unit,
                    is_active: $newStatus,
                    image: $product->image
                );

                return $this->repository->update($product->id, $dto->toDatabaseArray());
            });

            $message = $product->active ? 'Produto ativado com sucesso' : 'Produto desativado com sucesso';
            return $this->success($product, $message);
        }, 'Erro ao alterar status do produto.');
    }

    public function deleteProductBySku(string $sku): ServiceResult
    {
        return $this->safeExecute(function () use ($sku) {
            DB::transaction(function () use ($sku) {
                $ownerResult = $this->findAndVerifyOwnership($sku);
                if ($ownerResult->isError()) {
                    throw new Exception($ownerResult->getMessage());
                }

                $product = $ownerResult->getData();

                if (!$this->repository->canBeDeactivatedOrDeleted($product->id)) {
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
            $tenantId = $this->ensureTenantId();

            $filters['deleted'] = 'only';
            $paginator = $this->repository->getPaginated(
                $this->normalizeFilters($filters),
                $perPage,
                $with,
            );

            return $paginator;
        }, 'Erro ao carregar produtos deletados.');
    }

    public function restoreProductBySku(string $sku): ServiceResult
    {
        return $this->safeExecute(function () use ($sku) {
            $tenantId = $this->ensureTenantId();
            $product = $this->repository->findBySku($sku, [], true);

            if (!$product || !$product->trashed()) {
                return $this->error(OperationStatus::NOT_FOUND, 'Produto não encontrado ou não está excluído');
            }

            $this->repository->restore($product->id);
            return $this->success($product, 'Produto restaurado com sucesso');
        }, 'Erro ao restaurar produto.');
    }


    public function getFilteredProducts(array $filters = [], array $with = [], int $perPage = 15): ServiceResult
    {
        return $this->safeExecute(function () use ($filters, $with, $perPage) {
            if (!$this->tenantId()) {
                return $this->error(OperationStatus::ERROR, 'Tenant não identificado');
            }

            $paginator = $this->repository->getPaginated(
                $this->normalizeFilters($filters),
                $perPage,
                $with,
            );

            Log::info('Produtos carregados', ['total' => $paginator->total()]);
            return $paginator;
        }, 'Erro ao carregar produtos.');
    }

    // --- Auxiliares Privados ---

    private function ensureTenantId(): int
    {
        $id = $this->tenantId();
        if (!$id) {
            throw new Exception('Tenant não identificado');
        }
        return $id;
    }

    private function findAndVerifyOwnership(string $sku): ServiceResult
    {
        $result = $this->findBySku($sku);
        if ($result->isError()) return $result;

        $product = $result->getData();
        if ($product->tenant_id !== $this->tenantId()) {
            return $this->error(OperationStatus::UNAUTHORIZED, 'Produto não pertence ao tenant atual');
        }

        return $this->success($product);
    }

    private function uploadProductImage($imageFile): ?string
    {
        if (!$imageFile) return null;

        $tenantId = $this->tenantId();
        $path     = 'products/' . ($tenantId ?? 'unknown');
        $filename = Str::random(40) . '.' . $imageFile->getClientOriginalExtension();

        if (!Storage::disk('public')->exists($path)) {
            Storage::disk('public')->makeDirectory($path);
        }

        $imageFile->storePubliclyAs($path, $filename, 'public');
        return $path . '/' . $filename;
    }

    private function resolveRelativeStoragePath(?string $image): ?string
    {
        if (empty($image)) return null;
        $trimmed = ltrim($image, '/');
        if (Str::startsWith($trimmed, 'storage/')) {
            return Str::after($trimmed, 'storage/');
        }
        return $trimmed;
    }

    private function deletePhysicalImage(?string $image): void
    {
        if (!$image) return;

        $relative = $this->resolveRelativeStoragePath($image);
        if ($relative && Storage::disk('public')->exists($relative)) {
            Storage::disk('public')->delete($relative);
        }
    }

    /**
     * Normaliza preço removendo formatação brasileira (R$ 12,00 -> 12.00).
     */
    private function normalizePrice(string $price): float
    {
        return \App\Helpers\CurrencyHelper::unformat($price);
    }
}
