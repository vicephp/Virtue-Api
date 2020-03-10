<?php
namespace Vice\Routing;

use Psr\Container\ContainerInterface as Locator;
use Psr\Http\Server\MiddlewareInterface;
use Slim\Interfaces\AdvancedCallableResolverInterface;
use Slim\Interfaces\CallableResolverInterface;
use Slim\MiddlewareDispatcher;

class RouteGroup
{
    /** @var string */
    private $pattern;
    /** @var callable|string */
    private $callable;
    /** @var Locator */
    private $services;
    /** @var CallableResolverInterface */
    private $callableResolver;
    /** @var RouteCollectorProxy */
    private $routeCollectorProxy;
    /** @var MiddlewareInterface[] */
    private $middleware = [];

    public function __construct(
        string $pattern,
        $callable,
        Locator $services,
        CallableResolverInterface $callableResolver,
        RouteCollectorProxy $routeCollectorProxy
    ) {
        $this->pattern = $pattern;
        $this->callable = $callable;
        $this->services = $services;
        $this->callableResolver = $callableResolver;
        $this->routeCollectorProxy = $routeCollectorProxy;
    }

    public function collectRoutes(): void
    {
        if ($this->callableResolver instanceof AdvancedCallableResolverInterface) {
            $callable = $this->callableResolver->resolveRoute($this->callable);
        } else {
            $callable = $this->callableResolver->resolve($this->callable);
        }
        $callable($this->routeCollectorProxy);
    }

    public function add(string $middleware): void
    {
        $this->middleware[] = $this->services->get($middleware);
    }

    public function addMiddleware(MiddlewareInterface $middleware): void
    {
        $this->middleware[] = $middleware;
    }

    public function appendMiddlewareToDispatcher(MiddlewareDispatcher $dispatcher): void
    {
        foreach ($this->middleware as $middleware) {
            $dispatcher->addMiddleware($middleware);
        }
    }

    public function getPattern(): string
    {
        return $this->pattern;
    }
}
