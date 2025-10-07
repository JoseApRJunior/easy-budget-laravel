<?php

declare(strict_types=1);

namespace app\database\servicesORM;

use app\database\entitiesORM\ContactEntity;
use app\database\repositories\ContactRepository;
use app\enums\OperationStatus;
use app\interfaces\ServiceInterface;
use app\support\ServiceResult;
use Exception;

/**
 * Service para gerenciar operações relacionadas aos contatos.
 */
class ContactService implements ServiceInterface
{
    public function __construct(
        private ContactRepository $contactRepository,
    ) {}

    public function getByIdAndTenantId( int $id, int $tenant_id ): ServiceResult
    {
        try {
            $contact = $this->contactRepository->find( $id );

            if ( !$contact ) {
                return ServiceResult::error( OperationStatus::NOT_FOUND, 'Contato não encontrado' );
            }

            return ServiceResult::success( $contact, 'Contato encontrado com sucesso' );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao buscar contato: ' . $e->getMessage() );
        }
    }

    public function listByTenantId( int $tenant_id, array $filters = [] ): ServiceResult
    {
        try {
            $contacts = $this->contactRepository->findAll();
            return ServiceResult::success( $contacts, 'Contatos listados com sucesso' );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao listar contatos: ' . $e->getMessage() );
        }
    }

    public function createByTenantId( array $data, int $tenant_id ): ServiceResult
    {
        try {
            $contact = ContactEntity::create( $data );
            $created = $this->contactRepository->save( $contact );
            return ServiceResult::success( $created, 'Contato criado com sucesso' );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao criar contato: ' . $e->getMessage() );
        }
    }

    public function updateByIdAndTenantId( int $id, array $data, int $tenantId ): ServiceResult
    {
        try {
            $contact = $this->contactRepository->find( $id );

            if ( !$contact ) {
                return ServiceResult::error( OperationStatus::NOT_FOUND, 'Contato não encontrado' );
            }

            foreach ( $data as $key => $value ) {
                $setter = 'set' . ucfirst( $key );
                if ( method_exists( $contact, $setter ) ) {
                    $contact->$setter( $value );
                }
            }

            $updated = $this->contactRepository->save( $contact );
            return ServiceResult::success( $updated, 'Contato atualizado com sucesso' );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao atualizar contato: ' . $e->getMessage() );
        }
    }

    public function deleteByIdAndTenantId( int $id, int $tenant_id ): ServiceResult
    {
        try {
            $contact = $this->contactRepository->find( $id );

            if ( !$contact ) {
                return ServiceResult::error( OperationStatus::NOT_FOUND, 'Contato não encontrado' );
            }

            $this->contactRepository->delete( $contact->getId() );
            return ServiceResult::success( null, 'Contato removido com sucesso' );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao remover contato: ' . $e->getMessage() );
        }
    }

    public function validate( array $data, bool $isUpdate = false ): ServiceResult
    {
        $errors = [];

        if ( !$isUpdate && empty( $data[ 'email' ] ) && empty( $data[ 'phone' ] ) ) {
            $errors[] = 'Email ou telefone é obrigatório';
        }

        if ( isset( $data[ 'email' ] ) && !empty( $data[ 'email' ] ) && !filter_var( $data[ 'email' ], FILTER_VALIDATE_EMAIL ) ) {
            $errors[] = 'Email inválido';
        }

        if ( !empty( $errors ) ) {
            return ServiceResult::error( OperationStatus::VALIDATION, implode( ', ', $errors ) );
        }

        return ServiceResult::success( null, 'Dados válidos' );
    }

    // Métodos específicos do ContactService
    public function findByIdAndTenantId( int $id, int $tenantId ): ServiceResult
    {
        return $this->getByIdAndTenantId( $id, $tenantId );
    }

    public function update( ContactEntity $contact, int $tenantId ): ServiceResult
    {
        try {
            $updated = $this->contactRepository->save( $contact );
            return ServiceResult::success( $updated, 'Contato atualizado com sucesso' );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao atualizar contato: ' . $e->getMessage() );
        }
    }

    public function create( ContactEntity $contact, int $tenantId ): ServiceResult
    {
        try {
            $created = $this->contactRepository->save( $contact );
            return ServiceResult::success( $created, 'Contato criado com sucesso' );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao criar contato: ' . $e->getMessage() );
        }
    }

    public function delete( int $id, int $tenantId ): ServiceResult
    {
        return $this->deleteByIdAndTenantId( $id, $tenantId );
    }

}
