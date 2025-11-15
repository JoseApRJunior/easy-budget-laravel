<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use App\Models\Service;
use App\Repositories\ScheduleRepository;
use App\Services\Domain\ScheduleService;
use App\Support\ServiceResult;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ScheduleController extends Controller
{
    /**
     * @var ScheduleService
     */
    protected $scheduleService;

    /**
     * @var ScheduleRepository
     */
    protected $scheduleRepository;

    /**
     * ScheduleController constructor.
     *
     * @param ScheduleService $scheduleService
     * @param ScheduleRepository $scheduleRepository
     */
    public function __construct(ScheduleService $scheduleService, ScheduleRepository $scheduleRepository)
    {
        $this->scheduleService = $scheduleService;
        $this->scheduleRepository = $scheduleRepository;
    }

    /**
     * Display a listing of schedules.
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
        
        $schedules = $this->scheduleRepository->getByDateRange($startDate, $endDate);
        $upcomingSchedules = $this->scheduleService->getUpcomingSchedules(5);

        return view('pages.schedule.index', compact('schedules', 'upcomingSchedules', 'startDate', 'endDate'));
    }

    /**
     * Display schedules in calendar format.
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function calendar(Request $request)
    {
        $month = $request->get('month', Carbon::now()->format('Y-m'));
        $startDate = Carbon::parse($month . '-01')->startOfMonth();
        $endDate = Carbon::parse($month . '-01')->endOfMonth();

        $schedules = $this->scheduleRepository->getByDateRange(
            $startDate->format('Y-m-d'),
            $endDate->format('Y-m-d')
        );

        return view('pages.schedule.calendar', compact('schedules', 'month'));
    }

    /**
     * Show the form for creating a new schedule.
     *
     * @param Service $service
     * @return \Illuminate\View\View
     */
    public function create(Service $service)
    {
        return view('pages.schedule.create', compact('service'));
    }

    /**
     * Store a newly created schedule.
     *
     * @param Request $request
     * @param Service $service
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request, Service $service)
    {
        $validated = $request->validate([
            'start_date_time' => 'required|date|after:now',
            'end_date_time' => 'required|date|after:start_date_time',
            'location' => 'nullable|string|max:500',
        ]);

        $result = $this->scheduleService->handleScheduledStatus($service, $validated);

        if ($result->isSuccess()) {
            return redirect()->route('services.show', $service)
                ->with('success', 'Agendamento criado com sucesso!');
        }

        return redirect()->back()
            ->withInput()
            ->with('error', $result->getMessage());
    }

    /**
     * Display the specified schedule.
     *
     * @param Schedule $schedule
     * @return \Illuminate\View\View
     */
    public function show(Schedule $schedule)
    {
        $this->authorize('view', $schedule);

        $schedule->load(['service', 'service.customer', 'userConfirmationToken']);

        return view('pages.schedule.show', compact('schedule'));
    }

    /**
     * Show the form for editing the specified schedule.
     *
     * @param Schedule $schedule
     * @return \Illuminate\View\View
     */
    public function edit(Schedule $schedule)
    {
        $this->authorize('update', $schedule);

        $schedule->load(['service', 'service.customer']);

        return view('pages.schedule.edit', compact('schedule'));
    }

    /**
     * Update the specified schedule in storage.
     *
     * @param Request $request
     * @param Schedule $schedule
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Schedule $schedule)
    {
        $this->authorize('update', $schedule);

        $validated = $request->validate([
            'start_date_time' => 'required|date|after:now',
            'end_date_time' => 'required|date|after:start_date_time',
            'location' => 'nullable|string|max:500',
        ]);

        $result = $this->scheduleService->updateScheduledToken($schedule, $validated);

        if ($result->isSuccess()) {
            return redirect()->route('schedules.show', $schedule)
                ->with('success', 'Agendamento atualizado com sucesso!');
        }

        return redirect()->back()
            ->withInput()
            ->with('error', $result->getMessage());
    }

    /**
     * Remove the specified schedule from storage.
     *
     * @param Schedule $schedule
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Schedule $schedule)
    {
        $this->authorize('delete', $schedule);

        $result = $this->scheduleService->deleteSchedule($schedule);

        if ($result->isSuccess()) {
            return redirect()->route('schedules.index')
                ->with('success', 'Agendamento excluído com sucesso!');
        }

        return redirect()->back()
            ->with('error', $result->getMessage());
    }

    /**
     * Get schedule data for AJAX calendar.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCalendarData(Request $request)
    {
        $start = $request->get('start');
        $end = $request->get('end');

        $schedules = $this->scheduleRepository->getByDateRange($start, $end);

        $events = $schedules->map(function ($schedule) {
            return [
                'id' => $schedule->id,
                'title' => $schedule->service->title . ' - ' . $schedule->service->customer->name,
                'start' => $schedule->start_date_time->format('Y-m-d\TH:i:s'),
                'end' => $schedule->end_date_time->format('Y-m-d\TH:i:s'),
                'location' => $schedule->location,
                'url' => route('schedules.show', $schedule->id),
                'backgroundColor' => '#007bff',
                'borderColor' => '#0056b3',
            ];
        });

        return response()->json($events);
    }

    /**
     * Check for scheduling conflicts.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkConflicts(Request $request)
    {
        $serviceId = $request->get('service_id');
        $startDateTime = $request->get('start_date_time');
        $endDateTime = $request->get('end_date_time');
        $excludeId = $request->get('exclude_id');

        $hasConflict = $this->scheduleRepository->hasConflict(
            $serviceId,
            $startDateTime,
            $endDateTime,
            $excludeId
        );

        return response()->json([
            'has_conflict' => $hasConflict,
        ]);
    }
}