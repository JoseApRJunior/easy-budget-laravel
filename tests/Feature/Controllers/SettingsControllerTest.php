<?php

namespace Tests\Feature\Controllers;

use App\Models\Provider;
use App\Models\ProviderCredential;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Infrastructure\EncryptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SettingsControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Tenant $tenant;
    private Provider $provider;
    private EncryptionService $encryptionService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->encryptionService = new EncryptionService();
        
        $this->tenant = Tenant::factory()->create();
        $this->provider = Provider::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'provider_id' => $this->provider->id,
        ]);
    }

    public function test_index_shows_settings_page_with_all_tabs(): void
    {
        $response = $this->actingAs($this->user)
            ->get('/settings');

        $response->assertStatus(200);
        $response->assertViewIs('pages.settings.index');
        $response->assertViewHas('activeTab', 'general');
        $response->assertViewHas('tabs');
        $response->assertViewHas('userSettings');
        $response->assertViewHas('systemSettings');
    }

    public function test_index_shows_specific_tab(): void
    {
        $response = $this->actingAs($this->user)
            ->get('/settings?tab=profile');

        $response->assertStatus(200);
        $response->assertViewHas('activeTab', 'profile');
    }

    public function test_index_shows_integrations_with_real_data(): void
    {
        // Criar credencial do Mercado Pago
        ProviderCredential::factory()->create([
            'tenant_id' => $this->tenant->id,
            'provider_id' => $this->provider->id,
            'payment_gateway' => 'mercadopago',
            'access_token_encrypted' => $this->encryptionService->encrypt('test_token'),
            'refresh_token_encrypted' => $this->encryptionService->encrypt('refresh_token'),
            'public_key' => 'TEST-key',
            'user_id_gateway' => '123456',
            'expires_in' => 21600,
        ]);

        $response = $this->actingAs($this->user)
            ->get('/settings?tab=integrations');

        $response->assertStatus(200);
        $response->assertViewHas('tabs');
        
        $tabs = $response->viewData('tabs');
        $this->assertArrayHasKey('integrations', $tabs);
        $this->assertArrayHasKey('data', $tabs['integrations']);
        $this->assertArrayHasKey('integrations', $tabs['integrations']['data']);
        
        $integrations = $tabs['integrations']['data']['integrations'];
        $this->assertArrayHasKey('mercadopago', $integrations);
        $this->assertEquals('connected', $integrations['mercadopago']['status']);
        $this->assertNotNull($integrations['mercadopago']['last_sync']);
    }

    public function test_update_general_settings_successfully(): void
    {
        $data = [
            'company_name' => 'Nova Empresa Teste',
            'company_email' => 'novo@teste.com',
            'company_phone' => '(11) 98765-4321',
            'company_address' => 'Rua Nova, 123',
            'timezone' => 'America/Sao_Paulo',
            'currency' => 'BRL',
            'language' => 'pt_BR',
        ];

        $response = $this->actingAs($this->user)
            ->put('/settings/general', $data);

        $response->assertRedirect('/settings?tab=general');
        $response->assertSessionHas('success', 'Configurações gerais atualizadas com sucesso!');
    }

    public function test_update_profile_settings_successfully(): void
    {
        $data = [
            'name' => 'Nome Atualizado',
            'email' => 'novoemail@teste.com',
            'phone' => '(11) 98765-4321',
        ];

        $response = $this->actingAs($this->user)
            ->put('/settings/profile', $data);

        $response->assertRedirect('/settings?tab=profile');
        $response->assertSessionHas('success', 'Perfil atualizado com sucesso!');
        
        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'name' => 'Nome Atualizado',
            'email' => 'novoemail@teste.com',
        ]);
    }

    public function test_update_security_password_successfully(): void
    {
        $currentPassword = 'password123';
        $newPassword = 'newpassword123';
        
        $this->user->update(['password' => Hash::make($currentPassword)]);

        $data = [
            'current_password' => $currentPassword,
            'password' => $newPassword,
            'password_confirmation' => $newPassword,
        ];

        $response = $this->actingAs($this->user)
            ->put('/settings/security', $data);

        $response->assertRedirect('/settings?tab=security');
        $response->assertSessionHas('success', 'Senha alterada com sucesso!');
        
        $this->assertTrue(Hash::check($newPassword, $this->user->fresh()->password));
    }

    public function test_update_security_password_fails_with_wrong_current_password(): void
    {
        $data = [
            'current_password' => 'wrongpassword',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ];

        $response = $this->actingAs($this->user)
            ->put('/settings/security', $data);

        $response->assertRedirect('/settings?tab=security');
        $response->assertSessionHasErrors('current_password');
    }

    public function test_update_notifications_settings_successfully(): void
    {
        $data = [
            'email_notifications' => true,
            'sms_notifications' => false,
            'notification_frequency' => 'daily',
            'marketing_emails' => false,
        ];

        $response = $this->actingAs($this->user)
            ->put('/settings/notifications', $data);

        $response->assertRedirect('/settings?tab=notifications');
        $response->assertSessionHas('success', 'Configurações de notificação atualizadas com sucesso!');
    }

    public function test_update_avatar_successfully(): void
    {
        Storage::fake('public');
        
        $file = UploadedFile::fake()->image('avatar.jpg', 200, 200);

        $response = $this->actingAs($this->user)
            ->post('/settings/avatar', [
                'avatar' => $file,
            ]);

        $response->assertRedirect('/settings?tab=profile');
        $response->assertSessionHas('success', 'Avatar atualizado com sucesso!');
        
        Storage::disk('public')->assertExists('avatars/' . $this->user->id . '.jpg');
    }

    public function test_remove_avatar_successfully(): void
    {
        Storage::fake('public');
        
        // Criar avatar fake
        $avatarPath = 'avatars/' . $this->user->id . '.jpg';
        Storage::disk('public')->put($avatarPath, 'fake content');

        $response = $this->actingAs($this->user)
            ->delete('/settings/avatar');

        $response->assertRedirect('/settings?tab=profile');
        $response->assertSessionHas('success', 'Avatar removido com sucesso!');
        
        Storage::disk('public')->assertMissing($avatarPath);
    }

    public function test_update_company_logo_successfully(): void
    {
        Storage::fake('public');
        
        $file = UploadedFile::fake()->image('logo.png', 300, 100);

        $response = $this->actingAs($this->user)
            ->post('/settings/logo', [
                'logo' => $file,
            ]);

        $response->assertRedirect('/settings?tab=customization');
        $response->assertSessionHas('success', 'Logo da empresa atualizada com sucesso!');
        
        Storage::disk('public')->assertExists('logos/' . $this->provider->id . '.png');
    }

    public function test_unauthenticated_user_cannot_access_settings(): void
    {
        $response = $this->get('/settings');
        $response->assertRedirect('/login');

        $response = $this->put('/settings/general', []);
        $response->assertRedirect('/login');

        $response = $this->put('/settings/profile', []);
        $response->assertRedirect('/login');

        $response = $this->put('/settings/security', []);
        $response->assertRedirect('/login');

        $response = $this->post('/settings/avatar', []);
        $response->assertRedirect('/login');
    }
}