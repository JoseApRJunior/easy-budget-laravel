<?php

declare(strict_types=1);

namespace App\Http\Controllers\Integrations;

use App\Http\Controllers\Abstracts\Controller;
use App\Models\ProviderCredential;
use App\Services\Infrastructure\EncryptionService;
use App\Services\Infrastructure\MercadoPagoOAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MercadoPagoController extends Controller
{
    public function index( MercadoPagoOAuthService $oauth ): View
    {
        $cred = ProviderCredential::where( 'tenant_id', auth()->user()->tenant_id )
            ->where( 'payment_gateway', 'mercadopago' )
            ->first();

        $isConnected = (bool) $cred;
        $state       = (string) auth()->user()->id . ':' . now()->timestamp;
        $authUrl     = $oauth->getAuthorizationUrl( $state );

        $expiresReadable = null;
        $expires = (int) ( $cred?->expires_in ?? 0 );
        if ( $expires > 0 ) {
            if ( $expires < 3600 ) {
                $expiresReadable = ceil( $expires / 60 ) . ' min';
            } else {
                $expiresReadable = ceil( $expires / 3600 ) . ' h';
            }
        }

        return view( 'pages.mercadopago.index', [
            'isConnected'       => $isConnected,
            'authorization_url' => $authUrl,
            'public_key'        => $cred?->public_key,
            'expires_in'        => (int) ( $cred?->expires_in ?? 0 ),
            'expires_readable'  => $expiresReadable,
            'can_refresh'       => $isConnected,
        ] );
    }

    public function callback( Request $request, EncryptionService $encryption, MercadoPagoOAuthService $oauth ): RedirectResponse
    {
        $code  = (string) $request->get( 'code' );
        $state = (string) $request->get( 'state' );

        if ( empty( $code ) ) {
            return redirect()->route( 'integrations.mercadopago.index' )->with( 'error', 'Código de autorização inválido' );
        }

        $tenantId = auth()->user()->tenant_id;

        $exchange = $oauth->exchangeCode( $code );
        if ( !$exchange->isSuccess() ) {
            return redirect()->route( 'integrations.mercadopago.index' )->with( 'error', 'Falha na troca de tokens' );
        }

        $data    = $exchange->getData();
        $access  = $encryption->encryptStringLaravel( (string) ( $data[ 'access_token' ] ?? '' ) );
        $refresh = $encryption->encryptStringLaravel( (string) ( $data[ 'refresh_token' ] ?? '' ) );

        if ( !$access->isSuccess() ) {
            return redirect()->route( 'integrations.mercadopago.index' )->with( 'error', 'Falha ao criptografar access token' );
        }
        if ( !$refresh->isSuccess() ) {
            return redirect()->route( 'integrations.mercadopago.index' )->with( 'error', 'Falha ao criptografar refresh token' );
        }

        ProviderCredential::updateOrCreate(
            [ 'tenant_id' => $tenantId, 'payment_gateway' => 'mercadopago' ],
            [
                'access_token_encrypted'  => (string) $access->getData()[ 'encrypted' ],
                'refresh_token_encrypted' => (string) $refresh->getData()[ 'encrypted' ],
                'public_key'              => (string) ( $data[ 'public_key' ] ?? '' ),
                'user_id_gateway'         => (string) ( $data[ 'user_id' ] ?? '' ),
                'expires_in'              => (int) ( $data[ 'expires_in' ] ?? 0 ),
                'provider_id'             => auth()->user()->provider->id ?? null,
            ],
        );

        return redirect()->route( 'integrations.mercadopago.index' )->with( 'success', 'Conta Mercado Pago conectada' );
    }

    public function disconnect(): RedirectResponse
    {
        $tenantId = auth()->user()->tenant_id;
        ProviderCredential::where( 'tenant_id', $tenantId )->where( 'payment_gateway', 'mercadopago' )->delete();
        return redirect()->route( 'integrations.mercadopago.index' )->with( 'success', 'Conta Mercado Pago desconectada' );
    }

    public function refresh( EncryptionService $encryption, MercadoPagoOAuthService $oauth ): RedirectResponse
    {
        $tenantId = auth()->user()->tenant_id;
        $cred = ProviderCredential::where('tenant_id', $tenantId)
            ->where('payment_gateway', 'mercadopago')
            ->first();
        if (!$cred) {
            return redirect()->route('integrations.mercadopago.index')->with('error', 'Credenciais não encontradas');
        }

        $dr = $encryption->decryptStringLaravel((string)$cred->refresh_token_encrypted);
        if (!$dr->isSuccess()) {
            return redirect()->route('integrations.mercadopago.index')->with('error', 'Falha ao descriptografar refresh token');
        }

        $refreshToken = (string)($dr->getData()['decrypted'] ?? '');
        $res = $oauth->refreshToken($refreshToken);
        if (!$res->isSuccess()) {
            return redirect()->route('integrations.mercadopago.index')->with('error', 'Falha ao renovar token');
        }

        $data = $res->getData();
        $accessEnc = $encryption->encryptStringLaravel((string)($data['access_token'] ?? ''));
        $refreshEnc = $encryption->encryptStringLaravel((string)($data['refresh_token'] ?? ''));
        if (!$accessEnc->isSuccess() || !$refreshEnc->isSuccess()) {
            return redirect()->route('integrations.mercadopago.index')->with('error', 'Falha ao criptografar novos tokens');
        }

        $cred->update([
            'access_token_encrypted' => (string)$accessEnc->getData()['encrypted'],
            'refresh_token_encrypted' => (string)$refreshEnc->getData()['encrypted'],
            'expires_in' => (int)($data['expires_in'] ?? 0),
        ]);

        return redirect()->route('integrations.mercadopago.index')->with('success', 'Tokens renovados com sucesso');
    }

    public function testConnection( EncryptionService $encryption ): \Illuminate\Http\JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;
        $cred = ProviderCredential::where('tenant_id', $tenantId)->where('payment_gateway', 'mercadopago')->first();

        $accessToken = '';
        if ($cred && $cred->access_token_encrypted) {
            $dr = $encryption->decryptStringLaravel((string)$cred->access_token_encrypted);
            if ($dr->isSuccess()) {
                $accessToken = (string)($dr->getData()['decrypted'] ?? '');
            }
        }
        if ($accessToken === '') {
            $accessToken = (string) (config('services.mercadopago.access_token') ?? env('MERCADO_PAGO_ACCESS_TOKEN', ''));
        }

        if ($accessToken === '') {
            return response()->json([
                'success' => false,
                'error' => 'Access token não encontrado nas credenciais do provider nem no .env',
            ], 400);
        }

        $me = \Illuminate\Support\Facades\Http::withToken($accessToken)->get('https://api.mercadopago.com/users/me');
        $pm = \Illuminate\Support\Facades\Http::withToken($accessToken)->get('https://api.mercadopago.com/payment_methods');

        $result = [
            'success' => $me->ok() && $pm->ok(),
            'users_me' => $me->ok() ? $me->json() : ['status' => $me->status(), 'error' => $me->body()],
            'payment_methods' => $pm->ok() ? $pm->json() : ['status' => $pm->status(), 'error' => $pm->body()],
        ];

        return response()->json($result, $result['success'] ? 200 : 400);
    }
}
