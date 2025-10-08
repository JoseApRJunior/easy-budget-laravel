<?php

namespace app\database\servicesORM;

use app\database\entitiesORM\ProviderCredentialEntity;
use app\database\repositories\ProviderCredentialRepository;
use app\interfaces\ServiceInterface;
use app\support\ServiceResult;
use app\enums\OperationStatus;
use core\dbal\EntityNotFound;
use core\library\Session;
use MercadoPago\Client\OAuth\OAuthClient;
use MercadoPago\Client\OAuth\OAuthCreateRequest;
use MercadoPago\Exceptions\MPApiException;
use Exception;

class MercadoPagoService implements ServiceInterface
{
    private OAuthClient                  $client;
    private string                       $appId;
    private string                       $clientSecret;
    private ProviderCredentialRepository $providerCredentialRepository;
    private EncryptionService            $encryptionService;

    public function __construct(
        ProviderCredentialRepository $providerCredentialRepository,
        EncryptionService $encryptionService,
    ) {
        $this->providerCredentialRepository = $providerCredentialRepository;
        $this->encryptionService            = $encryptionService;

        $this->appId        = env( 'MERCADOPAGO_APP_ID' );
        $this->clientSecret = env( 'MERCADOPAGO_CLIENT_SECRET' );

        if ( empty( $this->appId ) || empty( $this->clientSecret ) ) {
            throw new Exception( 'As credenciais do Mercado Pago (APP_ID, CLIENT_SECRET) não estão definidas no arquivo de ambiente.' );
        }

        $this->client = new OAuthClient();
    }

    /**
     * Busca uma credencial pelo seu ID e ID do tenant.
     *
     * @param int $id ID da entidade
     * @param int $tenant_id ID do tenant
     * @return ServiceResult Resultado da operação com status, mensagem e dados
     */
    public function getByIdAndTenantId( int $id, int $tenant_id ): ServiceResult
    {
        try {
            $entity = $this->providerCredentialRepository->findByIdAndTenantId( $id, $tenant_id );

            if ( !$entity || $entity instanceof EntityNotFound ) {
                return ServiceResult::error( OperationStatus::NOT_FOUND, 'Credencial não encontrada.' );
            }

            return ServiceResult::success( $entity, 'Credencial encontrada com sucesso.' );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao buscar credencial: ' . $e->getMessage() );
        }
    }

    /**
     * Lista todas as credenciais de um tenant, com filtros opcionais.
     *
     * @param int $tenant_id ID do tenant
     * @param array<string, mixed> $filters Filtros a serem aplicados
     * @return ServiceResult Resultado da operação com status, mensagem e dados
     */
    public function listByTenantId( int $tenant_id, array $filters = [] ): ServiceResult
    {
        try {
            $criteria = [];
            $orderBy  = [ 'created_at' => 'DESC' ];
            $limit    = $filters[ 'limit' ] ?? null;
            $offset   = $filters[ 'offset' ] ?? null;

            $entities = $this->providerCredentialRepository->findAllByTenantId( $tenant_id, $criteria, $orderBy, $limit, $offset );

            return ServiceResult::success( $entities, 'Credenciais listadas com sucesso.' );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao listar credenciais: ' . $e->getMessage() );
        }
    }

    /**
     * Cria uma nova credencial.
     *
     * @param array<string, mixed> $data Dados para criação da entidade
     * @param int $tenant_id ID do tenant
     * @return ServiceResult Resultado da operação com status, mensagem e dados
     */
    public function createByTenantId( array $data, int $tenant_id ): ServiceResult
    {
        try {
            // Validar dados de entrada
            $validation = $this->validate( $data );
            if ( !$validation->isSuccess() ) {
                return $validation;
            }

            // Criar nova entidade
            $entity = new ProviderCredentialEntity();

            // Preencher os dados da entidade
            foreach ( $data as $key => $value ) {
                $setter = 'set' . str_replace( '_', '', ucwords( $key, '_' ) );
                if ( method_exists( $entity, $setter ) ) {
                    $entity->$setter( $value );
                }
            }

            $entity->setTenantId( $tenant_id );

            // Salvar no repositório
            $result = $this->providerCredentialRepository->save( $entity, $tenant_id );

            if ( $result !== false ) {
                return ServiceResult::success( $result, 'Credencial criada com sucesso.' );
            } else {
                return ServiceResult::error( OperationStatus::ERROR, 'Falha ao salvar credencial no banco de dados.' );
            }
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao criar credencial: ' . $e->getMessage() );
        }
    }

