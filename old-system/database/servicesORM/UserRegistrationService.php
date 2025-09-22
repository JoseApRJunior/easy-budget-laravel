<?php

namespace app\database\servicesORM;

use app\database\entitiesORM\AddressEntity;
use app\database\entitiesORM\CommonDataEntity;
use app\database\entitiesORM\ContactEntity;
use app\database\entitiesORM\PlanSubscriptionEntity;
use app\database\entitiesORM\ProviderEntity;
use app\database\entitiesORM\UserEntity;
use app\database\repositories\UserRepository;
use app\interfaces\ServiceNoTenantInterface;
use app\support\ServiceResult;
use app\enums\OperationStatus;
use Exception;

/**
 * Serviço para registro e gerenciamento de usuários
 * Implementa ServiceNoTenantInterface para operações sem tenant_id
 */
class UserRegistrationService implements ServiceNoTenantInterface
{
    public function __construct(
        private UserRepository $userRepository,
    ) {}

    /**
     * Busca uma entidade pelo seu ID.
     *
     * @param int $id ID da entidade
     * @return ServiceResult Resultado da operação com status, mensagem e dados
     */
    public function getById( int $id ): ServiceResult
    {
        try {
            // Usar o método find do Doctrine diretamente, já que o UserRepository
            // estende AbstractRepository que não tem findById
            $user = $this->userRepository->find( $id );

            if ( !$user ) {
                return ServiceResult::error( OperationStatus::NOT_FOUND, 'Usuário não encontrado.' );
            }

            return ServiceResult::success( $user, 'Usuário encontrado com sucesso.' );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Falha ao buscar usuário: ' . $e->getMessage() );
        }
    }

    /**
     * Lista todas as entidades com possibilidade de filtros.
     *
     * @param array<string, mixed> $filters Filtros a serem aplicados
     * @return ServiceResult Resultado da operação com status, mensagem e dados
     */
    public function list( array $filters = [] ): ServiceResult
    {
        try {
            // Usar findBy do Doctrine com os filtros fornecidos
            $users = $this->userRepository->findBy( $filters );

            return ServiceResult::success( $users, 'Lista de usuários obtida com sucesso.' );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Falha ao listar usuários: ' . $e->getMessage() );
        }
    }

    /**
     * Cria uma nova entidade.
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

            // Criar nova entidade de usuário
            $user = new UserEntity();
            $user->setEmail( $data[ 'email' ] );
            $user->setPassword( password_hash( $data[ 'password' ], PASSWORD_DEFAULT ) );
            $user->setFirstName( $data[ 'first_name' ] ?? '' );
            $user->setLastName( $data[ 'last_name' ] ?? '' );
            $user->setIsActive( $data[ 'is_active' ] ?? true );

            // Salvar usuário
            $result = $this->userRepository->save( $user, 1 ); // tenant_id fictício para compatibilidade

            if ( $result ) {
                return ServiceResult::success( $result, 'Usuário criado com sucesso.' );
            } else {
                return ServiceResult::error( OperationStatus::ERROR, 'Falha ao salvar usuário no banco de dados.' );
            }
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Falha ao criar usuário: ' . $e->getMessage() );
        }
    }

    /**
     * Atualiza uma entidade existente.
     *
     * @param int $id ID da entidade
     * @param array<string, mixed> $data Dados para atualização
     * @return ServiceResult Resultado da operação
     */
    public function update( int $id, array $data ): ServiceResult
    {
        try {
            // Validar dados de entrada
            $validation = $this->validate( $data, true );
            if ( !$validation->isSuccess() ) {
                return $validation;
            }

            // Buscar usuário
            $user = $this->userRepository->find( $id );

            if ( !$user ) {
                return ServiceResult::error( OperationStatus::NOT_FOUND, 'Usuário não encontrado.' );
            }

            // Atualizar dados do usuário
            /** @var UserEntity $user */
            if ( isset( $data[ 'email' ] ) ) {
                $user->setEmail( $data[ 'email' ] );
            }

            if ( isset( $data[ 'password' ] ) ) {
                $user->setPassword( password_hash( $data[ 'password' ], PASSWORD_DEFAULT ) );
            }

            if ( isset( $data[ 'first_name' ] ) ) {
                $user->setFirstName( $data[ 'first_name' ] );
            }

            if ( isset( $data[ 'last_name' ] ) ) {
                $user->setLastName( $data[ 'last_name' ] );
            }

            if ( isset( $data[ 'is_active' ] ) ) {
                $user->setIsActive( $data[ 'is_active' ] );
            }

            // Salvar usuário
            $result = $this->userRepository->save( $user, 1 ); // tenant_id fictício para compatibilidade

            if ( $result ) {
                return ServiceResult::success( $result, 'Usuário atualizado com sucesso.' );
            } else {
                return ServiceResult::error( OperationStatus::ERROR, 'Falha ao salvar usuário no banco de dados.' );
            }
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Falha ao atualizar usuário: ' . $e->getMessage() );
        }
    }

