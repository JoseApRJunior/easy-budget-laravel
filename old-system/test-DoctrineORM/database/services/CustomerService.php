<?php

namespace app\database\services;

use app\database\entitiesORM\AddressEntity;
use app\database\entitiesORM\CommonDataEntity;
use app\database\entitiesORM\ContactEntity;
use app\database\entitiesORM\CustomerEntity;
use app\database\models\Address;
use app\database\models\CommonData;
use app\database\models\Contact;
use app\database\models\Customer;
use core\dbal\EntityNotFound;
use core\library\Session;
use Doctrine\DBAL\Connection;
use Exception;
use RuntimeException;

class CustomerService
{
    /**
     * Summary of table
     * @var string
     */

    protected string $tableCustomers = 'customers';
    protected string $tableContacts = 'contacts';
    protected string $tableAddresses = 'addresses';
    protected string $tableCommonData = 'common_datas';
    private mixed $authenticated;

    public function __construct(
        private readonly Connection $connection,
        private Customer $customer,
        private CommonData $commonData,
        private Contact $contact,
        private Address $address,
    ) {
        if (Session::has('auth')) {
            $this->authenticated = Session::get('auth');
        }

    }

    /**
     * Registra um novo usuário com os dados fornecidos.
     *
     * @param array<string, mixed> $data Um array associativo contendo os detalhes do registro do usuário.
     * @return array<string, mixed> Retorna true ou o ID do usuário em caso de sucesso, ou false em caso de falha.
     */
    public function create(array $data): array
    {
        try {
            return $this->connection->transactional(function () use ($data) {
                $result = false;
                $createdCommonDataId = 0;
                $createdContactId = 0;
                $createdAddressId = 0;
                $createdCustomerId = 0;

                // Sessão criar common data
                $properties = getConstructorProperties(CommonDataEntity::class);
                $properties[ 'tenant_id' ] = $this->authenticated->tenant_id;

                // popula model CommonDataEntity
                $entity = CommonDataEntity::create(removeUnnecessaryIndexes(
                    $properties,
                    [ 'id', 'created_at', 'updated_at' ],
                    $data,
                ));

                $result = $this->commonData->create($entity);
                if ($result[ 'status' ] === 'success') {
                    $createdCommonDataId = $result[ 'data' ][ 'id' ];
                    // Fim da sessão criar common data

                    // Sessão criar contato
                    $properties = getConstructorProperties(ContactEntity::class);
                    $properties[ 'tenant_id' ] = $this->authenticated->tenant_id;

                    // popula model CommonDataEntity
                    $entity = ContactEntity::create(removeUnnecessaryIndexes(
                        $properties,
                        [ 'id', 'created_at', 'updated_at' ],
                        $data,
                    ));

                    $result = $this->contact->create($entity);
                    if ($result[ 'status' ] === 'success') {
                        $createdContactId = $result[ 'data' ][ 'id' ];
                        // Fim da sessão criar contact

                        // Sessão criar address
                        $properties = getConstructorProperties(AddressEntity::class);
                        $properties[ 'tenant_id' ] = $this->authenticated->tenant_id;

                        $entity = AddressEntity::create(removeUnnecessaryIndexes(
                            $properties,
                            [ 'id', 'created_at', 'updated_at' ],
                            $data,
                        ));

                        $result = $this->address->create($entity);
                        // verifica se o CommonData foi criado com sucesso, se não, retorna false
                        if ($result[ 'status' ] === 'success') {
                            $createdAddressId = $result[ 'data' ][ 'id' ];
                            // Fim da sessão criar address

                            // Sessão criar customer
                            $properties = getConstructorProperties(CustomerEntity::class);
                            $properties[ 'tenant_id' ] = $this->authenticated->tenant_id;
                            $properties[ 'common_data_id' ] = $createdCommonDataId;
                            $properties[ 'contact_id' ] = $createdContactId;
                            $properties[ 'address_id' ] = $createdAddressId;
                            $properties[ 'status' ] = 'active';
                            $customerEntity = CustomerEntity::create(removeUnnecessaryIndexes(
                                $properties,
                                [ 'id', 'created_at', 'updated_at' ],
                                $data,
                            ));
                            $result = $this->customer->create($customerEntity);
                            if ($result[ 'status' ] === 'success') {
                                $createdCustomerId = $result[ 'data' ][ 'id' ];
                                // Fim da sessão criar customer
                            }
                        }
                    }
                }

                return [
                    'status' => $result[ 'status' ] === 'success' ? 'success' : 'error',
                    'message' => $result[ 'status' ] === 'success' ? 'Cliente criado com sucesso!' : 'Falha ao criar cliente!',
                    'data' => [
                        'id' => $createdCustomerId,
                        'common_data_id' => $createdCommonDataId,
                        'contact_id' => $createdContactId,
                        'address_id' => $createdAddressId,
                        'data' => $data,
                    ],
                ];
            });

        } catch (Exception $e) {
            throw new RuntimeException("Falha ao registrar o novo cliente, tente novamente mais tarde ou entre em contato com suporte!", 0, $e);
        }
    }

