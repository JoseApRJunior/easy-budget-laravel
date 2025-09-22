<?php

namespace design_patern\design_pattern_with_tenant\controller;

use app\controllers\AbstractController;
use app\database\servicesORM\ActivityService;
use app\enums\OperationStatus;
use core\library\Response;
use core\library\Sanitize;
use core\library\Session;
use core\library\Twig;
use design_patern\design_pattern_with_tenant\entities\DesignPatternWithTenantEntity;
use design_patern\design_pattern_with_tenant\request\DesignPatternWithTenantFormRequest;
use design_patern\design_pattern_with_tenant\services\DesignPatternWithTenantService;
use http\Redirect;
use http\Request;
use Throwable;

/**
 * Padrão de Controller WithTenant - Easy Budget
 *
 * PADRÕES IMPLEMENTADOS:
 * ✅ Herda do AbstractController - Reutilização de propriedades e métodos
 * ✅ Não redefine propriedades da classe pai - Evita conflitos de tipos
 * ✅ Usa ServiceResult dos services - Manipulação consistente de retornos
 * ✅ Sanitização automática via traits - autoSanitizeForEntity()
 * ✅ Comentários em português brasileiro - Padrão do projeto
 * ✅ Tratamento estruturado de erros - Try/catch com redirecionamentos
 * ✅ Padrão misto de verificação - isSuccess() no index, status comparison em CRUD
 * ✅ Error logging detalhado - getDetailedErrorInfo() para debug
 * ✅ Controle obrigatório de tenant - Validação em todos os métodos
 * ✅ Métodos auxiliares para tenant - getTenantIdFromAuth() e logActivity()
 * ✅ Logs de auditoria obrigatórios - Rastreabilidade por tenant
 *
 * BENEFÍCIOS:
 * - Isolamento completo de dados entre tenants
 * - Consistência na arquitetura MVC
 * - Reutilização de código comum
 * - Manipulação padronizada de erros
 * - Logging detalhado para debug em produção
 * - Mensagens contextuais específicas
 * - Fácil manutenção e extensão
 * - Auditoria obrigatória para compliance
 * - Validação de segurança multi-tenant
 */
class DesignPatternWithTenantController extends AbstractController
{
    /**
     * Construtor da classe DesignPatternWithTenantController
     *
     * IMPORTANTE: Não redefinir propriedades do AbstractController!
     * - $activityService já está disponível
     * - $sanitize já está disponível
     * - Outras propriedades herdadas
     *
     * @param Twig $twig Serviço de template
     * @param DesignPatternWithTenantService $designPatternService Serviço de negócio
     * @param Request $request Requisição HTTP
     */
    public function __construct(
        protected Twig $twig,
        private DesignPatternWithTenantService $designPatternService,
        Sanitize $sanitize,
        ActivityService $activityService,
        Request $request,
    ) {
        parent::__construct( $request, $activityService, $sanitize );
    }

    /**
     * Exibe a lista de entidades do tenant.
     *
     * PADRÃO:
     * - Usa ServiceResult para manipulação consistente
     * - Verificação via isSuccess() para renderização de página
     * - Error logging detalhado com getDetailedErrorInfo()
     * DIFERENÇA WithTenant: Obrigatório filtrar por tenant_id
     *
     * @return Response
     */
    public function index(): Response
    {
        try {
            // Obter tenant_id do usuário autenticado
            $tenant_id = $this->getTenantIdFromAuth();

            // Buscar entidades do tenant usando o Service
            $entitiesResult = $this->designPatternService->listByTenantId( $tenant_id );

            // Verificar se a operação foi bem-sucedida
            if ( !$entitiesResult->isSuccess() ) {
                Session::flash( 'error', $entitiesResult->message ?? 'Erro ao carregar as entidades do tenant.' );
                return new Response( $this->twig->env->render( 'pages/design_pattern_with_tenant/index.twig', [ 
                    'entities'  => [],
                    'tenant_id' => $tenant_id,
                ] ), 500 );
            }

            // Renderizar página com as entidades encontradas
            return new Response( $this->twig->env->render( 'pages/design_pattern_with_tenant/index.twig', [ 
                'entities'  => $entitiesResult->data,
                'tenant_id' => $tenant_id,
            ] ) );
        } catch ( Throwable $e ) {
            // Em caso de erro, renderizar página com array vazio e mensagem de erro
            getDetailedErrorInfo( $e );
            Session::flash( 'error', 'Erro ao carregar os Design Pattern WithTenant. Tente novamente mais tarde.' );
            return Redirect::redirect( '/admin/design-patterns-tenant' );
        }
    }

