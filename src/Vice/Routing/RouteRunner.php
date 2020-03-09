<?php

namespace Vice\Routing;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Psr\Http\Server\RequestHandlerInterface as HandlesServerRequests;
use Slim\Routing\Route;
use Slim\Routing\RouteContext;

class RouteRunner implements HandlesServerRequests
{
    public function handle(ServerRequest $request): Response
    {
        if ($request->getAttribute(RouteContext::ROUTING_RESULTS) === null) {
            throw new \RuntimeException('Routing results not found. Did you add middleware for routing?');
        }
        /** @var Route $route */
        $route = $request->getAttribute(RouteContext::ROUTE);

        return $route->run($request);
    }
}
