<?php

namespace app\database\servicesORM;

use app\database\entitiesORM\ActivityEntity;
use app\database\repositories\ActivityRepository;
use app\interfaces\ServiceInterface;
use app\support\ServiceResult;
use app\enums\OperationStatus;
use Exception;

/**
 * Serviço para gerenciamento de atividades do sistema.
 * Responsável por registrar e consultar logs de atividades dos usuários.
 */
class ActivityService implements ServiceInterface
{
    private ActivityRepository $activityRepository;

    public function __construct( ActivityRepository $activityRepository )
    {
        $this->activityRepository = $activityRepository;
    }

    /**
     * Busca uma atividade pelo seu ID e ID do tenant.
     *
     * @param int $id ID da entidade
     * @param int $tenant_id ID do tenant
     * @return ServiceResult Resultado da operação com status, mensagem e dados
     */
    public function getByIdAndTenantId( int $id, int $tenant_id ): ServiceResult
    {
        try {
            $entity = $this->activityRepository->findByIdAndTenantId( $id, $tenant_id );

            if ( !$entity ) {
                return ServiceResult::error( OperationStatus::NOT_FOUND, 'Atividade não encontrada.' );
            }

            return ServiceResult::success( $entity, 'Atividade encontrada com sucesso.' );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao buscar atividade: ' . $e->getMessage() );
        }
    }

    /**
     * Lista todas as atividades de um tenant, com filtros opcionais.
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

            $activities = $this->activityRepository->findAllByTenantId( $tenant_id, $criteria, $orderBy, $limit, $offset );

            return ServiceResult::success( $activities, 'Lista de atividades obtida com sucesso.' );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao listar atividades: ' . $e->getMessage() );
        }
    }

    /**
     * Cria uma nova atividade (log).
     *
     * @param array<string, mixed> $data Dados para criação da entidade
     * @param int $tenant_id ID do tenant
     * @return ServiceResult Resultado da operação com status, mensagem e dados
     */
    public function createByTenantId( array $data, int $tenant_id ): ServiceResult
    {
        $validation = $this->validate( $data );
        if ( !$validation->isSuccess() ) {
            return $validation;
        }

        try {
            $activity = new ActivityEntity();
            $activity->setTenantId( $tenant_id );
            $activity->setUserId( $data[ 'user_id' ] ?? 0 );
            $activity->setActionType( $data[ 'action_type' ] );
            $activity->setEntityType( $data[ 'entity_type' ] );
            $activity->setEntityId( $data[ 'entity_id' ] );
            $activity->setDescription( $data[ 'description' ] );
            $activity->setMetadata( $this->sanitizeMetadata( $data[ 'metadata' ] ?? [] ) );

            $result = $this->activityRepository->save( $activity, $tenant_id );

            // Verificar se o resultado é null
            if ( $result === null ) {
                return ServiceResult::error( OperationStatus::ERROR, 'Erro ao criar atividade.' );
            }

            return ServiceResult::success( $result, 'Atividade criada com sucesso.' );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao criar atividade: ' . $e->getMessage() );
        }
    }

    /**
     * Atualização de atividades é bloqueada por regra de negócio.
     *
     * @param int $id ID da entidade
     * @param int $tenant_id ID do tenant
     * @param array<string, mixed> $data Dados para atualização
     * @return ServiceResult Resultado da operação com status, mensagem e dados
     */
    public function updateByIdAndTenantId( int $id, int $tenant_id, array $data ): ServiceResult
    {
        return ServiceResult::error(
            OperationStatus::FORBIDDEN,
            'Atividades não podem ser atualizadas por questões de auditoria.',
        );
    }

    /**
     * Exclusão de atividades é bloqueada por regra de negócio.
     *
     * @param int $id ID da entidade
     * @param int $tenant_id ID do tenant
     * @return ServiceResult Resultado da operação com status, mensagem e dados
     */
    public function deleteByIdAndTenantId( int $id, int $tenant_id ): ServiceResult
    {
        return ServiceResult::error(
            OperationStatus::FORBIDDEN,
            'Atividades não podem ser removidas por questões de auditoria.',
        );
    }

