<?php

declare(strict_types=1);

namespace app\database\servicesORM;

use app\database\entitiesORM\AddressEntity;
use app\database\repositories\AddressRepository;
use app\enums\OperationStatus;
use app\interfaces\ServiceInterface;
use app\support\ServiceResult;
use Exception;

/**
 * Service para gerenciar operações relacionadas aos endereços.
 */
class AddressService implements ServiceInterface
{
    public function __construct(
        private AddressRepository $addressRepository,
    ) {}

    public function getByIdAndTenantId( int $id, int $tenant_id ): ServiceResult
    {
        try {
            $address = $this->addressRepository->find( $id );

            if ( !$address ) {
                return ServiceResult::error( OperationStatus::NOT_FOUND, 'Endereço não encontrado' );
            }

            return ServiceResult::success( $address, 'Endereço encontrado com sucesso' );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao buscar endereço: ' . $e->getMessage() );
        }
    }

    public function listByTenantId( int $tenant_id, array $filters = [] ): ServiceResult
    {
        try {
            $addresses = $this->addressRepository->findAll();
            return ServiceResult::success( $addresses, 'Endereços listados com sucesso' );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao listar endereços: ' . $e->getMessage() );
        }
    }

    public function createByTenantId( array $data, int $tenant_id ): ServiceResult
    {
        try {
            // Validar dados
            $validation = $this->validate( $data );
            if ( !$validation->isSuccess() ) {
                return $validation;
            }

            return parent::createByTenantId( $data, $tenant_id );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao criar endereço: ' . $e->getMessage() );
        }
    }

    public function updateByIdAndTenantId( int $id, array $data, int $tenantId ): ServiceResult
    {
        try {
            // Validar dados
            $validation = $this->validate( $data, true );
            if ( !$validation->isSuccess() ) {
                return $validation;
            }

            return parent::updateByIdAndTenantId( $id, $tenant_id, $data );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao atualizar endereço: ' . $e->getMessage() );
        }
    }

    public function deleteByIdAndTenantId( int $id, int $tenant_id ): ServiceResult
    {
        try {
            $address = $this->addressRepository->find( $id );

            if ( !$address ) {
                return ServiceResult::error( OperationStatus::NOT_FOUND, 'Endereço não encontrado' );
            }

            $this->addressRepository->delete( $address->getId() );
            return ServiceResult::success( null, 'Endereço removido com sucesso' );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao remover endereço: ' . $e->getMessage() );
        }
    }

    public function validate( array $data, bool $isUpdate = false ): ServiceResult
    {
        $errors = [];

        if ( !$isUpdate && empty( $data[ 'street' ] ) ) {
            $errors[] = 'Rua é obrigatória';
        }

        if ( !$isUpdate && empty( $data[ 'city' ] ) ) {
            $errors[] = 'Cidade é obrigatória';
        }

        if ( !empty( $errors ) ) {
            return ServiceResult::error( OperationStatus::VALIDATION, implode( ', ', $errors ) );
        }

        return ServiceResult::success( null, 'Dados válidos' );
    }

    // Métodos específicos do AddressService
    public function findByIdAndTenantId( int $id, int $tenantId ): ServiceResult
    {
        return $this->getByIdAndTenantId( $id, $tenantId );
    }

    public function update( AddressEntity $address, int $tenantId ): ServiceResult
    {
        try {
            return parent::updateByIdAndTenantId( $address->getId(), $tenantId, [] );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao atualizar endereço: ' . $e->getMessage() );
        }
    }

    public function create( AddressEntity $address, int $tenantId ): ServiceResult
    {
        try {
            return parent::createByTenantId( [], $tenantId );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao criar endereço: ' . $e->getMessage() );
        }
    }

    public function delete( int $id, int $tenantId ): ServiceResult
    {
        return $this->deleteByIdAndTenantId( $id, $tenantId );
    }

    /**
     * Lista endereços com paginação.
     */
    public function listAddresses( int $limit, int $offset ): ServiceResult
    {
        try {
            $addresses = $this->addressRepository->findBy( [], null, $limit, $offset );
            return ServiceResult::success( $addresses, 'Endereços listados com sucesso' );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao listar endereços: ' . $e->getMessage() );
        }
    }

}
