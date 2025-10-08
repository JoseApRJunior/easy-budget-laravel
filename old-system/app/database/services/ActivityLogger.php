<?php

namespace app\database\services;

use core\library\Session;
use Doctrine\DBAL\Connection;

class ActivityLogger
{

    /**
     * Summary of table
     * @var string
     */
    protected string $table = 'activities';

    public function __construct(
        private readonly Connection $connection,

    ) {}

    public function logActivity( $tenantId, $userId, $actionType, $entityType, $entityId, $description, $metadata = [] )
    {
        return $this->connection->createQueryBuilder()
            ->insert( $this->table )
            ->values( [
                'tenant_id'   => ':tenant_id',
                'user_id'     => ':user_id',
                'action_type' => ':action_type',
                'entity_type' => ':entity_type',
                'entity_id'   => ':entity_id',
                'description' => ':description',
                'metadata'    => ':metadata'
            ] )
            ->setParameters( [
                'tenant_id'   => $tenantId,
                'user_id'     => $userId,
                'action_type' => $actionType,
                'entity_type' => $entityType,
                'entity_id'   => $entityId,
                'description' => $description,
                'metadata'    => json_encode( $metadata )
            ] )
            ->executeQuery();
    }

    public function getRecentActivities( $tenantId, $limit = 5 )
    {
        return $this->connection->createQueryBuilder()
            ->select(
                'a.id',
                'a.action_type',
                'a.entity_type',
                'a.entity_id',
                'a.description',
                'a.created_at',
                'concat( cd.first_name, " ", cd.last_name ) as user_name',
                'a.metadata',
            )
            ->from( $this->table, 'a' )
            ->join( 'a', 'providers', 'p', 'a.user_id = p.user_id and a.tenant_id = p.tenant_id' )
            ->join( 'p', 'common_datas', 'cd', 'p.common_data_id = cd.id and p.tenant_id = cd.tenant_id' )
            ->where( 'a.tenant_id = :tenant_id' )
            ->setParameter( 'tenant_id', $tenantId )
            ->orderBy( 'a.created_at', 'DESC' )
            ->setMaxResults( $limit )
            ->executeQuery()
            ->fetchAllAssociative();
    }

}
