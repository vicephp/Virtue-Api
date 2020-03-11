<?php

namespace Virtue\Api\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Psr\Http\Server\MiddlewareInterface as ServerMiddleware;
use Psr\Http\Server\RequestHandlerInterface as HandlesServerRequests;
use RuntimeException;
use Slim\Exception\HttpMethodNotAllowedException;
use Slim\Exception\HttpNotFoundException;
use Virtue\Api\Routing;

class RoutingMiddleware implements ServerMiddleware
{
    /** @var Routing\RouteDispatcher */
    private $routes;

    public function __construct(Routing\RouteDispatcher $routes) {
        $this->routes = $routes;
    }

    /**
     * @param ServerRequest  $request
     * @param HandlesServerRequests $handler
     * @return Response
     *
     * @throws HttpNotFoundException
     * @throws HttpMethodNotAllowedException
     * @throws RuntimeException
     */
    public function process(ServerRequest $request, HandlesServerRequests $handler): Response
    {
        return $handler->handle($this->performRouting($request));
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
        $routingResults = $this->routes->dispatch($request->getMethod(), $request->getUri()->getPath());
        return $routingResults->withRequest($request);
    }
}
