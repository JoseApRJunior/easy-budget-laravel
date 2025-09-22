<?php

namespace design_patern\design_pattern_no_tenant\controller;

use app\controllers\AbstractController;
use app\database\servicesORM\ActivityService;
use app\enums\OperationStatus;
use core\library\Response;
use core\library\Sanitize;
use core\library\Session;
use core\library\Twig;
use design_patern\design_pattern_no_tenant\entities\DesignPatternNoTenantEntity;
use design_patern\design_pattern_no_tenant\request\DesignPatternNoTenantFormRequest;
use design_patern\design_pattern_no_tenant\services\DesignPatternNoTenantService;
use http\Redirect;
use http\Request;
use Throwable;

/**
 * Padrão de Controller NoTenant - Easy Budget
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
 * ✅ Não requer controle de tenant - Adequado para entidades globais
 *
 * BENEFÍCIOS:
 * - Consistência na arquitetura MVC
 * - Reutilização de código comum
 * - Manipulação padronizada de erros
 * - Logging detalhado para debug em produção
 * - Mensagens contextuais específicas
 * - Fácil manutenção e extensão
 * - Simplicidade sem controle de tenant
 */
class DesignPatternNoTenantController extends AbstractController
{
    /**
     * Construtor da classe DesignPatternNoTenantController
     *
     * IMPORTANTE: Não redefinir propriedades do AbstractController!
     * - $activityService já está disponível
     * - $sanitize já está disponível
     * - Outras propriedades herdadas
     *
     * @param Twig $twig Serviço de template
     * @param DesignPatternNoTenantService $designPatternService Serviço de negócio
     * @param Request $request Requisição HTTP
     */
    public function __construct(
        protected Twig $twig,
        private DesignPatternNoTenantService $designPatternService,
        Sanitize $sanitize,
        ActivityService $activityService,
        Request $request,
    ) {
        parent::__construct( $request, $activityService, $sanitize );
    }

    /**
     * Exibe a lista de entidades.
     *
     * PADRÃO:
     * - Usa ServiceResult para manipulação consistente
     * - Verificação via isSuccess() para renderização de página
     * - Error logging detalhado com getDetailedErrorInfo()
     * DIFERENÇA NoTenant: Não precisa filtrar por tenant_id
     *
     * @return Response
     */
    public function index(): Response
    {
        try {
            // Buscar todas as entidades usando o Service
            $entitiesResult = $this->designPatternService->list();

            // Verificar se a operação foi bem-sucedida
            if ( !$entitiesResult->isSuccess() ) {
                Session::flash( 'error', $entitiesResult->message ?? 'Erro ao carregar as entidades.' );
                return new Response( $this->twig->env->render( 'pages/design_pattern/index.twig', [
                    'entities' => [],
                ] ), 500 );
            }

            // Renderizar página com as entidades encontradas
            return new Response( $this->twig->env->render( 'pages/design_pattern/index.twig', [
                'entities' => $entitiesResult->data,
            ] ) );
        } catch ( Throwable $e ) {
            // Em caso de erro, renderizar página com array vazio e mensagem de erro
            getDetailedErrorInfo( $e );
            Session::flash( 'error', 'Erro ao carregar os Design Pattern. Tente novamente mais tarde.' );
            return Redirect::redirect( '/admin/design-patterns' );
        }

    }

    /**
     * Exibe o formulário de criação de entidade.
     *
     * @return Response
     */
    public function create(): Response
    {
        return new Response( $this->twig->env->render( 'pages/design_pattern/create.twig' ) );
    }

    /**
     * Processa o formulário de criação de entidade.
     *
     * PADRÃO:
     * - Validação via FormRequest (opcional)
     * - Sanitização automática via autoSanitizeForEntity()
     * - ServiceResult para manipulação de retorno
     * - Verificação via comparação de status para redirecionamentos
     * - Log de atividades para auditoria (opcional para NoTenant)
     *
     * @return Response
     */
    public function store(): Response
    {
        try {
            // Validar os dados do formulário
            if ( !DesignPatternNoTenantFormRequest::validate( $this->request ) ) {
                return Redirect::redirect( '/admin/design-patterns/create' )
                    ->withMessage( 'error', 'Dados inválidos. Verifique os campos e tente novamente.' );
            }

            // Obter e preparar dados validados
            $data = $this->request->all();

            // Criar via service (retorna ServiceResult)
            $result = $this->designPatternService->create( $data );

            if ( $result->status !== OperationStatus::SUCCESS ) {
                return Redirect::redirect( '/admin/design-patterns/create' )
                    ->withMessage( 'error', $result->message ?? 'Erro ao cadastrar a entidade.' );
            }

            // Log de atividades para auditoria (opcional para NoTenant)
            /** @var \design_patern\design_pattern_no_tenant\entities\DesignPatternNoTenantEntity $entity */
            $entity = $result->data;
            $this->activityLogger(
                0, // NoTenant não possui tenant_id, usar 0 para indicar global
                $this->getAuthenticatedUserId() ?? 0,
                'design_pattern_no_tenant_created',
                'design_pattern_no_tenant',
                $entity->getId(),
                "Design Pattern NoTenant '{$entity->getName()}' criado",
                [
                    'entity' => $entity->jsonSerialize()
                ],
            );

            return Redirect::redirect( '/admin/design-patterns' )
                ->withMessage( 'success', 'Entidade cadastrada com sucesso!' );
        } catch ( Throwable $e ) {
            getDetailedErrorInfo( $e );
            Session::flash( 'error', "Falha ao cadastrar a entidade, tente novamente mais tarde ou entre em contato com suporte!" );
            return Redirect::redirect( '/admin/design-patterns/create' );
        }
    }

