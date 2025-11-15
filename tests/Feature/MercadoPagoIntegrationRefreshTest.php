<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ProviderCredential;
use App\Models\User;
use App\Services\Infrastructure\EncryptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MercadoPagoIntegrationRefreshTest extends TestCase
{
    use RefreshDatabase;

    public function testRefreshUpdatesTokensAndShowsFeedback(): void
    {
        $tenant = \App\Models\Tenant::factory()->create();
        $user   = User::factory()->create( [ 'tenant_id' => $tenant->id ] );
        $provider = \App\Models\Provider::create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'terms_accepted' => true,
        ]);
        $this->actingAs( $user );

        $enc        = app( EncryptionService::class);
        $refreshEnc = $enc->encryptStringLaravel( 'refresh_token_value' );

        ProviderCredential::create( [
            'tenant_id'               => $user->tenant_id,
            'payment_gateway'         => 'mercadopago',
            'access_token_encrypted'  => $enc->encryptStringLaravel( 'old_access' )->getData()[ 'encrypted' ],
            'refresh_token_encrypted' => $refreshEnc->getData()[ 'encrypted' ],
            'public_key'              => 'PUBLIC_KEY',
            'user_id_gateway'         => 'USER_GATEWAY',
            'expires_in'              => 1000,
            'provider_id'             => $provider->id,
        ] );

        Http::fake( [
            'https://api.mercadopago.com/oauth/token' => Http::response( [
                'access_token'  => 'new_access_token',
                'refresh_token' => 'new_refresh_token',
                'expires_in'    => 3600,
            ], 200 ),
        ] );

        $resp = $this->post( route( 'integrations.mercadopago.refresh' ) );
        $resp->assertRedirect( route( 'integrations.mercadopago.index' ) );
        $resp->assertSessionHas( 'success' );

        $cred = ProviderCredential::where( 'tenant_id', $user->tenant_id )->where( 'payment_gateway', 'mercadopago' )->first();
        $this->assertNotNull( $cred );
        $this->assertEquals( 3600, (int) $cred->expires_in );
    }

}
