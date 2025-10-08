<?php

namespace http;

use Closure;
use DI\Container;

/**
 * Classe Router
 *
 * Esta classe é responsável por gerenciar as rotas da aplicação.
 */
class Router
{
    /** @var array $routes Array de rotas registradas */
    private array $routes = [];

    /** @var array $routeOptions Opções de grupo de rotas */
    private array $routeOptions = [];

    /** @var Route $route Rota atual */
    private Route $route;

    /**
     * Construtor da classe Router
     *
     * @param Container $container Container de injeção de dependência
     * @param Request $request Objeto de requisição
     */
    public function __construct(private Container $container, public readonly Request $request)
    {
    }

    /**
     * Adiciona uma nova rota
     *
     * @param string $uri URI da rota
     * @param string $request Método HTTP da rota
     * @param string $controller Controlador e método a ser chamado
     * @param array $wildCardAliases Aliases para wildcards na URI
     * @return self
     */
    public function add(
        string $uri,
        string $request,
        string $controller,
        array $wildCardAliases = [],
    ) {
        $this->route = new Route($request, $controller, $wildCardAliases);
        $this->route->AddRouteUri(new Uri($uri));
        $this->route->addRouteWildcard(new RouteWildcard());
        $this->route->addRouteGroupOptions(new RouteOptions($this->routeOptions));
        $this->routes[] = $this->route;

        return $this;
    }

    /**
     * Define um grupo de rotas
     *
     * @param array $routeOptions Opções do grupo de rotas
     * @param Closure $callback Função de callback para definir as rotas do grupo
     * @return void
     */
    public function group(array $routeOptions, Closure $callback)
    {
        $this->routeOptions = $routeOptions;
        $callback->call($this);
        $this->routeOptions = [];
    }

    /**
     * Adiciona middlewares à rota atual
     *
     * @param array $middlewares Array de middlewares a serem adicionados
     * @return void
     */
    public function middlewares(array $middlewares)
    {
        $options = [];
        $options = (!empty($this->routeOptions)) ?
            array_merge($this->routeOptions, [ 'middlewares' => $middlewares ]) :
            [ 'middlewares' => $middlewares ];

        $this->route->addRouteGroupOptions(new RouteOptions($options));
    }

    /**
     * Adiciona opções à rota atual
     *
     * @param array $options Opções a serem adicionadas
     * @return void
     */
    public function options(array $options)
    {
        if (!empty($this->routeOptions)) {
            $options = array_merge($this->routeOptions, $options);
        }
        $this->route->addRouteGroupOptions(new RouteOptions($options));
    }

    /**
     * Inicializa o roteamento
     *
     * @return mixed Retorna o resultado da chamada do controlador ou lança uma exceção
     * @throws \Exception Se a rota não for encontrada (apenas em ambiente de desenvolvimento)
     */
    public function init()
    {
        foreach ($this->routes as $route) {
            /** @var Route $route */
            if ($route->match()) {
                Redirect::register($route);

                return (new Controller())->call($route, $this->container, $this->request);
            }
        }

        // Rota não encontrada
        if (env('APP_ENV') === 'production') {
            // Em produção, redirecionar para o controlador NotFound
            return (new Controller())->call(
                new Route('GET', 'NotFoundController:index', []),
                $this->container,
                $this->request,
            );
        } else {
            // Em desenvolvimento, mostrar mais detalhes sobre o erro
            $method = $this->request->getMethod();
            $url = $_SERVER[ 'HTTP_HOST' ] . $_SERVER[ 'REQUEST_URI' ];

            throw new \Exception(sprintf(
                "Rota [%s] não encontrada para o caminho: [%s]",
                $method,
                $url,
            ));
        }
    }

}
