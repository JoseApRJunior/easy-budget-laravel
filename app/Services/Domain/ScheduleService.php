<?php

declare(strict_types=1);

namespace App\Services\Domain;

use App\DTOs\Schedule\ScheduleDTO;
use App\DTOs\Schedule\ScheduleUpdateDTO;
use App\Enums\OperationStatus;
use App\Enums\ScheduleStatus;
use App\Enums\ServiceStatus;
use App\Enums\TokenType;
use App\Repositories\ScheduleRepository;
use App\Repositories\ServiceRepository;
use App\Services\Application\UserConfirmationTokenService;
use App\Services\Core\Abstracts\AbstractBaseService;
use App\Services\Core\Traits\HasSafeExecution;
use App\Services\Core\Traits\HasTenantIsolation;
use App\Support\ServiceResult;
use Carbon\Carbon;

class ScheduleService extends AbstractBaseService
{
    use HasSafeExecution, HasTenantIsolation;

    public function __construct(
        private ScheduleRepository $scheduleRepository,
        private ServiceRepository $serviceRepository,
        private UserConfirmationTokenService $tokenService,
    ) {
        parent::__construct($scheduleRepository);
    }

    /**
     * Cria um novo agendamento com validações de conflito
     */
    public function createSchedule(ScheduleDTO $dto): ServiceResult
    {
        return $this->safeExecute(function () use ($dto) {
            // Verifica se o serviço existe (o escopo de tenant já é aplicado pelo ServiceRepository)
            $service = $this->serviceRepository->find($dto->service_id);

            if (! $service) {
                return $this->error(OperationStatus::NOT_FOUND, 'Serviço não encontrado.');
            }

            // Verifica conflitos de horário (global por tenant)
            if ($this->scheduleRepository->hasConflict($dto->start_date_time, $dto->end_date_time)) {
                return $this->error(OperationStatus::CONFLICT, 'Conflito de horário detectado.');
            }

            // Gera token de confirmação para o agendamento
            // Nota: O agendamento é vinculado ao cliente do serviço
            $customerUser = $service->customer?->user;
            $tokenId = null;

            if ($customerUser) {
                $tokenResult = $this->tokenService->createTokenWithGeneration(
                    $customerUser,
                    TokenType::SCHEDULE_CONFIRMATION,
                    60 * 24 * 7 // 1 semana de expiração
                );

                if ($tokenResult->isSuccess()) {
                    $tokenId = $tokenResult->getData()['id'];
                }
            }

            // Prepara os dados para criação
            $data = $dto->toArrayWithoutNulls();

            // Vincula o token se gerado
            if ($tokenId) {
                $data['user_confirmation_token_id'] = $tokenId;
            }

            // Cria o agendamento já com o token (importante para Observers dispararem notificações com link correto)
            $schedule = $this->scheduleRepository->create($data);

            // Atualiza o status do serviço apenas se não estiver em processo de agendamento (SCHEDULING) ou agendado (SCHEDULED)
            if (! in_array($service->status, [ServiceStatus::SCHEDULING, ServiceStatus::SCHEDULED])) {
                $this->serviceRepository->update($service->id, ['status' => ServiceStatus::SCHEDULING->value]);
            }

            return $this->success($schedule, 'Agendamento criado com sucesso.');
        }, 'Erro ao criar agendamento.');
    }

    /**
     * Confirma um agendamento via token
     */
    public function confirmScheduleByToken(string $token): ServiceResult
    {
        return $this->safeExecute(function () use ($token) {
            // Valida o token
            $tokenResult = $this->tokenService->validateToken($token, TokenType::SCHEDULE_CONFIRMATION);

            if ($tokenResult->isError()) {
                return $tokenResult;
            }

            $tokenRecord = $tokenResult->getData();

            // Busca o agendamento vinculado ao token
            $schedule = $this->scheduleRepository->findOneBy(['user_confirmation_token_id' => $tokenRecord->id]);

            if (! $schedule) {
                return $this->error(OperationStatus::NOT_FOUND, 'Agendamento não encontrado para este token.');
            }

            // Se já estiver confirmado, apenas retorna sucesso
            if ($schedule->status === ScheduleStatus::CONFIRMED) {
                return $this->success($schedule, 'Agendamento já estava confirmado.');
            }

            // Atualiza o status do agendamento
            $this->scheduleRepository->update($schedule->id, [
                'status' => ScheduleStatus::CONFIRMED->value,
            ]);

            return $this->success($schedule->fresh(), 'Agendamento confirmado com sucesso.');
        }, 'Erro ao confirmar agendamento.');
    }

    /**
     * Atualiza agendamento com validações
     */
    public function updateSchedule(int $scheduleId, ScheduleUpdateDTO $dto): ServiceResult
    {
        return $this->safeExecute(function () use ($scheduleId, $dto) {
            // Verifica se o agendamento existe
            $schedule = $this->scheduleRepository->find($scheduleId);

            if (! $schedule) {
                return $this->error('Agendamento não encontrado.');
            }

            // Verifica conflitos se houver mudança de horário
            if ($dto->start_date_time || $dto->end_date_time) {
                $startTime = $dto->start_date_time ?? $schedule->start_date_time->format('Y-m-d H:i:s');
                $endTime = $dto->end_date_time ?? $schedule->end_date_time->format('Y-m-d H:i:s');

                if ($this->scheduleRepository->hasConflict($startTime, $endTime, $scheduleId)) {
                    return $this->error('Conflito de horário detectado.');
                }
            }

            $updated = $this->scheduleRepository->updateFromDTO($scheduleId, $dto);

            return $this->success($updated, 'Agendamento atualizado com sucesso.');
        }, 'Erro ao atualizar agendamento.');
    }

