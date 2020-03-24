<?php

namespace Virtue\Api\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Psr\Http\Server\MiddlewareInterface as ServerMiddleware;
use Psr\Http\Server\RequestHandlerInterface as HandlesServerRequests;
use Slim\Exception\HttpMethodNotAllowedException;
use Slim\Exception\HttpNotFoundException;
use Virtue\Api\Routing\Found;
use Virtue\Api\Routing\MethodNotAllowed;
use Virtue\Api\Routing\Router;
use Virtue\Api\Routing\Route;
use Virtue\Api\Routing\RouteParams;

class Routing implements ServerMiddleware
{
    /** @var Router */
    private $routes;

    public function __construct(Router $routes) {
        $this->routes = $routes;
    }

    public function process(ServerRequest $request, HandlesServerRequests $handler): Response
    {
        $result = $this->routes->route(
            $request->getMethod(), $request->getUri()->getPath()
        );

        if ($result instanceof Found) {
            $request = $request->withAttribute(Route::class, $result->getRoute());
            $request = $request->withAttribute(RouteParams::class, $result->getRouteParams());
        } elseif ($result instanceof MethodNotAllowed) {
            $exception = new HttpMethodNotAllowedException($request);
            $exception->setAllowedMethods($result->getAllowedMethods());

            throw $exception;
        } else {
            throw new HttpNotFoundException($request);
        }

        return $handler->handle($request);
    }
}
