<?php

namespace Virtue\Api\Routing;

use FastRoute;
use Psr\Container\ContainerInterface as Locator;
use function array_pop;

class FastRouter implements RouteCollector
{
    /** @var Locator */
    private $kernel;
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

        return $route;
    }

    protected function createRoute(array $methods, string $pattern, $handler): Route
    {
        return new Route(
            $methods,
            $pattern,
            $handler,
            $this->kernel,
            $this->routeGroups,
            $this->routeCounter++
        );
    }
}
