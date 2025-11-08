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

    private function generateUniqueSku(): string
    {
        do {
            $sku = 'PROD' . str_pad( (string) mt_rand( 1, 999999 ), 6, '0', STR_PAD_LEFT );
        } while ( $this->productRepository->findBySku( $sku ) );

        return $sku;
    }

    private function uploadProductImage( $imageFile ): ?string
    {
        if ( !$imageFile ) {
            return null;
        }

        $path     = 'products/' . tenant()->id;
        $filename = Str::random( 40 ) . '.' . $imageFile->getClientOriginalExtension();

        // Redimensionar e salvar imagem
        // Usar uma biblioteca de imagem como Intervention Image ou similar
        // Por simplicidade, aqui apenas salva o arquivo original
        $imageFile->storePubliclyAs( $path, $filename, 'public' );

        return Storage::url( $path . '/' . $filename );
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
                    Storage::disk( 'public' )->delete( Str::after( $product->image, '/storage/' ) );
                    $data[ 'image' ] = null;
                }

                // Processar nova imagem se fornecida
                if ( isset( $data[ 'image' ] ) && is_a( $data[ 'image' ], 'Illuminate\Http\UploadedFile' ) ) {
                    // Deletar imagem antiga se existir
                    if ( $product->image ) {
                        Storage::disk( 'public' )->delete( Str::after( $product->image, '/storage/' ) );
                    }
                    $data[ 'image' ] = $this->uploadProductImage( $data[ 'image' ] );
                } else if ( isset( $data[ 'image' ] ) && $data[ 'image' ] === null ) {
                    // Se a imagem foi explicitamente definida como null (e não foi removida pelo remove_image)
                    // Isso pode acontecer se o campo de upload for limpo sem o checkbox de remover
                    if ( $product->image ) {
                        Storage::disk( 'public' )->delete( Str::after( $product->image, '/storage/' ) );
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
                    Storage::disk( 'public' )->delete( Str::after( $product->image, '/storage/' ) );
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

}
