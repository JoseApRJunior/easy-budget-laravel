<?php

declare(strict_types=1);

namespace app\database\servicesORM;

use app\database\entitiesORM\CommonDataEntity;
use app\database\repositories\CommonDataRepository;
use app\enums\OperationStatus;
use app\interfaces\ServiceInterface;
use app\support\ServiceResult;
use Exception;

/**
 * Service para gerenciar operações relacionadas aos dados comuns.
 */
class CommonDataService implements ServiceInterface
{
    public function __construct(
        private CommonDataRepository $commonDataRepository,
    ) {}

    public function getByIdAndTenantId( int $id, int $tenant_id ): ServiceResult
    {
        try {
            $commonData = $this->commonDataRepository->find( $id );

            if ( !$commonData ) {
                return ServiceResult::error( OperationStatus::NOT_FOUND, 'Dados comuns não encontrados' );
            }

            return ServiceResult::success( $commonData, 'Dados comuns encontrados com sucesso' );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao buscar dados comuns: ' . $e->getMessage() );
        }
    }

    public function listByTenantId( int $tenant_id, array $filters = [] ): ServiceResult
    {
        try {
            $commonData = $this->commonDataRepository->findAll();
            return ServiceResult::success( $commonData, 'Dados comuns listados com sucesso' );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao listar dados comuns: ' . $e->getMessage() );
        }
    }

    public function createByTenantId( array $data, int $tenant_id ): ServiceResult
    {
        try {
            $commonData = CommonDataEntity::create( $data );
            $created    = $this->commonDataRepository->save( $commonData );
            return ServiceResult::success( $created, 'Dados comuns criados com sucesso' );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao criar dados comuns: ' . $e->getMessage() );
        }
    }

    public function updateByIdAndTenantId( int $id, array $data, int $tenantId ): ServiceResult
    {
        try {
            $commonData = $this->commonDataRepository->find( $id );

            if ( !$commonData ) {
                return ServiceResult::error( OperationStatus::NOT_FOUND, 'Dados comuns não encontrados' );
            }

            foreach ( $data as $key => $value ) {
                $setter = 'set' . ucfirst( $key );
                if ( method_exists( $commonData, $setter ) ) {
                    $commonData->$setter( $value );
                }
            }

            $updated = $this->commonDataRepository->save( $commonData );
            return ServiceResult::success( $updated, 'Dados comuns atualizados com sucesso' );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao atualizar dados comuns: ' . $e->getMessage() );
        }
    }

    public function deleteByIdAndTenantId( int $id, int $tenant_id ): ServiceResult
    {
        try {
            $commonData = $this->commonDataRepository->find( $id );

            if ( !$commonData ) {
                return ServiceResult::error( OperationStatus::NOT_FOUND, 'Dados comuns não encontrados' );
            }

            $this->commonDataRepository->delete( $commonData->getId() );
            return ServiceResult::success( null, 'Dados comuns removidos com sucesso' );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao remover dados comuns: ' . $e->getMessage() );
        }
    }

    public function validate( array $data, bool $isUpdate = false ): ServiceResult
    {
        $errors = [];

        if ( !$isUpdate && empty( $data[ 'name' ] ) ) {
            $errors[] = 'Nome é obrigatório';
        }

        if ( !empty( $errors ) ) {
            return ServiceResult::error( OperationStatus::VALIDATION, implode( ', ', $errors ) );
        }

        return ServiceResult::success( null, 'Dados válidos' );
    }

    // Métodos específicos do CommonDataService
    public function findByIdAndTenantId( int $id, int $tenantId ): ServiceResult
    {
        return $this->getByIdAndTenantId( $id, $tenantId );
    }

    public function update( CommonDataEntity $commonData, int $tenantId ): ServiceResult
    {
        try {
            $updated = $this->commonDataRepository->save( $commonData );
            return ServiceResult::success( $updated, 'Dados comuns atualizados com sucesso' );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao atualizar dados comuns: ' . $e->getMessage() );
        }
    }

    public function create( CommonDataEntity $commonData, int $tenantId ): ServiceResult
    {
        try {
            $created = $this->commonDataRepository->save( $commonData );
            return ServiceResult::success( $created, 'Dados comuns criados com sucesso' );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao criar dados comuns: ' . $e->getMessage() );
        }
    }

    public function delete( int $id, int $tenantId ): ServiceResult
    {
        return $this->deleteByIdAndTenantId( $id, $tenantId );
    }

}