    /**
     * Atualiza uma credencial existente.
     *
     * @param int $id ID da entidade
     * @param int $tenant_id ID do tenant
     * @param array<string, mixed> $data Dados para atualização
     * @return ServiceResult Resultado da operação com status, mensagem e dados
     */
    public function updateByIdAndTenantId( int $id, array $data, int $tenantId ): ServiceResult
    {
        try {
            // Validar dados de entrada
            $validation = $this->validate( $data, true );
            if ( !$validation->isSuccess() ) {
                return $validation;
            }

            // Buscar entidade existente
            $entity = $this->providerCredentialRepository->findByIdAndTenantId( $id, $tenant_id );

            if ( !$entity || $entity instanceof EntityNotFound ) {
                return ServiceResult::error( OperationStatus::NOT_FOUND, 'Credencial não encontrada.' );
            }

            // Atualizar os dados da entidade
            foreach ( $data as $key => $value ) {
                $setter = 'set' . str_replace( '_', '', ucwords( $key, '_' ) );
                if ( method_exists( $entity, $setter ) ) {
                    $entity->$setter( $value );
                }
            }

            // Salvar no repositório
            $result = $this->providerCredentialRepository->save( $entity, $tenant_id );

            if ( $result !== false ) {
                return ServiceResult::success( $result, 'Credencial atualizada com sucesso.' );
            } else {
                return ServiceResult::error( OperationStatus::ERROR, 'Falha ao salvar credencial no banco de dados.' );
            }
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao atualizar credencial: ' . $e->getMessage() );
        }
    }

    /**
     * Remove uma credencial.
     *
     * @param int $id ID da entidade
     * @param int $tenant_id ID do tenant
     * @return ServiceResult Resultado da operação com status, mensagem e dados
     */
    public function deleteByIdAndTenantId( int $id, int $tenant_id ): ServiceResult
    {
        try {
            // Verificar se a entidade existe
            $entity = $this->providerCredentialRepository->findByIdAndTenantId( $id, $tenant_id );

            if ( !$entity || $entity instanceof EntityNotFound ) {
                return ServiceResult::error( OperationStatus::NOT_FOUND, 'Credencial não encontrada.' );
            }

            // Executar a exclusão
            $result = $this->providerCredentialRepository->deleteByIdAndTenantId( $id, $tenant_id );

            if ( $result ) {
                return ServiceResult::success( null, 'Credencial removida com sucesso.' );
            } else {
                return ServiceResult::error( OperationStatus::ERROR, 'Falha ao remover credencial do banco de dados.' );
            }
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao excluir credencial: ' . $e->getMessage() );
        }
    }

