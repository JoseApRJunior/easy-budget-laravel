<?php

declare(strict_types=1);

namespace App\Services\Domain;

use App\DTOs\Provider\ProviderDTO;
use App\Models\Provider;
use App\Repositories\ProviderRepository;
use App\Services\Core\Abstracts\AbstractBaseService;
use App\Support\ServiceResult;

/**
 * Service de domínio para operações CRUD do Provider.
 *
 * Responsável por:
 * - CRUD básico da entidade Provider
 * - Validações de domínio específicas
 * - Regras de negócio puras do Provider
 */
class ProviderService extends AbstractBaseService
{
    public function __construct(
        private ProviderRepository $providerRepository,
    ) {
        parent::__construct($providerRepository);
    }

    /**
     * Busca Provider por user_id com relacionamentos.
     */
    public function getByUserId(int $userId): ?Provider
    {
        try {
            return $this->providerRepository->findByUserIdWithRelations($userId);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Verifica se email está disponível (não usado por outro usuário).
     */
    public function isEmailAvailable(string $email, int $excludeUserId): bool
    {
        try {
            return $this->providerRepository->isEmailAvailable($email, $excludeUserId);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Busca Provider com todos os relacionamentos carregados.
     */
    public function getWithRelations(int $providerId): ServiceResult
    {
        return $this->safeExecute(function () use ($providerId) {
            $provider = $this->providerRepository->findWithRelations($providerId, [
                'user',
                'commonData',
                'contact',
                'address',
                'businessData',
            ]);

            if (! $provider) {
                return ServiceResult::error(\App\Enums\OperationStatus::NOT_FOUND, 'Provider não encontrado');
            }

            return ServiceResult::success($provider);
        }, 'Erro ao buscar provider com relações.');
    }

    /**
     * Cria um novo provider.
     */
    public function createProvider(ProviderDTO $dto): ServiceResult
    {
        return $this->safeExecute(function () use ($dto) {
            $provider = $this->providerRepository->createFromDTO($dto);

            return ServiceResult::success($provider, 'Provider criado com sucesso.');
        }, 'Erro ao criar provider.');
    }

    /**
     * Atualiza um provider existente.
     */
    public function updateProvider(int $id, ProviderDTO $dto): ServiceResult
    {
        return $this->safeExecute(function () use ($id, $dto) {
            $provider = $this->providerRepository->updateFromDTO($id, $dto);

            if (! $provider) {
                return ServiceResult::error(\App\Enums\OperationStatus::NOT_FOUND, 'Provider não encontrado para atualização.');
            }

            return ServiceResult::success($provider, 'Provider atualizado com sucesso.');
        }, 'Erro ao atualizar provider.');
    }

    /**
     * Obtém dados do dashboard para o provider.
     */
    public function getDashboardData(): ServiceResult
    {
        return $this->safeExecute(function () {
            $stats = $this->providerRepository->getDashboardStats();

            return ServiceResult::success($stats, 'Dados do dashboard obtidos com sucesso.');
        }, 'Erro ao obter dados do dashboard do provider.');
    }
}
