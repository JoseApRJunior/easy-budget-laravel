<?php

declare(strict_types=1);

namespace App\Services\Domain;

use App\Enums\OperationStatus;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\User;
use App\Models\UserConfirmationToken;
use App\Repositories\ScheduleRepository;
use App\Services\Core\Abstracts\AbstractBaseService;
use App\Support\ServiceResult;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class ScheduleService extends AbstractBaseService
{
    public function __construct(
        private ScheduleRepository $scheduleRepository,
    ) {
        parent::__construct($scheduleRepository);
    }

    /**
     * Cria um novo agendamento com validações de conflito
     */
    public function createSchedule(array $data): ServiceResult
    {
        try {
            // Validações de negócio
            $validation = $this->validateScheduleData($data);
            if (!$validation['valid']) {
                return $this->error(OperationStatus::VALIDATION_ERROR, $validation['message']);
            }

            // Verifica se o serviço existe e pertence ao tenant
            $service = Service::where('id', $data['service_id'])
                ->where('tenant_id', $this->tenantId())
                ->first();

            if (!$service) {
                return $this->error(OperationStatus::NOT_FOUND, 'Serviço não encontrado ou não pertence ao tenant atual.');
            }

            // Converte strings para Carbon
            $startTime = Carbon::parse($data['start_date_time']);
            $endTime = Carbon::parse($data['end_date_time']);

            // Verifica conflitos de horário
            $conflict = $this->checkScheduleConflict($startTime, $endTime, $data['service_id'] ?? null);
            if ($conflict) {
                return $this->error(OperationStatus::CONFLICT, 'Conflito de horário detectado.');
            }

            // Cria token de confirmação se necessário
            if (isset($data['requires_confirmation']) && $data['requires_confirmation']) {
                $token = $this->createConfirmationToken($data);
                $data['user_confirmation_token_id'] = $token->id;
            }

            $data['tenant_id'] = $this->tenantId();

            // Cria o agendamento
            $schedule = $this->create($data);

            if (!$schedule->isSuccess()) {
                return $schedule;
            }

            return $this->success($schedule->getData(), 'Agendamento criado com sucesso.');
        } catch (\Exception $e) {
            return $this->error(OperationStatus::ERROR, 'Erro ao criar agendamento.', null, $e);
        }
    }

    /**
     * Atualiza agendamento com validações
     */
    public function updateSchedule(int $scheduleId, array $data): ServiceResult
    {
        try {
            // Verifica se o agendamento existe e pertence ao tenant
            $schedule = Schedule::where('id', $scheduleId)
                ->where('tenant_id', $this->tenantId())
                ->first();

            if (!$schedule) {
                return $this->error(OperationStatus::NOT_FOUND, 'Agendamento não encontrado.');
            }

            // Validações
            $validation = $this->validateScheduleData($data, $scheduleId);
            if (!$validation['valid']) {
                return $this->error(OperationStatus::VALIDATION_ERROR, $validation['message']);
            }

            // Converte strings para Carbon se fornecidas
            if (isset($data['start_date_time'])) {
                $startTime = Carbon::parse($data['start_date_time']);
            }
            if (isset($data['end_date_time'])) {
                $endTime = Carbon::parse($data['end_date_time']);
            }

            // Verifica conflitos se houver mudança de horário
            if (isset($startTime) || isset($endTime)) {
                $conflict = $this->checkScheduleConflict(
                    $startTime ?? Carbon::parse($schedule->start_date_time),
                    $endTime ?? Carbon::parse($schedule->end_date_time),
                    $schedule->service_id,
                    $scheduleId
                );
                if ($conflict) {
                    return $this->error(OperationStatus::CONFLICT, 'Conflito de horário detectado.');
                }
            }

            $updated = $this->update($scheduleId, $data);

            if (!$updated->isSuccess()) {
                return $updated;
            }

            return $this->success($updated->getData(), 'Agendamento atualizado com sucesso.');
        } catch (\Exception $e) {
            return $this->error(OperationStatus::ERROR, 'Erro ao atualizar agendamento.', null, $e);
        }
    }

    /**
     * Cancela agendamento
     */
    public function cancelSchedule(int $scheduleId, string $reason = null): ServiceResult
    {
        try {
            $schedule = Schedule::where('id', $scheduleId)
                ->where('tenant_id', $this->tenantId())
                ->first();

            if (!$schedule) {
                return $this->error(OperationStatus::NOT_FOUND, 'Agendamento não encontrado.');
            }

            // Verifica se já está cancelado
            if ($schedule->status === 'cancelled') {
                return $this->error(OperationStatus::CONFLICT, 'Agendamento já está cancelado.');
            }

            // Verifica se é passado
            if (Carbon::parse($schedule->start_date_time)->isPast()) {
                return $this->error(OperationStatus::CONFLICT, 'Não é possível cancelar agendamentos passados.');
            }

            $schedule->update([
                'status' => 'cancelled',
                'cancellation_reason' => $reason,
                'cancelled_at' => now(),
            ]);

            return $this->success($schedule, 'Agendamento cancelado com sucesso.');
        } catch (\Exception $e) {
            return $this->error(OperationStatus::ERROR, 'Erro ao cancelar agendamento.', null, $e);
        }
    }

    /**
     * Confirma agendamento
     */
    public function confirmSchedule(int $scheduleId): ServiceResult
    {
        try {
            $schedule = Schedule::where('id', $scheduleId)
                ->where('tenant_id', $this->tenantId())
                ->first();

            if (!$schedule) {
                return $this->error(OperationStatus::NOT_FOUND, 'Agendamento não encontrado.');
            }

            if ($schedule->status !== 'pending') {
                return $this->error(OperationStatus::CONFLICT, 'Apenas agendamentos pendentes podem ser confirmados.');
            }

            $schedule->update([
                'status' => 'confirmed',
                'confirmed_at' => now(),
            ]);

            return $this->success($schedule, 'Agendamento confirmado com sucesso.');
        } catch (\Exception $e) {
            return $this->error(OperationStatus::ERROR, 'Erro ao confirmar agendamento.', null, $e);
        }
    }



    /**
     * Lista agendamentos por período
     */
    public function getSchedulesByPeriod(Carbon $startDate, Carbon $endDate, array $filters = []): ServiceResult
    {
        try {
            $query = Schedule::with(['service', 'service.customer', 'confirmationToken'])
                ->where('tenant_id', $this->tenantId())
                ->whereBetween('start_date_time', [$startDate, $endDate]);

            // Aplica filtros adicionais
            if (isset($filters['service_id'])) {
                $query->where('service_id', $filters['service_id']);
            }

            if (isset($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            if (isset($filters['location'])) {
                $query->where('location', 'like', '%' . $filters['location'] . '%');
            }

            $schedules = $query->orderBy('start_date_time', 'asc')->get();

            return $this->success($schedules, 'Agendamentos listados com sucesso.');
        } catch (\Exception $e) {
            return $this->error(OperationStatus::ERROR, 'Erro ao listar agendamentos.', null, $e);
        }
    }

    /**
     * Verifica conflitos de agendamento
     */
    private function checkScheduleConflict(Carbon $startTime, Carbon $endTime, ?int $serviceId = null, ?int $excludeScheduleId = null): bool
    {
        $query = Schedule::where('tenant_id', $this->tenantId())
            ->where('status', '!=', 'cancelled')
            ->where(function ($q) use ($startTime, $endTime) {
                $q->where(function ($query) use ($startTime, $endTime) {
                    $query->where('start_date_time', '<', $endTime)
                          ->where('end_date_time', '>', $startTime);
                });
            });

        if ($serviceId) {
            $query->where('service_id', $serviceId);
        }

        if ($excludeScheduleId) {
            $query->where('id', '!=', $excludeScheduleId);
        }

        return $query->exists();
    }

    /**
     * Obtém conflitos de agendamento
     */
    private function getScheduleConflicts(Carbon $startTime, Carbon $endTime, ?int $serviceId = null, ?int $excludeScheduleId = null)
    {
        $query = Schedule::where('tenant_id', $this->tenantId())
            ->where('status', '!=', 'cancelled')
            ->where(function ($q) use ($startTime, $endTime) {
                $q->where(function ($query) use ($startTime, $endTime) {
                    $query->where('start_date_time', '<', $endTime)
                          ->where('end_date_time', '>', $startTime);
                });
            });

        if ($serviceId) {
            $query->where('service_id', $serviceId);
        }

        if ($excludeScheduleId) {
            $query->where('id', '!=', $excludeScheduleId);
        }

        return $query->with(['service', 'service.customer'])->get();
    }

    /**
     * Cria token de confirmação
     */
    private function createConfirmationToken(array $data): UserConfirmationToken
    {
        return UserConfirmationToken::create([
            'user_id' => $this->authUser()->id,
            'tenant_id' => $this->tenantId(),
            'token' => Str::random(32),
            'expires_at' => now()->addDays(7),
            'type' => 'schedule_confirmation',
        ]);
    }

    /**
     * Valida dados do agendamento
     */
    private function validateScheduleData(array $data, ?int $excludeId = null): array
    {
        $required = ['service_id', 'start_date_time', 'end_date_time'];
        
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return ['valid' => false, 'message' => "Campo obrigatório: {$field}"];
            }
        }

        try {
            $startTime = Carbon::parse($data['start_date_time']);
            $endTime = Carbon::parse($data['end_date_time']);

            if ($endTime->lte($startTime)) {
                return ['valid' => false, 'message' => 'Data/hora de término deve ser posterior à de início.'];
            }

            // Verifica se é no futuro (para novos agendamentos)
            if (!$excludeId && $startTime->isPast()) {
                return ['valid' => false, 'message' => 'Agendamentos devem ser no futuro.'];
            }

            // Duração mínima de 15 minutos
            if ($startTime->diffInMinutes($endTime) < 15) {
                return ['valid' => false, 'message' => 'Duração mínima do agendamento é de 15 minutos.'];
            }

        } catch (\Exception $e) {
            return ['valid' => false, 'message' => 'Formato de data/hora inválido.'];
        }

        return ['valid' => true, 'message' => 'Dados válidos.'];
    }

    /**
     * Define filtros suportados
     */
    protected function getSupportedFilters(): array
    {
        return [
            'id',
            'service_id',
            'user_confirmation_token_id',
            'start_date_time',
            'end_date_time',
            'location',
            'status',
            'created_at',
            'updated_at',
        ];
    }

    /**
     * Lista agendamentos com filtros
     */
    public function getSchedules(array $filters = []): ServiceResult
    {
        try {
            $query = Schedule::with(['service', 'service.customer', 'confirmationToken'])
                ->where('tenant_id', $this->tenantId());

            // Aplica filtros
            if (!empty($filters['date_from'])) {
                $query->where('start_date_time', '>=', $filters['date_from']);
            }

            if (!empty($filters['date_to'])) {
                $query->where('end_date_time', '<=', $filters['date_to']);
            }

            if (!empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            if (!empty($filters['provider_id'])) {
                $query->whereHas('service', function($q) use ($filters) {
                    $q->where('user_id', $filters['provider_id']);
                });
            }

            if (!empty($filters['customer_id'])) {
                $query->whereHas('service.customer', function($q) use ($filters) {
                    $q->where('id', $filters['customer_id']);
                });
            }

            $schedules = $query->orderBy('start_date_time', 'asc')->get();

            return $this->success($schedules, 'Agendamentos listados com sucesso.');
        } catch (\Exception $e) {
            return $this->error(OperationStatus::ERROR, 'Erro ao listar agendamentos.', null, $e);
        }
    }

    /**
     * Obtém calendário de disponibilidade
     */
    public function getAvailabilityCalendar(string $providerId, string $month): ServiceResult
    {
        try {
            $startDate = Carbon::parse($month . '-01');
            $endDate = $startDate->copy()->endOfMonth();

            // Busca agendamentos do prestador no mês
            $schedules = Schedule::with(['service'])
                ->where('tenant_id', $this->tenantId())
                ->whereHas('service', function($q) use ($providerId) {
                    $q->where('user_id', $providerId);
                })
                ->whereBetween('start_date_time', [$startDate, $endDate])
                ->where('status', '!=', 'cancelled')
                ->get();

            // Organiza por dia
            $calendar = [];
            for ($date = $startDate->copy(); $date <= $endDate; $date->addDay()) {
                $daySchedules = $schedules->filter(function($schedule) use ($date) {
                    return Carbon::parse($schedule->start_date_time)->isSameDay($date);
                });

                $calendar[$date->format('Y-m-d')] = [
                    'date' => $date->format('Y-m-d'),
                    'day_of_week' => $date->dayOfWeek,
                    'is_weekend' => $date->isWeekend(),
                    'schedules' => $daySchedules,
                    'busy_count' => $daySchedules->count(),
                ];
            }

            return $this->success($calendar, 'Calendário de disponibilidade obtido com sucesso.');
        } catch (\Exception $e) {
            return $this->error(OperationStatus::ERROR, 'Erro ao obter calendário de disponibilidade.', null, $e);
        }
    }

    /**
     * Obtém prestadores disponíveis
     */
    public function getAvailableProviders(): ServiceResult
    {
        try {
            $providers = User::where('tenant_id', $this->tenantId())
                ->where('type', 'provider')
                ->where('status', 'active')
                ->get();

            return $this->success($providers, 'Prestadores disponíveis listados com sucesso.');
        } catch (\Exception $e) {
            return $this->error(OperationStatus::ERROR, 'Erro ao listar prestadores.', null, $e);
        }
    }

    /**
     * Obtém agendamento específico
     */
    public function getSchedule(string $id): ServiceResult
    {
        try {
            $schedule = Schedule::with(['service', 'service.customer', 'confirmationToken'])
                ->where('id', $id)
                ->where('tenant_id', $this->tenantId())
                ->first();

            if (!$schedule) {
                return $this->error(OperationStatus::NOT_FOUND, 'Agendamento não encontrado.');
            }

            return $this->success($schedule, 'Agendamento encontrado com sucesso.');
        } catch (\Exception $e) {
            return $this->error(OperationStatus::ERROR, 'Erro ao buscar agendamento.', null, $e);
        }
    }

    /**
     * Atualiza status do agendamento
     */
    public function updateScheduleStatus(string $id, string $status, int $userId): ServiceResult
    {
        try {
            $schedule = Schedule::where('id', $id)
                ->where('tenant_id', $this->tenantId())
                ->first();

            if (!$schedule) {
                return $this->error(OperationStatus::NOT_FOUND, 'Agendamento não encontrado.');
            }

            // Verifica se o usuário tem permissão
            $user = User::find($userId);
            if (!$user || ($user->type !== 'admin' && 
                $schedule->service->user_id !== $userId && 
                $schedule->service->customer_id !== $userId)) {
                return $this->error(OperationStatus::FORBIDDEN, 'Acesso não autorizado.');
            }

            // Valida transições de status
            $validTransitions = [
                'pending' => ['confirmed', 'cancelled'],
                'confirmed' => ['completed', 'no_show', 'cancelled'],
                'completed' => [],
                'no_show' => [],
                'cancelled' => [],
            ];

            if (!in_array($status, $validTransitions[$schedule->status] ?? [])) {
                return $this->error(OperationStatus::CONFLICT, "Transição de status inválida de '{$schedule->status}' para '{$status}'.");
            }

            $updateData = ['status' => $status];

            // Adiciona timestamps baseados no status
            switch ($status) {
                case 'confirmed':
                    $updateData['confirmed_at'] = now();
                    break;
                case 'completed':
                    $updateData['completed_at'] = now();
                    break;
                case 'no_show':
                    $updateData['no_show_at'] = now();
                    break;
                case 'cancelled':
                    $updateData['cancelled_at'] = now();
                    break;
            }

            $schedule->update($updateData);

            return $this->success($schedule, "Status do agendamento atualizado para '{$status}' com sucesso.");
        } catch (\Exception $e) {
            return $this->error(OperationStatus::ERROR, 'Erro ao atualizar status do agendamento.', null, $e);
        }
    }

    /**
     * Verifica disponibilidade de horários
     */
    public function checkAvailability(string $providerId, string $date, int $duration = 60): ServiceResult
    {
        try {
            $startTime = Carbon::parse($date);
            $endTime = $startTime->copy()->addMinutes($duration);

            // Verifica conflitos
            $conflicts = $this->getScheduleConflicts($startTime, $endTime, null, null)
                ->where('service.user_id', $providerId);

            return $this->success([
                'available' => $conflicts->isEmpty(),
                'conflicts' => $conflicts,
                'date' => $date,
                'duration' => $duration,
            ], 'Verificação de disponibilidade concluída.');
        } catch (\Exception $e) {
            return $this->error(OperationStatus::ERROR, 'Erro ao verificar disponibilidade.', null, $e);
        }
    }

    /**
     * Obtém horários disponíveis
     */
    public function getAvailableTimeSlots(string $providerId, string $date, int $serviceDuration = 60): ServiceResult
    {
        try {
            $date = Carbon::parse($date);
            
            // Define horário de trabalho (8h às 18h)
            $workStart = $date->copy()->setTime(8, 0);
            $workEnd = $date->copy()->setTime(18, 0);
            
            // Busca agendamentos existentes do prestador no dia
            $existingSchedules = Schedule::with(['service'])
                ->where('tenant_id', $this->tenantId())
                ->whereHas('service', function($q) use ($providerId) {
                    $q->where('user_id', $providerId);
                })
                ->whereDate('start_date_time', $date)
                ->where('status', '!=', 'cancelled')
                ->orderBy('start_date_time')
                ->get();

            // Calcula horários disponíveis
            $availableSlots = [];
            $currentTime = $workStart->copy();

            while ($currentTime->copy()->addMinutes($serviceDuration) <= $workEnd) {
                $slotEnd = $currentTime->copy()->addMinutes($serviceDuration);
                $isAvailable = true;

                // Verifica conflitos com agendamentos existentes
                foreach ($existingSchedules as $schedule) {
                    $scheduleStart = Carbon::parse($schedule->start_date_time);
                    $scheduleEnd = Carbon::parse($schedule->end_date_time);

                    if ($currentTime < $scheduleEnd && $slotEnd > $scheduleStart) {
                        $isAvailable = false;
                        break;
                    }
                }

                if ($isAvailable) {
                    $availableSlots[] = [
                        'start_time' => $currentTime->format('H:i'),
                        'end_time' => $slotEnd->format('H:i'),
                        'duration' => $serviceDuration,
                    ];
                }

                $currentTime->addMinutes(30); // Incrementa de 30 em 30 minutos
            }

            return $this->success($availableSlots, 'Horários disponíveis obtidos com sucesso.');
        } catch (\Exception $e) {
            return $this->error(OperationStatus::ERROR, 'Erro ao obter horários disponíveis.', null, $e);
        }
    }
}