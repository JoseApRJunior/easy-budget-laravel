<?php

namespace App\Services\Domain;

use App\Models\Schedule;
use App\Models\Service;
use App\Models\UserConfirmationToken;
use App\Repositories\ScheduleRepository;
use App\Repositories\UserConfirmationTokenRepository;
use App\Services\Infrastructure\EmailService;
use App\Support\ServiceResult;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ScheduleService
{
    /**
     * @var ScheduleRepository
     */
    protected $scheduleRepository;

    /**
     * @var UserConfirmationTokenRepository
     */
    protected $tokenRepository;

    /**
     * @var EmailService
     */
    protected $emailService;

    /**
     * ScheduleService constructor.
     *
     * @param ScheduleRepository $scheduleRepository
     * @param UserConfirmationTokenRepository $tokenRepository
     * @param EmailService $emailService
     */
    public function __construct(
        ScheduleRepository $scheduleRepository,
        UserConfirmationTokenRepository $tokenRepository,
        EmailService $emailService
    ) {
        $this->scheduleRepository = $scheduleRepository;
        $this->tokenRepository = $tokenRepository;
        $this->emailService = $emailService;
    }

    /**
     * Handle scheduling when service status changes to SCHEDULED.
     *
     * @param Service $service
     * @param array $scheduleData
     * @return ServiceResult
     */
    public function handleScheduledStatus(Service $service, array $scheduleData): ServiceResult
    {
        try {
            return DB::transaction(function () use ($service, $scheduleData) {
                // Validate schedule data
                $validationResult = $this->validateScheduleData($scheduleData);
                if (!$validationResult->isSuccess()) {
                    return $validationResult;
                }

                // Check for scheduling conflicts
                if ($this->scheduleRepository->hasConflict(
                    $service->id,
                    $scheduleData['start_date_time'],
                    $scheduleData['end_date_time']
                )) {
                    return ServiceResult::failure('Conflito de agendamento detectado para o horário solicitado.');
                }

                // Create user confirmation token
                $tokenData = [
                    'user_id' => $service->customer->user_id,
                    'token' => Str::random(64),
                    'type' => 'schedule_confirmation',
                    'expires_at' => Carbon::now()->addDays(7),
                ];

                $token = $this->tokenRepository->create($tokenData);

                // Create schedule
                $scheduleData['service_id'] = $service->id;
                $scheduleData['user_confirmation_token_id'] = $token->id;
                $schedule = $this->scheduleRepository->create($scheduleData);

                // Send notification email
                $this->sendScheduleNotification($service, $schedule, $token);

                return ServiceResult::success([
                    'schedule' => $schedule,
                    'token' => $token,
                ], 'Serviço agendado com sucesso.');
            });
        } catch (\Exception $e) {
            return ServiceResult::failure('Erro ao agendar serviço: ' . $e->getMessage());
        }
    }

    /**
     * Update schedule and regenerate token.
     *
     * @param Schedule $schedule
     * @param array $scheduleData
     * @return ServiceResult
     */
    public function updateScheduledToken(Schedule $schedule, array $scheduleData): ServiceResult
    {
        try {
            return DB::transaction(function () use ($schedule, $scheduleData) {
                // Validate schedule data
                $validationResult = $this->validateScheduleData($scheduleData);
                if (!$validationResult->isSuccess()) {
                    return $validationResult;
                }

                // Check for scheduling conflicts (excluding current schedule)
                if ($this->scheduleRepository->hasConflict(
                    $schedule->service_id,
                    $scheduleData['start_date_time'],
                    $scheduleData['end_date_time'],
                    $schedule->id
                )) {
                    return ServiceResult::failure('Conflito de agendamento detectado para o horário solicitado.');
                }

                // Update schedule
                $this->scheduleRepository->update($schedule->id, $scheduleData);

                // Regenerate token
                $newToken = Str::random(64);
                $this->tokenRepository->update($schedule->user_confirmation_token_id, [
                    'token' => $newToken,
                    'expires_at' => Carbon::now()->addDays(7),
                ]);

                // Reload schedule with updated data
                $schedule->refresh();

                // Send updated notification email
                $this->sendScheduleNotification($schedule->service, $schedule, $schedule->userConfirmationToken);

                return ServiceResult::success([
                    'schedule' => $schedule,
                    'token' => $schedule->userConfirmationToken,
                ], 'Agendamento atualizado com sucesso.');
            });
        } catch (\Exception $e) {
            return ServiceResult::failure('Erro ao atualizar agendamento: ' . $e->getMessage());
        }
    }

    /**
     * Get the latest schedule for a service.
     *
     * @param int $serviceId
     * @return Schedule|null
     */
    public function getLatestScheduleByService(int $serviceId): ?Schedule
    {
        return $this->scheduleRepository->findLatestByServiceId($serviceId);
    }

    /**
     * Get upcoming schedules.
     *
     * @param int $limit
     * @return \Illuminate\Support\Collection
     */
    public function getUpcomingSchedules(int $limit = 10)
    {
        return $this->scheduleRepository->getUpcomingSchedules($limit);
    }

    /**
     * Get schedules by date range.
     *
     * @param string $startDate
     * @param string $endDate
     * @return \Illuminate\Support\Collection
     */
    public function getSchedulesByDateRange(string $startDate, string $endDate)
    {
        return $this->scheduleRepository->getByDateRange($startDate, $endDate);
    }

    /**
     * Delete a schedule.
     *
     * @param Schedule $schedule
     * @return ServiceResult
     */
    public function deleteSchedule(Schedule $schedule): ServiceResult
    {
        try {
            $this->scheduleRepository->delete($schedule->id);
            return ServiceResult::success([], 'Agendamento excluído com sucesso.');
        } catch (\Exception $e) {
            return ServiceResult::failure('Erro ao excluir agendamento: ' . $e->getMessage());
        }
    }

    /**
     * Validate schedule data.
     *
     * @param array $data
     * @return ServiceResult
     */
    protected function validateScheduleData(array $data): ServiceResult
    {
        $rules = [
            'start_date_time' => 'required|date|after:now',
            'end_date_time' => 'required|date|after:start_date_time',
            'location' => 'nullable|string|max:500',
        ];

        $validator = validator($data, $rules);

        if ($validator->fails()) {
            return ServiceResult::failure('Dados de agendamento inválidos.', $validator->errors()->toArray());
        }

        return ServiceResult::success();
    }

    /**
     * Send schedule notification email.
     *
     * @param Service $service
     * @param Schedule $schedule
     * @param UserConfirmationToken $token
     * @return void
     */
    protected function sendScheduleNotification(Service $service, Schedule $schedule, UserConfirmationToken $token): void
    {
        $emailData = [
            'service' => $service,
            'schedule' => $schedule,
            'token' => $token,
            'customer' => $service->customer,
            'publicUrl' => route('services.view-status', [
                'code' => $service->code,
                'token' => $token->token,
            ]),
        ];

        $this->emailService->sendScheduleNotification($service->customer->email, $emailData);
    }
}