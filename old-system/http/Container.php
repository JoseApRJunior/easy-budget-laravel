<?php

namespace http;

use function DI\autowire;

use DI\Container as DIContainer;
use DI\ContainerBuilder;

class Container
{
    public DIContainer $container;
    private array      $services;

    public function build(array $services = [])
    {
        $this->load($services);
        $container = new ContainerBuilder();

        $container->addDefinitions(...$this->services);

        return $container->build();
    }

    public function bind(string $interface, string $class)
    {
        $this->services[] = [ $interface => autowire($class) ];
    }

    private function load(array $services)
    {
        $default = CORE_PATH . '/services/services.php';
        $this->services[] = $default;
        if (!empty($services)) {
            foreach ($services as $service) {

                $this->services[] = APP_PATH . '/services/' . $service . '.php';
            }
        }
    }

}