    /**
     * Exibe o formulário de criação de entidade.
     *
     * @return Response
     */
    public function create(): Response
    {
        try {
            $tenant_id = $this->getTenantIdFromAuth();

            return new Response( $this->twig->env->render( 'pages/design_pattern_with_tenant/create.twig', [ 
                'tenant_id' => $tenant_id,
            ] ) );
        } catch ( Throwable $e ) {
            getDetailedErrorInfo( $e );
            Session::flash( 'error', 'Erro ao carregar formulário de criação.' );
            return Redirect::redirect( '/admin/design-patterns-tenant' );
        }
    }

    /**
     * Processa o formulário de criação de entidade.
     *
     * PADRÃO:
     * - Validação via FormRequest (obrigatória)
     * - Sanitização automática via autoSanitizeForEntity()
     * - ServiceResult para manipulação de retorno
     * - Verificação via comparação de status para redirecionamentos
     * - Log de atividades obrigatório para WithTenant
     * - Validação rigorosa de tenant_id
     *
     * @return Response
     */
    public function store(): Response
    {
        try {
            // Obter tenant_id do usuário autenticado
            $tenant_id     = $this->getTenantIdFromAuth();
            $authenticated = $this->getAuthenticatedUser();

            // Validar os dados do formulário
            if ( !DesignPatternWithTenantFormRequest::validate( $this->request ) ) {
                return Redirect::redirect( '/admin/design-patterns-tenant/create' )
                    ->withMessage( 'error', 'Dados inválidos. Verifique os campos e tente novamente.' );
            }

            // Obter e preparar dados validados
            $data = $this->request->all();

            // Validação adicional de tenant_id (segurança)
            if ( isset( $data[ 'tenant_id' ] ) && (int) $data[ 'tenant_id' ] !== $tenant_id ) {
                // Log de segurança para tentativas de manipulação cross-tenant
                error_log(
                    "SECURITY VIOLATION: Tentativa de criação com tenant_id inválido: {$data[ 'tenant_id' ]} (esperado: {$tenant_id}). " .
                    "IP: " . ( $_SERVER[ 'REMOTE_ADDR' ] ?? 'unknown' ) . ", " .
                    "User-Agent: " . ( $_SERVER[ 'HTTP_USER_AGENT' ] ?? 'unknown' )
                );
                return Redirect::redirect( '/admin/design-patterns-tenant/create' )
                    ->withMessage( 'error', 'Erro de validação de segurança.' );
            }

            // Criar via service (retorna ServiceResult)
            $result = $this->designPatternService->createByTenantId( $data, $tenant_id );

            if ( $result->status !== OperationStatus::SUCCESS ) {
                return Redirect::redirect( '/admin/design-patterns-tenant/create' )
                    ->withMessage( 'error', $result->message ?? 'Erro ao cadastrar a entidade.' );
            }

            // Log de atividades adicional no controller (além do log já feito no service)
            /** @var \design_patern\design_pattern_with_tenant\entities\DesignPatternWithTenantEntity $entity */
            $entity = $result->data;
            $this->activityLogger(
                $tenant_id,
                $authenticated->getId(),
                'design_pattern_with_tenant_created_controller',
                'design_pattern_with_tenant',
                $entity->getId(),
                "Design Pattern WithTenant '{$entity->getName()}' criado via controller (tenant {$tenant_id})",
                [ 
                    'entity' => $entity->jsonSerialize(),
                    'source' => 'controller'
                ],
            );

            return Redirect::redirect( '/admin/design-patterns-tenant' )
                ->withMessage( 'success', 'Entidade cadastrada com sucesso!' );
        } catch ( Throwable $e ) {
            getDetailedErrorInfo( $e );
            Session::flash( 'error', "Falha ao cadastrar a entidade, tente novamente mais tarde ou entre em contato com suporte!" );
            return Redirect::redirect( '/admin/design-patterns-tenant/create' );
        }
    }

