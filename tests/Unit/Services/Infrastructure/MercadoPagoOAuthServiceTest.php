<?php

namespace Tests\Unit\Services\Infrastructure;

use App\Services\Infrastructure\MercadoPagoOAuthService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MercadoPagoOAuthServiceTest extends TestCase
{
    private MercadoPagoOAuthService $service;

    protected function setUp(): void
    {
        parent::setUp();
        
        Config::set('services.mercadopago.client_id', 'test_client_id');
        Config::set('services.mercadopago.client_secret', 'test_client_secret');
        Config::set('services.mercadopago.oauth.redirect_uri', 'http://localhost/integrations/mercadopago/callback');
        
        $this->service = new MercadoPagoOAuthService();
    }

    public function test_get_authorization_url_returns_correct_url(): void
    {
        $state = 'test_state_123';
        $url = $this->service->getAuthorizationUrl($state);
        
        $this->assertStringContainsString('https://auth.mercadopago.com/authorization', $url);
        $this->assertStringContainsString('client_id=test_client_id', $url);
        $this->assertStringContainsString('response_type=code', $url);
        $this->assertStringContainsString('platform_id=mp', $url);
        $this->assertStringContainsString('redirect_uri=' . urlencode('http://localhost/integrations/mercadopago/callback'), $url);
        $this->assertStringContainsString('state=' . $state, $url);
    }

    public function test_exchange_code_successfully(): void
    {
        $code = 'test_authorization_code';
        $expectedResponse = [
            'access_token' => 'test_access_token_123',
            'refresh_token' => 'test_refresh_token_456',
            'expires_in' => 21600,
            'user_id' => '123456789',
            'public_key' => 'TEST-public-key',
        ];

        Http::fake([
            'https://api.mercadopago.com/oauth/token' => Http::response($expectedResponse, 200),
        ]);

        $result = $this->service->exchangeCode($code);

        $this->assertEquals($expectedResponse, $result);
        
        Http::assertSent(function ($request) use ($code) {
            return $request->url() === 'https://api.mercadopago.com/oauth/token' &&
                   $request['grant_type'] === 'authorization_code' &&
                   $request['client_id'] === 'test_client_id' &&
                   $request['client_secret'] === 'test_client_secret' &&
                   $request['code'] === $code &&
                   $request['redirect_uri'] === 'http://localhost/integrations/mercadopago/callback';
        });
    }

    public function test_exchange_code_handles_api_error(): void
    {
        $code = 'invalid_code';

        Http::fake([
            'https://api.mercadopago.com/oauth/token' => Http::response([
                'message' => 'Invalid authorization code',
                'error' => 'invalid_grant',
            ], 400),
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Erro ao trocar código por token: Invalid authorization code');

        $this->service->exchangeCode($code);
    }

    public function test_exchange_code_handles_network_error(): void
    {
        $code = 'test_code';

        Http::fake([
            'https://api.mercadopago.com/oauth/token' => Http::timeout(),
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Erro ao trocar código por token: Network error');

        $this->service->exchangeCode($code);
    }

    public function test_refresh_token_successfully(): void
    {
        $refreshToken = 'test_refresh_token_456';
        $expectedResponse = [
            'access_token' => 'new_access_token_789',
            'refresh_token' => 'new_refresh_token_012',
            'expires_in' => 21600,
            'user_id' => '123456789',
            'public_key' => 'TEST-public-key-new',
        ];

        Http::fake([
            'https://api.mercadopago.com/oauth/token' => Http::response($expectedResponse, 200),
        ]);

        $result = $this->service->refreshToken($refreshToken);

        $this->assertEquals($expectedResponse, $result);
        
        Http::assertSent(function ($request) use ($refreshToken) {
            return $request->url() === 'https://api.mercadopago.com/oauth/token' &&
                   $request['grant_type'] === 'refresh_token' &&
                   $request['client_id'] === 'test_client_id' &&
                   $request['client_secret'] === 'test_client_secret' &&
                   $request['refresh_token'] === $refreshToken;
        });
    }

    public function test_refresh_token_handles_invalid_token(): void
    {
        $refreshToken = 'invalid_refresh_token';

        Http::fake([
            'https://api.mercadopago.com/oauth/token' => Http::response([
                'message' => 'Invalid refresh token',
                'error' => 'invalid_grant',
            ], 400),
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Erro ao atualizar token: Invalid refresh token');

        $this->service->refreshToken($refreshToken);
    }

    public function test_refresh_token_handles_empty_refresh_token(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Refresh token é obrigatório');

        $this->service->refreshToken('');
    }

    public function test_get_authorization_url_without_state(): void
    {
        $url = $this->service->getAuthorizationUrl();
        
        $this->assertStringContainsString('state=', $url);
        $this->assertGreaterThan(10, strlen(explode('state=', $url)[1]));
    }
}