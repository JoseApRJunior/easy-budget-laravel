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
    public function index( Request $request ): View
    {
        /** @var User $user */
        $user = Auth::user();

        $filters = [
            'date_from'   => $request->input( 'date_from' ),
            'date_to'     => $request->input( 'date_to' ),
            'status'      => $request->input( 'status' ),
            'provider_id' => $user->isProvider() ? $user->id : $request->input( 'provider_id' ),
            'customer_id' => $user->isCustomer() ? $user->id : $request->input( 'customer_id' ),
        ];

        $result = $this->scheduleService->getSchedules( $filters );

        return view( 'pages.schedule.index', [
            'schedules' => $this->getServiceData( $result, collect() ),
            'filters'   => $filters,
        ] );
    }

    public function dashboard( Request $request ): View
    {
        /** @var User $user */
        $user     = Auth::user();
        $tenantId = (int) ( $user->tenant_id ?? 0 );

        $total = Schedule::where( 'tenant_id', $tenantId )->count();

        $hasStatus = Schema::hasColumn( 'schedules', 'status' );

        $pending = $hasStatus
            ? Schedule::where( 'tenant_id', $tenantId )->where( 'status', 'pending' )->count()
            : Schedule::where( 'tenant_id', $tenantId )->where( 'start_date_time', '>', now() )->count();

        $confirmed = $hasStatus
            ? Schedule::where( 'tenant_id', $tenantId )->where( 'status', 'confirmed' )->count()
            : 0;

        $completed = $hasStatus
            ? Schedule::where( 'tenant_id', $tenantId )->where( 'status', 'completed' )->count()
            : Schedule::where( 'tenant_id', $tenantId )->where( 'end_date_time', '<', now() )->count();

        $cancelled = $hasStatus
            ? Schedule::where( 'tenant_id', $tenantId )->where( 'status', 'cancelled' )->count()
            : 0;

        $noShow = $hasStatus
            ? Schedule::where( 'tenant_id', $tenantId )->where( 'status', 'no_show' )->count()
            : 0;

        $upcomingCount = Schedule::where( 'tenant_id', $tenantId )
            ->when( $hasStatus, function ( $q ) {
                $q->where( 'status', '!=', 'cancelled' );
            } )
            ->where( 'start_date_time', '>=', now() )
            ->count();

        $recentUpcoming = Schedule::where( 'tenant_id', $tenantId )
            ->when( $hasStatus, function ( $q ) {
                $q->where( 'status', '!=', 'cancelled' );
            } )
            ->where( 'start_date_time', '>=', now()->subDays( 1 ) )
            ->orderBy( 'start_date_time' )
            ->limit( 10 )
            ->with( [ 'service.customer', 'service' ] )
            ->get();

        $statusBreakdown = [
            'pending'   => [ 'count' => $pending, 'color' => '#F59E0B' ],
            'confirmed' => [ 'count' => $confirmed, 'color' => '#3B82F6' ],
            'completed' => [ 'count' => $completed, 'color' => '#10B981' ],
            'cancelled' => [ 'count' => $cancelled, 'color' => '#EF4444' ],
            'no_show'   => [ 'count' => $noShow, 'color' => '#6B7280' ],
        ];

        $stats = [
            'total_schedules'     => $total,
            'pending_schedules'   => $pending,
            'confirmed_schedules' => $confirmed,
            'completed_schedules' => $completed,
            'cancelled_schedules' => $cancelled,
            'no_show_schedules'   => $noShow,
            'upcoming_schedules'  => $upcomingCount,
            'status_breakdown'    => $statusBreakdown,
            'recent_upcoming'     => $recentUpcoming,
        ];

        return view( 'pages.schedule.dashboard', compact( 'stats' ) );
    }

    /**
     * Exibe o calendário de disponibilidade de um prestador
     */
    public function calendar( Request $request, ?string $providerId = null ): View
    {
        /** @var User $user */
        $user = Auth::user();

        // Se não for especificado um prestador, usa o usuário logado (se for prestador)
        $targetProviderId = $providerId ?? ( $user->isProvider() ? $user->id : null );

        if ( !$targetProviderId ) {
            abort( 404, 'Prestador não encontrado' );
        }

        $month = $request->input( 'month', now()->format( 'Y-m' ) );

        $result = $this->scheduleService->getAvailabilityCalendar( $targetProviderId, $month );

        return view( 'pages.schedule.calendar', [
            'calendar'   => $this->getServiceData( $result, [] ),
            'providerId' => $targetProviderId,
            'month'      => $month,
        ] );
    }

    /**
     * Exibe o formulário de criação de agendamento
     */
    public function create( Request $request, string $serviceCode ): View
    {
        /** @var User $user */
        $user = Auth::user();

        // Buscar o serviço pelo código
        $service = Service::where( 'code', $serviceCode )
            ->where( 'tenant_id', $user->tenant_id )
            ->with( [ 'customer.commonData', 'status' ] )
            ->first();

        if ( !$service ) {
            abort( 404, 'Serviço não encontrado' );
        }

        // Verificar se o usuário tem permissão para agendar este serviço
        if ( !$user->isAdmin() && $service->customer_id !== $user->id ) {
            abort( 403, 'Acesso não autorizado' );
        }

        return view( 'pages.schedule.create', [
            'service'      => $service,
            'selectedDate' => $request->input( 'date' ),
            'selectedTime' => $request->input( 'time' ),
        ] );
    }

    /**
     * Cria um novo agendamento
     */
    public function store( ScheduleRequest $request ): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        try {
            $data = array_merge( $request->validated(), [
                'customer_id' => $user->isCustomer() ? $user->id : $request->input( 'customer_id' ),
                'created_by'  => $user->id,
            ] );

            $result = $this->scheduleService->createSchedule( $data );

            if ( $result->isSuccess() ) {
                return $this->redirectSuccess(
                    'schedules.index',
                    'Agendamento criado com sucesso!',
                );
            }

            return redirect()->back()
                ->withInput()
                ->with( 'error', $this->getServiceErrorMessage( $result, 'Erro ao criar agendamento' ) );

        } catch ( \Exception $e ) {
            return redirect()->back()
                ->withInput()
                ->with( 'error', 'Erro ao criar agendamento: ' . $e->getMessage() );
        }
    }

    /**
     * Exibe os detalhes de um agendamento
     */
    public function show( string $id ): View
    {
        /** @var User $user */
        $user = Auth::user();

        $result = $this->scheduleService->getSchedule( $id );

        if ( !$result->isSuccess() ) {
            abort( 404, 'Agendamento não encontrado' );
        }

        $schedule = $result->getData();

        // Verifica se o usuário tem permissão para ver este agendamento
        if (
            !$user->isAdmin() &&
            $schedule->customer_id !== $user->id &&
            $schedule->provider_id !== $user->id
        ) {
            abort( 403, 'Acesso não autorizado' );
        }

        return view( 'pages.schedule.show', [
            'schedule' => $schedule,
        ] );
    }

    /**
     * Atualiza o status de um agendamento
     */
    public function updateStatus( Request $request, string $id ): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $request->validate( [
            'status' => 'required|in:confirmed,cancelled,completed,no_show',
        ] );

        try {
            $result = $this->scheduleService->updateScheduleStatus(
                $id,
                $request->input( 'status' ),
                $user->id,
            );

            return $this->jsonResponse( $result );

        } catch ( \Exception $e ) {
            return $this->jsonError( 'Erro ao atualizar status: ' . $e->getMessage() );
        }
    }

    /**
     * Cancela um agendamento
     */
    public function cancel( string $id ): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        try {
            $result = $this->scheduleService->cancelSchedule( $id, $user->id );

            return $this->redirectWithServiceResult(
                'schedules.index',
                $result,
                'Agendamento cancelado com sucesso!',
            );

        } catch ( \Exception $e ) {
            return redirect()->back()
                ->with( 'error', 'Erro ao cancelar agendamento: ' . $e->getMessage() );
        }
    }

    /**
     * Verifica disponibilidade de horários para agendamento
     */
    public function checkAvailability( Request $request ): JsonResponse
    {
        $request->validate( [
            'provider_id' => 'required|exists:users,id',
            'date'        => 'required|date|after:today',
            'duration'    => 'nullable|integer|min:30|max:480', // 30 minutos a 8 horas
        ] );

        try {
            $result = $this->scheduleService->checkAvailability(
                $request->input( 'provider_id' ),
                $request->input( 'date' ),
                $request->input( 'duration', 60 ),
            );

            return $this->jsonResponse( $result );

        } catch ( \Exception $e ) {
            return $this->jsonError( 'Erro ao verificar disponibilidade: ' . $e->getMessage() );
        }
    }

    /**
     * Obtém os horários disponíveis de um prestador em uma data específica
     */
    public function getAvailableSlots( Request $request ): JsonResponse
    {
        $request->validate( [
            'provider_id'      => 'required|exists:users,id',
            'date'             => 'required|date|after:today',
            'service_duration' => 'nullable|integer|min:30|max:480',
        ] );

        try {
            $result = $this->scheduleService->getAvailableTimeSlots(
                $request->input( 'provider_id' ),
                $request->input( 'date' ),
                $request->input( 'service_duration', 60 ),
            );

            return $this->jsonResponse( $result );

        } catch ( \Exception $e ) {
            return $this->jsonError( 'Erro ao obter horários disponíveis: ' . $e->getMessage() );
        }
    }

}
