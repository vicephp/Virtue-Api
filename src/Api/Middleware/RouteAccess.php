<?php

namespace Virtue\Api\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Psr\Http\Server\MiddlewareInterface as ServerMiddleware;
use Psr\Http\Server\RequestHandlerInterface as HandlesServerRequests;
use Virtue\Access;
use Slim\Exception\HttpForbiddenException;
use Virtue\Api\Routing\Route;

class RouteAccess implements ServerMiddleware
{
    /** @var Access\GrantsAccess */
    private $routeAccess;

    public function __construct(Access\GrantsAccess $routeAccess)
    {
        $this->routeAccess = $routeAccess;
    }

    public function process(ServerRequest $request, HandlesServerRequests $handler): Response
    {
        $route = $request->getAttribute(Route::class);
        if ($this->routeAccess->granted($route->getPattern())) {
            return $handler->handle($request);
        }

        throw new HttpForbiddenException($request);
    }
}
