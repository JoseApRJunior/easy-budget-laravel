<?php

namespace app\database\servicesORM;

use app\database\entitiesORM\BudgetStatusesEntity;
use app\database\repositories\BudgetStatusesRepository;
use app\interfaces\ServiceNoTenantInterface;
use app\support\ServiceResult;
use app\enums\OperationStatus;
use Exception;

/**
 * Serviço para gerenciar operações relacionadas aos status de orçamentos.
 */
class BudgetStatusesService implements ServiceNoTenantInterface
{
    private BudgetStatusesRepository $repository;

    public function __construct( BudgetStatusesRepository $repository )
    {
        $this->repository = $repository;
    }

    /**
     * Busca um status de orçamento por ID.
     *
     * @param int $id ID do status de orçamento
     * @return ServiceResult Resultado da operação com status, mensagem e dados
     */
    public function getById( int $id ): ServiceResult
    {
        try {
            $entity = $this->repository->findById( $id );

            if ( !$entity ) {
                return ServiceResult::error( OperationStatus::NOT_FOUND, 'Status de orçamento não encontrado.' );
            }

            return ServiceResult::success( $entity, 'Status de orçamento encontrado com sucesso.' );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao buscar status de orçamento: ' . $e->getMessage() );
        }
    }

    /**
     * Lista todos os status de orçamentos.
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

            return ServiceResult::success( $entities, 'Status de orçamentos listados com sucesso.' );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao listar status de orçamentos: ' . $e->getMessage() );
        }
    }

    /**
     * Cria um novo status de orçamento.
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
            $entity = new BudgetStatusesEntity();
            $entity->setName( $data[ 'name' ] );
            $entity->setSlug( $data[ 'slug' ] ?? '' );
            $entity->setDescription( $data[ 'description' ] ?? '' );
            $entity->setColor( $data[ 'color' ] ?? '' );
            $entity->setIcon( $data[ 'icon' ] ?? '' );

            // Salvar no repositório
            $result = $this->repository->save( $entity );

            if ( $result !== false ) {
                return ServiceResult::success( $result, 'Status de orçamento criado com sucesso.' );
            } else {
                return ServiceResult::error( OperationStatus::ERROR, 'Falha ao salvar status de orçamento no banco de dados.' );
            }
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao criar status de orçamento: ' . $e->getMessage() );
        }
    }

    /**
     * Atualiza um status de orçamento existente.
     *
     * @param int $id ID do status de orçamento
     * @param array<string, mixed> $data Dados para atualização
     * @return ServiceResult Resultado da operação
     */
    public function update( int $id, array $data ): ServiceResult
    {
        try {
            // Buscar entidade existente
            $entity = $this->repository->findById( $id );
            if ( !$entity ) {
                return ServiceResult::error( OperationStatus::NOT_FOUND, 'Status de orçamento não encontrado.' );
            }

            // Verificar se a entidade é nula após a busca
            if ( $entity === null ) {
                return ServiceResult::error( OperationStatus::NOT_FOUND, 'Status de orçamento não encontrado.' );
            }

            /** @var BudgetStatusesEntity $entity  */
            // Atualizar dados
            if ( isset( $data[ 'name' ] ) ) {
                $entity->setName( $data[ 'name' ] );
            }

            if ( isset( $data[ 'slug' ] ) ) {
                $entity->setSlug( $data[ 'slug' ] );
            }

            if ( isset( $data[ 'description' ] ) ) {
                $entity->setDescription( $data[ 'description' ] );
            }

            if ( isset( $data[ 'color' ] ) ) {
                $entity->setColor( $data[ 'color' ] );
            }

            if ( isset( $data[ 'icon' ] ) ) {
                $entity->setIcon( $data[ 'icon' ] );
            }

            // Salvar no repositório
            $result = $this->repository->save( $entity );

            if ( $result !== false ) {
                return ServiceResult::success( $result, 'Status de orçamento atualizado com sucesso.' );
            } else {
                return ServiceResult::error( OperationStatus::ERROR, 'Falha ao salvar status de orçamento no banco de dados.' );
            }
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao atualizar status de orçamento: ' . $e->getMessage() );
        }
    }

    /**
     * Remove um status de orçamento.
     *
     * @param int $id ID do status de orçamento
     * @return ServiceResult Resultado da operação
     */
    public function delete( int $id ): ServiceResult
    {
        try {
            // Verificar se a entidade existe antes de tentar deletar
            $entity = $this->repository->findById( $id );
            if ( !$entity ) {
                return ServiceResult::error( OperationStatus::NOT_FOUND, 'Status de orçamento não encontrado.' );
            }

            // Executar a exclusão
            $result = $this->repository->delete( $id );

            if ( $result ) {
                return ServiceResult::success( null, 'Status de orçamento removido com sucesso.' );
            } else {
                return ServiceResult::error( OperationStatus::ERROR, 'Falha ao remover status de orçamento do banco de dados.' );
            }
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao excluir status de orçamento: ' . $e->getMessage() );
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
            $errors[] = "O nome do status de orçamento é obrigatório.";
        } elseif ( strlen( $data[ 'name' ] ) > 100 ) {
            $errors[] = "O nome do status de orçamento deve ter no máximo 100 caracteres.";
        }

        // Validar slug (se fornecido)
        if ( isset( $data[ 'slug' ] ) && !empty( $data[ 'slug' ] ) ) {
            if ( strlen( $data[ 'slug' ] ) > 100 ) {
                $errors[] = "O slug deve ter no máximo 100 caracteres.";
            } elseif ( !preg_match( '/^[a-z0-9-]+$/', $data[ 'slug' ] ) ) {
                $errors[] = "O slug deve conter apenas letras minúsculas, números e hífens.";
            }
        }

        if ( count( $errors ) > 0 ) {
            return ServiceResult::error( OperationStatus::INVALID_DATA, "Dados de status de orçamento inválidos: " . implode( ', ', $errors ) );
        }

        return ServiceResult::success( null, "Dados de status de orçamento válidos." );
    }

}
