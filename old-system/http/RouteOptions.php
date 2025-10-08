<?php

namespace http;

class RouteOptions
{
    public function __construct(private readonly array $routeOptions)
    {
    }

    public function optionsExist(string $index): bool
    {
        return !empty($this->routeOptions) && isset($this->routeOptions[$index]);
    }

    public function execute(string $index)
    {
        return $this->routeOptions[$index];
    }
}
