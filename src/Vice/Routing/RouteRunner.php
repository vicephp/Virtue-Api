<?php

namespace Vice\Routing;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Psr\Http\Server\RequestHandlerInterface as HandlesServerRequests;
use Slim\Interfaces\RouteCollectorInterface;
use Slim\Routing\Route;
use Slim\Routing\RouteContext;

class RouteRunner implements HandlesServerRequests
{
    /** @var RouteCollectorInterface */
    private $routeCollector;

    public function __construct(
        RouteCollectorInterface $routeCollector
    ) {
        $this->routeCollector = $routeCollector;
    }

    public function handle(ServerRequest $request): Response
    {
        if ($request->getAttribute(RouteContext::ROUTING_RESULTS) === null) {
            throw new \RuntimeException('Routing results not found. Did you add middleware for routing.');
        }

        $request = $request->withAttribute(RouteContext::BASE_PATH, $this->routeCollector->getBasePath());
        /** @var Route $route */
        $route = $request->getAttribute(RouteContext::ROUTE);

        return $route->run($request);
    }
}
