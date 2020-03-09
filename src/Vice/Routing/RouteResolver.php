<?php

namespace Vice\Routing;

use RuntimeException;
use Slim\Interfaces\DispatcherInterface;
use Slim\Interfaces\RouteCollectorInterface;
use Slim\Interfaces\RouteInterface;
use Slim\Interfaces\RouteResolverInterface;

use Slim\Routing\RoutingResults;
use function rawurldecode;

/**
 * RouteResolver instantiates the FastRoute dispatcher
 * and computes the routing results of a given URI and request method
 */
class RouteResolver implements RouteResolverInterface
{
    /** @var RouteCollectorInterface */
    protected $routeCollector;
    /** @var DispatcherInterface */
    private $dispatcher;

    /**
     * @param RouteCollectorInterface  $routeCollector
     * @param DispatcherInterface $dispatcher
     */
    public function __construct(RouteCollectorInterface $routeCollector, DispatcherInterface $dispatcher)
    {
        $this->routeCollector = $routeCollector;
        $this->dispatcher = $dispatcher;
    }

    /**
     * @param string $uri Should be $request->getUri()->getPath()
     * @param string $method
     * @return RoutingResults
     */
    public function computeRoutingResults(string $uri, string $method): RoutingResults
    {
        $uri = rawurldecode($uri);
        if ($uri === '' || $uri[0] !== '/') {
            $uri = '/' . $uri;
        }
        return $this->dispatcher->dispatch($method, $uri);
    }

    /**
     * @param string $identifier
     * @return RouteInterface
     * @throws RuntimeException
     */
    public function resolveRoute(string $identifier): RouteInterface
    {
        return $this->routeCollector->lookupRoute($identifier);
    }
}
