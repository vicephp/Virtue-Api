<?php

namespace Vice\Middleware;

use FastRoute;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;
use Slim\Exception\HttpMethodNotAllowedException;
use Slim\Exception\HttpNotFoundException;
use Slim\Interfaces\DispatcherInterface;
use Slim\Interfaces\RouteCollectorInterface;
use Slim\Interfaces\RouteParserInterface;
use Slim\Routing\FastRouteDispatcher;
use Slim\Routing\RouteContext;
use Slim\Routing\RoutingResults;

class FastRouteMiddleware implements MiddlewareInterface, DispatcherInterface
{
    /** @var RouteCollectorInterface */
    private $routeCollector;
    /** @var FastRoute\RouteCollector */
    private $fastRouteCollector;
    /** @var RouteParserInterface */
    private $routeParser;

    public function __construct(
        RouteCollectorInterface $routeCollector,
        FastRoute\RouteCollector $fastRouteCollector,
        RouteParserInterface $routeParser
    ) {
        $this->routeCollector = $routeCollector;
        $this->fastRouteCollector = $fastRouteCollector;
        $this->routeParser = $routeParser;
    }

    /**
     * @param ServerRequest  $request
     * @param RequestHandlerInterface $handler
     * @return Response
     *
     * @throws HttpNotFoundException
     * @throws HttpMethodNotAllowedException
     * @throws RuntimeException
     */
    public function process(ServerRequest $request, RequestHandlerInterface $handler): Response
    {
        $request = $request->withAttribute(RouteContext::ROUTE_PARSER, $this->routeParser);
        $request = $request->withAttribute(RouteContext::BASE_PATH, $this->routeCollector->getBasePath());
        $request = $this->performRouting($request);
        return $handler->handle($request);
    }

    public function dispatch(string $method, string $uri): RoutingResults
    {
        $uri = rawurldecode($uri);
        $uri = ($uri === '' || $uri[0] !== '/') ? "/{$uri}" : $uri;
        $dispatcher = new FastRouteDispatcher($this->fastRouteCollector->getData());
        $results = $dispatcher->dispatch($method, $uri);
        return new RoutingResults($this, $method, $uri, $results[0], $results[1], $results[2]);
    }

    public function getAllowedMethods(string $uri): array
    {
        $dispatcher = new FastRouteDispatcher($this->fastRouteCollector->getData());
        return $dispatcher->getAllowedMethods($uri);
    }

    /**
     * @param  ServerRequest $request
     * @return ServerRequest
     *
     * @throws HttpNotFoundException
     * @throws HttpMethodNotAllowedException
     * @throws RuntimeException
     */
    private function performRouting(ServerRequest $request): ServerRequest
    {
        foreach ($this->routeCollector->getRoutes() as $route) {
            $this->fastRouteCollector->addRoute(
                $route->getMethods(),
                "{$this->routeCollector->getBasePath()}{$route->getPattern()}",
                $route->getIdentifier()
            );
        }
        $routingResults = $this->dispatch($request->getMethod(), $request->getUri()->getPath());
        $routeStatus = $routingResults->getRouteStatus();

        $request = $request->withAttribute(RouteContext::ROUTING_RESULTS, $routingResults);

        switch ($routeStatus) {
            case RoutingResults::FOUND:
                $arguments = $routingResults->getRouteArguments();
                $identifier = $routingResults->getRouteIdentifier() ?? '';
                $route = $this->routeCollector->lookupRoute($identifier)->prepare($arguments);
                return $request->withAttribute(RouteContext::ROUTE, $route);

            case RoutingResults::NOT_FOUND:
                throw new HttpNotFoundException($request);

            case RoutingResults::METHOD_NOT_ALLOWED:
                $exception = new HttpMethodNotAllowedException($request);
                $exception->setAllowedMethods($routingResults->getAllowedMethods());
                throw $exception;

            default:
                throw new RuntimeException('An unexpected error occurred while performing routing.');
        }
    }
}
