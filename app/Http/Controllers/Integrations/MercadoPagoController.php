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

        return view( 'pages.mercadopago.index', [
            'isConnected'       => $isConnected,
            'authorization_url' => $authUrl,
            'public_key'        => $cred?->public_key,
        ] );
    }

    public function callback( Request $request, EncryptionService $encryption, MercadoPagoOAuthService $oauth ): RedirectResponse
    {
        $code  = (string) $request->get( 'code' );
        $state = (string) $request->get( 'state' );

        if ( empty( $code ) ) {
            return redirect()->route( 'provider.integrations.mercadopago.index' )->with( 'error', 'Código de autorização inválido' );
        }

        $tenantId = auth()->user()->tenant_id;

        $exchange = $oauth->exchangeCode( $code );
        if ( !$exchange->isSuccess() ) {
            return redirect()->route( 'provider.integrations.mercadopago.index' )->with( 'error', 'Falha na troca de tokens' );
        }

        $data    = $exchange->getData();
        $access  = $encryption->encryptStringLaravel( (string) ( $data[ 'access_token' ] ?? '' ) );
        $refresh = $encryption->encryptStringLaravel( (string) ( $data[ 'refresh_token' ] ?? '' ) );

        if ( !$access->isSuccess() ) {
            return redirect()->route( 'provider.integrations.mercadopago.index' )->with( 'error', 'Falha ao criptografar access token' );
        }
        if ( !$refresh->isSuccess() ) {
            return redirect()->route( 'provider.integrations.mercadopago.index' )->with( 'error', 'Falha ao criptografar refresh token' );
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

        return redirect()->route( 'provider.integrations.mercadopago.index' )->with( 'success', 'Conta Mercado Pago conectada' );
    }

    public function disconnect(): RedirectResponse
    {
        $tenantId = auth()->user()->tenant_id;
        ProviderCredential::where( 'tenant_id', $tenantId )->where( 'payment_gateway', 'mercadopago' )->delete();
        return redirect()->route( 'provider.integrations.mercadopago.index' )->with( 'success', 'Conta Mercado Pago desconectada' );
    }

}
