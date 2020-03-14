<?php

namespace Virtue\Api\Routing;

interface RouteCollector
{
    public function group(string $pattern, $callable): RouteGroup;
    public function map(array $methods, string $pattern, $handler): Route;
    /** @return array|Route[] */
    public function getRoutes(): array;
}
