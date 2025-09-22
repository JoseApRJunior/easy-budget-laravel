<?php

namespace app\database\servicesORM;

use app\database\entitiesORM\UserConfirmationTokenEntity;
use app\database\models\Provider;
use app\database\repositories\UserConfirmationTokenRepository;
use app\enums\OperationStatus;
use app\interfaces\ServiceInterface;
use app\support\ServiceResult;
use core\dbal\EntityNotFound;
use DateTime;
use DateTimeImmutable;
use Exception;

/**
 * Serviço compartilhado para operações comuns que utilizam tenant_id.
 * Implementa ServiceInterface para padronização.
 */
class SharedService implements ServiceInterface
{
    public function __construct(
        private UserConfirmationTokenRepository $userConfirmationTokenRepository,
        private Provider $provider,
    ) {}

    /**
     * Busca uma entidade pelo seu ID e tenant_id.
     *
     * @param int $id ID da entidade
     * @param int $tenant_id ID do tenant
     * @return ServiceResult Resultado da operação com status, mensagem e dados
     */
    public function getByIdAndTenantId( int $id, int $tenant_id ): ServiceResult
    {
        try {
            // Validar tenant_id
            if ( $tenant_id <= 0 ) {
                return ServiceResult::error( OperationStatus::INVALID_DATA, 'ID do tenant inválido.' );
            }

            $entity = $this->userConfirmationTokenRepository->findByIdAndTenantId( $id, $tenant_id );

            if ( $entity === null ) {
                return ServiceResult::error( OperationStatus::NOT_FOUND, 'Token não encontrado.' );
            }

            return ServiceResult::success( $entity, 'Token encontrado com sucesso.' );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Falha ao buscar token: ' . $e->getMessage() );
        }
    }

