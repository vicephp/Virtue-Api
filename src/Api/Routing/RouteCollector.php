<?php

namespace Virtue\Api\Routing;

use Psr\Container\ContainerInterface as Locator;
use RuntimeException;
use Slim\Interfaces\AdvancedCallableResolverInterface;
use function array_pop;

/**
 * RouteCollector is used to collect routes and route groups
 * as well as generate paths and URLs relative to its environment
 */
class RouteCollector
{
    /** @var Locator */
    protected $kernel;
    /** @var RouteParser */
    protected $routeParser;
    /** @var AdvancedCallableResolverInterface */
    protected $callableResolver;
    /** @var string */
    protected $basePath = '';
    /** @var Route[] */
    protected $routes = [];
    /** @var RouteGroup[] */
    protected $routeGroups = [];
    /** @var int */
    protected $routeCounter = 0;

    public function __construct(Locator $kernel) {
        $this->kernel = $kernel;
        $this->callableResolver = $kernel->get(AdvancedCallableResolverInterface::class);
        $this->routeParser = $routeParser ?? new RouteParser($this);
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
        $routeCollectorProxy = new Api(
            $this->kernel,
            $this,
            $pattern
        );
        $routeGroup = new RouteGroup($pattern, $callable, $this->kernel, $routeCollectorProxy);
        $this->routeGroups[] = $routeGroup;

        $routeGroup->collectRoutes();
        array_pop($this->routeGroups);

        return $routeGroup;
    }

    public function map(array $methods, string $pattern, $handler): Route
    {
        $route = $this->createRoute($methods, $pattern, $handler);
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
