<?php

namespace Virtue\Api\Routing;

interface RouteCollector
{
    public function map(array $methods, string $pattern, $handler): Route;
    /** @return array|Route[] */
    public function getRoutes(): array;
}