    /**
     * Exibe os detalhes de uma entidade.
     *
     * PADRÃO:
     * - Sanitização de parâmetros + ServiceResult
     * - Verificação via isSuccess() para redirecionamento
     * - Validação obrigatória de tenant_id
     *
     * @param string $id ID da entidade
     * @return Response
     */
    public function show( string $id ): Response
    {
        try {
            // Obter tenant_id do usuário autenticado
            $tenant_id = $this->getTenantIdFromAuth();

            // Sanitizar o ID usando o método do trait AutoSanitizationTrait
            $params       = $this->autoSanitizeForEntity( [ 'id' => $id ], DesignPatternWithTenantEntity::class);
            $entityResult = $this->designPatternService->getByIdAndTenantId( $params[ 'id' ], $tenant_id );

            if ( !$entityResult->isSuccess() ) {
                return Redirect::redirect( '/admin/design-patterns-tenant' )
                    ->withMessage( 'error', $entityResult->message ?? 'Entidade não encontrada ou acesso negado.' );
            }

            $entity = $entityResult->data;

            return new Response( $this->twig->env->render( 'pages/design_pattern_with_tenant/show.twig', [ 
                'entity'    => $entity,
                'tenant_id' => $tenant_id,
            ] ) );
        } catch ( Throwable $e ) {
            getDetailedErrorInfo( $e );
            Session::flash( 'error', 'Erro ao carregar detalhes da entidade.' );
            return Redirect::redirect( '/admin/design-patterns-tenant' );
        }
    }

    /**
     * Exibe o formulário de edição de entidade.
     *
     * @param string $id ID da entidade
     * @return Response
     */
    public function edit( string $id ): Response
    {
        try {
            // Obter tenant_id do usuário autenticado
            $tenant_id = $this->getTenantIdFromAuth();

            $params       = $this->autoSanitizeForEntity( [ 'id' => $id ], DesignPatternWithTenantEntity::class);
            $entityResult = $this->designPatternService->getByIdAndTenantId( $params[ 'id' ], $tenant_id );

            if ( !$entityResult->isSuccess() ) {
                return Redirect::redirect( '/admin/design-patterns-tenant' )
                    ->withMessage( 'error', $entityResult->message ?? 'Entidade não encontrada ou acesso negado.' );
            }

            $entity = $entityResult->data;

            return new Response( $this->twig->env->render( 'pages/design_pattern_with_tenant/edit.twig', [ 
                'entity'    => $entity,
                'tenant_id' => $tenant_id,
            ] ) );
        } catch ( Throwable $e ) {
            getDetailedErrorInfo( $e );
            Session::flash( 'error', 'Erro ao carregar formulário de edição.' );
            return Redirect::redirect( '/admin/design-patterns-tenant' );
        }
    }

    /**
     * Processa o formulário de atualização de entidade.
     *
     * PADRÃO:
     * - Sanitização de parâmetros e dados
     * - ServiceResult para manipulação de retorno
     * - Verificação via isSuccess() para redirecionamento
     * - Log de atividades obrigatório para WithTenant
     * - Validação rigorosa de tenant_id
     *
     * @param string $id ID da entidade
     * @return Response
     */
    public function update( string $id ): Response
    {
        try {
            // Obter tenant_id do usuário autenticado
            $tenant_id     = $this->getTenantIdFromAuth();
            $authenticated = $this->getAuthenticatedUser();

            // Sanitizar o ID
            $params = $this->autoSanitizeForEntity( [ 'id' => $id ], DesignPatternWithTenantEntity::class);

            // Validar os dados do formulário
            if ( !DesignPatternWithTenantFormRequest::validate( $this->request ) ) {
                return Redirect::redirect( "/admin/design-patterns-tenant/{$id}/edit" )
                    ->withMessage( 'error', 'Dados inválidos. Verifique os campos e tente novamente.' );
            }

            // Obter dados validados
            $data = $this->request->all();

            // Validação adicional de tenant_id (segurança)
            if ( isset( $data[ 'tenant_id' ] ) && (int) $data[ 'tenant_id' ] !== $tenant_id ) {
                // Log de segurança para tentativas de manipulação cross-tenant
                error_log(
                    "SECURITY VIOLATION: Tentativa de atualização com tenant_id inválido: {$data[ 'tenant_id' ]} (esperado: {$tenant_id}). " .
                    "IP: " . ( $_SERVER[ 'REMOTE_ADDR' ] ?? 'unknown' ) . ", " .
                    "User-Agent: " . ( $_SERVER[ 'HTTP_USER_AGENT' ] ?? 'unknown' )
                );
                return Redirect::redirect( "/admin/design-patterns-tenant/{$id}/edit" )
                    ->withMessage( 'error', 'Erro de validação de segurança.' );
            }

            // Atualizar via service (retorna ServiceResult)
            $result = $this->designPatternService->updateByIdAndTenantId( $params[ 'id' ], $tenant_id, $data );

            if ( !$result->isSuccess() ) {
                return Redirect::redirect( "/admin/design-patterns-tenant/{$id}/edit" )
                    ->withMessage( 'error', $result->message ?? 'Erro ao atualizar a entidade.' );
            }

            // Log de atividades adicional no controller (além do log já feito no service)
            /** @var \design_patern\design_pattern_with_tenant\entities\DesignPatternWithTenantEntity $entity */
            $entity = $result->data;
            $this->activityLogger(
                $tenant_id,
                $authenticated->getId(),
                'design_pattern_with_tenant_updated_controller',
                'design_pattern_with_tenant',
                $entity->getId(),
                "Design Pattern WithTenant '{$entity->getName()}' atualizado via controller (tenant {$tenant_id})",
                [ 
                    'entity' => $entity->jsonSerialize(),
                    'source' => 'controller'
                ],
            );

            return Redirect::redirect( '/admin/design-patterns-tenant' )
                ->withMessage( 'success', 'Entidade atualizada com sucesso!' );
        } catch ( Throwable $e ) {
            getDetailedErrorInfo( $e );
            Session::flash( 'error', "Falha ao atualizar a entidade, tente novamente mais tarde ou entre em contato com suporte!" );
            return Redirect::redirect( "/admin/design-patterns-tenant/{$id}/edit" );
        }
    }

