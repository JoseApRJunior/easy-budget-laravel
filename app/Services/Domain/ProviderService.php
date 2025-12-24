<?php

declare(strict_types=1);

namespace App\Services\Domain;

use App\Models\Provider;
use App\Models\User;
use App\Repositories\ProviderRepository;
use App\Services\Core\Abstracts\AbstractBaseService;
use App\Support\ServiceResult;
use App\DTOs\Provider\ProviderDTO;

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
        parent::__construct( $providerRepository );
    }

    /**
     * Busca Provider por user_id com relacionamentos.
     */
    public function getByUserId( int $userId, ?int $tenantId = null ): ?Provider
    {
        $tenantId = $tenantId ?? $this->ensureTenantId();
        return $this->providerRepository->findByUserIdAndTenant( $userId, $tenantId );
    }

    /**
     * Verifica se email está disponível (não usado por outro usuário).
     */
    public function isEmailAvailable( string $email, int $excludeUserId, ?int $tenantId = null ): bool
    {
        $tenantId = $tenantId ?? $this->ensureTenantId();
        return $this->providerRepository->isEmailAvailable( $email, $excludeUserId, $tenantId );
    }

    /**
     * Busca Provider com todos os relacionamentos carregados.
     */
    public function getWithRelations( int $providerId ): ServiceResult
    {
        return $this->safeExecute(function () use ($providerId) {
            $provider = $this->providerRepository->findWithRelations( $providerId, [
                'user', 'commonData', 'contact', 'address', 'businessData'
            ] );

            if ( !$provider ) {
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
            $data = $dto->toArray();
            if (!isset($data['tenant_id'])) {
                $data['tenant_id'] = $this->ensureTenantId();
            }
            
            $provider = $this->providerRepository->create($data);
            return ServiceResult::success($provider, 'Provider criado com sucesso.');
        }, 'Erro ao criar provider.');
    }
}
