<?php

declare(strict_types=1);

namespace app\database\servicesORM;

use app\database\entitiesORM\AlertSettingsEntity;
use app\database\repositories\AlertSettingsRepository;
use app\enums\OperationStatus;
use app\interfaces\ServiceNoTenantInterface;
use app\support\ServiceResult;
use Exception;

/**
 * Service para gerenciar configurações de alertas
 */
class AlertSettingsService implements ServiceNoTenantInterface
{
    private AlertSettingsRepository $repository;

    public function __construct(
        AlertSettingsRepository $repository,
    ) {
        $this->repository = $repository;
    }

    /**
     * Salva ou atualiza as configurações de alertas do sistema.
     */
    public function saveSettings( array $settings ): ServiceResult
    {
        $validationResult = $this->validate( $settings );
        if ( !$validationResult->isSuccess() ) {
            return $validationResult;
        }

        try {
            $entity = $this->repository->findOneBy( [] );

            if ( $entity === null ) {
                $entity = new AlertSettingsEntity();
            }
            /** @var AlertSettingsEntity $entity */
            $entity->setSettings( $settings );
            $this->repository->save( $entity );

            return ServiceResult::success( $entity, 'Configurações salvas com sucesso' );

        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao salvar configurações: ' . $e->getMessage() );
        }
    }

    /**
     * Busca as configurações de alertas do sistema.
     */
    public function getSettings(): ServiceResult
    {
        try {
            $entity = $this->repository->findOneBy( [] );

            if ( !$entity ) {
                $defaultSettings = $this->getDefaultSettings();
                return ServiceResult::success( $defaultSettings, 'Configurações padrão carregadas' );
            }

            return ServiceResult::success( $entity->getSettings(), 'Configurações carregadas' );

        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao carregar configurações: ' . $e->getMessage() );
        }
    }

    public function getById( int $id ): ServiceResult
    {
        return ServiceResult::error( OperationStatus::NOT_SUPPORTED, 'Operação não suportada para este serviço.' );
    }

    public function list( array $params = [] ): ServiceResult
    {
        return ServiceResult::error( OperationStatus::NOT_SUPPORTED, 'Operação não suportada para este serviço.' );
    }

    public function create( array $data ): ServiceResult
    {
        return ServiceResult::error( OperationStatus::NOT_SUPPORTED, 'Use saveSettings para criar ou atualizar configurações.' );
    }

    public function update( int $id, array $data ): ServiceResult
    {
        return ServiceResult::error( OperationStatus::NOT_SUPPORTED, 'Use saveSettings para criar ou atualizar configurações.' );
    }

    public function delete( int $id ): ServiceResult
    {
        return ServiceResult::error( OperationStatus::NOT_SUPPORTED, 'Operação não suportada para este serviço.' );
    }

    public function validate( array $data, bool $isUpdate = false ): ServiceResult
    {
        $requiredKeys = [ 'thresholds', 'notifications', 'monitoring', 'interface' ];
        $errors       = [];

        foreach ( $requiredKeys as $key ) {
            if ( !array_key_exists( $key, $data ) ) {
                $errors[] = "A chave '{$key}' é obrigatória.";
            }
        }

        if ( !empty( $errors ) ) {
            return ServiceResult::error( OperationStatus::INVALID_DATA, implode( ' ', $errors ) );
        }

        return ServiceResult::success( null, "Dados válidos." );
    }

    /**
     * Retorna configurações padrão
     */
    public function getDefaultSettings(): array
    {
        return [ 
            'thresholds'    => [ 
                'critical_success_rate'  => 90,
                'warning_success_rate'   => 95,
                'critical_response_time' => 200,
                'warning_response_time'  => 100,
                'max_memory_mb'          => 512,
                'max_cpu_percent'        => 80
            ],
            'notifications' => [ 
                'email_enabled'   => true,
                'email_addresses' => '',
                'webhook_enabled' => false,
                'webhook_url'     => '',
                'slack_enabled'   => false,
                'slack_webhook'   => ''
            ],
            'monitoring'    => [ 
                'check_interval'      => 5,
                'auto_resolve'        => true,
                'min_severity'        => 'WARNING',
                'enabled_middlewares' => [ 'auth', 'admin', 'user', 'provider', 'guest' ]
            ],
            'interface'     => [ 
                'auto_refresh' => 30,
                'theme'        => 'light',
                'timezone'     => 'America/Sao_Paulo'
            ]
        ];
    }

}