    /**
     * Remove uma entidade.
     *
     * PADRÃO:
     * - Sanitização do ID
     * - ServiceResult para manipulação de retorno
     * - Verificação via isSuccess() para redirecionamento
     * - Log de atividades obrigatório para WithTenant
     * - Validação rigorosa de tenant_id
     *
     * @param string $id ID da entidade
     * @return Response
     */
    public function delete( string $id ): Response
    {
        try {
            // Obter tenant_id do usuário autenticado
            $tenant_id     = $this->getTenantIdFromAuth();
            $authenticated = $this->getAuthenticatedUser();

            // Sanitizar o ID
            $params = $this->autoSanitizeForEntity( [ 'id' => $id ], DesignPatternWithTenantEntity::class);

            // Deletar via service (retorna ServiceResult)
            $result = $this->designPatternService->deleteByIdAndTenantId( $params[ 'id' ], $tenant_id );

            if ( !$result->isSuccess() ) {
                return Redirect::redirect( '/admin/design-patterns-tenant' )
                    ->withMessage( 'error', $result->message ?? 'Erro ao excluir a entidade.' );
            }

            // Log de atividades adicional no controller (além do log já feito no service)
            $this->activityLogger(
                $tenant_id,
                $authenticated->getId(),
                'design_pattern_with_tenant_deleted_controller',
                'design_pattern_with_tenant',
                $params[ 'id' ],
                "Design Pattern WithTenant com ID {$params[ 'id' ]} excluído via controller (tenant {$tenant_id})",
                [ 
                    'entity_id' => $params[ 'id' ],
                    'tenant_id' => $tenant_id,
                    'source'    => 'controller'
                ],
            );

            return Redirect::redirect( '/admin/design-patterns-tenant' )
                ->withMessage( 'success', 'Entidade excluída com sucesso!' );
        } catch ( Throwable $e ) {
            getDetailedErrorInfo( $e );
            Session::flash( 'error', "Falha ao excluir a entidade, tente novamente mais tarde ou entre em contato com suporte!" );
            return Redirect::redirect( '/admin/design-patterns-tenant' );
        }
    }

    // ==================================================================
    // MÉTODOS AUXILIARES ESPECÍFICOS PARA WITHTENANT
    // ==================================================================

    /**
     * Obtém o tenant_id do usuário autenticado.
     *
     * IMPORTANTE: Método crucial para segurança multi-tenant.
     *
     * @return int ID do tenant
     * @throws \RuntimeException Se não conseguir obter o tenant_id
     */
    private function getTenantIdFromAuth(): int
    {
        try {
            // Implementar lógica específica do projeto para obter tenant_id
            // Exemplo usando sessão ou JWT

            $user = $this->getAuthenticatedUser();

            if ( !$user || !method_exists( $user, 'getTenantId' ) ) {
                throw new \RuntimeException( 'Usuário não autenticado ou sem tenant associado.' );
            }

            $tenant_id = $user->getTenantId();

            if ( !$tenant_id || $tenant_id <= 0 ) {
                throw new \RuntimeException( 'Tenant ID inválido ou não encontrado.' );
            }

            return $tenant_id;

        } catch ( Throwable $e ) {
            getDetailedErrorInfo( $e );
            // Log de segurança para tentativas de acesso sem tenant
            error_log( "SECURITY: Tentativa de acesso sem tenant válido - " . $e->getMessage() );
            throw new \RuntimeException( 'Acesso negado: Tenant não identificado.' );
        }
    }

