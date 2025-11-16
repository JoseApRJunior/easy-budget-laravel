<?php

namespace Tests\Feature\Controllers;

use App\Models\Customer;
use App\Models\Provider;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UserConfirmationToken;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Tenant $tenant;
    private Provider $provider;
    private Service $service;
    private Schedule $schedule;
    private UserConfirmationToken $token;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->tenant = Tenant::factory()->create();
        $this->provider = Provider::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'provider_id' => $this->provider->id,
        ]);
        
        $customer = Customer::factory()->create(['tenant_id' => $this->tenant->id]);
        
        $this->service = Service::factory()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $customer->id,
            'code' => 'SERV-001',
            'title' => 'Serviço Teste',
        ]);
        
        $this->token = UserConfirmationToken::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'token' => 'test-token-123',
            'type' => 'schedule_confirmation',
            'expires_at' => Carbon::now()->addDays(7),
        ]);
        
        $this->schedule = Schedule::factory()->create([
            'tenant_id' => $this->tenant->id,
            'service_id' => $this->service->id,
            'user_confirmation_token_id' => $this->token->id,
            'start_date_time' => Carbon::now()->addDays(2),
            'end_date_time' => Carbon::now()->addDays(2)->addHours(2),
            'location' => 'Local do Agendamento',
        ]);
    }

    public function test_index_shows_schedules_list(): void
    {
        $response = $this->actingAs($this->user)
            ->get('/provider/schedules');

        $response->assertStatus(200);
        $response->assertViewIs('pages.schedule.index');
        $response->assertViewHas('schedules');
        $response->assertViewHas('upcomingSchedules');
        $response->assertViewHas('startDate');
        $response->assertViewHas('endDate');
    }

    public function test_index_filters_by_date_range(): void
    {
        $startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
        $endDate = Carbon::now()->endOfMonth()->format('Y-m-d');
        
        $response = $this->actingAs($this->user)
            ->get("/provider/schedules?start_date={$startDate}&end_date={$endDate}");

        $response->assertStatus(200);
        $schedules = $response->viewData('schedules');
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $schedules);
    }

    public function test_calendar_shows_calendar_view(): void
    {
        $month = Carbon::now()->format('Y-m');
        
        $response = $this->actingAs($this->user)
            ->get("/provider/schedules/calendar?month={$month}");

        $response->assertStatus(200);
        $response->assertViewIs('pages.schedule.calendar');
        $response->assertViewHas('schedules');
        $response->assertViewHas('month', $month);
    }

    public function test_create_shows_schedule_form(): void
    {
        $response = $this->actingAs($this->user)
            ->get("/provider/schedules/create/{$this->service->id}");

        $response->assertStatus(200);
        $response->assertViewIs('pages.schedule.create');
        $response->assertViewHas('service', $this->service);
    }

    public function test_store_creates_new_schedule(): void
    {
        $startDateTime = Carbon::now()->addDays(5)->format('Y-m-d H:i:s');
        $endDateTime = Carbon::now()->addDays(5)->addHours(3)->format('Y-m-d H:i:s');
        
        $response = $this->actingAs($this->user)
            ->post("/provider/schedules/{$this->service->id}", [
                'start_date_time' => $startDateTime,
                'end_date_time' => $endDateTime,
                'location' => 'Novo Local',
            ]);

        $response->assertRedirect("/provider/services/{$this->service->id}");
        $response->assertSessionHas('success', 'Agendamento criado com sucesso!');
        
        // Verifica que o agendamento foi criado
        $this->assertDatabaseHas('schedules', [
            'service_id' => $this->service->id,
            'location' => 'Novo Local',
        ]);
    }

    public function test_store_validates_required_fields(): void
    {
        $response = $this->actingAs($this->user)
            ->post("/provider/schedules/{$this->service->id}", []);

        $response->assertSessionHasErrors(['start_date_time', 'end_date_time']);
    }

    public function test_store_validates_date_order(): void
    {
        $startDateTime = Carbon::now()->addDays(5)->format('Y-m-d H:i:s');
        $endDateTime = Carbon::now()->addDays(5)->subHours(1)->format('Y-m-d H:i:s'); // End before start
        
        $response = $this->actingAs($this->user)
            ->post("/provider/schedules/{$this->service->id}", [
                'start_date_time' => $startDateTime,
                'end_date_time' => $endDateTime,
                'location' => 'Local Teste',
            ]);

        $response->assertSessionHasErrors(['end_date_time']);
    }

    public function test_store_validates_past_dates(): void
    {
        $startDateTime = Carbon::now()->subDays(1)->format('Y-m-d H:i:s'); // Past date
        $endDateTime = Carbon::now()->subDays(1)->addHours(2)->format('Y-m-d H:i:s');
        
        $response = $this->actingAs($this->user)
            ->post("/provider/schedules/{$this->service->id}", [
                'start_date_time' => $startDateTime,
                'end_date_time' => $endDateTime,
                'location' => 'Local Teste',
            ]);

        $response->assertSessionHasErrors(['start_date_time']);
    }

    public function test_show_displays_schedule_details(): void
    {
        $response = $this->actingAs($this->user)
            ->get("/provider/schedules/{$this->schedule->id}");

        $response->assertStatus(200);
        $response->assertViewIs('pages.schedule.show');
        $response->assertViewHas('schedule');
        
        $schedule = $response->viewData('schedule');
        $this->assertTrue($schedule->relationLoaded('service'));
        $this->assertTrue($schedule->relationLoaded('service.customer'));
        $this->assertTrue($schedule->relationLoaded('userConfirmationToken'));
    }

    public function test_edit_shows_edit_form(): void
    {
        $response = $this->actingAs($this->user)
            ->get("/provider/schedules/{$this->schedule->id}/edit");

        $response->assertStatus(200);
        $response->assertViewIs('pages.schedule.edit');
        $response->assertViewHas('schedule');
        
        $schedule = $response->viewData('schedule');
        $this->assertTrue($schedule->relationLoaded('service'));
        $this->assertTrue($schedule->relationLoaded('service.customer'));
    }

    public function test_update_modifies_existing_schedule(): void
    {
        $newStartDateTime = Carbon::now()->addDays(3)->format('Y-m-d H:i:s');
        $newEndDateTime = Carbon::now()->addDays(3)->addHours(2)->format('Y-m-d H:i:s');
        
        $response = $this->actingAs($this->user)
            ->put("/provider/schedules/{$this->schedule->id}", [
                'start_date_time' => $newStartDateTime,
                'end_date_time' => $newEndDateTime,
                'location' => 'Local Atualizado',
            ]);

        $response->assertRedirect("/provider/schedules/{$this->schedule->id}");
        $response->assertSessionHas('success', 'Agendamento atualizado com sucesso!');
        
        // Verifica que o agendamento foi atualizado
        $this->schedule->refresh();
        $this->assertEquals($newStartDateTime, $this->schedule->start_date_time->format('Y-m-d H:i:s'));
        $this->assertEquals('Local Atualizado', $this->schedule->location);
    }

    public function test_destroy_deletes_schedule(): void
    {
        $response = $this->actingAs($this->user)
            ->delete("/provider/schedules/{$this->schedule->id}");

        $response->assertRedirect('/provider/schedules');
        $response->assertSessionHas('success', 'Agendamento excluído com sucesso!');
        
        // Verifica que o agendamento foi excluído
        $this->assertDatabaseMissing('schedules', [
            'id' => $this->schedule->id,
        ]);
    }

    public function test_get_calendar_data_returns_json_events(): void
    {
        $start = Carbon::now()->startOfMonth()->format('Y-m-d');
        $end = Carbon::now()->endOfMonth()->format('Y-m-d');
        
        $response = $this->actingAs($this->user)
            ->getJson("/provider/schedules/calendar/data?start={$start}&end={$end}");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            '*' => [
                'id',
                'title',
                'start',
                'end',
                'location',
                'url',
                'backgroundColor',
                'borderColor',
            ],
        ]);
    }

    public function test_check_conflicts_detects_conflicting_schedules(): void
    {
        // Criar um agendamento conflitante
        $conflictingSchedule = Schedule::factory()->create([
            'tenant_id' => $this->tenant->id,
            'service_id' => $this->service->id,
            'start_date_time' => Carbon::now()->addDays(2)->addHours(1), // Conflita com o schedule existente
            'end_date_time' => Carbon::now()->addDays(2)->addHours(3),
        ]);
        
        $response = $this->actingAs($this->user)
            ->getJson("/provider/schedules/check-conflicts?service_id={$this->service->id}&start_date_time=" . 
                     Carbon::now()->addDays(2)->addHours(2)->format('Y-m-d H:i:s') . 
                     "&end_date_time=" . Carbon::now()->addDays(2)->addHours(4)->format('Y-m-d H:i:s'));

        $response->assertStatus(200);
        $response->assertJson([
            'has_conflict' => true,
        ]);
    }

    public function test_check_conflicts_allows_non_conflicting_schedules(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson("/provider/schedules/check-conflicts?service_id={$this->service->id}&start_date_time=" . 
                     Carbon::now()->addDays(5)->format('Y-m-d H:i:s') . 
                     "&end_date_time=" . Carbon::now()->addDays(5)->addHours(2)->format('Y-m-d H:i:s'));

        $response->assertStatus(200);
        $response->assertJson([
            'has_conflict' => false,
        ]);
    }

    public function test_check_conflicts_excludes_current_schedule_when_updating(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson("/provider/schedules/check-conflicts?service_id={$this->service->id}&start_date_time=" . 
                     $this->schedule->start_date_time->format('Y-m-d H:i:s') . 
                     "&end_date_time=" . $this->schedule->end_date_time->format('Y-m-d H:i:s') . 
                     "&exclude_id={$this->schedule->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'has_conflict' => false,
        ]);
    }
}