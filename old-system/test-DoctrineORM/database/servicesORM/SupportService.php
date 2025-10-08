<?php

namespace app\database\servicesORM;

use app\database\entitiesORM\SupportEntity;
use app\database\repositories\SupportRepository;
use app\interfaces\ServiceNoTenantInterface;
use app\support\ServiceResult;
use app\enums\OperationStatus;
use Doctrine\DBAL\Connection;
use Exception;
use RuntimeException;

class SupportService implements ServiceNoTenantInterface
{
    public function __construct(
        private readonly Connection $connection,
        private SupportRepository $supportRepository,
        private NotificationService $notificationService,
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
            $entity = $this->supportRepository->findById( $id );

            if ( $entity === null ) {
                return ServiceResult::error( OperationStatus::NOT_FOUND, 'Registro de suporte não encontrado.' );
            }

            return ServiceResult::success( $entity, 'Registro de suporte encontrado com sucesso.' );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Falha ao buscar registro de suporte: ' . $e->getMessage() );
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
            $criteria = [];
            $orderBy  = [ 'createdAt' => 'DESC' ];

            // Aplicar filtros conforme necessário
            if ( !empty( $filters[ 'email' ] ) ) {
                $criteria[ 'email' ] = $filters[ 'email' ];
            }

            if ( !empty( $filters[ 'subject' ] ) ) {
                $criteria[ 'subject' ] = $filters[ 'subject' ];
            }

            $entities = $this->supportRepository->findAll( $criteria, $orderBy );

            return ServiceResult::success( $entities, 'Registros de suporte listados com sucesso.' );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Falha ao listar registros de suporte: ' . $e->getMessage() );
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

            // Criar nova entidade
            $entity = new SupportEntity(
                first_name: $data[ 'first_name' ],
                last_name: $data[ 'last_name' ],
                email: $data[ 'email' ],
                subject: $data[ 'subject' ],
                message: $data[ 'message' ],
                tenantId: $data[ 'tenant_id' ] ?? null
            );

            // Salvar via repository
            $result = $this->supportRepository->save( $entity );

            if ( $result !== false ) {
                // Verificar se o resultado é uma instância válida de SupportEntity
                if ( $result === null ) {
                    return ServiceResult::error( OperationStatus::ERROR, 'Erro ao criar o registro de suporte.' );
                }

                /** @var SupportEntity $result */

                // Enviar e-mail de suporte
                $sendEmail = $this->notificationService->sendSupportEmail( $result );

                if ( $sendEmail === false ) {
                    return ServiceResult::error( OperationStatus::ERROR, 'Erro ao enviar o email de suporte.' );
                }

                return ServiceResult::success( $result, 'Registro de suporte criado com sucesso.' );
            } else {
                return ServiceResult::error( OperationStatus::ERROR, 'Erro ao criar o registro de suporte.' );
            }
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Falha ao criar registro de suporte: ' . $e->getMessage() );
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

            // Buscar entidade existente
            $entity = $this->supportRepository->findById( $id );
            if ( $entity === null ) {
                return ServiceResult::error( OperationStatus::NOT_FOUND, 'Registro de suporte não encontrado.' );
            }

            // Atualizar dados
            if ( isset( $data[ 'first_name' ] ) ) {
                $entity->setFirstName( $data[ 'first_name' ] );
            }

            if ( isset( $data[ 'last_name' ] ) ) {
                $entity->setLastName( $data[ 'last_name' ] );
            }

            if ( isset( $data[ 'email' ] ) ) {
                $entity->setEmail( $data[ 'email' ] );
            }

            if ( isset( $data[ 'subject' ] ) ) {
                $entity->setSubject( $data[ 'subject' ] );
            }

            if ( isset( $data[ 'message' ] ) ) {
                $entity->setMessage( $data[ 'message' ] );
            }

            if ( isset( $data[ 'tenant_id' ] ) ) {
                $entity->setTenantId( $data[ 'tenant_id' ] );
            }

            // Salvar via repository
            $result = $this->supportRepository->save( $entity );

            if ( $result !== false ) {
                return ServiceResult::success( $result, 'Registro de suporte atualizado com sucesso.' );
            } else {
                return ServiceResult::error( OperationStatus::ERROR, 'Erro ao atualizar o registro de suporte.' );
            }
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Falha ao atualizar registro de suporte: ' . $e->getMessage() );
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
            // Verificar se a entidade existe
            $entity = $this->supportRepository->findById( $id );
            if ( $entity === null ) {
                return ServiceResult::error( OperationStatus::NOT_FOUND, 'Registro de suporte não encontrado.' );
            }

            // Executar exclusão via repository
            $result = $this->supportRepository->delete( $id );

            if ( $result ) {
                return ServiceResult::success( null, 'Registro de suporte removido com sucesso.' );
            } else {
                return ServiceResult::error( OperationStatus::ERROR, 'Falha ao remover registro de suporte do banco de dados.' );
            }
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Falha ao excluir registro de suporte: ' . $e->getMessage() );
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

        // Validar campos obrigatórios para criação
        if ( !$isUpdate ) {
            if ( empty( $data[ 'first_name' ] ) ) {
                $errors[] = "Primeiro nome é obrigatório.";
            }

            if ( empty( $data[ 'last_name' ] ) ) {
                $errors[] = "Último nome é obrigatório.";
            }

            if ( empty( $data[ 'email' ] ) ) {
                $errors[] = "Email é obrigatório.";
            } elseif ( !filter_var( $data[ 'email' ], FILTER_VALIDATE_EMAIL ) ) {
                $errors[] = "Email inválido.";
            }

            if ( empty( $data[ 'subject' ] ) ) {
                $errors[] = "Assunto é obrigatório.";
            }

            if ( empty( $data[ 'message' ] ) ) {
                $errors[] = "Mensagem é obrigatória.";
            }
        }

        // Validar campos quando fornecidos
        if ( isset( $data[ 'email' ] ) && !empty( $data[ 'email' ] ) ) {
            if ( !filter_var( $data[ 'email' ], FILTER_VALIDATE_EMAIL ) ) {
                $errors[] = "Email inválido.";
            }
        }

        if ( isset( $data[ 'first_name' ] ) && strlen( $data[ 'first_name' ] ) > 100 ) {
            $errors[] = "Primeiro nome deve ter no máximo 100 caracteres.";
        }

        if ( isset( $data[ 'last_name' ] ) && strlen( $data[ 'last_name' ] ) > 100 ) {
            $errors[] = "Último nome deve ter no máximo 100 caracteres.";
        }

        if ( isset( $data[ 'subject' ] ) && strlen( $data[ 'subject' ] ) > 255 ) {
            $errors[] = "Assunto deve ter no máximo 255 caracteres.";
        }

        if ( isset( $data[ 'message' ] ) && strlen( $data[ 'message' ] ) > 1000 ) {
            $errors[] = "Mensagem deve ter no máximo 1000 caracteres.";
        }

        if ( count( $errors ) > 0 ) {
            return ServiceResult::error( OperationStatus::INVALID_DATA, "Dados inválidos: " . implode( ', ', $errors ) );
        }

        return ServiceResult::success( null, "Dados válidos." );
    }

}
