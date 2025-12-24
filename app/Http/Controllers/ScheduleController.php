<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Abstracts\Controller;
use App\Http\Requests\ScheduleRequest;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\User;
use App\Services\Domain\ScheduleService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

/**
 * Controller para gerenciamento de agendamentos
 *
 * Este controller gerencia o sistema de agendamentos de serviços,
 * permitindo que prestadores gerenciem seus horários disponíveis
 * e clientes agendem serviços.
 */
class ScheduleController extends Controller
{
    public function __construct(
        private ScheduleService $scheduleService,
    ) {}

    /**
     * Lista os agendamentos do usuário logado
     */
    public function index(Request $request): View
    {
        $dateFrom = $request->input('date_from') ? Carbon::parse($request->input('date_from')) : now()->startOfMonth();
        $dateTo   = $request->input('date_to') ? Carbon::parse($request->input('date_to')) : now()->endOfMonth();

        $filters = [
            'status'      => $request->input('status'),
            'service_id'  => $request->input('service_id'),
            'location'    => $request->input('location'),
        ];

        $result = $this->scheduleService->getSchedulesByPeriod($dateFrom, $dateTo, $filters);

        return view('pages.schedule.index', [
            'schedules' => $this->getServiceData($result, collect()),
            'filters'   => array_merge($filters, [
                'date_from' => $dateFrom->toDateString(),
                'date_to'   => $dateTo->toDateString(),
            ]),
        ]);
    }

    public function dashboard(Request $request): View
    {
        $result = $this->scheduleService->getDashboardStats();

        return view('pages.schedule.dashboard', [
            'stats' => $this->getServiceData($result, [])
        ]);
    }

    /**
     * Exibe o calendário de disponibilidade de um prestador
     */
    public function calendar(Request $request, ?string $providerId = null): View
    {
        /** @var User $user */
        $user = Auth::user();

        // Se não for especificado um prestador, usa o usuário logado (se for prestador)
        $targetProviderId = $providerId ?? ($user->isProvider() ? $user->id : null);

        if (!$targetProviderId) {
            abort(404, 'Prestador não encontrado');
        }

        $month = $request->input('month', now()->format('Y-m'));

        $result = $this->scheduleService->getAvailabilityCalendar($targetProviderId, $month);

        return view('pages.schedule.calendar', [
            'calendar'   => $this->getServiceData($result, []),
            'providerId' => $targetProviderId,
            'month'      => $month,
        ]);
    }

    /**
     * Exibe o formulário de criação de agendamento
     */
    public function create(Request $request, string $serviceCode): View
    {
        /** @var User $user */
        $user = Auth::user();

        // Buscar o serviço pelo código
        $service = Service::where('code', $serviceCode)
            ->where('tenant_id', $user->tenant_id)
            ->with(['customer.commonData', 'status'])
            ->first();

        if (!$service) {
            abort(404, 'Serviço não encontrado');
        }

        // Verificar se o usuário tem permissão para agendar este serviço
        if (!$user->isAdmin() && $service->customer_id !== $user->id) {
            abort(403, 'Acesso não autorizado');
        }

        return view('pages.schedule.create', [
            'service'      => $service,
            'selectedDate' => $request->input('date'),
            'selectedTime' => $request->input('time'),
        ]);
    }

    /**
     * Cria um novo agendamento
     */
    public function store(ScheduleRequest $request): RedirectResponse
    {
        try {
            $dto = \App\DTOs\Schedule\ScheduleDTO::fromRequest($request->validated());
            $result = $this->scheduleService->createSchedule($dto);

            if ($result->isSuccess()) {
                return $this->redirectSuccess(
                    'schedules.index',
                    'Agendamento criado com sucesso!',
                );
            }

            return redirect()->back()
                ->withInput()
                ->with('error', $this->getServiceErrorMessage($result, 'Erro ao criar agendamento'));
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Erro ao criar agendamento: ' . $e->getMessage());
        }
    }

    /**
     * Exibe os detalhes de um agendamento
     */
    public function show(string $id): View
    {
        /** @var User $user */
        $user = Auth::user();

        $result = $this->scheduleService->getSchedule($id);

        if (!$result->isSuccess()) {
            abort(404, 'Agendamento não encontrado');
        }

        $schedule = $result->getData();

        // Verifica se o usuário tem permissão para ver este agendamento
        if (
            !$user->isAdmin() &&
            $schedule->customer_id !== $user->id &&
            $schedule->provider_id !== $user->id
        ) {
            abort(403, 'Acesso não autorizado');
        }

        return view('pages.schedule.show', [
            'schedule' => $schedule,
        ]);
    }

    /**
     * Atualiza o status de um agendamento
     */
    public function updateStatus(Request $request, string $id): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $request->validate([
            'status' => 'required|in:confirmed,cancelled,completed,no_show',
        ]);

        try {
            $result = $this->scheduleService->updateScheduleStatus(
                $id,
                $request->input('status'),
                $user->id,
            );

            return $this->jsonResponse($result);
        } catch (\Exception $e) {
            return $this->jsonError('Erro ao atualizar status: ' . $e->getMessage());
        }
    }

    /**
     * Cancela um agendamento
     */
    public function cancel(string $id): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        try {
            $result = $this->scheduleService->cancelSchedule($id, $user->id);

            return $this->redirectWithServiceResult(
                'schedules.index',
                $result,
                'Agendamento cancelado com sucesso!',
            );
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Erro ao cancelar agendamento: ' . $e->getMessage());
        }
    }

    /**
     * Verifica disponibilidade de horários para agendamento
     */
    public function checkAvailability(Request $request): JsonResponse
    {
        $request->validate([
            'provider_id' => 'required|exists:users,id',
            'date'        => 'required|date|after:today',
            'duration'    => 'nullable|integer|min:30|max:480', // 30 minutos a 8 horas
        ]);

        try {
            $result = $this->scheduleService->checkAvailability(
                $request->input('provider_id'),
                $request->input('date'),
                $request->input('duration', 60),
            );

            return $this->jsonResponse($result);
        } catch (\Exception $e) {
            return $this->jsonError('Erro ao verificar disponibilidade: ' . $e->getMessage());
        }
    }

    /**
     * Obtém os horários disponíveis de um prestador em uma data específica
     */
    public function getAvailableSlots(Request $request): JsonResponse
    {
        $request->validate([
            'provider_id'      => 'required|exists:users,id',
            'date'             => 'required|date|after:today',
            'service_duration' => 'nullable|integer|min:30|max:480',
        ]);

        try {
            $result = $this->scheduleService->getAvailableTimeSlots(
                $request->input('provider_id'),
                $request->input('date'),
                $request->input('service_duration', 60),
            );

            return $this->jsonResponse($result);
        } catch (\Exception $e) {
            return $this->jsonError('Erro ao obter horários disponíveis: ' . $e->getMessage());
        }
    }
}
