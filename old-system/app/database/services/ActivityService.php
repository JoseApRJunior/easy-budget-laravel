<?php

namespace app\database\services;

use app\database\entities\ActivityEntity;
use app\database\models\Activity;

class ActivityService
{
    /**
     * Summary of table
     * @var string
     */
    protected string $table = 'activities';

    public function __construct(
        private Activity $activity,
    ) {
    }

    public function logActivity($tenant_id, $user_id, $action_type, $entity_type, $entity_id, $description, $metadata)
    {
        // Activity
        $properties = getConstructorProperties(ActivityEntity::class);
        $properties[ 'tenant_id' ] = $tenant_id;
        $properties[ 'user_id' ] = $user_id;
        $properties[ 'action_type' ] = $action_type;
        $properties[ 'entity_type' ] = $entity_type;
        $properties[ 'entity_id' ] = $entity_id;
        $properties[ 'description' ] = $description;
        $properties[ 'metadata' ] = json_encode($metadata);
        // popula model ActivityEntity
        $entity = ActivityEntity::create(removeUnnecessaryIndexes(
            $properties,
            [ 'id', 'created_at' ],
            [],
        ));

        return $this->activity->create($entity);

    }

}
