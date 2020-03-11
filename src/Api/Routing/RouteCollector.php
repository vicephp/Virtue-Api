<?php

namespace Virtue\Api\Routing;

interface RouteCollector
{
//    public function getRouteParser(): RouteParser;
    public function getBasePath(): string;
    public function getRoutes(): array;
    public function getNamedRoute(string $name): Route;
    public function lookupRoute(string $identifier): Route;
    public function group(string $pattern, $callable): RouteGroup;
    public function map(array $methods, string $pattern, $handler): Route;
}
