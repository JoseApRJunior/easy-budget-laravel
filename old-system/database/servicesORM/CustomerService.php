<?php

declare(strict_types=1);

namespace app\database\servicesORM;

use app\database\entitiesORM\AddressEntity;
use app\database\entitiesORM\CommonDataEntity;
use app\database\entitiesORM\ContactEntity;
use app\database\entitiesORM\CustomerEntity;
use app\database\repositories\AddressRepository;
use app\database\repositories\CommonDataRepository;
use app\database\repositories\ContactRepository;
use app\database\repositories\CustomerRepository;
use app\enums\OperationStatus;
use app\interfaces\ServiceInterface;
use app\support\ServiceResult;
use core\dbal\EntityNotFound;
use core\library\Session;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use RuntimeException;

/**
 * Serviço para gerenciamento de clientes.
 *
 * Implementa o padrão ServiceInterface para operações CRUD de clientes, incluindo
 * criação, atualização e remoção com entidades relacionadas (CommonData, Contact, Address).
 * Usa transações Doctrine para consistência e ServiceResult para retornos padronizados.
 */
class CustomerService implements ServiceInterface
{
    private mixed $authenticated = null;

    public function __construct(
        private CustomerRepository $customerRepository,
        private CommonDataRepository $commonDataRepository,
        private ContactRepository $contactRepository,
        private AddressRepository $addressRepository,
        private EntityManagerInterface $entityManager,
    ) {
        if ( Session::has( 'auth' ) ) {
            $this->authenticated = Session::get( 'auth' );
        }
    }

    /**
     * Busca um cliente pelo ID e tenant_id.
     *
     * @param int $id ID do cliente
     * @param int $tenant_id ID do tenant
     * @return ServiceResult Resultado da operação
     */
    public function getByIdAndTenantId( int $id, int $tenant_id ): ServiceResult
    {
        try {
            $customer = $this->customerRepository->findByIdAndTenantId( $id, $tenant_id );

            if ( !$customer ) {
                return ServiceResult::error( OperationStatus::NOT_FOUND, 'Cliente não encontrado.' );
            }

            return ServiceResult::success( $customer, 'Cliente encontrado com sucesso.' );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao buscar cliente: ' . $e->getMessage() );
        }
    }

    /**
     * Lista clientes por tenant_id com filtros opcionais.
     *
     * @param int $tenant_id ID do tenant
     * @param array<string, mixed> $filters Filtros opcionais
     * @return ServiceResult Resultado da operação
     */
    public function listByTenantId( int $tenant_id, array $filters = [] ): ServiceResult
    {
        try {
            $criteria = [];
            $orderBy  = [ 'createdAt' => 'DESC' ];
            $limit    = $filters[ 'limit' ] ?? null;
            $offset   = $filters[ 'offset' ] ?? null;

            $customers = $this->customerRepository->findAllByTenantId( $tenant_id, $criteria, $orderBy, $limit, $offset );

            return ServiceResult::success( $customers, 'Clientes listados com sucesso.' );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao listar clientes: ' . $e->getMessage() );
        }
    }

