<?php

namespace app\database\servicesORM;

use app\database\entitiesORM\UnitEntity;
use app\interfaces\RepositoryNoTenantInterface;
use app\interfaces\ServiceNoTenantInterface;
use app\support\ServiceResult;
use app\enums\OperationStatus;
use core\dbal\EntityNotFound;
use core\library\Session;
use DateTime;
use Exception;
use RuntimeException;

/**
 * Classe UnitService
 *
 * Implementa a interface ServiceNoTenantInterface para fornecer operações de serviço para unidades.
 * Como a entidade Unit não possui tenant_id, esta implementação é adequada para entidades sem controle multi-tenant.
 */
class UnitService implements ServiceNoTenantInterface
{
    /**
     * Usuário autenticado
     * @var mixed
     */
    private mixed $authenticated = null;

    /**
     * Construtor da classe UnitService
     *
     * @param RepositoryNoTenantInterface $unitRepository Repositório de unidades
     */
    public function __construct(
        private readonly RepositoryNoTenantInterface $unitRepository,
    ) {
        if ( Session::has( 'auth' ) ) {
            $this->authenticated = Session::get( 'auth' );
        }
    }

    /**
     * Busca uma unidade pelo seu ID.
     *
     * @param int $id ID da unidade
     * @return ServiceResult Resultado da operação com status, mensagem e dados
     */
    public function getById( int $id ): ServiceResult
    {
        try {
            $entity = $this->unitRepository->findById( $id );

            if ( !$entity ) {
                return ServiceResult::error( OperationStatus::NOT_FOUND, 'Unidade não encontrada.' );
            }

            return ServiceResult::success( $entity, 'Unidade encontrada com sucesso.' );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao buscar unidade: ' . $e->getMessage() );
        }
    }

    /**
     * Lista todas as unidades.
     *
     * @param array<string, mixed> $filters Filtros a serem aplicados
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

            if ( isset( $filters[ 'is_active' ] ) ) {
                $criteria[ 'is_active' ] = (bool) $filters[ 'is_active' ];
            }

            $entities = $this->unitRepository->findAll( $criteria, $orderBy );

            return ServiceResult::success( $entities, 'Unidades listadas com sucesso.' );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao listar unidades: ' . $e->getMessage() );
        }
    }

    /**
     * Cria uma nova unidade.
     *
     * @param array<string, mixed> $data Dados para criação da unidade
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
            $unit = new UnitEntity();
            $unit->setName( $data[ 'name' ] );
            $unit->setSlug( $data[ 'slug' ] ?? $this->generateSlug( $data[ 'name' ] ) );
            $unit->setIsActive( $data[ 'is_active' ] ?? true );
            $unit->setCreatedAt( new DateTime() );
            $unit->setUpdatedAt( new DateTime() );

            // Salvar no repositório
            $result = $this->unitRepository->save( $unit );

            if ( $result !== false ) {
                return ServiceResult::success( $result, 'Unidade criada com sucesso.' );
            } else {
                return ServiceResult::error( OperationStatus::ERROR, 'Falha ao salvar unidade no banco de dados.' );
            }
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao criar unidade: ' . $e->getMessage() );
        }
    }

    /**
     * Atualiza uma unidade existente.
     *
     * @param int $id ID da unidade
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

            // Buscar unidade existente
            $entity = $this->unitRepository->findById( $id );
            if ( !$entity ) {
                return ServiceResult::error( OperationStatus::NOT_FOUND, 'Unidade não encontrada.' );
            }

            // Atualizar dados
            /** @var UnitEntity $unitEntity */
            $unitEntity = $entity;
            $oldName    = $unitEntity->getName();
            $unitEntity->setName( $data[ 'name' ] );

            if ( isset( $data[ 'slug' ] ) ) {
                $unitEntity->setSlug( $data[ 'slug' ] );
            } elseif ( $oldName !== $data[ 'name' ] ) {
                // Atualizar slug apenas se o nome foi alterado e slug não foi fornecido
                $unitEntity->setSlug( $this->generateSlug( $data[ 'name' ] ) );
            }

            if ( isset( $data[ 'is_active' ] ) ) {
                $unitEntity->setIsActive( (bool) $data[ 'is_active' ] );
            }

            $unitEntity->setUpdatedAt( new DateTime() );

            // Salvar no repositório
            $result = $this->unitRepository->save( $unitEntity );

            if ( $result !== false ) {
                return ServiceResult::success( $result, 'Unidade atualizada com sucesso.' );
            } else {
                return ServiceResult::error( OperationStatus::ERROR, 'Falha ao salvar unidade no banco de dados.' );
            }
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao atualizar unidade: ' . $e->getMessage() );
        }
    }

    /**
     * Remove uma unidade.
     *
     * @param int $id ID da unidade
     * @return ServiceResult Resultado da operação
     */
    public function delete( int $id ): ServiceResult
    {
        try {
            // Verificar se a entidade existe antes de tentar deletar
            $entity = $this->unitRepository->findById( $id );
            if ( !$entity ) {
                return ServiceResult::error( OperationStatus::NOT_FOUND, 'Unidade não encontrada.' );
            }

            // Executar a exclusão
            $result = $this->unitRepository->delete( $id );

            if ( $result ) {
                return ServiceResult::success( null, 'Unidade removida com sucesso.' );
            } else {
                return ServiceResult::error( OperationStatus::ERROR, 'Falha ao remover unidade do banco de dados.' );
            }
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao excluir unidade: ' . $e->getMessage() );
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
            $errors[] = "O nome da unidade é obrigatório.";
        } elseif ( strlen( $data[ 'name' ] ) > 100 ) {
            $errors[] = "O nome da unidade deve ter no máximo 100 caracteres.";
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

        // Validar is_active (se fornecido)
        if ( isset( $data[ 'is_active' ] ) && !is_bool( $data[ 'is_active' ] ) && !in_array( $data[ 'is_active' ], [ '0', '1', 0, 1, 'true', 'false' ] ) ) {
            $errors[] = "O campo ativo deve ser um valor booleano válido.";
        }

        if ( count( $errors ) > 0 ) {
            return ServiceResult::error( OperationStatus::INVALID_DATA, "Dados de unidade inválidos: " . implode( ', ', $errors ) );
        }

        return ServiceResult::success( null, "Dados de unidade válidos." );
    }

    /**
     * Gera um slug a partir do nome da unidade.
     *
     * @param string $name Nome da unidade
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
