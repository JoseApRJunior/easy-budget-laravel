<?php

namespace app\database\servicesORM;

use app\database\entitiesORM\CategoryEntity;
use app\database\repositories\CategoryRepository;
use app\enums\OperationStatus;
use app\interfaces\ServiceNoTenantInterface;
use app\support\ServiceResult;
use Exception;

/**
 * Serviço para gerenciar operações relacionadas às categorias.
 */
class CategoryService implements ServiceNoTenantInterface
{
    private CategoryRepository $repository;

    public function __construct( CategoryRepository $repository )
    {
        $this->repository = $repository;
    }

    /**
     * Busca uma categoria por ID.
     *
     * @param int $id ID da categoria
     * @return ServiceResult Resultado da operação com status, mensagem e dados
     */
    public function getById( int $id ): ServiceResult
    {
        try {
            $entity = $this->repository->findById( $id );

            if ( !$entity ) {
                return ServiceResult::error( OperationStatus::NOT_FOUND, 'Categoria não encontrada.' );
            }

            return ServiceResult::success( $entity, 'Categoria encontrada com sucesso.' );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao buscar categoria: ' . $e->getMessage() );
        }
    }

    /**
     * Lista todas as categorias.
     *
     * @param array<string, mixed> $filters Filtros opcionais
     * @return ServiceResult Resultado da operação com status, mensagem e dados
     */
    public function list( array $filters = [] ): ServiceResult
    {
        try {
            $criteria = [];
            $orderBy  = [ 'name' => 'ASC' ];

            // Aplicar filtros se existirem
            if ( !empty( $filters[ 'name' ] ) ) {
                $criteria[ 'name' ] = $filters[ 'name' ];
            }

            if ( !empty( $filters[ 'slug' ] ) ) {
                $criteria[ 'slug' ] = $filters[ 'slug' ];
            }

            $entities = $this->repository->findAll( $criteria, $orderBy );

            return ServiceResult::success( $entities, 'Categorias listadas com sucesso.' );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao listar categorias: ' . $e->getMessage() );
        }
    }

    /**
     * Cria uma nova categoria.
     *
     * @param array<string, mixed> $data Dados para criação da entidade
     * @return ServiceResult Resultado da operação
     */
    public function create( array $data ): ServiceResult
    {
        try {
            // Validar dados de entrada
            $validation = $this->validate( $data );
            if ( !$validation->isSuccess() ) {
                return $validation;
            }

            // Criar nova entidade
            $entity = new CategoryEntity();
            $entity->setName( $data[ 'name' ] );

            if ( isset( $data[ 'slug' ] ) ) {
                $entity->setSlug( $data[ 'slug' ] );
            } else {
                $generatedSlug = $this->generateSlug( $data[ 'name' ] );
                $entity->setSlug( $generatedSlug );
            }

            // Salvar no repositório
            $result = $this->repository->save( $entity );

            if ( $result !== false ) {
                return ServiceResult::success( $result, 'Categoria criada com sucesso.' );
            } else {
                return ServiceResult::error( OperationStatus::ERROR, 'Falha ao salvar categoria no banco de dados.' );
            }
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao criar categoria: ' . $e->getMessage() );
        }
    }

    /**
     * Atualiza uma categoria existente.
     *
     * @param int $id ID da categoria
     * @param array<string, mixed> $data Dados para atualização
     * @return ServiceResult Resultado da operação
     */
    public function update( int $id, array $data ): ServiceResult
    {
        try {
            // Validar dados de entrada
            $validation = $this->validate( $data, true );
            if ( !$validation->isSuccess() ) {
                return $validation;
            }

            // Buscar entidade existente
            $entity = $this->repository->findById( $id );
            if ( $entity === null ) {
                return ServiceResult::error( OperationStatus::NOT_FOUND, 'Categoria não encontrada.' );
            }

            /** @var CategoryEntity $entity  */
            $oldName = $entity->getName();
            $entity->setName( $data[ 'name' ] );

            if ( isset( $data[ 'slug' ] ) ) {
                $entity->setSlug( $data[ 'slug' ] );
            } elseif ( $oldName !== $data[ 'name' ] ) {
                // Atualizar slug apenas se o nome foi alterado e slug não foi fornecido
                $entity->setSlug( $this->generateSlug( $data[ 'name' ] ) );
            }

            // Salvar no repositório
            $result = $this->repository->save( $entity );

            if ( $result !== false ) {
                return ServiceResult::success( $result, 'Categoria atualizada com sucesso.' );
            } else {
                return ServiceResult::error( OperationStatus::ERROR, 'Falha ao salvar categoria no banco de dados.' );
            }
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao atualizar categoria: ' . $e->getMessage() );
        }
    }

