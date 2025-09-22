<?php

declare(strict_types=1);

namespace app\database\servicesORM;

use app\database\entitiesORM\ProviderEntity;
use app\database\repositories\ProviderRepository;
use app\enums\OperationStatus;
use app\interfaces\ServiceInterface;
use app\support\ServiceResult;
use Exception;

/**
 * Service para gerenciar operações relacionadas aos providers.
 * 
 * Este service encapsula toda a lógica de negócio relacionada aos providers,
 * seguindo o padrão arquitetural ServiceInterface → Repository → Entity.
 */
class ProviderService implements ServiceInterface
{
    public function __construct(
        private ProviderRepository $providerRepository
    ) {}

    public function getByIdAndTenantId(int $id, int $tenant_id): ServiceResult
    {
        try {
            $provider = $this->providerRepository->findByIdAndTenantId($id, $tenant_id);
            
            if (!$provider) {
                return ServiceResult::error(OperationStatus::NOT_FOUND, 'Provider não encontrado');
            }

            return ServiceResult::success($provider, 'Provider encontrado com sucesso');
        } catch (Exception $e) {
            return ServiceResult::error(OperationStatus::ERROR, 'Erro ao buscar provider: ' . $e->getMessage());
        }
    }

    public function listByTenantId(int $tenant_id, array $filters = []): ServiceResult
    {
        try {
            $providers = $this->providerRepository->findAllByTenantId($tenant_id, $filters);
            return ServiceResult::success($providers, 'Providers listados com sucesso');
        } catch (Exception $e) {
            return ServiceResult::error(OperationStatus::ERROR, 'Erro ao listar providers: ' . $e->getMessage());
        }
    }

    public function createByTenantId(array $data, int $tenant_id): ServiceResult
    {
        try {
            $provider = ProviderEntity::create($data);
            $createdProvider = $this->providerRepository->save($provider, $tenant_id);
            return ServiceResult::success($createdProvider, 'Provider criado com sucesso');
        } catch (Exception $e) {
            return ServiceResult::error(OperationStatus::ERROR, 'Erro ao criar provider: ' . $e->getMessage());
        }
    }

    public function updateByIdAndTenantId(int $id, int $tenant_id, array $data): ServiceResult
    {
        try {
            $provider = $this->providerRepository->findByIdAndTenantId($id, $tenant_id);
            
            if (!$provider) {
                return ServiceResult::error(OperationStatus::NOT_FOUND, 'Provider não encontrado');
            }

            // Atualizar dados do provider
            foreach ($data as $key => $value) {
                $setter = 'set' . ucfirst($key);
                if (method_exists($provider, $setter)) {
                    $provider->$setter($value);
                }
            }

            $updatedProvider = $this->providerRepository->save($provider, $tenant_id);
            return ServiceResult::success($updatedProvider, 'Provider atualizado com sucesso');
        } catch (Exception $e) {
            return ServiceResult::error(OperationStatus::ERROR, 'Erro ao atualizar provider: ' . $e->getMessage());
        }
    }

    public function deleteByIdAndTenantId(int $id, int $tenant_id): ServiceResult
    {
        try {
            $deleted = $this->providerRepository->deleteByIdAndTenantId($id, $tenant_id);

            if (!$deleted) {
                return ServiceResult::error(OperationStatus::NOT_FOUND, 'Provider não encontrado');
            }

            return ServiceResult::success(null, 'Provider removido com sucesso');
        } catch (Exception $e) {
            return ServiceResult::error(OperationStatus::ERROR, 'Erro ao remover provider: ' . $e->getMessage());
        }
    }

    public function validate(array $data, bool $isUpdate = false): ServiceResult
    {
        $errors = [];

        if (!$isUpdate && empty($data['user_id'])) {
            $errors[] = 'User ID é obrigatório';
        }

        if (!$isUpdate && empty($data['tenant_id'])) {
            $errors[] = 'Tenant ID é obrigatório';
        }

        if (!empty($errors)) {
            return ServiceResult::error(OperationStatus::VALIDATION, implode(', ', $errors));
        }

        return ServiceResult::success(null, 'Dados válidos');
    }

    // Métodos específicos do ProviderService
    public function findProviderFullByUserId(int $userId, int $tenantId): ServiceResult
    {
        try {
            $provider = $this->providerRepository->findProviderFullByUserId($userId, $tenantId);
            
            if (!$provider) {
                return ServiceResult::error(OperationStatus::NOT_FOUND, 'Provider não encontrado');
            }

            return ServiceResult::success($provider, 'Provider encontrado com sucesso');
        } catch (Exception $e) {
            return ServiceResult::error(OperationStatus::ERROR, 'Erro ao buscar provider: ' . $e->getMessage());
        }
    }

    public function findProviderFullByEmail(string $email): ServiceResult
    {
        try {
            $provider = $this->providerRepository->findProviderFullByEmail($email);
            
            if (!$provider) {
                return ServiceResult::error(OperationStatus::NOT_FOUND, 'Provider não encontrado');
            }

            return ServiceResult::success($provider, 'Provider encontrado com sucesso');
        } catch (Exception $e) {
            return ServiceResult::error(OperationStatus::ERROR, 'Erro ao buscar provider: ' . $e->getMessage());
        }
    }

    public function update(ProviderEntity $provider, int $tenantId): ServiceResult
    {
        try {
            $existingProvider = $this->providerRepository->findByIdAndTenantId(
                $provider->getId(), 
                $tenantId
            );

            if (!$existingProvider) {
                return ServiceResult::error(OperationStatus::NOT_FOUND, 'Provider não encontrado');
            }

            $updatedProvider = $this->providerRepository->save($provider, $tenantId);
            return ServiceResult::success($updatedProvider, 'Provider atualizado com sucesso');
        } catch (Exception $e) {
            return ServiceResult::error(OperationStatus::ERROR, 'Erro ao atualizar provider: ' . $e->getMessage());
        }
    }

    public function findByIdAndTenantId(int $id, int $tenant_id): ServiceResult
    {
        return $this->getByIdAndTenantId($id, $tenant_id);
    }
}