<?php

namespace app\database\services;

use app\database\entitiesORM\ActivityEntity;
use app\database\modelsORM\Activity;
use app\interfaces\EntityORMInterface;
use JsonSerializable;

class ActivityService
{
    /**
     * Summary of table
     * @var string
     */
    protected string $table = 'activities';

    public function __construct(
        private Activity $activity,
    ) {}

    /**
     * Sanitiza metadados convertendo entidades ORM para arrays serializáveis.
     *
     * Este método percorre recursivamente os metadados e converte:
     * - Entidades ORM que implementam JsonSerializable
     * - Entidades ORM que implementam EntityORMInterface
     * - Arrays aninhados contendo entidades
     *
     * @param array<string, mixed> $metadata Metadados a serem sanitizados
     * @return array<string, mixed> Metadados sanitizados
     */
    private function sanitizeMetadata( array $metadata ): array
    {
        $sanitized = [];

        foreach ( $metadata as $key => $value ) {
            if ( $value instanceof JsonSerializable ) {
                // Se a entidade implementa JsonSerializable, usa o método jsonSerialize
                $sanitized[ $key ] = $value->jsonSerialize();
            } elseif ( $value instanceof EntityORMInterface ) {
                // Se é uma entidade ORM mas não implementa JsonSerializable,
                // converte para array básico (fallback)
                $sanitized[ $key ] = [ 
                    'entity_type' => get_class( $value ),
                    'entity_data' => 'Entity serialization not implemented'
                ];
            } elseif ( is_array( $value ) ) {
                // Se é um array, sanitiza recursivamente
                $sanitized[ $key ] = $this->sanitizeMetadata( $value );
            } else {
                // Para outros tipos, mantém o valor original
                $sanitized[ $key ] = $value;
            }
        }

        return $sanitized;
    }

    /**
     * Registra uma atividade no sistema.
     *
     * @param int $tenant_id ID do tenant
     * @param int $user_id ID do usuário
     * @param string $action_type Tipo da ação
     * @param string $entity_type Tipo da entidade
     * @param int $entity_id ID da entidade
     * @param string $description Descrição da atividade
     * @param array<string, mixed> $metadata Metadados da atividade
     * @return array<string, mixed> Resultado da operação
     */
    public function logActivity( int $tenant_id, int $user_id, string $action_type, string $entity_type, int $entity_id, string $description, array $metadata ): array
    {
        // Sanitizar metadados para evitar problemas de serialização
        $sanitizedMetadata = $this->sanitizeMetadata( $metadata );

        // Activity
        $properties                  = getConstructorProperties( ActivityEntity::class);
        $properties[ 'tenant_id' ]   = $tenant_id;
        $properties[ 'user_id' ]     = $user_id;
        $properties[ 'action_type' ] = $action_type;
        $properties[ 'entity_type' ] = $entity_type;
        $properties[ 'entity_id' ]   = $entity_id;
        $properties[ 'description' ] = $description;
        $properties[ 'metadata' ]    = json_encode( $sanitizedMetadata );
        // popula model ActivityEntity
        $entity = ActivityEntity::create( removeUnnecessaryIndexes(
            $properties,
            [ 'id', 'created_at' ],
            [],
        ) );

        return $this->activity->create( $entity );

    }

}