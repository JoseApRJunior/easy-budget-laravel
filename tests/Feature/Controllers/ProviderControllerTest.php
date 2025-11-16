<?php

namespace Tests\Feature\Controllers;

use App\Models\Provider;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProviderControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Tenant $tenant;
    private Provider $provider;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->tenant = Tenant::factory()->create();
        $this->provider = Provider::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Provider Teste',
            'email' => 'provider@teste.com',
        ]);
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'provider_id' => $this->provider->id,
            'name' => 'Usuário Teste',
            'email' => 'usuario@teste.com',
        ]);
    }

    public function test_dashboard_shows_provider_dashboard(): void
    {
        $response = $this->actingAs($this->user)
            ->get('/provider/dashboard');

        $response->assertStatus(200);
        $response->assertViewIs('pages.provider.dashboard');
        $response->assertViewHas('provider', $this->provider);
        $response->assertViewHas('stats');
        $response->assertViewHas('recentActivities');
    }

    public function test_dashboard_requires_authentication(): void
    {
        $response = $this->get('/provider/dashboard');
        $response->assertRedirect('/login');
    }

    public function test_change_password_form_is_accessible(): void
    {
        $response = $this->actingAs($this->user)
            ->get('/provider/change-password');

        $response->assertStatus(200);
        $response->assertViewIs('pages.provider.change-password');
    }

    public function test_change_password_requires_authentication(): void
    {
        $response = $this->get('/provider/change-password');
        $response->assertRedirect('/login');
    }

    public function test_change_password_successfully(): void
    {
        $currentPassword = 'oldpassword123';
        $newPassword = 'newpassword123';
        
        $this->user->update(['password' => Hash::make($currentPassword)]);

        $response = $this->actingAs($this->user)
            ->put('/provider/change-password', [
                'current_password' => $currentPassword,
                'password' => $newPassword,
                'password_confirmation' => $newPassword,
            ]);

        $response->assertRedirect('/provider/dashboard');
        $response->assertSessionHas('success', 'Senha alterada com sucesso!');
        
        $this->assertTrue(Hash::check($newPassword, $this->user->fresh()->password));
    }

    public function test_change_password_fails_with_wrong_current_password(): void
    {
        $response = $this->actingAs($this->user)
            ->put('/provider/change-password', [
                'current_password' => 'wrongpassword',
                'password' => 'newpassword123',
                'password_confirmation' => 'newpassword123',
            ]);

        $response->assertRedirect('/provider/change-password');
        $response->assertSessionHasErrors('current_password');
    }

    public function test_change_password_fails_with_mismatched_confirmation(): void
    {
        $currentPassword = 'oldpassword123';
        $this->user->update(['password' => Hash::make($currentPassword)]);

        $response = $this->actingAs($this->user)
            ->put('/provider/change-password', [
                'current_password' => $currentPassword,
                'password' => 'newpassword123',
                'password_confirmation' => 'differentpassword',
            ]);

        $response->assertRedirect('/provider/change-password');
        $response->assertSessionHasErrors('password');
    }

    public function test_change_password_fails_with_short_password(): void
    {
        $currentPassword = 'oldpassword123';
        $this->user->update(['password' => Hash::make($currentPassword)]);

        $response = $this->actingAs($this->user)
            ->put('/provider/change-password', [
                'current_password' => $currentPassword,
                'password' => '123',
                'password_confirmation' => '123',
            ]);

        $response->assertRedirect('/provider/change-password');
        $response->assertSessionHasErrors('password');
    }

    public function test_update_profile_redirects_to_business_settings(): void
    {
        $response = $this->actingAs($this->user)
            ->put('/provider/update-profile', []);

        $response->assertRedirect('/provider/business-settings');
    }

    public function test_update_business_redirects_to_business_settings(): void
    {
        $response = $this->actingAs($this->user)
            ->put('/provider/update-business', []);

        $response->assertRedirect('/provider/business-settings');
    }

    public function test_provider_data_is_tenant_scoped(): void
    {
        // Criar outro tenant e provider
        $otherTenant = Tenant::factory()->create();
        $otherProvider = Provider::factory()->create([
            'tenant_id' => $otherTenant->id,
            'name' => 'Outro Provider',
        ]);
        $otherUser = User::factory()->create([
            'tenant_id' => $otherTenant->id,
            'provider_id' => $otherProvider->id,
        ]);

        // Usuário do primeiro tenant não deve ver dados do segundo
        $response = $this->actingAs($this->user)
            ->get('/provider/dashboard');

        $response->assertStatus(200);
        $viewProvider = $response->viewData('provider');
        $this->assertEquals($this->provider->id, $viewProvider->id);
        $this->assertNotEquals($otherProvider->id, $viewProvider->id);
    }

    public function test_provider_dashboard_shows_correct_stats(): void
    {
        $response = $this->actingAs($this->user)
            ->get('/provider/dashboard');

        $response->assertStatus(200);
        $stats = $response->viewData('stats');
        
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('totalCustomers', $stats);
        $this->assertArrayHasKey('totalBudgets', $stats);
        $this->assertArrayHasKey('totalInvoices', $stats);
        $this->assertArrayHasKey('totalRevenue', $stats);
    }

    public function test_provider_dashboard_shows_recent_activities(): void
    {
        $response = $this->actingAs($this->user)
            ->get('/provider/dashboard');

        $response->assertStatus(200);
        $recentActivities = $response->viewData('recentActivities');
        
        $this->assertIsArray($recentActivities);
    }
}