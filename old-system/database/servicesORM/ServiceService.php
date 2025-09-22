<?php

namespace app\database\servicesORM;

use app\interfaces\ServiceInterface;
use app\support\ServiceResult;
use app\enums\OperationStatus;
use core\dbal\EntityNotFound;
use core\library\Session;
use Exception;
use app\database\entitiesORM\ServiceEntity;
use app\database\repositories\ServiceRepository;

/**
 * Serviço para gerenciamento de serviços
 * Implementa ServiceInterface para operações com tenant_id
 */
class ServiceService implements ServiceInterface
{
    private mixed $authenticated;

    public function __construct(
        private ServiceRepository $serviceRepository,
    ) {
        if ( Session::has( 'auth' ) ) {
            $this->authenticated = Session::get( 'auth' );
        }
    }

    /**
     * Busca um serviço por ID e tenant.
     *
     * @param int $id ID da entidade
     * @param int $tenant_id ID do tenant
     * @return ServiceResult Resultado da operação
     */
    public function getByIdAndTenantId( int $id, int $tenant_id ): ServiceResult
    {
        try {
            // Validar tenant_id
            if ( $tenant_id <= 0 ) {
                return ServiceResult::error( OperationStatus::INVALID_DATA, 'ID do tenant inválido.' );
            }

            $entity = $this->serviceRepository->findByIdAndTenantId( $id, $tenant_id );

            if ( $entity === null ) {
                return ServiceResult::error( OperationStatus::NOT_FOUND, 'Serviço não encontrado.' );
            }

            return ServiceResult::success( $entity, 'Serviço encontrado com sucesso.' );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Falha ao buscar serviço: ' . $e->getMessage() );
        }
    }

    /**
     * Lista serviços por tenant_id
     *
     * @param int $tenant_id ID do tenant
     * @param array<string, mixed> $filters Filtros opcionais
     * @return ServiceResult Resultado da operação
     */
    public function listByTenantId( int $tenant_id, array $filters = [] ): ServiceResult
    {
        try {
            // Validar tenant_id
            if ( $tenant_id <= 0 ) {
                return ServiceResult::error( OperationStatus::INVALID_DATA, 'ID do tenant inválido.' );
            }

            $criteria = [];
            $orderBy  = [ 'createdAt' => 'DESC' ];

            // Aplicar filtros conforme necessário
            if ( !empty( $filters[ 'description' ] ) ) {
                $criteria[ 'description' ] = $filters[ 'description' ];
            }

            if ( !empty( $filters[ 'budget_id' ] ) ) {
                $criteria[ 'budgetId' ] = (int) $filters[ 'budget_id' ];
            }

            if ( !empty( $filters[ 'service_status_id' ] ) ) {
                $criteria[ 'serviceStatusId' ] = (int) $filters[ 'service_status_id' ];
            }

            $entities = $this->serviceRepository->findAllByTenantId( $tenant_id, $criteria, $orderBy );

            return ServiceResult::success( $entities, 'Serviços listados com sucesso.' );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Falha ao listar serviços: ' . $e->getMessage() );
        }
    }