    /**
     * Valida os dados de entrada para operações de criação ou atualização.
     *
     * @param array<string, mixed> $data Dados a serem validados
     * @param bool $isUpdate Indica se é uma operação de atualização
     * @return ServiceResult Resultado da validação
     */
    public function validate( array $data, bool $isUpdate = false ): ServiceResult
    {
        $errors = [];

        // Validações básicas
        if ( !$isUpdate ) {
            // Validações específicas para criação
            if ( empty( $data[ 'provider_id' ] ) ) {
                $errors[] = 'ID do provedor é obrigatório.';
            }

            if ( empty( $data[ 'payment_gateway' ] ) ) {
                $errors[] = 'Gateway de pagamento é obrigatório.';
            }
        }

        // Validar campos numéricos
        if ( isset( $data[ 'provider_id' ] ) && !is_numeric( $data[ 'provider_id' ] ) ) {
            $errors[] = 'ID do provedor deve ser um número válido.';
        }

        if ( !empty( $errors ) ) {
            return ServiceResult::error( OperationStatus::INVALID_DATA, 'Dados inválidos: ' . implode( ', ', $errors ) );
        }

        return ServiceResult::success( null, 'Dados válidos.' );
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

    public function handleCallback( string $code, ?string $state, int $providerId, int $tenantId ): ServiceResult
    {
        try {
            $sessionState = Session::get( 'mp_oauth_state' );
            Session::remove( 'mp_oauth_state' );

            if ( empty( $state ) || !hash_equals( $sessionState, $state ) ) {
                logger()->error( 'CSRF attack detected in Mercado Pago OAuth callback.' );
                return ServiceResult::error( OperationStatus::ERROR, 'Ataque CSRF detectado.' );
            }

            $redirectUri = rtrim( env( 'APP_URL' ), '/' ) . '/provider/integrations/mercadopago/callback';

            $request                = new OAuthCreateRequest();
            $request->client_id     = $this->appId;
            $request->client_secret = $this->clientSecret;
            $request->code          = $code;
            $request->redirect_uri  = $redirectUri;

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

            $existingCredential = $this->providerCredentialRepository->findByProvider( $tenantId );
            if ( !$existingCredential instanceof EntityNotFound ) {
                // Verificar se $existingCredential é null antes de acessar propriedades
                if ( $existingCredential === null ) {
                    return ServiceResult::error( OperationStatus::ERROR, 'Credencial não encontrada.' );
                }
                /** @var ProviderCredentialEntity $existingCredential */
                $data[ 'id' ] = $existingCredential->getId();
            }

            $entity = ProviderCredentialEntity::create( $data );

            if ( isset( $data[ 'id' ] ) ) {
                $this->providerCredentialRepository->update( $entity );
            } else {
                // CommonData
                $properties = getConstructorProperties( ProviderCredentialEntity::class);

                // popula model CommonDataEntity
                $entity = ProviderCredentialEntity::create( removeUnnecessaryIndexes(
                    $properties,
                    [ 'id', 'created_at', 'updated_at' ],
                    $data,
                ) );

                $this->providerCredentialRepository->create( $entity );
            }

            return ServiceResult::success( null, 'Credenciais salvas com sucesso.' );
        } catch ( MPApiException $e ) {
            // Log a detailed error message from the Mercado Pago API response
            $responseContent = json_encode( $e->getApiResponse()->getContent() );
            logger()->error( "Mercado Pago API Error: " . $e->getMessage() . " - Response: " . $responseContent );
            return ServiceResult::error( OperationStatus::ERROR, 'Erro na API do Mercado Pago: ' . $e->getMessage() );
        } catch ( Exception $e ) {
            // Log other generic exceptions
            logger()->error( "Mercado Pago OAuth Generic Error: " . $e->getMessage() );
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao processar o callback: ' . $e->getMessage() );
        }
    }

    public function disconnect( int $tenantId ): ServiceResult
    {
        try {
            $credential = $this->providerCredentialRepository->findByProvider( $tenantId );
            if ( $credential instanceof EntityNotFound ) {
                return ServiceResult::success( null, 'Já desconectado.' );
            }

            /** @var ProviderCredentialEntity $credential */
            // Verificar se $credential é null antes de acessar propriedades
            if ( $credential === null ) {
                return ServiceResult::success( null, 'Já desconectado.' );
            }

            $result = $this->providerCredentialRepository->deleteByIdAndTenantId( $credential->id, $tenantId );

            if ( $result ) {
                return ServiceResult::success( null, 'Desconectado com sucesso.' );
            } else {
                return ServiceResult::error( OperationStatus::ERROR, 'Falha ao desconectar.' );
            }
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao desconectar: ' . $e->getMessage() );
        }
    }

}
