<?php

declare(strict_types=1);

namespace App\Services\Infrastructure;

use App\Enums\OperationStatus;
use App\Models\ProviderCredential;
use App\Support\ServiceResult;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MercadoPagoOAuthService
{
    public function getAuthorizationUrl( string $state ): string
    {
        $clientId = config( 'services.mercadopago.client_id' );
        $redirect = urlencode( config( 'services.mercadopago.redirect_uri' ) );
        $base     = 'https://auth.mercadopago.com/authorization';
        $params   = "?client_id={$clientId}&response_type=code&redirect_uri={$redirect}&state={$state}";
        return $base . $params;
    }

    public function exchangeCode( string $code ): ServiceResult
    {
        $clientId     = config( 'services.mercadopago.client_id' );
        $clientSecret = config( 'services.mercadopago.client_secret' );
        $redirectUri  = config( 'services.mercadopago.redirect_uri' );

        $response = Http::asForm()->post( 'https://api.mercadopago.com/oauth/token', [
            'grant_type'    => 'authorization_code',
            'client_id'     => $clientId,
            'client_secret' => $clientSecret,
            'code'          => $code,
            'redirect_uri'  => $redirectUri,
        ] );

        if ( !$response->ok() ) {
            Log::error( 'mp_oauth_exchange_error', [ 'status' => $response->status(), 'body' => $response->body() ] );
            return ServiceResult::error( OperationStatus::ERROR, 'Falha na troca do código' );
        }

        $data = $response->json();
        return ServiceResult::success( $data, 'Tokens obtidos' );
    }

    public function refreshToken( string $refreshToken ): ServiceResult
    {
        $clientId     = config( 'services.mercadopago.client_id' );
        $clientSecret = config( 'services.mercadopago.client_secret' );

        $response = Http::asForm()->post( 'https://api.mercadopago.com/oauth/token', [
            'grant_type'    => 'refresh_token',
            'client_id'     => $clientId,
            'client_secret' => $clientSecret,
            'refresh_token' => $refreshToken,
        ] );

        if ( !$response->ok() ) {
            Log::error( 'mp_oauth_refresh_error', [ 'status' => $response->status(), 'body' => $response->body() ] );
            return ServiceResult::error( OperationStatus::ERROR, 'Falha ao renovar token' );
        }

        $data = $response->json();
        return ServiceResult::success( $data, 'Token renovado' );
    }

}
