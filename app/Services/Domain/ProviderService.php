<?php

declare(strict_types=1);

namespace App\Services\Domain;

use App\Models\Provider;
use App\Models\User;
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
        parent::__construct( $providerRepository );
    }

    /**
     * Busca Provider por user_id com relacionamentos.
     */
    public function getByUserId( int $userId, int $tenantId ): ?Provider
    {
        return $this->providerRepository->findByUserIdAndTenant( $userId, $tenantId );
    }

    /**
     * Verifica se email está disponível (não usado por outro usuário).
     */
    public function isEmailAvailable( string $email, int $excludeUserId, int $tenantId ): bool
    {
        return $this->providerRepository->isEmailAvailable( $email, $excludeUserId, $tenantId );
    }

    /**
     * Busca Provider com todos os relacionamentos carregados.
     */
    public function getWithRelations( int $providerId ): ServiceResult
    {
        $provider = $this->providerRepository->findWithRelations( $providerId, [
            'user', 'commonData', 'contact', 'address'
        ] );

        if ( !$provider ) {
            return $this->error( 'Provider não encontrado' );
        }

        return $this->success( $provider );
    }

}