    /**
     * Lista todas as entidades de um tenant com possibilidade de filtros.
     *
     * @param int $tenant_id ID do tenant
     * @param array<string, mixed> $filters Filtros a serem aplicados
     * @return ServiceResult Resultado da operação com status, mensagem e dados
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
            if ( !empty( $filters[ 'user_id' ] ) ) {
                $criteria[ 'user_id' ] = $filters[ 'user_id' ];
            }

            if ( !empty( $filters[ 'token' ] ) ) {
                $criteria[ 'token' ] = $filters[ 'token' ];
            }

            $entities = $this->userConfirmationTokenRepository->findAllByTenantId( $tenant_id, $criteria, $orderBy );

            return ServiceResult::success( $entities, 'Tokens listados com sucesso.' );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Falha ao listar tokens: ' . $e->getMessage() );
        }
    }

    /**
     * Cria uma nova entidade.
     *
     * @param array<string, mixed> $data Dados para criação da entidade
     * @param int $tenant_id ID do tenant
     * @return ServiceResult Resultado da operação com status, mensagem e dados
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

            // Gera um token para confirmação de conta
            [ $token, $expiresDateString ] = generateTokenExpirate( '+7 days' );

            // Converter string para DateTimeImmutable
            $expiresDate = new DateTimeImmutable( $expiresDateString );

            // Criar nova entidade
            $entity = new UserConfirmationTokenEntity(
                $data[ 'user_id' ],
                $tenant_id,
                $token,
                $expiresDate,
            );

            // Salvar via repository
            $result = $this->userConfirmationTokenRepository->save( $entity, $tenant_id );

            // Verificar se o resultado é null
            if ( $result === null ) {
                return ServiceResult::error( OperationStatus::ERROR, 'Falha ao criar token.' );
            }

            return ServiceResult::success( $result, 'Token criado com sucesso.' );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Falha ao criar token: ' . $e->getMessage() );
        }
    }

    /**
     * Atualiza uma entidade existente.
     *
     * @param int $id ID da entidade
     * @param int $tenant_id ID do tenant
     * @param array<string, mixed> $data Dados para atualização
     * @return ServiceResult Resultado da operação com status, mensagem e dados
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
            $entity = $this->userConfirmationTokenRepository->findByIdAndTenantId( $id, $tenant_id );
            if ( $entity === null ) {
                return ServiceResult::error( OperationStatus::NOT_FOUND, 'Token não encontrado.' );
            }

            // Atualizar dados
            if ( isset( $data[ 'user_id' ] ) ) {
                $entity->setUserId( $data[ 'user_id' ] );
            }

            if ( isset( $data[ 'token' ] ) ) {
                $entity->setToken( $data[ 'token' ] );
            }

            if ( isset( $data[ 'expires_at' ] ) ) {
                $entity->setExpiresAt( new DateTimeImmutable( $data[ 'expires_at' ] ) );
            }

            // Salvar via repository
            $result = $this->userConfirmationTokenRepository->save( $entity, $tenant_id );

            // Verificar se o resultado é null
            if ( $result === null ) {
                return ServiceResult::error( OperationStatus::ERROR, 'Falha ao atualizar token.' );
            }

            return ServiceResult::success( $result, 'Token atualizado com sucesso.' );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Falha ao atualizar token: ' . $e->getMessage() );
        }
    }

    /**
     * Remove uma entidade.
     *
     * @param int $id ID da entidade
     * @param int $tenant_id ID do tenant
     * @return ServiceResult Resultado da operação com status, mensagem e dados
     */
    public function deleteByIdAndTenantId( int $id, int $tenant_id ): ServiceResult
    {
        try {
            // Validar tenant_id
            if ( $tenant_id <= 0 ) {
                return ServiceResult::error( OperationStatus::INVALID_DATA, 'ID do tenant inválido.' );
            }

            // Verificar se a entidade existe e pertence ao tenant
            $entity = $this->userConfirmationTokenRepository->findByIdAndTenantId( $id, $tenant_id );
            if ( $entity === null ) {
                return ServiceResult::error( OperationStatus::NOT_FOUND, 'Token não encontrado.' );
            }

            // Executar exclusão via repository
            $result = $this->userConfirmationTokenRepository->deleteByIdAndTenantId( $id, $tenant_id );

            if ( $result ) {
                return ServiceResult::success( null, 'Token removido com sucesso.' );
            } else {
                return ServiceResult::error( OperationStatus::ERROR, 'Falha ao remover token do banco de dados.' );
            }
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Falha ao remover token: ' . $e->getMessage() );
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
            if ( empty( $data[ 'user_id' ] ) ) {
                $errors[] = 'ID do usuário é obrigatório.';
            }
        }

        // Validar user_id se fornecido
        if ( isset( $data[ 'user_id' ] ) && !is_numeric( $data[ 'user_id' ] ) ) {
            $errors[] = 'ID do usuário deve ser um número válido.';
        }

        if ( count( $errors ) > 0 ) {
            return ServiceResult::error( OperationStatus::INVALID_DATA, "Dados inválidos para tenant {$tenant_id}: " . implode( ', ', $errors ) );
        }

        return ServiceResult::success( null, "Dados válidos para tenant {$tenant_id}." );
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
        // Redirecionar para validateForTenant com tenant_id padrão
        $tenant_id = $data[ 'tenant_id' ] ?? 0;
        return $this->validateForTenant( $data, $tenant_id, $isUpdate );
    }

    /**
     * Valida um token de confirmação de usuário.
     *
     * @param string $token Token de confirmação.
     * @return ServiceResult Resultado da validação.
     */
    public function validateUserConfirmationToken( string $token ): ServiceResult
    {
        try {
            $hashedToken = hash( 'sha256', $token );
            $entity      = $this->userConfirmationTokenRepository->findByToken( $hashedToken );

            if ( $entity === null ) {
                return ServiceResult::error( OperationStatus::NOT_FOUND, 'Token de confirmação inválido ou não encontrado.' );
            }

            // Verificar se o token expirou
            $expiresAt = $entity->getExpiresAt();
            $now       = new DateTime();

            if ( $expiresAt < $now ) {
                return ServiceResult::error( OperationStatus::EXPIRED, 'Token de confirmação expirado.' );
            }

            return ServiceResult::success( $entity, 'Token de confirmação válido.' );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Falha ao validar token de confirmação: ' . $e->getMessage() );
        }
    }

