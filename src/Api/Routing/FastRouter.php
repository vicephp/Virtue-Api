<?php

namespace Virtue\Api\Routing;

use FastRoute;
use Psr\Container\ContainerInterface as Locator;
use RuntimeException;
use function array_pop;

class FastRouter implements RouteCollector
{
    /** @var Locator */
    private $kernel;
    /** @var string */
    private $basePath = '';
    /** @var Route[] */
    private $routes = [];
    /** @var RouteGroup[] */
    private $routeGroups = [];
    /** @var int */
    private $routeCounter = 0;
    /** @var FastRoute\RouteCollector */
    private $fastRouteCollector;

    public function __construct(Locator $kernel) {
        $this->kernel = $kernel;
        $this->fastRouteCollector = $kernel->get(FastRoute\RouteCollector::class);
    }

    public function getRouteParser(): RouteParser
    {
        return $this->routeParser;
    }

    public function getBasePath(): string
    {
        return $this->basePath;
    }

    public function getRoutes(): array
    {
        return $this->routes;
    }

    public function removeNamedRoute(string $name): RouteCollector
    {
        $route = $this->getNamedRoute($name);
        unset($this->routes[$route->getIdentifier()]);
        return $this;
    }

    public function getNamedRoute(string $name): Route
    {
        foreach ($this->routes as $route) {
            if ($name === $route->getName()) {
                return $route;
            }
        }
        throw new RuntimeException('Named route does not exist for name: ' . $name);
    }

    public function lookupRoute(string $identifier): Route
    {
        if (!isset($this->routes[$identifier])) {
            throw new RuntimeException('Route not found, looks like your route cache is stale.');
        }
        return $this->routes[$identifier];
    }

    public function group(string $pattern, $callable): RouteGroup
    {
        $api = new Api($this->kernel, $this, $pattern);
        $routeGroup = new RouteGroup($pattern, $callable, $this->kernel, $api);
        $this->routeGroups[] = $routeGroup;
        $routeGroup->collectRoutes();
        array_pop($this->routeGroups);

        return $routeGroup;
    }

    public function map(array $methods, string $pattern, $handler): Route
    {
        $route = $this->createRoute($methods, $pattern, $handler);
        $this->fastRouteCollector->addRoute($methods, $pattern, $route);
        $this->routes[$route->getIdentifier()] = $route;
        $this->routeCounter++;

        return $route;
    }

    protected function createRoute(array $methods, string $pattern, $callable): Route
    {
        return new Route(
            $methods,
            $pattern,
            $callable,
            $this->kernel,
            $this->routeGroups,
            $this->routeCounter
        );
    }
}