    /**
     * Atualiza um cliente existente.
     *
     * @param array<string, mixed> $data Dados do cliente
     * @return array<string, mixed> Resultado da operação
     */
    public function update(array $data): array
    {
        try {
            return $this->connection->transactional(function () use ($data) {
                $result[ 'status' ] = 'error';
                $customerId = $data[ 'id' ];
                // Sessão atualizar customer
                $customerData = $this->customer->getCustomerById($customerId, $this->authenticated->tenant_id);

                // Verificar se email já existe
                $checkObj = $this->contact->getContactByEmail($data[ 'email' ], $this->authenticated->tenant_id);

                // Se já existe um prestador com este email, redirecionar para a página inicial e mostrar a mensagem de erro
                if (!$checkObj instanceof EntityNotFound && !$customerData instanceof EntityNotFound) {
                    /** @var ContactEntity $checkObj */
                    /** @var CustomerEntity $customerData */
                    if ($customerData->contact_id != $checkObj->id) {
                        return [
                            'status' => 'error',
                            'message' => 'Este e-mail já está registrado.',
                        ];
                    }

                }

                // Subistitui os dados do cliente com os dados do formulário
                // Converter o objeto para array
                $originalData = $customerData;

                // Popula UserEntity com os dados do formulário
                $customerEntity = CustomerEntity::create(removeUnnecessaryIndexes(
                    $originalData,
                    [ 'created_at', 'updated_at' ],
                    $data,
                ));
                // Verificar se os dados do formulário foram alterados
                if (!compareObjects($customerData, $customerEntity, [ 'created_at', 'updated_at' ])) {
                    // Atualizar CustomerEntity com os dados do formuláriorio
                    $result = $this->customer->update($customerEntity);

                    // Se não foi possível atualizar o cliente, redirecionar para a página de atualização e mostrar a mensagem de erro
                    if ($result[ 'status' ] === 'error') {
                        return [
                            'status' => 'error',
                            'message' => 'Falha ao atualizar o cliente.',
                        ];
                    }
                }
                // Fim da sessão atualizar customer

                // Início da sessão atualizar contact

                /** @var CustomerEntity $customerData */
                $contactData = $this->contact->getContactById($customerData->contact_id, $this->authenticated->tenant_id);

                // Subistitui os dados do cliente com os dados do formulário
                // Converter o objeto para array
                $originalData = $contactData->toArray();

                // Popula ContactEntity com os dados do formulário
                $data[ 'id' ] = $customerData->contact_id;
                $contactEntity = ContactEntity::create(removeUnnecessaryIndexes(
                    $originalData,
                    [],
                    $data,
                ));

                // Verificar se os dados do formulário foram alterados
                if (!compareObjects($contactData, $contactEntity, [ 'created_at', 'updated_at' ])) {
                    // Atualizar ContactEntity com os dados do formuláriorio
                    $result = $this->contact->update($contactEntity);

                    // Se não foi possível atualizar o cliente, redirecionar para a página de atualização e mostrar a mensagem de erro
                    if ($result[ 'status' ] === 'error') {
                        return [
                            'status' => 'error',
                            'message' => 'Falha ao atualizar o cliente.',
                        ];
                    }
                }
                // Fim da sessão atualizar contact

                // Início da sessão atualizar address
                $addressData = $this->address->getAddressById($customerData->address_id, $this->authenticated->tenant_id);

                // Subistitui os dados do cliente com os dados do formulário
                // Converter o objeto para array
                $originalData = $addressData->toArray();

                // Popula AddressEntity com os dados do formulário
                $data[ 'id' ] = $customerData->address_id;
                $addressEntity = AddressEntity::create(removeUnnecessaryIndexes(
                    $originalData,
                    [ 'created_at', 'updated_at' ],
                    $data,
                ));

                // Verificar se os dados do formulário foram alterados
                if (!compareObjects($addressData, $addressEntity, [ 'created_at', 'updated_at' ])) {
                    // Atualizar AddressEntity com os dados do formuláriorio
                    $result = $this->address->update($addressEntity);

                    // Se não foi possível atualizar o cliente, redirecionar para a página de atualização e mostrar a mensagem de erro
                    if ($result[ 'status' ] === 'error') {
                        return [
                            'status' => 'error',
                            'message' => 'Falha ao atualizar o cliente.',
                        ];
                    }
                }
                // Fim da sessão atualizar address

                // Início da sessão atualizar common data
                // Busca os dados atuais de CommonData do cliente
                $commonDataData = $this->commonData->getCommonDataById($customerData->common_data_id, $this->authenticated->tenant_id);

                // Subistitui os dados do cliente com os dados do formulário
                // Converter o objeto para array
                $originalData = $commonDataData->toArray();

                // Popula ContactEntity com os dados do formulário
                $data[ 'id' ] = $customerData->common_data_id;
                $data[ "area_of_activity_id" ] = (int) $data[ "area_of_activity_id" ];
                $data[ "profession_id" ] = (int) $data[ "profession_id" ];
                $commonDataEntity = CommonDataEntity::create(removeUnnecessaryIndexes(
                    $originalData,
                    [ 'created_at', 'updated_at' ],
                    $data,
                ));

                // Verificar se os dados do formulário foram alterados
                if (!compareObjects($commonDataData, $commonDataEntity, [ 'created_at', 'updated_at' ])) {
                    // Atualizar CommonDataEntity com os dados do formuláriorio
                    $result = $this->commonData->update($commonDataEntity);

                    // Se não foi possível atualizar o usuário, redirecionar para a página de atualização e mostrar a mensagem de erro
                    if ($result[ 'status' ] === 'error') {
                        return [
                            'status' => 'error',
                            'message' => 'Falha ao atualizar o cliente.',
                        ];
                    }
                }
                // Fim da sessão atualizar common data

                return [
                    'status' => $result[ 'status' ] === 'success' ? 'success' : 'error',
                    'message' => $result[ 'status' ] === 'success' ? 'Cliente atualizado com sucesso' : 'Nenhum dado foi alterado',
                    'data' => $data,
                ];

            });

        } catch (Exception $e) {
            throw new RuntimeException("Falha ao atualizar o cliente, tente novamente mais tarde ou entre em contato com suporte!", 0, $e);
        }
    }