    /**
     * Gera um novo token de confirmação de usuário.
     *
     * @param int $user_id ID do usuário.
     * @param int $tenant_id ID do tenant.
     * @return ServiceResult Resultado da geração do token.
     */
    public function generateNewUserConfirmationToken( int $user_id, int $tenant_id ): ServiceResult
    {
        try {
            // Validar parâmetros
            if ( $user_id <= 0 ) {
                return ServiceResult::error( OperationStatus::INVALID_DATA, 'ID do usuário inválido.' );
            }

            if ( $tenant_id <= 0 ) {
                return ServiceResult::error( OperationStatus::INVALID_DATA, 'ID do tenant inválido.' );
            }

            // Gera um token cru para confirmação de conta
            $rawToken    = bin2hex( random_bytes( 32 ) );
            $hashedToken = hash( 'sha256', $rawToken );
            $expiresDate = ( new DateTimeImmutable() )->modify( '+7 days' );

            // Criar nova entidade com token hashed
            $entity = new UserConfirmationTokenEntity(
                $user_id,
                $tenant_id,
                $hashedToken,
                $expiresDate,
            );

            // Retornar dados com raw_token para envio de email
            $resultData = [ 
                'id'           => null, // Será preenchido após save
                'raw_token'    => $rawToken,
                'hashed_token' => $hashedToken,
                'expires_at'   => $expiresDate
            ];

            // Salvar via repository
            $savedEntity = $this->userConfirmationTokenRepository->save( $entity, $tenant_id );

            // Verificar se o resultado é null
            if ( $savedEntity === null ) {
                return ServiceResult::error( OperationStatus::ERROR, 'Falha ao gerar novo token de confirmação.' );
            }

            $resultData[ 'id' ] = $savedEntity->getId();

            return ServiceResult::success( $resultData, 'Novo token de confirmação gerado com sucesso.' );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Falha ao gerar novo token de confirmação: ' . $e->getMessage() );
        }
    }

    /**
     * Busca um token de confirmação por ID.
     *
     * @param int $id ID do token.
     * @param int $tenant_id ID do tenant.
     * @return ServiceResult Token encontrado ou erro.
     */
    public function getUserConfirmationTokenById( int $id, int $tenant_id ): ServiceResult
    {
        try {
            // Validar parâmetros
            if ( $id <= 0 ) {
                return ServiceResult::error( OperationStatus::INVALID_DATA, 'ID do token inválido.' );
            }

            if ( $tenant_id <= 0 ) {
                return ServiceResult::error( OperationStatus::INVALID_DATA, 'ID do tenant inválido.' );
            }

            $entity = $this->userConfirmationTokenRepository->findByIdAndTenantId( $id, $tenant_id );

            if ( $entity === null ) {
                return ServiceResult::error( OperationStatus::NOT_FOUND, 'Token não encontrado.' );
            }

            return ServiceResult::success( $entity, 'Token encontrado com sucesso.' );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Falha ao buscar token: ' . $e->getMessage() );
        }
    }

    /**
     * Obtém o provedor completo associado a um token de confirmação de usuário.
     *
     * @param string $token Token de confirmação do usuário
     * @return mixed Retorna o provedor completo ou EntityNotFound se não encontrado
     */
    public function getProviderByToken( string $token ): mixed
    {
        try {
            // Buscar token de confirmação usando o repository
            $userConfirmationToken = $this->userConfirmationTokenRepository->getByToken( $token );

            // Se token não encontrado, retornar EntityNotFound
            if ( $userConfirmationToken instanceof EntityNotFound ) {
                return $userConfirmationToken;
            }

            // Buscar provedor completo usando o model Provider
            return $this->provider->getProviderFullByUserId(
                $userConfirmationToken->getUserId(),
                $userConfirmationToken->getTenantId(),
            );
        } catch ( Exception $e ) {
            throw new \RuntimeException( "Falha ao buscar prestador por token, tente mais tarde ou entre em contato com suporte.", 0, $e );
        }
    }

}
