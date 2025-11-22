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
use Illuminate\Support\Str;

class ProductService extends AbstractBaseService
{
    private ProductRepository $productRepository;

    public function __construct( ProductRepository $productRepository )
    {
        $this->productRepository = $productRepository;
    }

    /**
     * Retorna o total de produtos do tenant autenticado.
     */
    public function getTotalCount(): int
    {
        return Product::count();
    }

    /**
     * Retorna o total de produtos ativos do tenant autenticado.
     */
    public function getActiveCount(): int
    {
        return $this->productRepository->countActive();
    }

    /**
     * Retorna uma coleção de produtos recentes com relacionamentos opcionais.
     *
     * @param  int   $limit Quantidade de registros retornados
     * @param  array $with  Relacionamentos para eager loading
     */
    public function getRecentProducts( int $limit = 5, array $with = [] ): Collection
    {
        $query = Product::query();

        if ( !empty( $with ) ) {
            $query->with( $with );
        }

        return $query
            ->orderByDesc( 'created_at' )
            ->limit( $limit )
            ->get();
    }

    /**
     * Retorna produtos ativos
     */
    public function getActive(): Collection
    {
        try {
            return Product::where( 'active', true )
                ->orderBy( 'name' )
                ->get();
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
            $product = $this->productRepository->findBySku( $sku, $with );

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

    public function getFilteredProducts( array $filters = [], array $with = [] ): ServiceResult
    {
        try {
            $products = $this->productRepository->getPaginated( $filters, 15, $with );

            return $this->success( $products, 'Produtos filtrados' );

        } catch ( Exception $e ) {
            return $this->error(
                OperationStatus::ERROR,
                'Erro ao filtrar produtos',
                null,
                $e,
            );
        }
    }

    public function createProduct( array $data ): ServiceResult
    {
        try {
            return DB::transaction( function () use ($data) {
                // Gerar SKU se não fornecido
                if ( empty( $data[ 'sku' ] ) ) {
                    $data[ 'sku' ] = $this->generateUniqueSku();
                }

                // Processar imagem
                if ( isset( $data[ 'image' ] ) ) {
                    $data[ 'image' ] = $this->uploadProductImage( $data[ 'image' ] );
                }

                if ( empty( $data['tenant_id'] ) ) {
                    $resolvedTenantId = auth()->user()->tenant_id ?? ( function_exists( 'tenant' ) && tenant() ? tenant()->id : null );
                    $data['tenant_id'] = $resolvedTenantId;
                }

                $product = $this->productRepository->create( $data );

                return $this->success( $product, 'Produto criado com sucesso' );
            } );
        } catch ( Exception $e ) {
            return $this->error(
                OperationStatus::ERROR,
                'Erro ao criar produto',
                null,
                $e,
            );
        }
    }

    /**
     * Gera SKU único sequencial no padrão legado: PROD000001, PROD000002, ...
     *
     * - Busca o último SKU existente iniciando com "PROD"
     * - Extrai a parte numérica e incrementa
     * - Mantém 6 dígitos com zero à esquerda
     *
     * Compatível com comportamento do sistema antigo.
     */
    private function generateUniqueSku(): string
    {
        return DB::transaction( function () {
            $lastProduct = Product::where( 'sku', 'LIKE', 'PROD%' )
                ->orderByDesc( 'sku' )
                ->lockForUpdate()
                ->first();

            if ( !$lastProduct || !preg_match( '/^PROD(\d{6})$/', $lastProduct->sku, $matches ) ) {
                return 'PROD000001';
            }

            $nextNumber = (int) $matches[ 1 ] + 1;

            return 'PROD' . str_pad( (string) $nextNumber, 6, '0', STR_PAD_LEFT );
        } );
    }

    private function uploadProductImage( $imageFile ): ?string
    {
        if ( !$imageFile ) {
            return null;
        }

        $tenantId = auth()->user()->tenant_id ?? ( function_exists('tenant') && tenant() ? tenant()->id : null );
        $path     = 'products/' . ($tenantId ?? 'unknown');
        $filename = Str::random( 40 ) . '.' . $imageFile->getClientOriginalExtension();

        // Redimensionar e salvar imagem
        // Usar uma biblioteca de imagem como Intervention Image ou similar
        // Por simplicidade, aqui apenas salva o arquivo original
        $imageFile->storePubliclyAs( $path, $filename, 'public' );

        return $path . '/' . $filename;
    }

    public function updateProductBySku( string $sku, array $data ): ServiceResult
    {
        try {
            return DB::transaction( function () use ($sku, $data) {
                $product = $this->productRepository->findBySku( $sku );

                if ( !$product ) {
                    return $this->error(
                        OperationStatus::NOT_FOUND,
                        "Produto com SKU {$sku} não encontrado",
                    );
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
                    // Se a imagem foi explicitamente definida como null (e não foi removida pelo remove_image)
                    // Isso pode acontecer se o campo de upload for limpo sem o checkbox de remover
                    if ( $product->image ) {
                        $relative = $this->resolveRelativeStoragePath( $product->image );
                        if ( $relative ) {
                            Storage::disk( 'public' )->delete( $relative );
                        }
                    }
                    $data[ 'image' ] = null;
                } else {
                    // Manter imagem existente se não houver nova imagem e nem remoção solicitada
                    unset( $data[ 'image' ] );
                }

                $product = $this->productRepository->update( $product->id, $data );

                return $this->success( $product, 'Produto atualizado com sucesso' );
            } );
        } catch ( Exception $e ) {
            return $this->error(
                OperationStatus::ERROR,
                'Erro ao atualizar produto',
                null,
                $e,
            );
        }
    }

    public function toggleProductStatus( string $sku ): ServiceResult
    {
        try {
            return DB::transaction( function () use ($sku) {
                $product = $this->productRepository->findBySku( $sku );

                if ( !$product ) {
                    return $this->error(
                        OperationStatus::NOT_FOUND,
                        "Produto com SKU {$sku} não encontrado",
                    );
                }

                if ( !$this->productRepository->canBeDeactivatedOrDeleted( $product->id ) ) {
                    return $this->error(
                        OperationStatus::VALIDATION_ERROR,
                        'Produto não pode ser desativado/ativado pois está em uso em serviços.',
                    );
                }

                $newStatus = !$product->active;
                $product   = $this->productRepository->update( $product->id, [ 'active' => $newStatus ] );

                $message = $newStatus ? 'Produto ativado com sucesso' : 'Produto desativado com sucesso';
                return $this->success( $product, $message );
            } );
        } catch ( Exception $e ) {
            return $this->error(
                OperationStatus::ERROR,
                'Erro ao alterar status do produto',
                null,
                $e,
            );
        }
    }

    public function deleteProductBySku( string $sku ): ServiceResult
    {
        try {
            return DB::transaction( function () use ($sku) {
                $product = $this->productRepository->findBySku( $sku );

                if ( !$product ) {
                    return $this->error(
                        OperationStatus::NOT_FOUND,
                        "Produto com SKU {$sku} não encontrado",
                    );
                }

                if ( !$this->productRepository->canBeDeactivatedOrDeleted( $product->id ) ) {
                    return $this->error(
                        OperationStatus::VALIDATION_ERROR,
                        'Produto não pode ser excluído pois está em uso em serviços.',
                    );
                }

                // Deletar imagem física se existir
                if ( $product->image ) {
                    $relative = $this->resolveRelativeStoragePath( $product->image );
                    if ( $relative ) {
                        Storage::disk( 'public' )->delete( $relative );
                    }
                }

                $this->productRepository->delete( $product->id );

                return $this->success( null, 'Produto excluído com sucesso' );
            } );
        } catch ( Exception $e ) {
            return $this->error(
                OperationStatus::ERROR,
                'Erro ao excluir produto',
                null,
                $e,
            );
        }
    }

    private function resolveRelativeStoragePath( ?string $image ): ?string
    {
        if ( empty( $image ) ) {
            return null;
        }
        $trimmed = ltrim( $image, '/' );
        if ( Str::startsWith( $trimmed, 'storage/' ) ) {
            return Str::after( $trimmed, 'storage/' );
        }
        return $trimmed;
    }

}