    /**
     * Remove uma categoria.
     *
     * @param int $id ID da categoria
     * @return ServiceResult Resultado da operação
     */
    public function delete( int $id ): ServiceResult
    {
        try {
            // Verificar se a entidade existe antes de tentar deletar
            $entity = $this->repository->findById( $id );
            if ( !$entity ) {
                return ServiceResult::error( OperationStatus::NOT_FOUND, 'Categoria não encontrada.' );
            }

            // Executar a exclusão
            $result = $this->repository->delete( $id );

            if ( $result ) {
                return ServiceResult::success( null, 'Categoria removida com sucesso.' );
            } else {
                return ServiceResult::error( OperationStatus::ERROR, 'Falha ao remover categoria do banco de dados.' );
            }
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao excluir categoria: ' . $e->getMessage() );
        }
    }

    /**
     * Valida os dados de entrada para operações de criação ou atualização.
     *
     * @param array<string, mixed> $data Dados a serem validados
     * @param bool $isUpdate Indica se é uma operação de atualização
     * @return ServiceResult Resultado da validação
     */
    public function validate( array $data, bool $isUpdate = false ): ServiceResult
    {
        $errors = [];

        // Validar nome
        if ( empty( $data[ 'name' ] ) ) {
            $errors[] = "O nome da categoria é obrigatório.";
        } elseif ( strlen( $data[ 'name' ] ) > 100 ) {
            $errors[] = "O nome da categoria deve ter no máximo 100 caracteres.";
        }

        // Validar slug (se fornecido)
        if ( isset( $data[ 'slug' ] ) ) {
            if ( empty( $data[ 'slug' ] ) ) {
                $errors[] = "O slug não pode estar vazio quando fornecido.";
            } elseif ( strlen( $data[ 'slug' ] ) > 100 ) {
                $errors[] = "O slug deve ter no máximo 100 caracteres.";
            } elseif ( !preg_match( '/^[a-z0-9-]+$/', $data[ 'slug' ] ) ) {
                $errors[] = "O slug deve conter apenas letras minúsculas, números e hífens.";
            }
        }

        if ( count( $errors ) > 0 ) {
            return ServiceResult::error( OperationStatus::INVALID_DATA, "Dados de categoria inválidos: " . implode( ', ', $errors ) );
        }

        return ServiceResult::success( null, "Dados de categoria válidos." );
    }

    /**
     * Gera um slug a partir do nome da categoria.
     *
     * @param string $name Nome da categoria
     * @return string Slug gerado
     */
    private function generateSlug( string $name ): string
    {
        // Converter para minúsculas
        $slug = mb_strtolower( $name, 'UTF-8' );

        // Remover acentos
        $slug = preg_replace( '/[áàãâä]/u', 'a', $slug );
        $slug = preg_replace( '/[éèêë]/u', 'e', $slug );
        $slug = preg_replace( '/[íìîï]/u', 'i', $slug );
        $slug = preg_replace( '/[óòõôö]/u', 'o', $slug );
        $slug = preg_replace( '/[úùûü]/u', 'u', $slug );
        $slug = preg_replace( '/[ç]/u', 'c', $slug );

        // Substituir espaços e caracteres especiais por hífens
        $slug = preg_replace( '/[^a-z0-9\-]/', '-', $slug );

        // Remover hífens duplicados
        $slug = preg_replace( '/-+/', '-', $slug );

        // Remover hífens no início e fim
        $slug = trim( $slug, '-' );

        return $slug;
    }

}
