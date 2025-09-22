<?php

namespace app\database\servicesORM;

use app\database\entitiesORM\MerchantOrderMercadoPagoEntity;
use app\database\repositories\MerchantOrderMercadoPagoRepository;
use app\interfaces\ServiceInterface;
use app\support\ServiceResult;
use app\enums\OperationStatus;
use core\dbal\EntityNotFound;
use Doctrine\DBAL\Connection;
use Exception;

class MerchantOrderMercadoPagoService implements ServiceInterface
{
    private MerchantOrderMercadoPagoRepository $repository;
    private Connection                         $connection;

    public function __construct(
        MerchantOrderMercadoPagoRepository $repository,
        Connection $connection,
    ) {
        $this->repository = $repository;
        $this->connection = $connection;
    }

    /**
     * Busca uma ordem de comerciante pelo seu ID e ID do tenant.
     *
     * @param int $id ID da entidade
     * @param int $tenant_id ID do tenant
     * @return ServiceResult Resultado da operação com status, mensagem e dados
     */
    public function getByIdAndTenantId( int $id, int $tenant_id ): ServiceResult
    {
        try {
            $entity = $this->repository->findByIdAndTenantId( $id, $tenant_id );

            if ( !$entity || $entity instanceof EntityNotFound ) {
                return ServiceResult::error( OperationStatus::NOT_FOUND, 'Ordem de comerciante não encontrada.' );
            }

            return ServiceResult::success( $entity, 'Ordem de comerciante encontrada com sucesso.' );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao buscar ordem de comerciante: ' . $e->getMessage() );
        }
    }

    /**
     * Lista todas as ordens de comerciantes de um tenant, com filtros opcionais.
     *
     * @param int $tenant_id ID do tenant
     * @param array<string, mixed> $filters Filtros a serem aplicados
     * @return ServiceResult Resultado da operação com status, mensagem e dados
     */
    public function listByTenantId( int $tenant_id, array $filters = [] ): ServiceResult
    {
        try {
            $criteria = [];
            $orderBy  = [ 'created_at' => 'DESC' ];
            $limit    = $filters[ 'limit' ] ?? null;
            $offset   = $filters[ 'offset' ] ?? null;

            $entities = $this->repository->findAllByTenantId( $tenant_id, $criteria, $orderBy, $limit, $offset );

            return ServiceResult::success( $entities, 'Ordens de comerciantes listadas com sucesso.' );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao listar ordens de comerciantes: ' . $e->getMessage() );
        }
    }

    /**
     * Cria uma nova ordem de comerciante.
     *
     * @param array<string, mixed> $data Dados para criação da entidade
     * @param int $tenant_id ID do tenant
     * @return ServiceResult Resultado da operação com status, mensagem e dados
     */
    public function createByTenantId( array $data, int $tenant_id ): ServiceResult
    {
        try {
            // Validar dados de entrada
            $validation = $this->validate( $data );
            if ( !$validation->isSuccess() ) {
                return $validation;
            }

            // Criar nova entidade
            $entity = new MerchantOrderMercadoPagoEntity();

            // Preencher os dados da entidade
            foreach ( $data as $key => $value ) {
                $setter = 'set' . str_replace( '_', '', ucwords( $key, '_' ) );
                if ( method_exists( $entity, $setter ) ) {
                    $entity->$setter( $value );
                }
            }

            $entity->setTenantId( $tenant_id );

            // Salvar no repositório
            $result = $this->repository->save( $entity, $tenant_id );

            if ( $result !== false ) {
                return ServiceResult::success( $result, 'Ordem de comerciante criada com sucesso.' );
            } else {
                return ServiceResult::error( OperationStatus::ERROR, 'Falha ao salvar ordem de comerciante no banco de dados.' );
            }
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao criar ordem de comerciante: ' . $e->getMessage() );
        }
    }