    /**
     * Cria um novo cliente com entidades relacionadas.
     *
     * @param array<string, mixed> $data Dados para criação
     * @param int $tenant_id ID do tenant
     * @return ServiceResult Resultado da operação
     */
    public function createByTenantId( array $data, int $tenant_id ): ServiceResult
    {
        $validation = $this->validate( $data );
        if ( !$validation->isSuccess() ) {
            return $validation;
        }

        try {
            return $this->entityManager->transactional( function () use ($data, $tenant_id) {
                // CommonData
                $commonDataEntity = new CommonDataEntity();
                $commonDataEntity->setTenantId( $tenant_id );
                $commonDataEntity->setFirstName( $data[ 'first_name' ] );
                $commonDataEntity->setLastName( $data[ 'last_name' ] );
                $commonDataEntity->setCpf( $data[ 'cpf' ] ?? null );
                $commonDataEntity->setCnpj( $data[ 'cnpj' ] ?? null );
                $commonDataEntity->setBirthDate( \convertToDateTime( $data[ 'birth_date' ] ) ?? null );
                $commonDataEntity->setCreatedAt( new \DateTimeImmutable() );
                $commonDataEntity->setUpdatedAt( new \DateTimeImmutable() );

                $commonDataResult = $this->commonDataRepository->save( $commonDataEntity );
                if ( $commonDataResult->isError() ) {
                    return $commonDataResult;
                }
                $createdCommonDataId = $commonDataEntity->getId();

                // Contact
                $contactEntity = new ContactEntity();
                $contactEntity->setTenantId( $tenant_id );
                $contactEntity->setEmail( $data[ 'email' ] );
                $contactEntity->setPhone( $data[ 'phone' ] ?? null );
                $contactEntity->setCellphone( $data[ 'cellphone' ] ?? null );
                $contactEntity->setCreatedAt( new \DateTimeImmutable() );
                $contactEntity->setUpdatedAt( new \DateTimeImmutable() );

                $contactResult = $this->contactRepository->save( $contactEntity );
                if ( $contactResult->isError() ) {
                    return $contactResult;
                }
                $createdContactId = $contactEntity->getId();

                // Address
                $addressEntity = new AddressEntity();
                $addressEntity->setTenantId( $tenant_id );
                $addressEntity->setStreet( $data[ 'street' ] ?? null );
                $addressEntity->setNumber( $data[ 'number' ] ?? null );
                $addressEntity->setNeighborhood( $data[ 'neighborhood' ] ?? null );
                $addressEntity->setCity( $data[ 'city' ] ?? null );
                $addressEntity->setState( $data[ 'state' ] ?? null );
                $addressEntity->setZipCode( $data[ 'zip_code' ] ?? null );
                $addressEntity->setComplement( $data[ 'complement' ] ?? null );
                $addressEntity->setCreatedAt( new \DateTimeImmutable() );
                $addressEntity->setUpdatedAt( new \DateTimeImmutable() );

                $addressResult = $this->addressRepository->save( $addressEntity );
                if ( $addressResult->isError() ) {
                    return $addressResult;
                }
                $createdAddressId = $addressEntity->getId();

                // Customer
                $customerEntity = new CustomerEntity();
                $customerEntity->setTenantId( $tenant_id );
                $customerEntity->setCommonDataId( $createdCommonDataId );
                $customerEntity->setContactId( $createdContactId );
                $customerEntity->setAddressId( $createdAddressId );
                $customerEntity->setStatus( $data[ 'status' ] ?? 'active' );
                $customerEntity->setCreatedAt( new \DateTimeImmutable() );
                $customerEntity->setUpdatedAt( new \DateTimeImmutable() );

                $customerResult = $this->customerRepository->save( $customerEntity, $tenant_id );
                if ( $customerResult->isError() ) {
                    return $customerResult;
                }

                $data = [ 
                    'customer'    => $customerEntity,
                    'common_data' => $commonDataEntity,
                    'contact'     => $contactEntity,
                    'address'     => $addressEntity,
                ];

                return ServiceResult::success( $data, 'Cliente criado com sucesso!' );
            } );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Falha ao criar cliente: ' . $e->getMessage() );
        }
    }

