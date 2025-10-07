<?php

declare(strict_types=1);

namespace app\database\servicesORM;

use app\database\entitiesORM\UserConfirmationTokenEntity;
use app\database\entitiesORM\UserEntity;
use app\database\repositories\UserConfirmationTokenRepository;
use app\database\repositories\UserRepository;
use app\enums\OperationStatus;
use app\interfaces\ServiceInterface;
use app\support\ServiceResult;
use core\dbal\EntityNotFound;
use core\library\Session;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use RuntimeException;

class UserService implements ServiceInterface
{
    private mixed $authenticated;

    public function __construct(
        private UserRepository $userRepository,
        private UserConfirmationTokenRepository $userConfirmationTokenRepository,
        private EntityManagerInterface $entityManager,
    ) {
        if (Session::has('auth')) {
            $this->authenticated = Session::get('auth');
        }
    }

    /**
     * Cria um novo usuário.
     *
     * @param array $data Dados do usuário
     * @return ServiceResult Resultado da operação
     */
    public function create(array $data): ServiceResult
    {
        try {
            return $this->entityManager->transactional(function () use ($data) {
                $tenantId = $this->authenticated->tenant_id ?? 1; // Fallback se não autenticado

                // Validar dados
                if (empty($data['email']) || empty($data['password'])) {
                    return ServiceResult::error(OperationStatus::INVALID_DATA, 'Email e senha são obrigatórios.');
                }

                // Verificar se email já existe
                $existingUser = $this->userRepository->findByEmailAndTenantId($data['email'], $tenantId);
                if ($existingUser !== null) {
                    return ServiceResult::error(OperationStatus::CONFLICT, 'Email já cadastrado.');
                }

                $result = false;
                $createdUser = null;
                $createdToken = null;

                // Criar usuário
                $userEntity = new UserEntity(
                    $data['email'],
                    password_hash($data['password'], PASSWORD_DEFAULT),
                    $tenantId,
                    $data['is_active'] ?? false
                );

                $saveResult = $this->userRepository->save($userEntity, $tenantId);
                if ($saveResult !== null) {
                    $createdUser = $userEntity;

                    // Criar token de confirmação
                    $rawToken = bin2hex(random_bytes(32));
                    $hashedToken = hash('sha256', $rawToken);

                    $tokenEntity = new UserConfirmationTokenEntity(
                        $userEntity->getId(),
                        $tenantId,
                        $hashedToken,
                        (new DateTimeImmutable())->modify('+7 days')
                    );

                    $tokenResult = $this->userConfirmationTokenRepository->save($tokenEntity, $tenantId);
                    if ($tokenResult !== null) {
                        $createdToken = [
                            'id' => $tokenResult->getId(),
                            'raw_token' => $rawToken, // Para envio de email
                            'hashed_token' => $hashedToken
                        ];
                    }
                }

                if ($createdUser !== null && $createdToken !== null) {
                    return ServiceResult::success([
                        'user' => $createdUser,
                        'token' => $createdToken
                    ], 'Usuário criado com sucesso.');
                }

                return ServiceResult::error(OperationStatus::ERROR, 'Falha ao criar usuário.');
            });

        } catch (Exception $e) {
            return ServiceResult::error(OperationStatus::ERROR, 'Falha ao criar usuário: ' . $e->getMessage());
        }
    }

    /**
     * Atualiza um usuário existente.
     *
     * @param int $id ID do usuário
     * @param array $data Dados para atualização
     * @return ServiceResult Resultado da operação
     */
    public function update(int $id, array $data): ServiceResult
    {
        try {
            $tenantId = $this->authenticated->tenant_id ?? 1;

            return $this->entityManager->transactional(function () use ($id, $data, $tenantId) {
                $user = $this->userRepository->findByIdAndTenantId($id, $tenantId);

                if ($user instanceof EntityNotFound || $user === null) {
                    return ServiceResult::error(OperationStatus::NOT_FOUND, 'Usuário não encontrado.');
                }

                // Atualizar senha se fornecida
                if (isset($data['password']) && !empty($data['password'])) {
                    $user->setPassword(password_hash($data['password'], PASSWORD_DEFAULT));
                }

                if (isset($data['is_active'])) {
                    $user->setIsActive((bool) $data['is_active']);
                }

                $user->setUpdatedAt(new DateTimeImmutable());

                $result = $this->userRepository->save($user, $tenantId);

                if ($result !== null) {
                    return ServiceResult::success($user, 'Usuário atualizado com sucesso.');
                }

                return ServiceResult::error(OperationStatus::ERROR, 'Falha ao atualizar usuário.');
            });

        } catch (Exception $e) {
            return ServiceResult::error(OperationStatus::ERROR, 'Falha ao atualizar usuário: ' . $e->getMessage());
        }
    }

