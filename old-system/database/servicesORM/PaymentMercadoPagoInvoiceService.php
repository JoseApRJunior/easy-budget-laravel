<?php

namespace app\database\servicesORM;

use app\database\entitiesORM\PaymentMercadoPagoInvoicesEntity;
use app\database\repositories\PaymentMercadoPagoInvoicesRepository;
use app\interfaces\ServiceInterface;
use app\support\ServiceResult;
use app\enums\OperationStatus;
use core\dbal\EntityNotFound;
use Doctrine\DBAL\Connection;
use Exception;

class PaymentMercadoPagoInvoiceService implements ServiceInterface
{
    private PaymentMercadoPagoInvoicesRepository $repository;
    private Connection                           $connection;

    public function __construct(
        PaymentMercadoPagoInvoicesRepository $repository,
        Connection $connection,
    ) {
        $this->repository = $repository;
        $this->connection = $connection;
    }

    /**
     * Busca um pagamento pelo seu ID e ID do tenant.
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
                return ServiceResult::error( OperationStatus::NOT_FOUND, 'Pagamento não encontrado.' );
            }

            return ServiceResult::success( $entity, 'Pagamento encontrado com sucesso.' );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao buscar pagamento: ' . $e->getMessage() );
        }
    }

    /**
     * Lista todos os pagamentos de um tenant, com filtros opcionais.
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

            return ServiceResult::success( $entities, 'Pagamentos listados com sucesso.' );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao listar pagamentos: ' . $e->getMessage() );
        }
    }

    /**
     * Cria um novo pagamento.
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
            $entity = new PaymentMercadoPagoInvoicesEntity();

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
                return ServiceResult::success( $result, 'Pagamento criado com sucesso.' );
            } else {
                return ServiceResult::error( OperationStatus::ERROR, 'Falha ao salvar pagamento no banco de dados.' );
            }
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao criar pagamento: ' . $e->getMessage() );
        }
    }

    /**
     * Atualiza um pagamento existente.
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
                return ServiceResult::error( OperationStatus::NOT_FOUND, 'Pagamento não encontrado.' );
            }

            // Atualizar os dados da entidade
            /** @var PaymentMercadoPagoInvoicesEntity $entity */
            foreach ( $data as $key => $value ) {
                $setter = 'set' . str_replace( '_', '', ucwords( $key, '_' ) );
                if ( method_exists( $entity, $setter ) ) {
                    $entity->$setter( $value );
                }
            }

            // Salvar no repositório
            $result = $this->repository->save( $entity, $tenant_id );

            if ( $result !== false ) {
                return ServiceResult::success( $result, 'Pagamento atualizado com sucesso.' );
            } else {
                return ServiceResult::error( OperationStatus::ERROR, 'Falha ao salvar pagamento no banco de dados.' );
            }
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao atualizar pagamento: ' . $e->getMessage() );
        }
    }

    /**
     * Remove um pagamento.
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
                return ServiceResult::error( OperationStatus::NOT_FOUND, 'Pagamento não encontrado.' );
            }

            // Executar a exclusão
            $result = $this->repository->deleteByIdAndTenantId( $id, $tenant_id );

            if ( $result ) {
                return ServiceResult::success( null, 'Pagamento removido com sucesso.' );
            } else {
                return ServiceResult::error( OperationStatus::ERROR, 'Falha ao remover pagamento do banco de dados.' );
            }
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao excluir pagamento: ' . $e->getMessage() );
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
            if ( empty( $data[ 'payment_id' ] ) ) {
                $errors[] = 'ID do pagamento é obrigatório.';
            }

            if ( empty( $data[ 'invoice_id' ] ) ) {
                $errors[] = 'ID da fatura é obrigatório.';
            }

            if ( empty( $data[ 'status' ] ) ) {
                $errors[] = 'Status é obrigatório.';
            }

            if ( !isset( $data[ 'transaction_amount' ] ) ) {
                $errors[] = 'Valor da transação é obrigatório.';
            }
        }

        // Validar campos numéricos
        if ( isset( $data[ 'payment_id' ] ) && !is_numeric( $data[ 'payment_id' ] ) ) {
            $errors[] = 'ID do pagamento deve ser um número válido.';
        }

        if ( isset( $data[ 'invoice_id' ] ) && !is_numeric( $data[ 'invoice_id' ] ) ) {
            $errors[] = 'ID da fatura deve ser um número válido.';
        }

        if ( isset( $data[ 'transaction_amount' ] ) && !is_numeric( $data[ 'transaction_amount' ] ) ) {
            $errors[] = 'Valor da transação deve ser um número válido.';
        }

        if ( !empty( $errors ) ) {
            return ServiceResult::error( OperationStatus::INVALID_DATA, 'Dados inválidos: ' . implode( ', ', $errors ) );
        }

        return ServiceResult::success( null, 'Dados válidos.' );
    }

}