    /**
     * Deleta um cliente.
     *
     * @param int $id ID do cliente
     * @return array<string, mixed> Resultado da operação
     */
    public function delete(int $id): array
    {
        try {
            return $this->connection->transactional(function () use ($id) {
                // Busca o cliente pelo ID
                $customer = $this->customer->getCustomerById($id, $this->authenticated->tenant_id);
                if ($customer instanceof EntityNotFound) {
                    return [
                        'status' => 'error',
                        'message' => 'Cliente não encontrado.',
                    ];
                }

                /** @var CustomerEntity $customer */
                if ($this->customer->delete($customer->id, $this->authenticated->tenant_id)[ 'status' ] === 'error') {
                    return [
                        'status' => 'error',
                        'message' => 'Falha ao deletar o cliente.',
                    ];
                }
                if ($this->commonData->delete($customer->common_data_id, $this->authenticated->tenant_id)[ 'status' ] === 'error') {
                    return [
                        'status' => 'error',
                        'message' => 'Falha ao deletar o cliente.',
                    ];
                }
                if ($this->contact->delete($customer->contact_id, $this->authenticated->tenant_id)[ 'status' ] === 'error') {
                    return [
                        'status' => 'error',
                        'message' => 'Falha ao deletar o cliente.',
                    ];
                }
                if ($this->address->delete($customer->address_id, $this->authenticated->tenant_id)[ 'status' ] === 'error') {
                    return [
                        'status' => 'error',
                        'message' => 'Falha ao deletar o cliente.',
                    ];
                }

                return [
                    'status' => 'success',
                    'message' => 'Cliente deletado com sucesso',
                    'data' => [ 'id' => $id ],
                ];
            });

        } catch (Exception $e) {
            throw new RuntimeException("Falha ao deletar o cliente, tente novamente mais tarde ou entre em contato com suporte!", 0, $e);
        }
    }

