<?php

namespace app\database\servicesORM;

use app\database\entitiesORM\SessionEntity;
use app\database\entitiesORM\UserEntity;
use app\database\repositories\SessionRepository;
use app\database\repositories\UserRepository;
use app\interfaces\ServiceNoTenantInterface;
use app\support\ServiceResult;
use app\enums\OperationStatus;
use DateTimeImmutable;
use Exception;

/**
 * Serviço para gerenciamento de sessões de usuários
 *
 * Esta classe implementa a lógica de negócio para sessões,
 * incluindo criação, validação, renovação e limpeza de sessões.
 *
 * @package app\database\servicesORM
 */
class SessionService implements ServiceNoTenantInterface
{
    private SessionRepository $sessionRepository;
    private UserRepository    $userRepository;

    /**
     * Construtor do serviço de sessões
     *
     * @param SessionRepository $sessionRepository Repositório de sessões
     * @param UserRepository $userRepository Repositório de usuários
     */
    public function __construct(
        SessionRepository $sessionRepository,
        UserRepository $userRepository,
    ) {
        $this->sessionRepository = $sessionRepository;
        $this->userRepository    = $userRepository;
    }

    /**
     * Busca uma sessão pelo seu ID
     *
     * @param int $id ID da sessão
     * @return ServiceResult Resultado da operação com status, mensagem e dados
     */
    public function getById( int $id ): ServiceResult
    {
        try {
            /** @var SessionEntity|null $session */
            $session = $this->sessionRepository->find( $id );

            if ( !$session ) {
                return ServiceResult::error( OperationStatus::NOT_FOUND, 'Sessão não encontrada.' );
            }

            return ServiceResult::success( $session, 'Sessão encontrada com sucesso.' );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao buscar sessão: ' . $e->getMessage() );
        }
    }

    /**
     * Lista sessões com filtros opcionais
     *
     * @param array<string, mixed> $filters Filtros a serem aplicados
     * @return ServiceResult Resultado da operação com status, mensagem e dados
     */
    public function list( array $filters = [] ): ServiceResult
    {
        try {
            $sessions = $this->sessionRepository->findBy( $filters, [ 'lastActivity' => 'DESC' ] );

            return ServiceResult::success( $sessions, 'Sessões listadas com sucesso.' );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao listar sessões: ' . $e->getMessage() );
        }
    }

    /**
     * Cria uma nova sessão para um usuário
     *
     * @param array<string, mixed> $data Dados para criação da sessão
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

            // Buscar usuário
            $user = $this->userRepository->find( $data[ 'user_id' ] );
            if ( $user === null ) {
                return ServiceResult::error( OperationStatus::NOT_FOUND, 'Usuário não encontrado.' );
            }

            /** @var UserEntity $user */
            // Verificar se usuário está ativo
            if ( !$user->isActive() ) {
                return ServiceResult::error( OperationStatus::INVALID_DATA, 'Usuário inativo.' );
            }

            // Gerar token único de sessão
            $sessionToken = $this->generateSessionToken();

            // Calcular data de expiração (24 horas por padrão)
            $expirationHours = $data[ 'expiration_hours' ] ?? 24;
            $expiresAt       = new DateTimeImmutable();
            $expiresAt->modify( "+{$expirationHours} hours" );

            // Criar nova sessão
            $session = new SessionEntity(
                user: $user,
                sessionToken: $sessionToken,
                expirationMinutes: $expirationHours * 60 // Converter horas para minutos
            );

            // Definir propriedades opcionais
            $session->setIpAddress( $data[ 'ip_address' ] ?? '' );
            $session->setUserAgent( $data[ 'user_agent' ] ?? '' );
            $session->setSessionData( $data[ 'session_data' ] ?? [] );

            // Salvar sessão
            $result = $this->sessionRepository->save( $session, $user->getTenant()->getId() );

