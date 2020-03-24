<?php

namespace Virtue\Api\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Psr\Http\Server\MiddlewareInterface as ServerMiddleware;
use Psr\Http\Server\RequestHandlerInterface as HandlesServerRequests;
use Slim\Exception\HttpMethodNotAllowedException;
use Slim\Exception\HttpNotFoundException;
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
        $routingResults = $this->routes->route(
            $request->getMethod(), $request->getUri()->getPath()
        );

        switch ($routingResults[0]) {
            case 1:
                $request = $request->withAttribute(Route::class, $routingResults[1]);
                $request = $request->withAttribute(RouteParams::class, new RouteParams($routingResults[2]));
                break;

            case 0:
                throw new HttpNotFoundException($request);

            case 2:
                $exception = new HttpMethodNotAllowedException($request);
                $exception->setAllowedMethods($routingResults[1]);
                throw $exception;

            default:
                throw new \RuntimeException('An unexpected error occurred while performing routing.');
        }


        return $handler->handle($request);
    }
}
