<?php

namespace Virtue\Api\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Psr\Http\Server\MiddlewareInterface as ServerMiddleware;
use Psr\Http\Server\RequestHandlerInterface as HandlesServerRequests;
use Virtue\Api\Routing;

class RoutingMiddleware implements ServerMiddleware
{
    /** @var Routing\RouteDispatcher */
    private $routes;

    public function __construct(Routing\RouteDispatcher $routes) {
        $this->routes = $routes;
    }

    public function process(ServerRequest $request, HandlesServerRequests $handler): Response
    {
        return $handler->handle($this->routes->dispatch($request));
    }
}