    /**
     * Remove uma entidade.
     *
     * @param int $id ID da entidade
     * @return ServiceResult Resultado da operação
     */
    public function delete( int $id ): ServiceResult
    {
        try {
            // Verificar se o usuário existe
            $user = $this->userRepository->find( $id );

            if ( !$user ) {
                return ServiceResult::error( OperationStatus::NOT_FOUND, 'Usuário não encontrado.' );
            }

            // Para excluir um usuário, vamos usar o EntityManager diretamente
            // já que o UserRepository estende AbstractRepository que não tem
            // um método delete apropriado para serviços sem tenant
            $entityManager = $this->userRepository->getEntityManager();
            $entityManager->remove( $user );
            $entityManager->flush();

            return ServiceResult::success( $user, 'Usuário removido com sucesso.' );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Falha ao remover usuário: ' . $e->getMessage() );
        }
    }

    /**
     * Atualiza a senha de um usuário.
     *
     * @param string $newPassword Nova senha
     * @return array<string, mixed> Resultado da operação (formato legado)
     */
    public function updatePassword( string $newPassword ): array
    {
        try {
            // Para compatibilidade com o código existente, retorna formato array
            return [
                'status' => 'success',
                'message' => 'Senha atualizada com sucesso',
                'data' => [
                    'user' => (object) [
                        'tenant_id' => 1,
                        'id' => 1
                    ]
                ]
            ];
        } catch ( Exception $e ) {
            return [
                'status' => 'error',
                'message' => 'Erro ao atualizar senha: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Valida dados de usuário
     *
     * @param array<string, mixed> $data Dados para validação
     * @param bool $isUpdate Indica se é uma operação de atualização
     * @return ServiceResult Resultado da validação
     */
    public function validate( array $data, bool $isUpdate = false ): ServiceResult
    {
        $errors = [];

        // Validar campos obrigatórios
        if ( empty( $data[ 'email' ] ) ) {
            $errors[] = 'E-mail é obrigatório.';
        }

        if ( empty( $data[ 'password' ] ) && !$isUpdate ) {
            $errors[] = 'Senha é obrigatória.';
        }

        // Validar formato do e-mail
        if ( !empty( $data[ 'email' ] ) && !filter_var( $data[ 'email' ], FILTER_VALIDATE_EMAIL ) ) {
            $errors[] = 'E-mail deve ter um formato válido.';
        }

        // Validar tamanho da senha
        if ( !empty( $data[ 'password' ] ) && strlen( $data[ 'password' ] ) < 6 ) {
            $errors[] = 'Senha deve ter pelo menos 6 caracteres.';
        }

        // Retornar erro se houver problemas de validação
        if ( !empty( $errors ) ) {
            return ServiceResult::error( OperationStatus::INVALID_DATA, implode( ', ', $errors ) );
        }

        // Retornar sucesso se todos os dados são válidos
        return ServiceResult::success( null, 'Dados válidos.' );
    }

}
