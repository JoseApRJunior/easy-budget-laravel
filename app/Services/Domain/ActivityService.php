<?php

declare(strict_types=1);

namespace App\Services\Domain;

use App\Enums\OperationStatus;
use App\Repositories\Contracts\BaseRepositoryInterface;
use App\Services\Core\Abstracts\AbstractBaseService;
use App\Support\ServiceResult;
use Exception;
use Illuminate\Support\Facades\Auth;

/**
 * Serviço para gerenciamento de atividades/auditoria do sistema.
 *
 * Este serviço gerencia logs de atividades e auditoria, fornecendo
 * funcionalidades para rastrear ações dos usuários no sistema.
 */
class ActivityService extends AbstractBaseService
{
    /**
     * Construtor do serviço de atividades.
     */
    public function __construct( BaseRepositoryInterface $repository )
    {
        parent::__construct( $repository );
    }

    /**
     * Retorna filtros suportados pelo serviço de atividades.
     */
    protected function getSupportedFilters(): array
    {
        return [
            'id',
            'tenant_id',
            'user_id',
            'action',
            'model_type',
            'model_id',
            'severity',
            'category',
            'created_at',
            'updated_at',
        ];
    }

    /**
     * Retorna campos ordenáveis para atividades.
     */
    protected function getSortableFields(): array
    {
        return [
            'id',
            'created_at',
            'updated_at',
            'action',
            'severity',
        ];
    }

    /**
     * Registra uma nova atividade no sistema.
     */
    public function logActivity(
        string $action,
        string $modelType = '',
        int $modelId = 0,
        string $description = '',
        string $severity = 'info',
        string $category = 'general',
        array $metadata = [],
    ): ServiceResult {
        try {
            $user = Auth::user();

            $activityData = [
                'tenant_id'   => $user?->tenant_id ?? 1,
                'user_id'     => $user?->id,
                'action'      => $action,
                'model_type'  => $modelType,
                'model_id'    => $modelId,
                'description' => $description,
                'severity'    => $severity,
                'category'    => $category,
                'metadata'    => $metadata,
                'ip_address'  => request()->ip(),
                'user_agent'  => request()->userAgent(),
            ];

            $activity = $this->create( $activityData );

            return $activity;
        } catch ( Exception $e ) {
            return $this->error(
                OperationStatus::ERROR,
                'Erro ao registrar atividade: ' . $e->getMessage(),
                null,
                $e,
            );
        }
    }

    /**
     * Busca atividades por tenant.
     */
    public function getActivitiesByTenant( int $tenantId, array $filters = [] ): ServiceResult
    {
        try {
            $filters[ 'tenant_id' ] = $tenantId;

            return $this->list( $filters );
        } catch ( Exception $e ) {
            return $this->error(
                OperationStatus::ERROR,
                'Erro ao buscar atividades do tenant: ' . $e->getMessage(),
                null,
                $e,
            );
        }
    }

    /**
     * Busca atividades por usuário.
     */
    public function getActivitiesByUser( int $userId, array $filters = [] ): ServiceResult
    {
        try {
            $filters[ 'user_id' ] = $userId;

            return $this->list( $filters );
        } catch ( Exception $e ) {
            return $this->error(
                OperationStatus::ERROR,
                'Erro ao buscar atividades do usuário: ' . $e->getMessage(),
                null,
                $e,
            );
        }
    }

    /**
     * Busca atividades por severidade.
     */
    public function getActivitiesBySeverity( string $severity, array $filters = [] ): ServiceResult
    {
        try {
            $filters[ 'severity' ] = $severity;

            return $this->list( $filters );
        } catch ( Exception $e ) {
            return $this->error(
                OperationStatus::ERROR,
                'Erro ao buscar atividades por severidade: ' . $e->getMessage(),
                null,
                $e,
            );
        }
    }

    /**
     * Obtém estatísticas de atividades.
     */
    public function getActivityStats( int $tenantId, array $filters = [] ): ServiceResult
    {
        try {
            $baseFilters = [ 'tenant_id' => $tenantId ];
            $allFilters  = array_merge( $baseFilters, $filters );

            return $this->getStats( $allFilters );
        } catch ( Exception $e ) {
            return $this->error(
                OperationStatus::ERROR,
                'Erro ao obter estatísticas de atividades: ' . $e->getMessage(),
                null,
                $e,
            );
        }
    }

}
