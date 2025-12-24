<?php

declare(strict_types=1);

namespace App\Services\Domain;

use App\DTOs\Schedule\ScheduleDTO;
use App\DTOs\Schedule\ScheduleUpdateDTO;
use App\Enums\OperationStatus;
use App\Models\Schedule;
use App\Repositories\ScheduleRepository;
use App\Repositories\ServiceRepository;
use App\Services\Core\Abstracts\AbstractBaseService;
use App\Services\Core\Traits\HasSafeExecution;
use App\Services\Core\Traits\HasTenantIsolation;
use App\Support\ServiceResult;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ScheduleService extends AbstractBaseService
{
    use HasSafeExecution, HasTenantIsolation;

    public function __construct(
        private ScheduleRepository $scheduleRepository,
        private ServiceRepository $serviceRepository,
    ) {
        parent::__construct($scheduleRepository);
    }

    /**
     * Cria um novo agendamento com validações de conflito
     */
    public function createSchedule(ScheduleDTO $dto): ServiceResult
    {
        return $this->safeExecute(function () use ($dto) {
            $tenantId = $this->ensureTenantId();
            $data = $dto->toArray();

            // Verifica se o serviço existe e pertence ao tenant
            $service = $this->serviceRepository->findByIdAndTenant($dto->service_id, $tenantId);

            if (!$service) {
                return $this->error(OperationStatus::NOT_FOUND, 'Serviço não encontrado ou não pertence ao tenant atual.');
            }

            // Verifica conflitos de horário
            if ($this->scheduleRepository->hasConflict($dto->start_date_time, $dto->end_date_time, $dto->service_id)) {
                return $this->error(OperationStatus::CONFLICT, 'Conflito de horário detectado.');
            }

            $data['tenant_id'] = $tenantId;

            // Cria o agendamento
            return $this->repository->create($data);
        }, 'Erro ao criar agendamento.');
    }

    /**
     * Atualiza agendamento com validações
     */
    public function updateSchedule(int $scheduleId, ScheduleUpdateDTO $dto): ServiceResult
    {
        return $this->safeExecute(function () use ($scheduleId, $dto) {
            $tenantId = $this->ensureTenantId();

            // Verifica se o agendamento existe e pertence ao tenant
            $schedule = $this->scheduleRepository->findByIdAndTenant($scheduleId, $tenantId);

            if (!$schedule) {
                return $this->error(OperationStatus::NOT_FOUND, 'Agendamento não encontrado.');
            }

            $data = $dto->toArrayWithoutNulls();

            // Verifica conflitos se houver mudança de horário
            if (isset($data['start_date_time']) || isset($data['end_date_time'])) {
                $startTime = $data['start_date_time'] ?? $schedule->start_date_time->format('Y-m-d H:i:s');
                $endTime = $data['end_date_time'] ?? $schedule->end_date_time->format('Y-m-d H:i:s');

                if ($this->scheduleRepository->hasConflict($startTime, $endTime, $schedule->service_id, $scheduleId)) {
                    return $this->error(OperationStatus::CONFLICT, 'Conflito de horário detectado.');
                }
            }

            return $this->repository->update($scheduleId, $data);
        }, 'Erro ao atualizar agendamento.');
    }

    /**
     * Cancela agendamento
     */
    public function cancelSchedule(int $scheduleId, string $reason = null): ServiceResult
    {
        return $this->safeExecute(function () use ($scheduleId, $reason) {
            $tenantId = $this->ensureTenantId();
            $schedule = $this->scheduleRepository->findByIdAndTenant($scheduleId, $tenantId);

            if (!$schedule) {
                return $this->error(OperationStatus::NOT_FOUND, 'Agendamento não encontrado.');
            }

            // Verifica se já está cancelado
            if ($schedule->status === 'cancelled') {
                return $this->error(OperationStatus::CONFLICT, 'Agendamento já está cancelado.');
            }

            $updated = $this->repository->update($scheduleId, [
                'status'              => 'cancelled',
                'cancellation_reason' => $reason,
                'cancelled_at'        => now(),
            ]);

            return $this->success($updated->getData(), 'Agendamento cancelado com sucesso.');
        }, 'Erro ao cancelar agendamento.');
    }

    /**
     * Atualiza o status de um agendamento
     */
    public function updateScheduleStatus(int $id, string $status, ?int $userId = null): ServiceResult
    {
        return $this->safeExecute(function () use ($id, $status) {
            $tenantId = $this->ensureTenantId();
            $schedule = $this->scheduleRepository->findByIdAndTenant($id, $tenantId);

            if (!$schedule) {
                return $this->error(OperationStatus::NOT_FOUND, 'Agendamento não encontrado.');
            }

            $data = ['status' => $status];

            if ($status === 'confirmed') $data['confirmed_at'] = now();
            if ($status === 'completed') $data['completed_at'] = now();
            if ($status === 'no_show') $data['no_show_at'] = now();
            if ($status === 'cancelled') $data['cancelled_at'] = now();

            return $this->repository->update($id, $data);
        }, 'Erro ao atualizar status do agendamento.');
    }

    /**
     * Obtém calendário de disponibilidade
     */
    public function getAvailabilityCalendar(int $providerId, string $month): ServiceResult
    {
        return $this->safeExecute(function () use ($providerId, $month) {
            $startOfMonth = Carbon::parse($month)->startOfMonth();
            $endOfMonth = Carbon::parse($month)->endOfMonth();

            $schedules = $this->scheduleRepository->getByDateRange(
                $startOfMonth->toDateTimeString(),
                $endOfMonth->toDateTimeString()
            );

            // Lógica simplificada para o calendário
            $calendar = [];
            foreach ($schedules as $schedule) {
                $date = $schedule->start_date_time->toDateString();
                $calendar[$date][] = $schedule;
            }

            return $this->success($calendar);
        }, 'Erro ao obter calendário de disponibilidade.');
    }

    /**
     * Verifica disponibilidade
     */
    public function checkAvailability(int $providerId, string $date, int $durationMinutes = 60): ServiceResult
    {
        return $this->safeExecute(function () use ($providerId, $date, $durationMinutes) {
            // Lógica de verificação de disponibilidade (exemplo simplificado)
            return $this->success(['available' => true]);
        }, 'Erro ao verificar disponibilidade.');
    }

    /**
     * Obtém slots de horários disponíveis
     */
    public function getAvailableTimeSlots(int $providerId, string $date, int $durationMinutes = 60): ServiceResult
    {
        return $this->safeExecute(function () use ($providerId, $date, $durationMinutes) {
            // Lógica de geração de slots (exemplo simplificado)
            $slots = ['09:00', '10:00', '11:00', '14:00', '15:00', '16:00'];
            return $this->success($slots);
        }, 'Erro ao obter slots disponíveis.');
    }

    /**
     * Confirma agendamento
     */
    public function confirmSchedule(int $scheduleId): ServiceResult
    {
        return $this->safeExecute(function () use ($scheduleId) {
            $tenantId = $this->ensureTenantId();
            $schedule = $this->scheduleRepository->findByIdAndTenant($scheduleId, $tenantId);

            if (!$schedule) {
                return $this->error(OperationStatus::NOT_FOUND, 'Agendamento não encontrado.');
            }

            if ($schedule->status !== 'pending') {
                return $this->error(OperationStatus::CONFLICT, 'Apenas agendamentos pendentes podem ser confirmados.');
            }

            $updated = $this->repository->update($scheduleId, [
                'status'       => 'confirmed',
                'confirmed_at' => now(),
            ]);

            return $this->success($updated->getData(), 'Agendamento confirmado com sucesso.');
        }, 'Erro ao confirmar agendamento.');
    }

    /**
     * Lista agendamentos por período
     */
    public function getSchedulesByPeriod(Carbon $startDate, Carbon $endDate, array $filters = []): ServiceResult
    {
        return $this->safeExecute(function () use ($startDate, $endDate, $filters) {
            $this->ensureTenantId();
            $schedules = $this->scheduleRepository->getByDateRangeWithRelations(
                $startDate->toDateTimeString(),
                $endDate->toDateTimeString(),
                $filters
            );

            return $this->success($schedules, 'Agendamentos listados com sucesso.');
        }, 'Erro ao listar agendamentos.');
    }

    /**
     * Obtém estatísticas para o dashboard de agendamentos.
     */
    public function getDashboardStats(): ServiceResult
    {
        return $this->safeExecute(function () {
            $tenantId = $this->ensureTenantId();

            $total     = $this->scheduleRepository->getTotalCount($tenantId);
            $pending   = $this->scheduleRepository->getCountByStatus($tenantId, 'pending');
            $confirmed = $this->scheduleRepository->getCountByStatus($tenantId, 'confirmed');
            $completed = $this->scheduleRepository->getCountByStatus($tenantId, 'completed');
            $cancelled = $this->scheduleRepository->getCountByStatus($tenantId, 'cancelled');
            $noShow    = $this->scheduleRepository->getCountByStatus($tenantId, 'no_show');

            $upcomingCount  = $this->scheduleRepository->getUpcomingCount($tenantId);
            $recentUpcoming = $this->scheduleRepository->getRecentUpcoming($tenantId);

            $statusBreakdown = [
                'pending'   => ['count' => $pending, 'color' => '#F59E0B'],
                'confirmed' => ['count' => $confirmed, 'color' => '#3B82F6'],
                'completed' => ['count' => $completed, 'color' => '#10B981'],
                'cancelled' => ['count' => $cancelled, 'color' => '#EF4444'],
                'no_show'   => ['count' => $noShow, 'color' => '#6B7280'],
            ];

            return $this->success([
                'total_schedules'     => $total,
                'pending_schedules'   => $pending,
                'confirmed_schedules' => $confirmed,
                'completed_schedules' => $completed,
                'cancelled_schedules' => $cancelled,
                'no_show_schedules'   => $noShow,
                'upcoming_schedules'  => $upcomingCount,
                'status_breakdown'    => $statusBreakdown,
                'recent_upcoming'     => $recentUpcoming,
            ], 'Estatísticas carregadas com sucesso.');
        }, 'Erro ao carregar estatísticas do dashboard.');
    }

    /**
     * Busca um agendamento específico.
     */
    public function getSchedule(int $id): ServiceResult
    {
        return $this->safeExecute(function () use ($id) {
            $tenantId = $this->ensureTenantId();
            $schedule = $this->scheduleRepository->findByIdAndTenant($id, $tenantId);

            if (!$schedule) {
                return $this->error(OperationStatus::NOT_FOUND, 'Agendamento não encontrado.');
            }

            return $this->success($schedule);
        }, 'Erro ao buscar agendamento.');
    }
}