    /**
     * Cancela agendamento
     */
    public function cancelSchedule(int $scheduleId, ?string $reason = null): ServiceResult
    {
        return $this->safeExecute(function () use ($scheduleId, $reason) {
            $schedule = $this->scheduleRepository->find($scheduleId);

            if (! $schedule) {
                return $this->error(OperationStatus::NOT_FOUND, 'Agendamento não encontrado.');
            }

            // Verifica se já está cancelado
            if ($schedule->status === 'cancelled') {
                return $this->error(OperationStatus::CONFLICT, 'Agendamento já está cancelado.');
            }

            $success = $this->scheduleRepository->update($scheduleId, [
                'status' => 'cancelled',
                'cancellation_reason' => $reason,
                'cancelled_at' => now(),
            ]);

            if (! $success) {
                return $this->error('Falha ao cancelar agendamento.');
            }

            return $this->success($schedule->fresh(), 'Agendamento cancelado com sucesso.');
        }, 'Erro ao cancelar agendamento.');
    }

    /**
     * Atualiza o status de um agendamento
     */
    public function updateScheduleStatus(int $id, string $status, ?int $userId = null): ServiceResult
    {
        return $this->safeExecute(function () use ($id, $status) {
            $schedule = $this->scheduleRepository->find($id);

            if (! $schedule) {
                return $this->error(OperationStatus::NOT_FOUND, 'Agendamento não encontrado.');
            }

            $data = ['status' => $status];

            if ($status === 'confirmed') {
                $data['confirmed_at'] = now();
            }
            if ($status === 'completed') {
                $data['completed_at'] = now();
            }
            if ($status === 'no_show') {
                $data['no_show_at'] = now();
            }
            if ($status === 'cancelled') {
                $data['cancelled_at'] = now();
            }

            $success = $this->scheduleRepository->update($id, $data);

            if (! $success) {
                return $this->error('Falha ao atualizar status do agendamento.');
            }

            return $this->success($schedule->fresh(), 'Status atualizado com sucesso.');
        }, 'Erro ao atualizar status do agendamento.');
    }

    /**
     * Obtém calendário de disponibilidade
     */
    public function getAvailabilityCalendar(int $providerId, string $month): ServiceResult
    {
        return $this->safeExecute(function () use ($month) {
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
        return $this->safeExecute(function () {
            // Lógica de verificação de disponibilidade (exemplo simplificado)
            return $this->success(['available' => true]);
        }, 'Erro ao verificar disponibilidade.');
    }

    /**
     * Obtém slots de horários disponíveis
     */
    public function getAvailableTimeSlots(int $providerId, string $date, int $durationMinutes = 60): ServiceResult
    {
        return $this->safeExecute(function () {
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
            $schedule = $this->scheduleRepository->find($scheduleId);

            if (! $schedule) {
                return $this->error(OperationStatus::NOT_FOUND, 'Agendamento não encontrado.');
            }

            if ($schedule->status === ScheduleStatus::CONFIRMED) {
                return $this->success($schedule, 'Agendamento já estava confirmado.');
            }

            $success = $this->scheduleRepository->update($scheduleId, [
                'status' => ScheduleStatus::CONFIRMED->value,
                'confirmed_at' => now(),
            ]);

            if (! $success) {
                return $this->error('Falha ao confirmar agendamento.');
            }

            return $this->success($schedule->fresh(), 'Agendamento confirmado com sucesso.');
        }, 'Erro ao confirmar agendamento.');
    }

    /**
     * Lista agendamentos por período
     */
    public function getSchedulesByPeriod(Carbon $startDate, Carbon $endDate, array $filters = []): ServiceResult
    {
        return $this->safeExecute(function () use ($startDate, $endDate, $filters) {
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
            $statsData = $this->scheduleRepository->getStats();
            $recentUpcoming = $this->scheduleRepository->getRecentUpcoming();

            $statusBreakdown = [];
            foreach (\App\Enums\ScheduleStatus::cases() as $status) {
                $statusBreakdown[$status->label()] = [
                    'count' => $statsData['by_status'][$status->value] ?? 0,
                    'color' => $status->getColor(),
                ];
            }

            return $this->success([
                'total_schedules' => $statsData['total'],
                'upcoming_schedules' => $statsData['upcoming'],
                'today_schedules' => $statsData['today'],
                'this_week_schedules' => $statsData['this_week'],
                'completed_schedules' => $statsData['by_status'][\App\Enums\ScheduleStatus::COMPLETED->value] ?? 0,
                'no_show_schedules' => $statsData['by_status'][\App\Enums\ScheduleStatus::NO_SHOW->value] ?? 0,
                'pending_schedules' => $statsData['by_status'][\App\Enums\ScheduleStatus::PENDING->value] ?? 0,
                'status_breakdown' => $statusBreakdown,
                'recent_upcoming' => $recentUpcoming,
            ], 'Estatísticas carregadas com sucesso.');
        }, 'Erro ao carregar estatísticas do dashboard.');
    }

    /**
     * Busca um agendamento específico.
     */
    public function getSchedule(int $id): ServiceResult
    {
        return $this->safeExecute(function () use ($id) {
            $schedule = $this->scheduleRepository->find($id);

            if (! $schedule) {
                return $this->error(OperationStatus::NOT_FOUND, 'Agendamento não encontrado.');
            }

            return $this->success($schedule);
        }, 'Erro ao buscar agendamento.');
    }
}
