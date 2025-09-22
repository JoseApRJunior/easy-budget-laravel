<?php

namespace core\library;

use Doctrine\DBAL\Connection;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFilter;
use Twig\TwigFunction;

class Twig extends AbstractExtension
{
    public readonly Environment $env;

    public function __construct(
        private readonly Connection $connection,
    ) {

        $loader = new FilesystemLoader(BASE_PATH . '/app/views');
        $this->env = new Environment($loader, [
            'cache' => false, // ou caminho para cache
            'charset' => 'UTF-8',
            'auto_reload' => true,
            'autoescape' => 'html',
        ]);

        if (!Session::has('csrf_token') || !isValidTokenFormat(Session::get('csrf_token'))) {
            Session::set('csrf_token', generateCSRFToken());
        }

    }

    public function addFunctions(): void
    {

        $functions = require CORE_PATH . '/twig/functions/twig.php';

        foreach ($functions as $index => $function) {
            if ($function instanceof \Closure) {
                $function = $function->bindTo($this, self::class);
            }
            $this->env->addFunction(new TwigFunction($index, $function));
        }
    }

    public function addFilters(): void
    {

        $filters = require CORE_PATH . '/twig/filters/twig.php';

        foreach ($filters as $index => $filter) {
            if ($filter instanceof \Closure) {
                $filter = $filter->bindTo($this, self::class);
            }
            $this->env->addFilter(new TwigFilter($index, $filter, [ 'is_safe' => [ 'html' ] ]));
        }
    }

    /**
     * Adiciona variáveis globais ao ambiente Twig.
     */
    public function addGlobals(): void
    {

        $globals = require CORE_PATH . '/twig/globals/twig.php';

        foreach ($globals as $index => $global) {
            if ($global instanceof \Closure) {
                $global = $global->bindTo($this, self::class);
                $global = $global();
            }
            $this->env->addGlobal($index, $global);
        }

    }

    public function getConnection(): Connection
    {
        return $this->connection;
    }

}
