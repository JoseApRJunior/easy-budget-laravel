<?php

namespace app\database\services;

use app\database\entitiesORM\ProviderCredentialEntity;
use app\database\models\ProviderCredential;
use core\dbal\EntityNotFound;
use core\library\Session;
use MercadoPago\Client\OAuth\OAuthClient;
use MercadoPago\Client\OAuth\OAuthCreateRequest;
use MercadoPago\Exceptions\MPApiException;
use RuntimeException;

class MercadoPagoService
{
    private OAuthClient $client;
    private string      $appId;
    private string      $clientSecret;

    public function __construct(
        private ProviderCredential $providerCredentialModel,
        private EncryptionService $encryptionService,
    ) {
        $this->appId        = env( 'MERCADOPAGO_APP_ID' );
        $this->clientSecret = env( 'MERCADOPAGO_CLIENT_SECRET' );

        if ( empty( $this->appId ) || empty( $this->clientSecret ) ) {
            throw new RuntimeException( 'As credenciais do Mercado Pago (APP_ID, CLIENT_SECRET) não estão definidas no arquivo de ambiente.' );
        }

        $this->client = new OAuthClient();
    }

    public function getAuthorizationUrl(): string
    {
        $state = bin2hex( random_bytes( 16 ) );
        Session::set( 'mp_oauth_state', $state );

        $redirectUri = rtrim( env( 'APP_URL' ), '/' ) . '/provider/integrations/mercadopago/callback';

        // Manual URL construction to ensure the correct endpoint for Brazil.
        $auth_url = "https://auth.mercadopago.com.br/authorization";

        $query_params = [ 
            "client_id"     => $this->appId,
            "response_type" => "code",
            "platform_id"   => "mp",
            "state"         => $state,
            "redirect_uri"  => $redirectUri,
        ];

        return $auth_url . '?' . http_build_query( $query_params );
    }

    public function handleCallback( string $code, ?string $state, int $providerId, int $tenantId ): bool
    {
        $sessionState = Session::get( 'mp_oauth_state' );
        Session::remove( 'mp_oauth_state' );
        if ( empty( $state ) || !hash_equals( $sessionState, $state ) ) {
            logger()->error( 'CSRF attack detected in Mercado Pago OAuth callback.' );

            return false;
        }

        $redirectUri = rtrim( env( 'APP_URL' ), '/' ) . '/provider/integrations/mercadopago/callback';

        $request                = new OAuthCreateRequest();
        $request->client_id     = $this->appId;
        $request->client_secret = $this->clientSecret;
        $request->code          = $code;
        $request->redirect_uri  = $redirectUri;

        try {
            $oauth = $this->client->create( $request );

            $accessTokenEncrypted  = $this->encryptionService->encrypt( $oauth->access_token );
            $refreshTokenEncrypted = $this->encryptionService->encrypt( $oauth->refresh_token );

            $data = [ 
                'provider_id'             => $providerId,
                'tenant_id'               => $tenantId,
                'payment_gateway'         => 'mercadopago',
                'access_token_encrypted'  => $accessTokenEncrypted,
                'refresh_token_encrypted' => $refreshTokenEncrypted,
                'public_key'              => $oauth->public_key,
                'user_id_gateway'         => $oauth->user_id,
                'expires_in'              => $oauth->expires_in,
            ];

            $existingCredential = $this->providerCredentialModel->findByProvider( $tenantId );
            if ( !$existingCredential instanceof EntityNotFound ) {
                /** @var ProviderCredentialEntity $existingCredential */
                $data[ 'id' ] = $existingCredential->id;
            }

            $entity = ProviderCredentialEntity::create( $data );

            if ( isset( $data[ 'id' ] ) ) {
                $this->providerCredentialModel->update( $entity );
            } else {

                // CommonData
                $properties = getConstructorProperties( ProviderCredentialEntity::class);

                // popula model CommonDataEntity
                $entity = ProviderCredentialEntity::create( removeUnnecessaryIndexes(
                    $properties,
                    [ 'id', 'created_at', 'updated_at' ],
                    $data,
                ) );

                $this->providerCredentialModel->create( $entity );
            }

            return true;
        } catch ( MPApiException $e ) {
            // Log a detailed error message from the Mercado Pago API response
            $responseContent = json_encode( $e->getApiResponse()->getContent() );
            logger()->error( "Mercado Pago API Error: " . $e->getMessage() . " - Response: " . $responseContent );

            return false;
        } catch ( \Exception $e ) {
            // Log other generic exceptions
            logger()->error( "Mercado Pago OAuth Generic Error: " . $e->getMessage() );

            return false;
        }
    }

    public function disconnect( int $tenantId ): bool
    {
        $credential = $this->providerCredentialModel->findByProvider( $tenantId );
        if ( $credential instanceof EntityNotFound ) {
            return true; // Already disconnected
        }
        /** @var ProviderCredentialEntity $credential */
        $result = $this->providerCredentialModel->delete( $credential->id, $tenantId );

        return $result[ 'status' ] === 'success';
    }

}
