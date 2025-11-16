<?php

namespace Tests\Feature\Controllers\Integrations;

use App\Models\Provider;
use App\Models\ProviderCredential;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Infrastructure\EncryptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MercadoPagoControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Tenant $tenant;
    private Provider $provider;
    private EncryptionService $encryptionService;

    protected function setUp(): void
    {
        parent::setUp();
        
        Config::set('services.mercadopago.client_id', 'test_client_id');
        Config::set('services.mercadopago.client_secret', 'test_client_secret');
        Config::set('services.mercadopago.oauth.redirect_uri', 'http://localhost/integrations/mercadopago/callback');
        
        $this->encryptionService = new EncryptionService();
        
        $this->tenant = Tenant::factory()->create();
        $this->provider = Provider::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'provider_id' => $this->provider->id,
        ]);
    }

    public function test_index_shows_integration_page_without_credentials(): void
    {
        $response = $this->actingAs($this->user)
            ->get('/integrations/mercadopago');

        $response->assertStatus(200);
        $response->assertViewIs('pages.mercadopago.index');
        $response->assertViewHas('isConnected', false);
        $response->assertViewHas('authorizationUrl');
        $response->assertViewMissing('credentials');
    }

    public function test_index_shows_integration_page_with_credentials(): void
    {
        $credential = ProviderCredential::factory()->create([
            'tenant_id' => $this->tenant->id,
            'provider_id' => $this->provider->id,
            'payment_gateway' => 'mercadopago',
            'access_token_encrypted' => $this->encryptionService->encrypt('test_access_token'),
            'refresh_token_encrypted' => $this->encryptionService->encrypt('test_refresh_token'),
            'public_key' => 'TEST-public-key',
            'user_id_gateway' => '123456789',
            'expires_in' => 21600,
        ]);

        $response = $this->actingAs($this->user)
            ->get('/integrations/mercadopago');

        $response->assertStatus(200);
        $response->assertViewIs('pages.mercadopago.index');
        $response->assertViewHas('isConnected', true);
        $response->assertViewHas('credentials');
        $response->assertViewHas('lastSync');
    }

    public function test_callback_successfully_exchanges_code(): void
    {
        $state = encrypt(json_encode(['tenant_id' => $this->tenant->id, 'provider_id' => $this->provider->id]));
        $code = 'test_authorization_code';
        
        $oauthResponse = [
            'access_token' => 'test_access_token_123',
            'refresh_token' => 'test_refresh_token_456',
            'expires_in' => 21600,
            'user_id' => '123456789',
            'public_key' => 'TEST-public-key',
        ];

        Http::fake([
            'https://api.mercadopago.com/oauth/token' => Http::response($oauthResponse, 200),
        ]);

        $response = $this->actingAs($this->user)
            ->get('/integrations/mercadopago/callback', [
                'code' => $code,
                'state' => $state,
            ]);

        $response->assertRedirect('/integrations/mercadopago');
        $response->assertSessionHas('success', 'Integração com Mercado Pago realizada com sucesso!');

        $this->assertDatabaseHas('provider_credentials', [
            'tenant_id' => $this->tenant->id,
            'provider_id' => $this->provider->id,
            'payment_gateway' => 'mercadopago',
            'public_key' => 'TEST-public-key',
            'user_id_gateway' => '123456789',
            'expires_in' => 21600,
        ]);
    }

    public function test_callback_handles_invalid_state(): void
    {
        $response = $this->actingAs($this->user)
            ->get('/integrations/mercadopago/callback', [
                'code' => 'test_code',
                'state' => 'invalid_state',
            ]);

        $response->assertRedirect('/integrations/mercadopago');
        $response->assertSessionHas('error', 'Estado inválido ou sessão expirada.');
    }

    public function test_callback_handles_missing_code(): void
    {
        $state = encrypt(json_encode(['tenant_id' => $this->tenant->id, 'provider_id' => $this->provider->id]));
        
        $response = $this->actingAs($this->user)
            ->get('/integrations/mercadopago/callback', [
                'state' => $state,
                'error' => 'access_denied',
                'error_description' => 'User denied access',
            ]);

        $response->assertRedirect('/integrations/mercadopago');
        $response->assertSessionHas('error', 'Autorização negada: User denied access');
    }

    public function test_callback_handles_oauth_error(): void
    {
        $state = encrypt(json_encode(['tenant_id' => $this->tenant->id, 'provider_id' => $this->provider->id]));
        $code = 'invalid_code';
        
        Http::fake([
            'https://api.mercadopago.com/oauth/token' => Http::response([
                'message' => 'Invalid authorization code',
                'error' => 'invalid_grant',
            ], 400),
        ]);

        $response = $this->actingAs($this->user)
            ->get('/integrations/mercadopago/callback', [
                'code' => $code,
                'state' => $state,
            ]);

        $response->assertRedirect('/integrations/mercadopago');
        $response->assertSessionHas('error');
        $this->assertStringContainsString('Erro ao conectar com Mercado Pago', session('error'));
    }

    public function test_disconnect_removes_credentials(): void
    {
        $credential = ProviderCredential::factory()->create([
            'tenant_id' => $this->tenant->id,
            'provider_id' => $this->provider->id,
            'payment_gateway' => 'mercadopago',
        ]);

        $response = $this->actingAs($this->user)
            ->delete('/integrations/mercadopago/disconnect');

        $response->assertRedirect('/integrations/mercadopago');
        $response->assertSessionHas('success', 'Integração desconectada com sucesso!');

        $this->assertDatabaseMissing('provider_credentials', [
            'id' => $credential->id,
        ]);
    }

    public function test_disconnect_handles_no_credentials(): void
    {
        $response = $this->actingAs($this->user)
            ->delete('/integrations/mercadopago/disconnect');

        $response->assertRedirect('/integrations/mercadopago');
        $response->assertSessionHas('info', 'Nenhuma integração ativa encontrada.');
    }

    public function test_refresh_successfully_updates_token(): void
    {
        $credential = ProviderCredential::factory()->create([
            'tenant_id' => $this->tenant->id,
            'provider_id' => $this->provider->id,
            'payment_gateway' => 'mercadopago',
            'access_token_encrypted' => $this->encryptionService->encrypt('old_access_token'),
            'refresh_token_encrypted' => $this->encryptionService->encrypt('valid_refresh_token'),
            'public_key' => 'OLD-public-key',
            'user_id_gateway' => '123456789',
            'expires_in' => 21600,
        ]);

        $newTokenResponse = [
            'access_token' => 'new_access_token_789',
            'refresh_token' => 'new_refresh_token_012',
            'expires_in' => 21600,
            'user_id' => '123456789',
            'public_key' => 'NEW-public-key',
        ];

        Http::fake([
            'https://api.mercadopago.com/oauth/token' => Http::response($newTokenResponse, 200),
        ]);

        $response = $this->actingAs($this->user)
            ->post('/integrations/mercadopago/refresh');

        $response->assertRedirect('/integrations/mercadopago');
        $response->assertSessionHas('success', 'Token atualizado com sucesso!');

        $credential->refresh();
        $this->assertEquals('NEW-public-key', $credential->public_key);
        $this->assertEquals(21600, $credential->expires_in);
    }

    public function test_refresh_handles_invalid_refresh_token(): void
    {
        $credential = ProviderCredential::factory()->create([
            'tenant_id' => $this->tenant->id,
            'provider_id' => $this->provider->id,
            'payment_gateway' => 'mercadopago',
            'access_token_encrypted' => $this->encryptionService->encrypt('old_access_token'),
            'refresh_token_encrypted' => $this->encryptionService->encrypt('invalid_refresh_token'),
            'public_key' => 'OLD-public-key',
            'user_id_gateway' => '123456789',
            'expires_in' => 21600,
        ]);

        Http::fake([
            'https://api.mercadopago.com/oauth/token' => Http::response([
                'message' => 'Invalid refresh token',
                'error' => 'invalid_grant',
            ], 400),
        ]);

        $response = $this->actingAs($this->user)
            ->post('/integrations/mercadopago/refresh');

        $response->assertRedirect('/integrations/mercadopago');
        $response->assertSessionHas('error');
        $this->assertStringContainsString('Erro ao atualizar token', session('error'));
    }

    public function test_refresh_handles_no_credentials(): void
    {
        $response = $this->actingAs($this->user)
            ->post('/integrations/mercadopago/refresh');

        $response->assertRedirect('/integrations/mercadopago');
        $response->assertSessionHas('warning', 'Nenhuma credencial encontrada para atualizar.');
    }

    public function test_unauthenticated_user_cannot_access_integration_pages(): void
    {
        $response = $this->get('/integrations/mercadopago');
        $response->assertRedirect('/login');

        $response = $this->get('/integrations/mercadopago/callback?code=test&state=test');
        $response->assertRedirect('/login');

        $response = $this->delete('/integrations/mercadopago/disconnect');
        $response->assertRedirect('/login');

        $response = $this->post('/integrations/mercadopago/refresh');
        $response->assertRedirect('/login');
    }
}