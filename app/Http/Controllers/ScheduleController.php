<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Abstracts\Controller;
use App\Http\Requests\ScheduleRequest;
use App\Models\User;
use App\Repositories\ServiceRepository;
use App\Services\Domain\ScheduleService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
        private readonly ScheduleService $scheduleService,
        private readonly ServiceRepository $serviceRepository,
    ) {}

    /**
     * Lista os agendamentos do usuário logado
     */
    public function index(Request $request): View
    {
        $dateFrom = $request->input('date_from') ? Carbon::parse($request->input('date_from')) : now()->startOfMonth();
        $dateTo = $request->input('date_to') ? Carbon::parse($request->input('date_to')) : now()->endOfMonth();

        $filters = [
            'status' => $request->input('status'),
            'service_id' => $request->input('service_id'),
            'location' => $request->input('location'),
        ];

        $result = $this->scheduleService->getSchedulesByPeriod($dateFrom, $dateTo, $filters);

        return view('pages.schedule.index', [
            'schedules' => $this->getServiceData($result, collect()),
            'filters' => array_merge($filters, [
                'date_from' => $dateFrom->toDateString(),
                'date_to' => $dateTo->toDateString(),
            ]),
        ]);
    }

    public function dashboard(Request $request): View
    {
        $result = $this->scheduleService->getDashboardStats();

        return view('pages.schedule.dashboard', [
            'stats' => $this->getServiceData($result, []),
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

        if (! $targetProviderId) {
            abort(404, 'Prestador não encontrado');
        }

        $month = $request->input('month', now()->format('Y-m'));

        $result = $this->scheduleService->getAvailabilityCalendar($targetProviderId, $month);

        return view('pages.schedule.calendar', [
            'calendar' => $this->getServiceData($result, []),
            'providerId' => $targetProviderId,
            'month' => $month,
        ]);
    }

    /**
     * Exibe o formulário de criação de agendamento
     */
    public function create(Request $request, string $serviceCode): View
    {
        $tenantId = (int) (Auth::user()->tenant_id ?? 0);

        // Buscar o serviço pelo código usando o repositório
        $service = $this->serviceRepository->findByCode($serviceCode, $tenantId, [
            'customer.commonData',
            'serviceStatus',
        ]);

        if (! $service) {
            abort(404, 'Serviço não encontrado');
        }

        // A autorização já deve ser tratada por policies ou no service,
        // mas mantemos aqui por segurança se necessário ou removemos se o service garantir.
        $this->authorize('view', $service);

        return view('pages.schedule.create', [
            'service' => $service,
            'selectedDate' => $request->input('date'),
            'selectedTime' => $request->input('time'),
        ]);
    }

    /**
     * Cria um novo agendamento
     */
    public function store(ScheduleRequest $request): RedirectResponse
    {
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
    }

    /**
     * Exibe os detalhes de um agendamento
     */
    public function show(string $id): View
    {
        $result = $this->scheduleService->getSchedule((int) $id);

        if (! $result->isSuccess()) {
            abort(404, 'Agendamento não encontrado');
        }

        $schedule = $result->getData();

        // A autorização é tratada pela Policy
        $this->authorize('view', $schedule);

        return view('pages.schedule.show', [
            'schedule' => $schedule,
        ]);
    }

    /**
     * Atualiza o status de um agendamento
     */
    public function updateStatus(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:confirmed,cancelled,completed,no_show',
        ]);

        $result = $this->scheduleService->updateScheduleStatus(
            (int) $id,
            $request->input('status'),
            (int) Auth::id()
        );

        if ($result->isError()) {
            return response()->json([
                'success' => false,
                'message' => $result->getMessage(),
            ], 400);
        }

        return response()->json([
            'success' => true,
            'data' => $result->getData(),
            'message' => 'Status atualizado com sucesso',
        ]);
    }

    /**
     * Cancela um agendamento
     */
    public function cancel(string $id): RedirectResponse
    {
        $result = $this->scheduleService->cancelSchedule((int) $id);

        if ($result->isError()) {
            return redirect()->back()->with('error', $result->getMessage());
        }

        return redirect()->route('provider.schedules.index')
            ->with('success', 'Agendamento cancelado com sucesso!');
    }

    /**
     * Verifica disponibilidade de horários para agendamento
     */
    public function checkAvailability(Request $request): JsonResponse
    {
        $request->validate([
            'provider_id' => 'required|exists:users,id',
            'date' => 'required|date|after:today',
            'duration' => 'nullable|integer|min:30|max:480', // 30 minutos a 8 horas
        ]);

        $result = $this->scheduleService->checkAvailability(
            (int) $request->input('provider_id'),
            (string) $request->input('date'),
            (int) $request->input('duration', 60),
        );

        if ($result->isError()) {
            return response()->json([
                'success' => false,
                'message' => $result->getMessage(),
            ], 400);
        }

        return response()->json([
            'success' => true,
            'data' => $result->getData(),
        ]);
    }

    public function getTimeSlots(Request $request): JsonResponse
    {
        $request->validate([
            'provider_id' => 'required|exists:users,id',
            'date' => 'required|date',
            'duration' => 'nullable|integer|min:30|max:480',
        ]);

        $result = $this->scheduleService->getAvailableTimeSlots(
            (int) $request->input('provider_id'),
            (string) $request->input('date'),
            (int) $request->input('duration', 60),
        );

        if ($result->isError()) {
            return response()->json([
                'success' => false,
                'message' => $result->getMessage(),
            ], 400);
        }

        return response()->json([
            'success' => true,
            'data' => $result->getData(),
        ]);
    }
}