    /**
     * Cria um novo serviço
     *
     * @param array<string, mixed> $data Dados para criação da entidade
     * @param int $tenant_id ID do tenant
     * @return ServiceResult Resultado da operação
     */
    public function createByTenantId( array $data, int $tenant_id ): ServiceResult
    {
        try {
            // Validar tenant_id
            if ( $tenant_id <= 0 ) {
                return ServiceResult::error( OperationStatus::INVALID_DATA, 'ID do tenant inválido.' );
            }

            // Validar dados de entrada
            $validation = $this->validateForTenant( $data, $tenant_id );
            if ( !$validation->isSuccess() ) {
                return $validation;
            }

            // Criar nova entidade
            $entity = new ServiceEntity();
            $entity->setTenantId( $tenant_id );

            if ( isset( $data[ 'code' ] ) ) {
                $entity->setCode( $data[ 'code' ] );
            }

            if ( isset( $data[ 'description' ] ) ) {
                $entity->setDescription( $data[ 'description' ] );
            }

            if ( isset( $data[ 'amount' ] ) ) {
                $entity->setAmount( (float) $data[ 'amount' ] );
            }

            if ( isset( $data[ 'budget_id' ] ) ) {
                $entity->setBudgetId( (int) $data[ 'budget_id' ] );
            }

            if ( isset( $data[ 'service_status_id' ] ) ) {
                $entity->setServiceStatusId( (int) $data[ 'service_status_id' ] );
            }

            if ( isset( $data[ 'observation' ] ) ) {
                $entity->setObservation( $data[ 'observation' ] );
            }

            if ( isset( $data[ 'due_date' ] ) ) {
                $entity->setDueDate( new \DateTime( $data[ 'due_date' ] ) );
            }

            // Salvar via repository
            $result = $this->serviceRepository->save( $entity, $tenant_id );

            // Verificar se o resultado é null
            if ( $result === null ) {
                return ServiceResult::error( OperationStatus::ERROR, 'Falha ao criar serviço.' );
            }

            return ServiceResult::success( $result, 'Serviço criado com sucesso.' );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Falha ao criar serviço: ' . $e->getMessage() );
        }
    }

    /**
     * Atualiza um serviço existente
     *
     * @param int $id ID da entidade
     * @param int $tenant_id ID do tenant
     * @param array<string, mixed> $data Dados para atualização
     * @return ServiceResult Resultado da operação
     */
    public function updateByIdAndTenantId( int $id, array $data, int $tenantId ): ServiceResult
    {
        try {
            // Validar tenant_id
            if ( $tenant_id <= 0 ) {
                return ServiceResult::error( OperationStatus::INVALID_DATA, 'ID do tenant inválido.' );
            }

            // Validar dados de entrada
            $validation = $this->validateForTenant( $data, $tenant_id, true );
            if ( !$validation->isSuccess() ) {
                return $validation;
            }

            // Buscar entidade existente
            $entity = $this->serviceRepository->findByIdAndTenantId( $id, $tenant_id );
            if ( $entity instanceof EntityNotFound || $entity === null ) {
                return ServiceResult::error( OperationStatus::NOT_FOUND, 'Serviço não encontrado.' );
            }

            // Atualizar dados
            if ( isset( $data[ 'code' ] ) ) {
                $entity->setCode( $data[ 'code' ] );
            }

            if ( isset( $data[ 'description' ] ) ) {
                $entity->setDescription( $data[ 'description' ] );
            }

            if ( isset( $data[ 'amount' ] ) ) {
                $entity->setAmount( (float) $data[ 'amount' ] );
            }

            if ( isset( $data[ 'budget_id' ] ) ) {
                $entity->setBudgetId( (int) $data[ 'budget_id' ] );
            }

            if ( isset( $data[ 'service_status_id' ] ) ) {
                $entity->setServiceStatusId( (int) $data[ 'service_status_id' ] );
            }

            if ( isset( $data[ 'observation' ] ) ) {
                $entity->setObservation( $data[ 'observation' ] );
            }

            if ( isset( $data[ 'due_date' ] ) ) {
                $entity->setDueDate( new \DateTime( $data[ 'due_date' ] ) );
            }

            // Salvar via repository
            $result = $this->serviceRepository->save( $entity, $tenant_id );

            // Verificar se o resultado é null
            if ( $result === null ) {
                return ServiceResult::error( OperationStatus::ERROR, 'Falha ao atualizar serviço.' );
            }

            return ServiceResult::success( $result, 'Serviço atualizado com sucesso.' );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Falha ao atualizar serviço: ' . $e->getMessage() );
        }
    }