    /**
     * Exibe os detalhes de uma entidade.
     *
     * PADRÃO:
     * - Sanitização de parâmetros + ServiceResult
     * - Verificação via isSuccess() para redirecionamento
     *
     * @param string $id ID da entidade
     * @return Response
     */
    public function show( string $id ): Response
    {
        try {
            // Sanitizar o ID usando o método do trait AutoSanitizationTrait
            $params       = $this->autoSanitizeForEntity( [ 'id' => $id ], DesignPatternNoTenantEntity::class);
            $entityResult = $this->designPatternService->getById( $params[ 'id' ] );

            if ( !$entityResult->isSuccess() ) {
                return Redirect::redirect( '/admin/design-patterns' )
                    ->withMessage( 'error', $entityResult->message ?? 'Entidade não encontrada.' );
            }

            $entity = $entityResult->data;

            return new Response( $this->twig->env->render( 'pages/design_pattern/show.twig', [
                'entity' => $entity,
            ] ) );
        } catch ( Throwable $e ) {
            getDetailedErrorInfo( $e );
            Session::flash( 'error', "Falha ao carregar a entidade, tente novamente mais tarde ou entre em contato com suporte!" );
            return Redirect::redirect( '/admin/design-patterns' );
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
            // Sanitizar o ID usando o método do trait AutoSanitizationTrait
            $params       = $this->autoSanitizeForEntity( [ 'id' => $id ], DesignPatternNoTenantEntity::class);
            $entityResult = $this->designPatternService->getById( $params[ 'id' ] );

            if ( !$entityResult->isSuccess() ) {
                return Redirect::redirect( '/admin/design-patterns' )
                    ->withMessage( 'error', $entityResult->message ?? 'Entidade não encontrada.' );
            }

            $entity = $entityResult->data;

            return new Response( $this->twig->env->render( 'pages/design_pattern/edit.twig', [
                'entity' => $entity,
            ] ) );
        } catch ( Throwable $e ) {
            getDetailedErrorInfo( $e );
            Session::flash( 'error', "Falha ao carregar a entidade, tente novamente mais tarde ou entre em contato com suporte!" );
            return Redirect::redirect( '/admin/design-patterns' );
        }
    }

    /**
     * Processa o formulário de atualização de entidade.
     *
     * PADRÃO:
     * - Sanitização de parâmetros e dados
     * - ServiceResult para manipulação de retorno
     * - Verificação via isSuccess() para redirecionamento
     * - Log de atividades para auditoria (opcional para NoTenant)
     *
     * @param string $id ID da entidade
     * @return Response
     */
    public function update( string $id ): Response
    {
        try {
            // Sanitizar o ID
            $params = $this->autoSanitizeForEntity( [ 'id' => $id ], DesignPatternNoTenantEntity::class);

            // Validar os dados do formulário
            if ( !DesignPatternNoTenantFormRequest::validate( $this->request ) ) {
                return Redirect::redirect( "/admin/design-patterns/{$id}/edit" )
                    ->withMessage( 'error', 'Dados inválidos. Verifique os campos e tente novamente.' );
            }

            // Obter dados validados
            $data = $this->request->all();

            // Atualizar via service (retorna ServiceResult)
            $result = $this->designPatternService->update( $params[ 'id' ], $data );

            if ( !$result->isSuccess() ) {
                return Redirect::redirect( "/admin/design-patterns/{$id}/edit" )
                    ->withMessage( 'error', $result->message ?? 'Erro ao atualizar a entidade.' );
            }

            // Log de atividades para auditoria (opcional para NoTenant)
            /** @var \design_patern\design_pattern_no_tenant\entities\DesignPatternNoTenantEntity $entity */
            $entity = $result->data;
            $this->activityLogger(
                0, // NoTenant não possui tenant_id, usar 0 para indicar global
                $this->getAuthenticatedUserId() ?? 0,
                'design_pattern_no_tenant_updated',
                'design_pattern_no_tenant',
                $entity->getId(),
                "Design Pattern NoTenant '{$entity->getName()}' atualizado",
                [
                    'entity' => $entity->jsonSerialize()
                ],
            );

            return Redirect::redirect( '/admin/design-patterns' )
                ->withMessage( 'success', 'Entidade atualizada com sucesso!' );
        } catch ( Throwable $e ) {
            getDetailedErrorInfo( $e );
            Session::flash( 'error', "Falha ao atualizar a entidade, tente novamente mais tarde ou entre em contato com suporte!" );
            return Redirect::redirect( "/admin/design-patterns/{$id}/edit" );
        }
    }