            if ( $result ) {
                return ServiceResult::success( $result, 'Sessão criada com sucesso.' );
            } else {
                return ServiceResult::error( OperationStatus::ERROR, 'Falha ao salvar sessão no banco de dados.' );
            }
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Falha ao criar sessão: ' . $e->getMessage() );
        }
    }

    /**
     * Atualiza uma sessão existente
     *
     * @param int $id ID da sessão
     * @param array<string, mixed> $data Dados para atualização
     * @return ServiceResult Resultado da operação
     */
    public function update( int $id, array $data ): ServiceResult
    {
        try {
            /** @var SessionEntity|null $session */
            $session = $this->sessionRepository->find( $id );

            if ( !$session ) {
                return ServiceResult::error( OperationStatus::NOT_FOUND, 'Sessão não encontrada.' );
            }

            // Atualizar campos permitidos
            if ( isset( $data[ 'session_data' ] ) ) {
                $session->setSessionData( $data[ 'session_data' ] );
            }

            if ( isset( $data[ 'expires_at' ] ) ) {
                $expiresAt = new DateTimeImmutable( $data[ 'expires_at' ] );
                $session->setExpiresAt( $expiresAt );
            }

            if ( isset( $data[ 'is_active' ] ) ) {
                $session->setIsActive( $data[ 'is_active' ] );
            }

            // Atualizar última atividade
            $session->updateLastActivity();

            // Salvar alterações
            $result = $this->sessionRepository->save( $session, $session->getUser()->getTenant()->getId() );

            if ( $result ) {
                return ServiceResult::success( $result, 'Sessão atualizada com sucesso.' );
            } else {
                return ServiceResult::error( OperationStatus::ERROR, 'Falha ao salvar sessão no banco de dados.' );
            }
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Falha ao atualizar sessão: ' . $e->getMessage() );
        }
    }

    /**
     * Remove uma sessão
     *
     * @param int $id ID da sessão
     * @return ServiceResult Resultado da operação
     */
    public function delete( int $id ): ServiceResult
    {
        try {
            /** @var SessionEntity|null $session */
            $session = $this->sessionRepository->find( $id );

            if ( !$session ) {
                return ServiceResult::error( OperationStatus::NOT_FOUND, 'Sessão não encontrada.' );
            }

            // Desativar sessão em vez de deletar fisicamente
            $session->setIsActive( false );
            $result = $this->sessionRepository->save( $session, $session->getUser()->getTenant()->getId() );

            if ( $result ) {
                return ServiceResult::success( $result, 'Sessão removida com sucesso.' );
            } else {
                return ServiceResult::error( OperationStatus::ERROR, 'Falha ao remover sessão.' );
            }
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Falha ao remover sessão: ' . $e->getMessage() );
        }
    }

    /**
     * Valida uma sessão pelo token
     *
     * @param string $sessionToken Token da sessão
     * @return ServiceResult Resultado da validação
     */
    public function validateSession( string $sessionToken ): ServiceResult
    {
        try {
            /** @var SessionEntity|null $session */
            $session = $this->sessionRepository->findActiveByToken( $sessionToken );

            if ( !$session ) {
                return ServiceResult::error( OperationStatus::NOT_FOUND, 'Sessão não encontrada ou inválida.' );
            }

            // Verificar se a sessão não expirou
            if ( $session->getExpiresAt() < new DateTimeImmutable() ) {
                // Desativar sessão expirada
                $session->setIsActive( false );
                $this->sessionRepository->save( $session, $session->getUser()->getTenant()->getId() );

                return ServiceResult::error( OperationStatus::INVALID_DATA, 'Sessão expirada.' );
            }

            // Atualizar última atividade
            $this->updateLastActivity( $session->getId() );

            return ServiceResult::success( $session, 'Sessão válida.' );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao validar sessão: ' . $e->getMessage() );
        }
    }

    /**
     * Renova uma sessão existente
     *
     * @param string $sessionToken Token da sessão
     * @param int $extensionHours Horas para estender a sessão
     * @return ServiceResult Resultado da operação
     */
    public function renewSession( string $sessionToken, int $extensionHours = 24 ): ServiceResult
    {
        try {
            /** @var SessionEntity|null $session */
            $session = $this->sessionRepository->findActiveByToken( $sessionToken );

            if ( !$session ) {
                return ServiceResult::error( OperationStatus::NOT_FOUND, 'Sessão não encontrada.' );
            }

            // Estender data de expiração
            $newExpiresAt = new DateTimeImmutable();
            $newExpiresAt->modify( "+{$extensionHours} hours" );
            $session->setExpiresAt( $newExpiresAt );
            $session->updateLastActivity();

            // Salvar alterações
            $result = $this->sessionRepository->save( $session, $session->getUser()->getTenant()->getId() );

            if ( $result ) {
                return ServiceResult::success( $result, 'Sessão renovada com sucesso.' );
            } else {
                return ServiceResult::error( OperationStatus::ERROR, 'Falha ao renovar sessão.' );
            }
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao renovar sessão: ' . $e->getMessage() );
        }
    }

    /**
     * Desativa todas as sessões de um usuário
     *
     * @param int $userId ID do usuário
     * @return ServiceResult Resultado da operação
     */
    public function deactivateAllUserSessions( int $userId ): ServiceResult
    {
        try {
            $result = $this->sessionRepository->deactivateAllUserSessions( $userId );

            return ServiceResult::success(
                [ 'affected_sessions' => $result ],
                "Todas as sessões do usuário foram desativadas. Total: {$result}",
            );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao desativar sessões: ' . $e->getMessage() );
        }
    }

    /**
     * Limpa sessões expiradas do sistema
     *
     * @return ServiceResult Resultado da operação
     */
    public function cleanupExpiredSessions(): ServiceResult
    {
        try {
            $result = $this->sessionRepository->cleanupExpiredSessions();

            return ServiceResult::success(
                [ 'cleaned_sessions' => $result ],
                "Sessões expiradas limpas. Total: {$result}",
            );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao limpar sessões: ' . $e->getMessage() );
        }
    }

    /**
     * Atualiza a última atividade de uma sessão
     *
     * @param int $sessionId ID da sessão
     * @return ServiceResult Resultado da operação
     */
    public function updateLastActivity( int $sessionId ): ServiceResult
    {
        try {
            $result = $this->sessionRepository->updateLastActivity( $sessionId );

            if ( $result ) {
                return ServiceResult::success( null, 'Última atividade atualizada.' );
            } else {
                return ServiceResult::error( OperationStatus::ERROR, 'Falha ao atualizar última atividade.' );
            }
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao atualizar atividade: ' . $e->getMessage() );
        }
    }

    /**
     * Conta sessões ativas de um usuário
     *
     * @param int $userId ID do usuário
     * @return ServiceResult Resultado da operação
     */
    public function countActiveUserSessions( int $userId ): ServiceResult
    {
        try {
            $count = $this->sessionRepository->countActiveSessionsByUserId( $userId );

            return ServiceResult::success(
                [ 'active_sessions' => $count ],
                "Usuário possui {$count} sessões ativas.",
            );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao contar sessões: ' . $e->getMessage() );
        }
    }

    /**
     * Valida dados de entrada para criação/atualização de sessão
     *
     * @param array<string, mixed> $data Dados para validação
     * @param bool $isUpdate Indica se é uma operação de atualização
     * @return ServiceResult Resultado da validação
     */
    public function validate( array $data, bool $isUpdate = false ): ServiceResult
    {
        $errors = [];

        // Validar campos obrigatórios para criação
        if ( !$isUpdate ) {
            if ( empty( $data[ 'user_id' ] ) ) {
                $errors[] = 'ID do usuário é obrigatório.';
            }

            if ( !is_numeric( $data[ 'user_id' ] ) || $data[ 'user_id' ] <= 0 ) {
                $errors[] = 'ID do usuário deve ser um número válido.';
            }
        }

        // Validar IP address se fornecido
        if ( !empty( $data[ 'ip_address' ] ) && !filter_var( $data[ 'ip_address' ], FILTER_VALIDATE_IP ) ) {
            $errors[] = 'Endereço IP inválido.';
        }

        // Validar user agent se fornecido
        if ( !empty( $data[ 'user_agent' ] ) && strlen( $data[ 'user_agent' ] ) > 500 ) {
            $errors[] = 'User Agent muito longo (máximo 500 caracteres).';
        }

        // Validar horas de expiração
        if ( isset( $data[ 'expiration_hours' ] ) ) {
            if ( !is_numeric( $data[ 'expiration_hours' ] ) || $data[ 'expiration_hours' ] <= 0 ) {
                $errors[] = 'Horas de expiração devem ser um número positivo.';
            }

            if ( $data[ 'expiration_hours' ] > 8760 ) { // 1 ano
                $errors[] = 'Horas de expiração não podem exceder 1 ano (8760 horas).';
            }
        }

        // Validar dados de sessão se fornecidos
        if ( isset( $data[ 'session_data' ] ) && !is_array( $data[ 'session_data' ] ) ) {
            $errors[] = 'Dados de sessão devem ser um array.';
        }

        // Retornar erro se houver problemas de validação
        if ( !empty( $errors ) ) {
            return ServiceResult::error( OperationStatus::INVALID_DATA, implode( ', ', $errors ) );
        }

        return ServiceResult::success( null, 'Dados válidos.' );
    }

    /**
     * Gera um token único para a sessão
     *
     * @return string Token da sessão
     */
    private function generateSessionToken(): string
    {
        // Gerar token seguro de 64 caracteres
        return bin2hex( random_bytes( 32 ) );
    }

}
