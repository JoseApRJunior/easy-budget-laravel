<?php

namespace app\database\models;

use app\database\entities\ScheduleEntity;
use app\database\Model;
use core\dbal\Entity;
use core\dbal\EntityNotFound;
use Doctrine\DBAL\ParameterType;
use Exception;
use RuntimeException;

class Schedule extends Model
{
    /**
     * The name of the table associated with the model.
     *
     * @var string
     */
    protected string $table = 'schedules';

    protected static function createEntity(array $data): Entity
    {
        return ScheduleEntity::create($data);
    }

    /**
     * Retrieve an schedules by its ID and tenant ID.
     *
     * @param int $id The ID of the activities.
     * @param int $tenant_id The ID of the tenant.
     * @return ScheduleEntity|Entity The schedules entity or a generic entity.
     */
    public function getSchedulesById(int $id, int $tenant_id): ScheduleEntity|Entity
    {
        try {
            $result = $this->findBy([ 'id' => $id, 'tenant_id' => $tenant_id ]);

            return $result;
        } catch (Exception $e) {
            throw new RuntimeException("Falha ao buscar os dados de agendamentos, tente mais tarde ou entre em contato com suporte.", 0, $e);
        }
    }

    public function getLatestByServiceId(int $service_id, int $tenant_id): ScheduleEntity|Entity
    {
        try {

            $result = $this->findBy(
                [
                    'service_id' => $service_id,
                    'tenant_id' => $tenant_id,
                ],
                [
                    'start_date_time' => 'DESC',
                ],
                1,
            );

            return $result;
        } catch (Exception $e) {
            throw new RuntimeException("Falha ao buscar o último agendamento.", 0, $e);
        }
    }

    public function getLastSchedulingTokenByServiceId(int $service_id, int $tenant_id)
    {
        try {
            $result = $this->connection->createQueryBuilder()
                ->select(
                    's.id',
                    's.user_confirmation_token_id',
                    's.start_date_time',
                    's.end_date_time',
                    's.location',
                    'uct.token',
                    'uct.expires_at',
                )
                ->from($this->table, 's')
                ->join('s', 'user_confirmation_tokens', 'uct', 's.user_confirmation_token_id = uct.id')
                ->where('s.service_id = :service_id')
                ->andWhere('s.tenant_id = :tenant_id')
                ->setParameter('service_id', $service_id, ParameterType::INTEGER)
                ->setParameter('tenant_id', $tenant_id, ParameterType::INTEGER)
                ->orderBy('uct.created_at', 'DESC')
                ->setMaxResults(1)
                ->executeQuery()
                ->fetchAssociative();

            if (!$result) {
                return new EntityNotFound();
            }

            return (object) $result;
        } catch (Exception $e) {
            throw new RuntimeException("Falha ao buscar o token de agendamento.", 0, $e);
        }

    }

}