    /**
     * Remove uma entidade.
     *
     * PADRÃO:
     * - Sanitização do ID
     * - ServiceResult para manipulação de retorno
     * - Verificação via isSuccess() para redirecionamento
     * - Log de atividades para auditoria (opcional para NoTenant)
     *
     * @param string $id ID da entidade
     * @return Response
     */
    public function delete( string $id ): Response
    {
        try {
            // Sanitizar o ID
            $params = $this->autoSanitizeForEntity( [ 'id' => $id ], DesignPatternNoTenantEntity::class);

            // Deletar via service (retorna ServiceResult)
            $result = $this->designPatternService->delete( $params[ 'id' ] );

            if ( !$result->isSuccess() ) {
                return Redirect::redirect( '/admin/design-patterns' )
                    ->withMessage( 'error', $result->message ?? 'Erro ao excluir a entidade.' );
            }

            // Log de atividades para auditoria (opcional para NoTenant)
            $this->activityLogger(
                0, // NoTenant não possui tenant_id, usar 0 para indicar global
                $this->getAuthenticatedUserId() ?? 0,
                'design_pattern_no_tenant_deleted',
                'design_pattern_no_tenant',
                $params[ 'id' ],
                "Design Pattern NoTenant com ID {$params[ 'id' ]} excluído",
                [
                    'entity_id' => $params[ 'id' ]
                ],
            );

            return Redirect::redirect( '/admin/design-patterns' )
                ->withMessage( 'success', 'Entidade excluída com sucesso!' );
        } catch ( Throwable $e ) {
            getDetailedErrorInfo( $e );
            Session::flash( 'error', "Falha ao excluir a entidade, tente novamente mais tarde ou entre em contato com suporte!" );
            return Redirect::redirect( '/admin/design-patterns' );
        }
    }

}

/*
EXEMPLOS DE USO:

1. Roteamento típico para NoTenant (routes/web.php)
$router->get('/admin/design-patterns', [DesignPatternNoTenantController::class, 'index']);
$router->get('/admin/design-patterns/create', [DesignPatternNoTenantController::class, 'create']);
$router->post('/admin/design-patterns', [DesignPatternNoTenantController::class, 'store']);
$router->get('/admin/design-patterns/{id}', [DesignPatternNoTenantController::class, 'show']);
$router->get('/admin/design-patterns/{id}/edit', [DesignPatternNoTenantController::class, 'edit']);
$router->put('/admin/design-patterns/{id}', [DesignPatternNoTenantController::class, 'update']);
$router->delete('/admin/design-patterns/{id}', [DesignPatternNoTenantController::class, 'delete']);

2. Diferenças principais do padrão NoTenant:
- Não há filtros por tenant_id
- Não há verificação de permissões de tenant
- Log de atividades é opcional (entidades globais)
- Sanitização mais simples (sem tenant_id)
- Rotas podem ser mais diretas
- Templates podem ser compartilhados entre tenants
- ✅ Padrão misto de verificação de status (isSuccess() + comparação direta)
- ✅ Error logging detalhado com getDetailedErrorInfo()
- ✅ Mensagens contextuais específicas dos services

3. Injeção de dependência (container de DI)
$container->set(DesignPatternNoTenantController::class, function(ContainerInterface $c) {
    return new DesignPatternNoTenantController(
        $c->get(Twig::class),
        $c->get(DesignPatternNoTenantService::class),
        $c->get(Request::class)
    );
});

4. Padrões de Verificação implementados:

✅ Método index() - Renderização com tratamento de erro
if ( !$entitiesResult->isSuccess() ) {
    Session::flash( 'error', $entitiesResult->message ?? 'Erro ao carregar as entidades.' );
    return new Response( $this->twig->env->render( 'pages/design_pattern/index.twig', [
        'entities' => [],
    ] ), 500 );
}

✅ Métodos CRUD - Redirecionamento com status comparison
if ( $result->status !== OperationStatus::SUCCESS ) {
    return Redirect::redirect( '/admin/design-patterns/create' )
        ->withMessage( 'error', $result->message ?? 'Erro ao cadastrar a entidade.' );
}

✅ Error Handling - Logging detalhado
catch ( Throwable $e ) {
    getDetailedErrorInfo( $e ); // Log detalhado para debug
    Session::flash( 'error', 'Mensagem amigável para o usuário' );
    return Redirect::redirect( '/caminho/fallback' );
}
*/