    /**
     * Remove um usuário.
     *
     * @param int $id ID do usuário
     * @return ServiceResult Resultado da operação
     */
    public function delete(int $id): ServiceResult
    {
        try {
            $tenantId = $this->authenticated->tenant_id ?? 1;

            $user = $this->userRepository->findByIdAndTenantId($id, $tenantId);

            if ($user instanceof EntityNotFound || $user === null) {
                return ServiceResult::error(OperationStatus::NOT_FOUND, 'Usuário não encontrado.');
            }

            $result = $this->userRepository->deleteByIdAndTenantId($id, $tenantId);

            if ($result) {
                return ServiceResult::success(null, 'Usuário removido com sucesso.');
            }

            return ServiceResult::error(OperationStatus::ERROR, 'Falha ao remover usuário.');

        } catch (Exception $e) {
            return ServiceResult::error(OperationStatus::ERROR, 'Falha ao remover usuário: ' . $e->getMessage());
        }
    }

    /**
     * Lista usuários com filtros opcionais.
     *
     * @param array $filters Filtros para busca
     * @return ServiceResult Resultado da operação
     */
    public function list(array $filters = []): ServiceResult
    {
        try {
            $tenantId = $this->authenticated->tenant_id ?? 1;
            $criteria = [];
            $orderBy = ['createdAt' => 'DESC'];
            $limit = $filters['limit'] ?? null;
            $offset = $filters['offset'] ?? null;

            $entities = $this->userRepository->findAllByTenantId($tenantId, $criteria, $orderBy, $limit, $offset);

            return ServiceResult::success($entities, 'Usuários listados com sucesso.');

        } catch (Exception $e) {
            return ServiceResult::error(OperationStatus::ERROR, 'Falha ao listar usuários: ' . $e->getMessage());
        }
    }

    /**
     * Confirma conta de usuário via token.
     *
     * @param string $token Token de confirmação
     * @return ServiceResult Resultado da operação
     */
    public function confirmAccount(string $token): ServiceResult
    {
        try {
            $tenantId = $this->authenticated->tenant_id ?? 1;
            $hashedToken = hash('sha256', $token);

            $tokenEntity = $this->userConfirmationTokenRepository->findByTokenAndTenantId($hashedToken, $tenantId);

            if ($tokenEntity instanceof EntityNotFound || $tokenEntity === null) {
                return ServiceResult::error(OperationStatus::NOT_FOUND, 'Token inválido ou expirado.');
            }

            if ($tokenEntity->getExpiresAt() < new DateTimeImmutable()) {
                // Opcional: deletar token expirado
                $this->userConfirmationTokenRepository->deleteByIdAndTenantId($tokenEntity->getId(), $tenantId);
                return ServiceResult::error(OperationStatus::EXPIRED, 'Token expirado.');
            }

            $user = $this->userRepository->findByIdAndTenantId($tokenEntity->getUserId(), $tenantId);
            if ($user instanceof EntityNotFound || $user === null) {
                return ServiceResult::error(OperationStatus::NOT_FOUND, 'Usuário não encontrado.');
            }

            $user->setIsActive(true);
            $this->userRepository->save($user, $tenantId);

            // Invalidar token
            $this->userConfirmationTokenRepository->deleteByIdAndTenantId($tokenEntity->getId(), $tenantId);

            return ServiceResult::success($user, 'Conta confirmada com sucesso.');

        } catch (Exception $e) {
            return ServiceResult::error(OperationStatus::ERROR, 'Falha ao confirmar conta: ' . $e->getMessage());
        }
    }
}