    /**
     * Remove um serviço
     *
     * @param int $id ID da entidade
     * @param int $tenant_id ID do tenant
     * @return ServiceResult Resultado da operação
     */
    public function deleteByIdAndTenantId( int $id, int $tenant_id ): ServiceResult
    {
        try {
            // Validar tenant_id
            if ( $tenant_id <= 0 ) {
                return ServiceResult::error( OperationStatus::INVALID_DATA, 'ID do tenant inválido.' );
            }

            // Verificar se a entidade existe e pertence ao tenant
            $entity = $this->serviceRepository->findByIdAndTenantId( $id, $tenant_id );
            if ( $entity === null ) {
                return ServiceResult::error( OperationStatus::NOT_FOUND, 'Serviço não encontrado.' );
            }

            // Executar exclusão via repository
            $result = $this->serviceRepository->deleteByIdAndTenantId( $id, $tenant_id );

            if ( $result ) {
                return ServiceResult::success( null, 'Serviço removido com sucesso.' );
            } else {
                return ServiceResult::error( OperationStatus::ERROR, 'Falha ao remover serviço do banco de dados.' );
            }
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Falha ao excluir serviço: ' . $e->getMessage() );
        }
    }

    /**
     * Valida os dados de entrada para operações de criação ou atualização no tenant.
     *
     * @param array<string, mixed> $data Dados a serem validados
     * @param int $tenant_id ID do tenant
     * @param bool $isUpdate Indica se é uma operação de atualização
     * @return ServiceResult Resultado da validação
     */
    public function validateForTenant( array $data, int $tenant_id, bool $isUpdate = false ): ServiceResult
    {
        $errors = [];

        // Validar tenant_id
        if ( $tenant_id <= 0 ) {
            $errors[] = "ID do tenant deve ser um número positivo.";
        }

        // Validar campos obrigatórios para criação
        if ( !$isUpdate ) {
            if ( empty( $data[ 'description' ] ) ) {
                $errors[] = "Descrição do serviço é obrigatória.";
            }

            if ( empty( $data[ 'due_date' ] ) ) {
                $errors[] = "Data de vencimento é obrigatória.";
            }

            if ( empty( $data[ 'budget_id' ] ) ) {
                $errors[] = "ID do orçamento é obrigatório.";
            }
        }

        // Validar campos numéricos
        if ( isset( $data[ 'budget_id' ] ) && !is_numeric( $data[ 'budget_id' ] ) ) {
            $errors[] = "ID do orçamento deve ser numérico.";
        }

        if ( isset( $data[ 'service_status_id' ] ) && !is_numeric( $data[ 'service_status_id' ] ) ) {
            $errors[] = "ID do status do serviço deve ser numérico.";
        }

        // Validar data de vencimento
        if ( !empty( $data[ 'due_date' ] ) ) {
            $dueDate = \DateTime::createFromFormat( 'Y-m-d', $data[ 'due_date' ] );
            if ( !$dueDate ) {
                $errors[] = "Data de vencimento inválida. Use o formato YYYY-MM-DD.";
            }
        }

        if ( count( $errors ) > 0 ) {
            return ServiceResult::error( OperationStatus::INVALID_DATA, "Dados inválidos para tenant {$tenant_id}: " . implode( ', ', $errors ) );
        }

        return ServiceResult::success( null, "Dados válidos para tenant {$tenant_id}." );
    }

    /**
     * Método validate da interface base (não implementado para WithTenant).
     *
     * Use validateForTenant() em vez deste método.
     *
     * @param array<string, mixed> $data Dados a serem validados
     * @param bool $isUpdate Indica se é uma operação de atualização
     * @return ServiceResult Resultado da validação
     */
    public function validate( array $data, bool $isUpdate = false ): ServiceResult
    {
        // Redirecionar para validateForTenant com tenant_id padrão
        $tenant_id = $data[ 'tenant_id' ] ?? 0;
        return $this->validateForTenant( $data, $tenant_id, $isUpdate );
    }

}