    /**
     * Verifica relacionamentos do cliente.
     *
     * @param int $tableId ID da tabela
     * @param int $tenantId ID do tenant
     * @return array<string, mixed> Resultado da verificação
     */
    public function checkRelationships(int $tableId, int $tenantId): array
    {
        try {
            // Consulta para verificar relacionamentos no INFORMATION_SCHEMA
            $schemaQuery = $this->connection->createQueryBuilder()
                ->select('TABLE_NAME', 'COLUMN_NAME', 'CONSTRAINT_NAME')
                ->from('INFORMATION_SCHEMA.KEY_COLUMN_USAGE')
                ->where('REFERENCED_TABLE_NAME = :table')
                ->andWhere('REFERENCED_TABLE_SCHEMA = :schema')
                ->setParameters([
                    'table' => $this->tableCustomers,
                    'schema' => env('DB_NAME'),
                ])
                ->executeQuery()
                ->fetchAllAssociative();

            // Se não encontrou relacionamentos, retorna false
            if (empty($schemaQuery)) {
                return [ 'hasRelationships' => false ];
            }

            // Verifica cada tabela relacionada
            foreach ($schemaQuery as $relation) {
                $tableName = $relation[ 'TABLE_NAME' ];
                $columnName = $relation[ 'COLUMN_NAME' ];

                // Consulta para contar registros relacionados
                $count = $this->connection->createQueryBuilder()
                    ->select('COUNT(*) as count')
                    ->from($tableName)
                    ->where("$columnName = :id")
                    ->andWhere('tenant_id = :tenant_id')
                    ->setParameters([
                        'id' => $tableId,
                        'tenant_id' => $tenantId,
                    ])
                    ->executeQuery()
                    ->fetchOne();

                if ($count > 0) {
                    // Obtém informações adicionais sobre os registros relacionados
                    $relatedRecords = $this->connection->createQueryBuilder()
                        ->select('*')
                        ->from($tableName)
                        ->where("$columnName = :id")
                        ->andWhere('tenant_id = :tenant_id')
                        ->setParameters([
                            'id' => $tableId,
                            'tenant_id' => $tenantId,
                        ])
                        ->executeQuery()
                        ->fetchAllAssociative();

                    // Retorna informações detalhadas sobre o relacionamento
                    return [
                        'hasRelationships' => true,
                        'table' => $this->getTableAlias($tableName),
                        'count' => $count,
                        'records' => $relatedRecords,
                        'constraint' => $relation[ 'CONSTRAINT_NAME' ],
                    ];
                }
            }

            return [ 'hasRelationships' => false ];

        } catch (Exception $e) {
            error_log("Erro ao verificar relacionamentos: " . $e->getMessage());

            return [
                'error' => true,
                'message' => 'Erro ao verificar relacionamentos',
                'details' => $e->getMessage(),
            ];
        }
    }

    /**
     * Retorna um nome amigável para a tabela
     */
    /**
     * Retorna um nome amigável para a tabela.
     *
     * @param string $tableName Nome da tabela
     * @return string Nome amigável da tabela
     */
    private function getTableAlias(string $tableName): string
    {
        $aliases = [
            'budgets' => 'orçamento(s)',
            'services' => 'serviço(s)',
            // Adicione mais aliases conforme necessário
        ];

        return $aliases[ $tableName ] ?? $tableName;
    }

}