    /**
     * Valida os dados para criação de uma atividade.
     *
     * @param array<string, mixed> $data Dados a serem validados
     * @param bool $isUpdate Indica se é uma operação de atualização
     * @return ServiceResult Resultado da validação
     */
    public function validate( array $data, bool $isUpdate = false ): ServiceResult
    {
        $errors = [];
        if ( empty( $data[ 'action_type' ] ) ) $errors[] = 'Tipo da ação é obrigatório.';
        if ( empty( $data[ 'entity_type' ] ) ) $errors[] = 'Tipo da entidade é obrigatório.';
        if ( empty( $data[ 'entity_id' ] ) || !is_numeric( $data[ 'entity_id' ] ) ) $errors[] = 'ID da entidade é obrigatório e deve ser numérico.';
        if ( empty( $data[ 'description' ] ) ) $errors[] = 'Descrição é obrigatória.';

        if ( !empty( $errors ) ) {
            return ServiceResult::error( OperationStatus::INVALID_DATA, implode( ', ', $errors ) );
        }
        return ServiceResult::success( null, 'Dados válidos.' );
    }

    /**
     * Busca atividades recentes de um tenant.
     *
     * @param int $tenant_id ID do tenant
     * @param int $limit Limite de registros (padrão: 5)
     * @return ServiceResult Resultado da operação com status, mensagem e dados
     */
    public function getRecentActivities( int $tenant_id, int $limit = 5 ): ServiceResult
    {
        try {
            $criteria = [];
            $orderBy = [ 'created_at' => 'DESC' ];
            
            $activities = $this->activityRepository->findAllByTenantId( $tenant_id, $criteria, $orderBy, $limit );
            
            return ServiceResult::success( $activities, 'Atividades recentes encontradas com sucesso.' );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao buscar atividades recentes: ' . $e->getMessage() );
        }
    }

    /**
     * Registra uma atividade no sistema.
     *
     * @param int $tenant_id ID do tenant
     * @param int $user_id ID do usuário
     * @param string $action_type Tipo da ação
     * @param string $entity_type Tipo da entidade
     * @param int $entity_id ID da entidade
     * @param string $description Descrição da atividade
     * @param array<string, mixed> $metadata Metadados da atividade
     * @return ServiceResult Resultado da operação
     */
    public function logActivity( int $tenant_id, int $user_id, string $action_type, string $entity_type, int $entity_id, string $description, array $metadata ): ServiceResult
    {
        try {
            $activity = new ActivityEntity();
            $activity->setTenantId( $tenant_id );
            $activity->setUserId( $user_id );
            $activity->setActionType( $action_type );
            $activity->setEntityType( $entity_type );
            $activity->setEntityId( $entity_id );
            $activity->setDescription( $description );
            $activity->setMetadata( $this->sanitizeMetadata( $metadata ) );

            $result = $this->activityRepository->save( $activity, $tenant_id );

            if ( $result === null ) {
                return ServiceResult::error( OperationStatus::ERROR, 'Erro ao registrar atividade.' );
            }

            return ServiceResult::success( $result, 'Atividade registrada com sucesso.' );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao registrar atividade: ' . $e->getMessage() );
        }
    }

    /**
     * Sanitiza metadados recursivamente para evitar problemas de serialização.
     */
    private function sanitizeMetadata( mixed $data ): mixed
    {
        if ( is_array( $data ) ) {
            $sanitized = [];
            foreach ( $data as $key => $value ) {
                $sanitized[ $key ] = $this->sanitizeMetadata( $value );
            }
            return $sanitized;
        }
        if ( is_object( $data ) ) {
            return $this->sanitizeMetadata( (array) $data );
        }
        if ( is_string( $data ) && mb_strlen( $data ) > 1000 ) {
            return mb_substr( $data, 0, 1000 ) . '...';
        }
        return $data;
    }



}