    /**
     * Atualiza uma ordem de comerciante existente.
     *
     * @param int $id ID da entidade
     * @param int $tenant_id ID do tenant
     * @param array<string, mixed> $data Dados para atualização
     * @return ServiceResult Resultado da operação com status, mensagem e dados
     */
    public function updateByIdAndTenantId( int $id, int $tenant_id, array $data ): ServiceResult
    {
        try {
            // Validar dados de entrada
            $validation = $this->validate( $data, true );
            if ( !$validation->isSuccess() ) {
                return $validation;
            }

            // Buscar entidade existente
            $entity = $this->repository->findByIdAndTenantId( $id, $tenant_id );

            if ( !$entity || $entity instanceof EntityNotFound ) {
                return ServiceResult::error( OperationStatus::NOT_FOUND, 'Ordem de comerciante não encontrada.' );
            }

            // Atualizar os dados da entidade
            /** @var MerchantOrderMercadoPagoEntity $entity */
            foreach ( $data as $key => $value ) {
                $setter = 'set' . str_replace( '_', '', ucwords( $key, '_' ) );
                if ( method_exists( $entity, $setter ) ) {
                    $entity->$setter( $value );
                }
            }

            // Salvar no repositório
            $result = $this->repository->save( $entity, $tenant_id );

            if ( $result !== false ) {
                return ServiceResult::success( $result, 'Ordem de comerciante atualizada com sucesso.' );
            } else {
                return ServiceResult::error( OperationStatus::ERROR, 'Falha ao salvar ordem de comerciante no banco de dados.' );
            }
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao atualizar ordem de comerciante: ' . $e->getMessage() );
        }
    }

    /**
     * Remove uma ordem de comerciante.
     *
     * @param int $id ID da entidade
     * @param int $tenant_id ID do tenant
     * @return ServiceResult Resultado da operação com status, mensagem e dados
     */
    public function deleteByIdAndTenantId( int $id, int $tenant_id ): ServiceResult
    {
        try {
            // Verificar se a entidade existe
            $entity = $this->repository->findByIdAndTenantId( $id, $tenant_id );

            if ( !$entity || $entity instanceof EntityNotFound ) {
                return ServiceResult::error( OperationStatus::NOT_FOUND, 'Ordem de comerciante não encontrada.' );
            }

            // Executar a exclusão
            $result = $this->repository->deleteByIdAndTenantId( $id, $tenant_id );

            if ( $result ) {
                return ServiceResult::success( null, 'Ordem de comerciante removida com sucesso.' );
            } else {
                return ServiceResult::error( OperationStatus::ERROR, 'Falha ao remover ordem de comerciante do banco de dados.' );
            }
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao excluir ordem de comerciante: ' . $e->getMessage() );
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

        // Validações básicas
        if ( !$isUpdate ) {
            // Validações específicas para criação
            if ( empty( $data[ 'merchant_order_id' ] ) ) {
                $errors[] = 'ID da ordem do comerciante é obrigatório.';
            }

            if ( empty( $data[ 'provider_id' ] ) ) {
                $errors[] = 'ID do provedor é obrigatório.';
            }

            if ( empty( $data[ 'plan_subscription_id' ] ) ) {
                $errors[] = 'ID da assinatura do plano é obrigatório.';
            }

            if ( empty( $data[ 'status' ] ) ) {
                $errors[] = 'Status é obrigatório.';
            }

            if ( empty( $data[ 'order_status' ] ) ) {
                $errors[] = 'Status do pedido é obrigatório.';
            }

            if ( !isset( $data[ 'total_amount' ] ) ) {
                $errors[] = 'Valor total é obrigatório.';
            }
        }

        // Validar campos numéricos
        if ( isset( $data[ 'merchant_order_id' ] ) && !is_numeric( $data[ 'merchant_order_id' ] ) ) {
            $errors[] = 'ID da ordem do comerciante deve ser um número válido.';
        }

        if ( isset( $data[ 'provider_id' ] ) && !is_numeric( $data[ 'provider_id' ] ) ) {
            $errors[] = 'ID do provedor deve ser um número válido.';
        }

        if ( isset( $data[ 'plan_subscription_id' ] ) && !is_numeric( $data[ 'plan_subscription_id' ] ) ) {
            $errors[] = 'ID da assinatura do plano deve ser um número válido.';
        }

        if ( isset( $data[ 'total_amount' ] ) && !is_numeric( $data[ 'total_amount' ] ) ) {
            $errors[] = 'Valor total deve ser um número válido.';
        }

        if ( !empty( $errors ) ) {
            return ServiceResult::error( OperationStatus::INVALID_DATA, 'Dados inválidos: ' . implode( ', ', $errors ) );
        }

        return ServiceResult::success( null, 'Dados válidos.' );
    }

}
