<?php

declare(strict_types=1);

namespace App\Services\Domain;

use App\Models\Category;
use App\Repositories\CategoryRepository;
use App\Services\Core\Abstracts\AbstractBaseService;
use App\Support\ServiceResult;
use App\Enums\OperationStatus;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CategoryService extends AbstractBaseService
{
    public function __construct( CategoryRepository $repository )
    {
        parent::__construct( $repository );
    }

    protected function getSupportedFilters(): array
    {
        return [ 'id', 'name', 'slug', 'created_at', 'updated_at' ];
    }

    public function generateUniqueSlug( string $name ): string
    {
        $base = Str::slug( $name );
        $slug = $base;
        $i    = 1;
        while ( $this->repository->findBySlug( $slug ) ) {
            $slug = $base . '-' . $i;
            $i++;
        }
        return $slug;
    }

    public function validate( array $data, bool $isUpdate = false ): ServiceResult
    {
        $rules = Category::businessRules();

        if ( $isUpdate && isset( $data['id'] ) ) {
            $rules['slug'] = 'required|string|max:255|unique:categories,slug,' . $data['id'];
        }

        $validator = Validator::make( $data, $rules );

        if ( $validator->fails() ) {
            $messages = implode( ', ', $validator->errors()->all() );
            return $this->error( OperationStatus::INVALID_DATA, $messages );
        }

        return $this->success( $data );
    }

    public function createCategory( array $data ): ServiceResult
    {
        if ( !isset( $data[ 'slug' ] ) || empty( $data[ 'slug' ] ) ) {
            $data[ 'slug' ] = $this->generateUniqueSlug( $data[ 'name' ] ?? '' );
        }
        return $this->create( $data );
    }

    public function updateCategory( int $id, array $data ): ServiceResult
    {
        if ( isset( $data[ 'name' ] ) && empty( $data[ 'slug' ] ) ) {
            $data[ 'slug' ] = $this->generateUniqueSlug( $data[ 'name' ] );
        }
        return $this->update( $id, $data );
    }

    public function deleteCategory( int $id ): ServiceResult
    {
        $categoryResult = $this->findById( $id );
        if ( $categoryResult->isError() ) {
            return $categoryResult;
        }
        /** @var Category $category */
        $category = $categoryResult->getData();
        if ( $category->services()->exists() ) {
            return $this->error( 'Não é possível excluir: possui serviços associados' );
        }
        return $this->delete( $id );
    }

    public function getActive(): Collection
    {
        return $this->repository->listActive( ['name' => 'asc'] );
    }

    public function getWithGlobals(): Collection
    {
        return $this->repository->listWithGlobals( ['name' => 'asc'] );
    }

    public function findBySlug( string $slug ): ServiceResult
    {
        $entity = $this->repository->findBySlug( $slug );
        if ( !$entity ) {
            return $this->error( 'Categoria não encontrada' );
        }
        return $this->success( $entity );
    }

    public function listAll(): ServiceResult
    {
        $list = $this->repository->findOrderedByName( 'asc' );
        return $this->success( $list );
    }

}
