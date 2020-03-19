<?php

namespace Virtue\Api\Routing;

use FastRoute;
use Psr\Container\ContainerInterface as Locator;

class FastRouter implements RouteCollector, Router
{
    /** @var Locator */
    private $kernel;
    /** @var RouteGroup[] */
    private $routeGroups = [];
    /** @var RouteGroup */
    private $routeGroup;
    /** @var int */
    private $routeCounter = 0;
    /** @var FastRoute\RouteCollector */
    private $routeCollector;
    /** @var Route[] */
    private $routes = [];

    public function __construct(Locator $kernel, FastRoute\RouteCollector $routeCollector) {
        $this->kernel = $kernel;
        $this->routeCollector = $routeCollector;
    }

    public function getRoutes(): array
    {
        return $this->routes;
    }

    public function group(string $name, $callable): RouteGroup
    {
        $api = new Api($this->kernel, $this);
        $this->routeGroups[$name] = $this->routeGroups[$name] ?? new RouteGroup($this->kernel, $api);
        $routeGroup = $this->routeGroup = $this->routeGroups[$name];
        $routeGroup->collectRoutes($callable);
        unset($this->routeGroup);

        return $routeGroup;
    }

    public function map(array $methods, string $pattern, $handler): Route
    {
        $route = $this->createRoute($methods, $pattern, $handler);
        $this->routeCollector->addRoute($methods, $pattern, $route);
        $this->routes[$route->getIdentifier()] = $route;

        return $route;
    }

    public function route($httpMethod, $uri): array
    {
        $dispatcher = new FastRoute\Dispatcher\GroupCountBased($this->routeCollector->getData());
        return $dispatcher->dispatch($httpMethod, $uri);
    }

    protected function createRoute(array $methods, string $pattern, $handler): Route
    {
        return new Route(
            $this->kernel,
            $methods,
            $pattern,
            $handler,
            array_filter([$this->routeGroup]),
            $this->routeCounter++
        );
    }
}