    /**
     * Atualiza um cliente existente com entidades relacionadas.
     *
     * @param int $id ID do cliente
     * @param array<string, mixed> $data Dados para atualização
     * @param int $tenant_id ID do tenant
     * @return ServiceResult Resultado da operação
     */
    public function updateByIdAndTenantId( int $id, array $data, int $tenantId ): ServiceResult
    {
        $validation = $this->validate( $data, true );
        if ( !$validation->isSuccess() ) {
            return $validation;
        }

        try {
            return $this->entityManager->transactional( function () use ($id, $data, $tenant_id) {
                $customer = $this->customerRepository->findByIdAndTenantId( $id, $tenant_id );

                if ( $customer instanceof EntityNotFound ) {
                    return ServiceResult::error( OperationStatus::NOT_FOUND, 'Cliente não encontrado.' );
                }

                // Verificar email único
                $existingContact = $this->contactRepository->findOneBy( [ 'email' => $data[ 'email' ], 'tenantId' => $tenant_id ] );
                if ( $existingContact && $existingContact->getId() !== $customer->getContactId() ) {
                    return ServiceResult::error( OperationStatus::CONFLICT, 'Este e-mail já está registrado.' );
                }

                // Atualizar CommonData
                $commonData = $this->commonDataRepository->findById( $customer->getCommonDataId() );
                if ( $commonData instanceof EntityNotFound ) {
                    return ServiceResult::error( OperationStatus::NOT_FOUND, 'Dados comuns não encontrados.' );
                }
                $commonData->setFirstName( $data[ 'first_name' ] ?? $commonData->getFirstName() );
                $commonData->setLastName( $data[ 'last_name' ] ?? $commonData->getLastName() );
                $commonData->setCpf( $data[ 'cpf' ] ?? $commonData->getCpf() );
                $commonData->setCnpj( $data[ 'cnpj' ] ?? $commonData->getCnpj() );
                $commonData->setBirthDate( \convertToDateTime( $data[ 'birth_date' ] ) ?? $commonData->getBirthDate() );
                $commonData->setUpdatedAt( new \DateTimeImmutable() );
                $this->commonDataRepository->save( $commonData );

                // Atualizar Contact
                $contact = $this->contactRepository->findById( $customer->getContactId() );
                if ( $contact instanceof EntityNotFound ) {
                    return ServiceResult::error( OperationStatus::NOT_FOUND, 'Contato não encontrado.' );
                }
                $contact->setEmail( $data[ 'email' ] ?? $contact->getEmail() );
                $contact->setPhone( $data[ 'phone' ] ?? $contact->getPhone() );
                $contact->setCellphone( $data[ 'cellphone' ] ?? $contact->getCellphone() );
                $contact->setUpdatedAt( new \DateTimeImmutable() );
                $this->contactRepository->save( $contact );

                // Atualizar Address
                $address = $this->addressRepository->findById( $customer->getAddressId() );
                if ( $address instanceof EntityNotFound ) {
                    return ServiceResult::error( OperationStatus::NOT_FOUND, 'Endereço não encontrado.' );
                }
                $address->setStreet( $data[ 'street' ] ?? $address->getStreet() );
                $address->setNumber( $data[ 'number' ] ?? $address->getNumber() );
                $address->setNeighborhood( $data[ 'neighborhood' ] ?? $address->getNeighborhood() );
                $address->setCity( $data[ 'city' ] ?? $address->getCity() );
                $address->setState( $data[ 'state' ] ?? $address->getState() );
                $address->setZipCode( $data[ 'zip_code' ] ?? $address->getZipCode() );
                $address->setComplement( $data[ 'complement' ] ?? $address->getComplement() );
                $address->setUpdatedAt( new \DateTimeImmutable() );
                $this->addressRepository->save( $address );

                // Atualizar Customer
                $customer->setStatus( $data[ 'status' ] ?? $customer->getStatus() );
                $customer->setUpdatedAt( new \DateTimeImmutable() );
                $result = $this->customerRepository->save( $customer, $tenant_id );

                if ( $result->isError() ) {
                    return $result;
                }

                return ServiceResult::success( $customer, 'Cliente atualizado com sucesso.' );
            } );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Falha ao atualizar cliente: ' . $e->getMessage() );
        }
    }

