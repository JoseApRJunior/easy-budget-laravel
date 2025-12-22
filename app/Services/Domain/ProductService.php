<?php

declare(strict_types=1);

namespace App\Services\Domain;

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
    public function __construct( ProductRepository $repository )
    {
        parent::__construct( $repository );
    }

    protected function getSupportedFilters(): array
    {
        return [ 'id', 'name', 'sku', 'price', 'active', 'category_id', 'created_at', 'updated_at' ];
    }

    /**
     * Gera SKU único para o tenant.
     */
    private function generateUniqueSku( int $tenantId ): string
    {
        $lastProduct = Product::where( 'tenant_id', $tenantId )
            ->where( 'sku', 'LIKE', 'PROD%' )
            ->withTrashed() // ESSA É A CHAVE: Considera até os deletados para não repetir número
            ->orderBy( 'sku', 'desc' )
            ->lockForUpdate()
            ->first();

        if ( !$lastProduct ) {
            return 'PROD000001';
        }

        // Agora ele vai encontrar o PROD000030 corretamente
        $lastNumber = (int) filter_var( $lastProduct->sku, FILTER_SANITIZE_NUMBER_INT );
        $nextNumber = $lastNumber + 1;

        return 'PROD' . str_pad( (string) $nextNumber, 6, '0', STR_PAD_LEFT );
    }

    /**
     * Valida dados do produto.
     */
    public function validate( array $data, bool $isUpdate = false ): ServiceResult
    {
        $rules = [
            'name'        => 'required|string|max:255',
            'price'       => 'required|numeric|min:0',
            'active'      => 'boolean',
            'category_id' => 'nullable|exists:categories,id',
            'image'       => 'nullable|image|max:2048',
        ];

        $validator = Validator::make( $data, $rules );

        if ( $validator->fails() ) {
            return $this->error( OperationStatus::INVALID_DATA, implode( ', ', $validator->errors()->all() ) );
        }

        return $this->success( $data );
    }

    /**
     * Normaliza filtros do request para formato aceito pelo repository.
     */
    private function normalizeFilters( array $filters ): array
    {
        $normalized = [];

        // Status filter
        if ( isset( $filters[ 'active' ] ) && $filters[ 'active' ] !== '' && $filters[ 'active' ] !== null ) {
            $normalized[ 'active' ] = (string) $filters[ 'active' ] === '1' || $filters[ 'active' ] === 1;
        }

        // Search filters
        if ( !empty( $filters[ 'search' ] ) ) {
            $normalized[ 'search' ] = (string) $filters[ 'search' ];
        }

        if ( !empty( $filters[ 'name' ] ) ) {
            $normalized[ 'name' ] = [ 'operator' => 'like', 'value' => '%' . $filters[ 'name' ] . '%' ];
        }

        if ( !empty( $filters[ 'sku' ] ) ) {
            $normalized[ 'sku' ] = [ 'operator' => 'like', 'value' => '%' . $filters[ 'sku' ] . '%' ];
        }

        // Price filters (mantidos para funcionalidade específica de produtos)
        if ( isset( $filters[ 'min_price' ] ) && $filters[ 'min_price' ] !== null ) {
            $normalized[ 'min_price' ] = $this->normalizePrice( $filters[ 'min_price' ] );
        }

        if ( isset( $filters[ 'max_price' ] ) && $filters[ 'max_price' ] !== null ) {
            $normalized[ 'max_price' ] = $this->normalizePrice( $filters[ 'max_price' ] );
        }

        // Category filter (mantido para funcionalidade específica de produtos)
        if ( !empty( $filters[ 'category_id' ] ) ) {
            $normalized[ 'category_id' ] = $filters[ 'category_id' ];
        }

        // Soft delete filter
        if ( array_key_exists( 'deleted', $filters ) ) {
            $normalized[ 'deleted' ] = match ( $filters[ 'deleted' ] ) {
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
    public function getRecentProducts( int $limit = 5, array $with = [] ): Collection
    {
        // Obtém produtos recentes do repositório
        // Nota: O método do repositório já aplica ordenação por created_at desc
        $products = $this->repository->getRecentByTenant( $limit );

        // Carrega relacionamentos se solicitados
        if ( !empty( $with ) ) {
            $products->load( $with );
        }

        return $products;
    }

    /**
     * Retorna produtos ativos.
     */
    public function getActive(): Collection
    {
        try {
            return $this->repository->getAllByTenant(
                [ 'active' => true ],
                [ 'name' => 'asc' ],
            );
        } catch ( Exception $e ) {
            Log::error( 'Erro ao buscar produtos ativos', [
                'error' => $e->getMessage()
            ] );
            return new Collection();
        }
    }

    public function findBySku( string $sku, array $with = [] ): ServiceResult
    {
        try {
            $product = $this->repository->findBySku( $sku, $with );

            if ( !$product ) {
                return $this->error(
                    OperationStatus::NOT_FOUND,
                    "Produto com SKU {$sku} não encontrado",
                );
            }

            return $this->success( $product, 'Produto encontrado' );

        } catch ( Exception $e ) {
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
    public function createProduct( array $data ): ServiceResult
    {
        return $this->safeExecute( function () use ($data) {
            $tenantId = $this->ensureTenantId();

            // A transação envolve todo o processo de escrita
            $product = DB::transaction( function () use ($data, $tenantId) {

                // 1. Lógica de tratamento de dados (Nome, SKU, etc)
                if ( isset( $data[ 'name' ] ) ) {
                    $data[ 'name' ] = mb_convert_case( $data[ 'name' ], MB_CASE_TITLE, 'UTF-8' );
                }

                if ( empty( $data[ 'sku' ] ) ) {
                    // O lockForUpdate aqui dentro agora segura o banco até o final deste bloco
                    $data[ 'sku' ] = $this->generateUniqueSku( (int) $tenantId );
                }

                // 2. Upload e Tenant ID
                if ( isset( $data[ 'image' ] ) ) {
                    $data[ 'image' ] = $this->uploadProductImage( $data[ 'image' ] );
                }
                $data[ 'tenant_id' ] = $tenantId;

                // 3. Persistência (Insert no Banco)
                $product = $this->repository->create( $data );

                // 4. Inventário
                \App\Models\ProductInventory::create( [
                    'tenant_id'    => $product->tenant_id,
                    'product_id'   => $product->id,
                    'quantity'     => 0,
                    'min_quantity' => 0,
                    'max_quantity' => null,
                ] );

                return $product;
            } );

            return $this->success( $product, 'Produto criado com sucesso' );
        }, 'Erro ao criar produto.' );
    }

    /**
     * Atualiza produto buscando por SKU.
     */
    public function updateProductBySku( string $sku, array $data ): ServiceResult
    {
        return $this->safeExecute( function () use ($sku, $data) {
            $product = DB::transaction( function () use ($sku, $data) {
                $ownerResult = $this->findAndVerifyOwnership( $sku );
                if ( $ownerResult->isError() ) {
                    throw new Exception( $ownerResult->getMessage() );
                }

                $product = $ownerResult->getData();

                if ( isset( $data[ 'name' ] ) ) {
                    $data[ 'name' ] = mb_convert_case( $data[ 'name' ], MB_CASE_TITLE, 'UTF-8' );
                }

                // Remover imagem existente se solicitado
                if ( isset( $data[ 'remove_image' ] ) && $data[ 'remove_image' ] && $product->image ) {
                    $relative = $this->resolveRelativeStoragePath( $product->image );
                    if ( $relative ) {
                        Storage::disk( 'public' )->delete( $relative );
                    }
                    $data[ 'image' ] = null;
                }

                // Processar nova imagem se fornecida
                if ( isset( $data[ 'image' ] ) && is_a( $data[ 'image' ], 'Illuminate\Http\UploadedFile' ) ) {
                    // Deletar imagem antiga se existir
                    if ( $product->image ) {
                        $relative = $this->resolveRelativeStoragePath( $product->image );
                        if ( $relative ) {
                            Storage::disk( 'public' )->delete( $relative );
                        }
                    }
                    $data[ 'image' ] = $this->uploadProductImage( $data[ 'image' ] );
                } else if ( isset( $data[ 'image' ] ) && $data[ 'image' ] === null ) {
                    if ( $product->image ) {
                        $relative = $this->resolveRelativeStoragePath( $product->image );
                        if ( $relative ) {
                            Storage::disk( 'public' )->delete( $relative );
                        }
                    }
                    $data[ 'image' ] = null;
                } else {
                    unset( $data[ 'image' ] );
                }

                return $this->repository->update( $product->id, $data );
            } );

            return $this->success( $product, 'Produto atualizado com sucesso' );
        }, 'Erro ao atualizar produto.' );
    }

    public function toggleProductStatus( string $sku ): ServiceResult
    {
        return $this->safeExecute( function () use ($sku) {
            $product = DB::transaction( function () use ($sku) {
                $ownerResult = $this->findAndVerifyOwnership( $sku );
                if ( $ownerResult->isError() ) {
                    throw new Exception( $ownerResult->getMessage() );
                }

                $product = $ownerResult->getData();

                if ( !$this->repository->canBeDeactivatedOrDeleted( $product->id ) ) {
                    throw new Exception( 'Produto não pode ser desativado/ativado pois está em uso em serviços.' );
                }

                $newStatus = !$product->active;
                return $this->repository->update( $product->id, [ 'active' => $newStatus ] );
            } );

            $message = $product->active ? 'Produto ativado com sucesso' : 'Produto desativado com sucesso';
            return $this->success( $product, $message );
        }, 'Erro ao alterar status do produto.' );
    }

    public function deleteProductBySku( string $sku ): ServiceResult
    {
        return $this->safeExecute( function () use ($sku) {
            DB::transaction( function () use ($sku) {
                $ownerResult = $this->findAndVerifyOwnership( $sku );
                if ( $ownerResult->isError() ) {
                    throw new Exception( $ownerResult->getMessage() );
                }

                $product = $ownerResult->getData();

                if ( !$this->repository->canBeDeactivatedOrDeleted( $product->id ) ) {
                    throw new Exception( 'Produto não pode ser excluído pois está em uso em serviços.' );
                }

                // Deletar imagem física se existir
                if ( $product->image ) {
                    $relative = $this->resolveRelativeStoragePath( $product->image );
                    if ( $relative ) {
                        Storage::disk( 'public' )->delete( $relative );
                    }
                }

                $this->repository->delete( $product->id );
            } );

            return $this->success( null, 'Produto excluído com sucesso' );
        }, 'Erro ao excluir produto.' );
    }

    public function getDeleted( array $filters = [], array $with = [], int $perPage = 15 ): ServiceResult
    {
        return $this->safeExecute( function () use ($filters, $with, $perPage) {
            if ( !$this->tenantId() ) {
                return $this->error( OperationStatus::ERROR, 'Tenant não identificado' );
            }

            $filters[ 'deleted' ] = 'only';
            $paginator            = $this->repository->getPaginated(
                $this->normalizeFilters( $filters ),
                $perPage,
                $with,
            );

            Log::info( 'Produtos deletados carregados', [ 'total' => $paginator->total() ] );
            return $paginator;
        }, 'Erro ao carregar produtos deletados.' );
    }

    public function restoreProductBySku( string $sku ): ServiceResult
    {
        return $this->safeExecute( function () use ($sku) {
            $tenantId = $this->ensureTenantId();
            $product  = Product::onlyTrashed()
                ->where( 'tenant_id', $tenantId )
                ->where( 'sku', $sku )
                ->first();

            if ( !$product ) {
                return $this->error( OperationStatus::NOT_FOUND, 'Produto não encontrado ou não está excluído' );
            }

            $product->restore();
            return $this->success( $product, 'Produto restaurado com sucesso' );
        }, 'Erro ao restaurar produto.' );
    }

    // Método para dashboard
    public function getDashboardData( ?int $tenantId = null ): ServiceResult
    {
        return $this->safeExecute( function () {
            $total   = $this->repository->countByTenant();
            $active  = $this->repository->countActiveByTenant();
            $deleted = $this->repository->countOnlyTrashedByTenant();

            $stats = [
                'total_products'    => $total,
                'active_products'   => $active,
                'inactive_products' => max( 0, $total - $active ),
                'deleted_products'  => $deleted,
                'recent_products'   => $this->getRecentProducts( 5, [ 'category' ] ), // Usa método do service
            ];

            return $this->success( $stats, 'Estatísticas obtidas com sucesso' );
        }, 'Erro ao obter estatísticas de produtos.' );
    }

    public function getFilteredProducts( array $filters = [], array $with = [], int $perPage = 15 ): ServiceResult
    {
        return $this->safeExecute( function () use ($filters, $with, $perPage) {
            if ( !$this->tenantId() ) {
                return $this->error( OperationStatus::ERROR, 'Tenant não identificado' );
            }

            $paginator = $this->repository->getPaginated(
                $this->normalizeFilters( $filters ),
                $perPage,
                $with,
            );

            Log::info( 'Produtos carregados', [ 'total' => $paginator->total() ] );
            return $paginator;
        }, 'Erro ao carregar produtos.' );
    }

    // --- Auxiliares Privados ---

    private function ensureTenantId(): int
    {
        $id = $this->tenantId();
        if ( !$id ) {
            throw new Exception( 'Tenant não identificado' );
        }
        return $id;
    }

    private function findAndVerifyOwnership( string $sku ): ServiceResult
    {
        $result = $this->findBySku( $sku );
        if ( $result->isError() ) return $result;

        $product = $result->getData();
        if ( $product->tenant_id !== $this->tenantId() ) {
            return $this->error( OperationStatus::UNAUTHORIZED, 'Produto não pertence ao tenant atual' );
        }

        return $this->success( $product );
    }

    private function uploadProductImage( $imageFile ): ?string
    {
        if ( !$imageFile ) return null;

        $tenantId = $this->tenantId();
        $path     = 'products/' . ( $tenantId ?? 'unknown' );
        $filename = Str::random( 40 ) . '.' . $imageFile->getClientOriginalExtension();

        if ( !Storage::disk( 'public' )->exists( $path ) ) {
            Storage::disk( 'public' )->makeDirectory( $path );
        }

        $imageFile->storePubliclyAs( $path, $filename, 'public' );
        return $path . '/' . $filename;
    }

    private function resolveRelativeStoragePath( ?string $image ): ?string
    {
        if ( empty( $image ) ) return null;
        $trimmed = ltrim( $image, '/' );
        if ( Str::startsWith( $trimmed, 'storage/' ) ) {
            return Str::after( $trimmed, 'storage/' );
        }
        return $trimmed;
    }

    /**
     * Normaliza preço removendo formatação brasileira (R$ 12,00 -> 12.00).
     */
    private function normalizePrice( string $price ): float
    {
        // Remove R$, pontos de milhar e substitui vírgula por ponto
        $clean = preg_replace( '/[^\d,]/', '', $price );
        return (float) str_replace( ',', '.', $clean );
    }

}
