<?php

namespace app\controllers;

use app\database\servicesORM\ActivityService;
use app\interfaces\ControllerInterface;
use app\traits\AutoSanitizationTrait;
use core\library\Response;
use core\library\Sanitize;
use core\library\Session;
use core\orm\EntityManagerFactory;
use core\support\Logger;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use http\Request;
use Throwable;

/**
 * Classe abstrata base para todos os controllers do sistema.
 *
 * Fornece implementações padrão dos métodos da ControllerInterface,
 * garantindo consistência e reutilização de código em todo o sistema.
 *
 * @package app\controllers
 * @author Easy Budget System
 * @since 1.0.0
 */
abstract class AbstractController implements ControllerInterface
{
    use AutoSanitizationTrait;

    protected mixed                  $authenticated   = null;
    protected ActivityService        $activityService;
    protected Sanitize               $sanitize;
    protected Logger                 $logger;
    protected EntityManagerInterface $entityManager;

    public function __construct(
        protected Request $request,
        ?ActivityService $activityService = null,
        ?Sanitize $sanitize = null,
        ?Logger $logger = null,
        ?EntityManagerInterface $entityManager = null,
    ) {
        if ( Session::has( 'auth' ) ) {
            $this->authenticated = Session::get( 'auth' );
        }

        // Inicializar EntityManager via DI (se fornecido) ou fallback para factory
        if ( $entityManager ) {
            $this->entityManager = $entityManager;
        } else {
            // Fallback para compatibilidade com testes ou casos especiais
            $this->entityManager = EntityManagerFactory::create();
        }

        // Inicializar dependências se fornecidas
        if ( $activityService ) {
            $this->activityService = $activityService;
        }
        if ( $sanitize ) {
            $this->sanitize = $sanitize;
        }
        if ( $logger ) {
            $this->logger = $logger;
        }
    }

    public function activityLogger(
        int $tenant_id,
        int $user_id,
        string $action_type,
        string $entity_type,
        int $entity_id,
        string $description,
        array $metadata = [],
    ): void {
        // Implementação padrão usando o ActivityService se disponível
        if ( isset( $this->activityService ) ) {
            $this->activityService->logActivity(
                $tenant_id,
                $user_id,
                $action_type,
                $entity_type,
                $entity_id,
                $description,
                $metadata,
            );
        }
    }

    public function validateTenantAccess( int $tenant_id, int $user_tenant_id ): bool
    {
        return $tenant_id === $user_tenant_id;
    }

    public function sanitizeInput( mixed $value, string $type ): mixed
    {
        if ( isset( $this->sanitize ) ) {
            return $this->sanitize->sanitizeParamValue( $value, $type );
        }

        // Implementação básica de fallback
        return match ( $type ) {
            'int'    => (int) filter_var( $value, FILTER_SANITIZE_NUMBER_INT ),
            'email'  => filter_var( $value, FILTER_SANITIZE_EMAIL ),
            'string' => htmlspecialchars( trim( (string) $value ), ENT_QUOTES, 'UTF-8' ),
            'float'  => (float) filter_var( $value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION ),
            default  => $value
        };
    }

    public function errorResponse( string $message, int $code = 400, array $data = [] ): Response
    {
        $responseData = [ 
            'success'   => false,
            'message'   => $message,
            'data'      => $data,
            'timestamp' => date( 'Y-m-d H:i:s' )
        ];

        return new Response(
            json_encode( $responseData, JSON_UNESCAPED_UNICODE ),
            $code,
            [ 'Content-Type' => 'application/json' ],
        );
    }

    public function successResponse( string $message, array $data = [], int $code = 200 ): Response
    {
        $responseData = [ 
            'success'   => true,
            'message'   => $message,
            'data'      => $data,
            'timestamp' => date( 'Y-m-d H:i:s' )
        ];

        return new Response(
            json_encode( $responseData, JSON_UNESCAPED_UNICODE ),
            $code,
            [ 'Content-Type' => 'application/json' ],
        );
    }

    public function validateRequestMethod( Request $request, array $allowedMethods ): bool
    {
        $currentMethod  = strtoupper( $request->getMethod() );
        $allowedMethods = array_map( 'strtoupper', $allowedMethods );

        return in_array( $currentMethod, $allowedMethods, true );
    }

    public function logError( Throwable $exception, string $context, array $additionalData = [] ): void
    {
        $errorData = [ 
            'message'         => $exception->getMessage(),
            'file'            => $exception->getFile(),
            'line'            => $exception->getLine(),
            'trace'           => $exception->getTraceAsString(),
            'context'         => $context,
            'additional_data' => $additionalData,
            'timestamp'       => date( 'Y-m-d H:i:s' ),
            'user_id'         => $this->authenticated->user_id ?? null,
            'tenant_id'       => $this->authenticated->tenant_id ?? null
        ];

        if ( isset( $this->logger ) ) {
            $this->logger->error( 'Controller Error: ' . $context, $errorData );
        } else {
            // Fallback para error_log nativo do PHP
            error_log( sprintf(
                "[%s] Controller Error in %s: %s at %s:%d",
                date( 'Y-m-d H:i:s' ),
                $context,
                $exception->getMessage(),
                $exception->getFile(),
                $exception->getLine(),
            ) );
        }
    }

    /**
     * Verifica se o usuário está autenticado.
     *
     * @return bool True se autenticado, false caso contrário.
     */
    protected function isAuthenticated(): bool
    {
        return $this->authenticated !== null;
    }

    /**
     * Obtém o ID do tenant do usuário autenticado.
     *
     * @return int|null ID do tenant ou null se não autenticado.
     */
    protected function getAuthenticatedTenantId(): ?int
    {
        return $this->authenticated->tenant_id ?? null;
    }

    /**
     * Obtém o ID do usuário autenticado.
     *
     * @return int|null ID do usuário ou null se não autenticado.
     */
    protected function getAuthenticatedUserId(): ?int
    {
        return $this->authenticated->user_id ?? null;
    }

    /**
     * Valida acesso baseado no tenant do usuário autenticado.
     *
     * @param int $resourceTenantId ID do tenant do recurso.
     *
     * @return bool True se autorizado, false caso contrário.
     */
    protected function validateAuthenticatedTenantAccess( int $resourceTenantId ): bool
    {
        $userTenantId = $this->getAuthenticatedTenantId();

        if ( $userTenantId === null ) {
            return false;
        }

        return $this->validateTenantAccess( $resourceTenantId, $userTenantId );
    }

    /**
     * Retorna a instância do EntityManager
     * Método necessário para o AutoSanitizationTrait
     *
     * @return EntityManagerInterface
     */
    protected function getEntityManager(): EntityManagerInterface
    {
        return $this->entityManager;
    }

}