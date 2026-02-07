<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ScheduleStatus;
use App\Models\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ScheduleConfirmationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Evita que notificações reais sejam enviadas durante os testes
        Event::fake([
            \App\Events\StatusUpdated::class,
        ]);
    }

    /**
     * Test that a public confirmation URL is generated correctly.
     */
    public function test_schedule_generates_correct_public_url(): void
    {
        $schedule = Schedule::factory()->create();
        $token = $schedule->userConfirmationToken;

        $publicUrl = $schedule->getPublicUrl();

        $this->assertNotNull($publicUrl);
        $this->assertStringContainsString('/services/schedules/confirm/', $publicUrl);
        $this->assertStringContainsString($token->token, $publicUrl);
    }

    /**
     * Test that a schedule can be confirmed via the public token link.
     */
    public function test_schedule_can_be_confirmed_via_public_link(): void
    {
        $schedule = Schedule::factory()->create([
            'status' => ScheduleStatus::PENDING->value,
        ]);

        $token = $schedule->userConfirmationToken;

        $response = $this->get(route('services.public.schedules.confirm', ['token' => $token->token]));

        $response->assertStatus(200);
        $response->assertViewIs('pages.schedule.confirmation-success');

        $this->assertEquals(ScheduleStatus::CONFIRMED, $schedule->fresh()->status);
    }

    /**
     * Test that an invalid token returns an error page.
     */
    public function test_invalid_token_returns_error_page(): void
    {
        $response = $this->get(route('services.public.schedules.confirm', ['token' => 'invalid-token']));

        $response->assertStatus(200); // We return a view with error, not a 404
        $response->assertViewIs('pages.schedule.confirmation-error');
    }
}