    /**
     * Remove um cliente e entidades relacionadas.
     *
     * @param int $id ID do cliente
     * @param int $tenant_id ID do tenant
     * @return ServiceResult Resultado da operação
     */
    public function deleteByIdAndTenantId( int $id, int $tenant_id ): ServiceResult
    {
        try {
            return $this->entityManager->transactional( function () use ($id, $tenant_id) {
                $customer = $this->customerRepository->findByIdAndTenantId( $id, $tenant_id );

                if ( $customer instanceof EntityNotFound ) {
                    return ServiceResult::error( OperationStatus::NOT_FOUND, 'Cliente não encontrado.' );
                }

                // Verificar relacionamentos antes de deletar
                $relationships = $this->checkRelationships( $id, $tenant_id );
                if ( $relationships[ 'hasRelationships' ] ) {
                    return ServiceResult::error( OperationStatus::CONFLICT, 'Cliente possui relacionamentos ativos. Não é possível deletar.' );
                }

                $this->customerRepository->deleteByIdAndTenantId( $id, $tenant_id );
                $this->commonDataRepository->delete( $customer->getCommonDataId() );
                $this->contactRepository->delete( $customer->getContactId() );
                $this->addressRepository->delete( $customer->getAddressId() );

                return ServiceResult::success( null, 'Cliente removido com sucesso.' );
            } );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Falha ao remover cliente: ' . $e->getMessage() );
        }
    }

    /**
     * Valida dados para criação ou atualização de cliente.
     *
     * @param array<string, mixed> $data Dados a validar
     * @param bool $isUpdate Se é atualização
     * @return ServiceResult Resultado da validação
     */
    public function validate( array $data, bool $isUpdate = false ): ServiceResult
    {
        $errors = [];

        // Campos obrigatórios
        if ( empty( $data[ 'first_name' ] ) ) $errors[] = 'Nome é obrigatório.';
        if ( empty( $data[ 'last_name' ] ) ) $errors[] = 'Sobrenome é obrigatório.';
        if ( empty( $data[ 'email' ] ) ) $errors[] = 'E-mail é obrigatório.';
        if ( empty( $data[ 'phone' ] ) ) $errors[] = 'Telefone é obrigatório.';
        if ( empty( $data[ 'street' ] ) ) $errors[] = 'Rua é obrigatória.';
        if ( empty( $data[ 'city' ] ) ) $errors[] = 'Cidade é obrigatória.';
        if ( empty( $data[ 'state' ] ) ) $errors[] = 'Estado é obrigatório.';

        // Email validation
        if ( empty( $data[ 'email' ] ) || !filter_var( $data[ 'email' ], FILTER_VALIDATE_EMAIL ) ) {
            $errors[] = 'E-mail inválido.';
        }

        if ( !empty( $errors ) ) {
            return ServiceResult::error( OperationStatus::INVALID_DATA, implode( ', ', $errors ) );
        }

        return ServiceResult::success( null, 'Dados válidos.' );
    }

    /**
     * Verifica relacionamentos do cliente antes de deletar.
     *
     * @param int $id ID do cliente
     * @param int $tenant_id ID do tenant
     * @return array Relacionamentos encontrados
     */
    private function checkRelationships( int $id, int $tenant_id ): array
    {
        // Usar Doctrine para verificar relacionamentos (exemplo com budgets)
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select( 'COUNT(b.id) as count' )
            ->from( 'app\\database\\entitiesORM\\BudgetEntity', 'b' )
            ->where( 'b.customerId = :id' )
            ->andWhere( 'b.tenantId = :tenant_id' )
            ->setParameters( [ 
                'id'        => $id,
                'tenant_id' => $tenant_id
            ] );

        $count = $qb->getQuery()->getSingleScalarResult();

        return [ 
            'hasRelationships' => $count > 0,
            'count'            => $count,
            'table'            => 'budgets' // Exemplo; expandir para outras entidades
        ];
    }

    /**
     * Busca cliente completo por ID, incluindo dados relacionados (contacts, address).
     *
     * @param int $id ID do cliente
     * @param int $tenantId ID do tenant
     * @return CustomerEntity|null
     */
    public function getCustomerFullById( int $id, int $tenantId ): ?CustomerEntity
    {
        return $this->customerRepository->findFullById( $id, $tenantId );
    }

}