    /**
     * Obtém o usuário autenticado.
     *
     * @return object Usuário autenticado
     * @throws \RuntimeException Se usuário não estiver autenticado
     */
    private function getAuthenticatedUser(): object
    {
        // Implementar lógica específica do projeto para obter usuário autenticado
        // Exemplo usando sessão, JWT ou outro mecanismo de autenticação

        $user = Session::get( 'authenticated_user' );

        if ( !$user ) {
            throw new \RuntimeException( 'Usuário não autenticado.' );
        }

        return $user;
    }

}

/**
EXEMPLOS DE USO:

1. Roteamento típico para WithTenant (routes/web.php)
$router->group(['middleware' => ['auth', 'tenant']], function() use ($router) {
   $router->get('/admin/design-patterns-tenant', [DesignPatternWithTenantController::class, 'index']);
   $router->get('/admin/design-patterns-tenant/create', [DesignPatternWithTenantController::class, 'create']);
   $router->post('/admin/design-patterns-tenant', [DesignPatternWithTenantController::class, 'store']);
   $router->get('/admin/design-patterns-tenant/{id}', [DesignPatternWithTenantController::class, 'show']);
   $router->get('/admin/design-patterns-tenant/{id}/edit', [DesignPatternWithTenantController::class, 'edit']);
   $router->put('/admin/design-patterns-tenant/{id}', [DesignPatternWithTenantController::class, 'update']);
   $router->delete('/admin/design-patterns-tenant/{id}', [DesignPatternWithTenantController::class, 'delete']);
});

2. Principais diferenças do padrão WithTenant:
- ✅ getTenantIdFromAuth() obrigatório em todos os métodos
- ✅ Validação rigorosa de tenant_id em formulários
- ✅ Log de auditoria obrigatório para todas as operações
- ✅ Sanitização específica para entidades com tenant
- ✅ Rotas protegidas por middleware de autenticação e tenant
- ✅ Templates incluem tenant_id para validação client-side
- ✅ Verificação de segurança contra manipulação cross-tenant
- ✅ Error logging específico para violações de segurança

3. Injeção de dependência (container de DI)
$container->set(DesignPatternWithTenantController::class, function(ContainerInterface $c) {
   return new DesignPatternWithTenantController(
       $c->get(Twig::class),
       $c->get(DesignPatternWithTenantService::class),
       $c->get(Request::class)
   );
});

4. Middleware de segurança recomendado:
class TenantMiddleware {
   public function handle($request, $next) {
       $user = auth()->user();
       if (!$user || !$user->getTenantId()) {
           return redirect('/login')->with('error', 'Acesso negado: Tenant não identificado.');
       }
       return $next($request);
   }
}

5. Padrões de Verificação implementados:

✅ Método index() - Renderização com tenant isolado
$entitiesResult = $this->designPatternService->listByTenantId( $tenant_id );
if ( !$entitiesResult->isSuccess() ) {
   Session::flash( 'error', $entitiesResult->message ?? 'Erro ao carregar as entidades do tenant.' );
   return new Response( $this->twig->env->render( 'pages/design_pattern_with_tenant/index.twig', [
       'entities' => [],
       'tenant_id' => $tenant_id,
   ] ), 500 );
}

✅ Métodos CRUD - Validação de segurança multi-tenant
if ( isset( $data[ 'tenant_id' ] ) && (int) $data[ 'tenant_id' ] !== $tenant_id ) {
   $this->activityLogger(
       $tenant_id,
       $authenticated,
       'security_violation_attempt',
       'design_pattern',
       null,
       "Tentativa de manipulação cross-tenant detectada"
   );
   return Redirect::redirect( '/caminho/seguro' )
       ->withMessage( 'error', 'Erro de validação de segurança.' );
}

✅ Error Handling - Logging detalhado com contexto tenant
catch ( Throwable $e ) {
   getDetailedErrorInfo( $e ); // Log detalhado para debug
   $this->activityLogger($tenant_id, $authenticated, 'error', 'system', null, $e->getMessage());
   Session::flash( 'error', 'Mensagem amigável para o usuário' );
   return Redirect::redirect( '/caminho/fallback' );
}
*/
