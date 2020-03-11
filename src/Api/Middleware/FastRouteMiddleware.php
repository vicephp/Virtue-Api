<?php

namespace Virtue\Api\Middleware;

use FastRoute;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Psr\Http\Server\MiddlewareInterface as ServerMiddleware;
use Psr\Http\Server\RequestHandlerInterface as HandlesServerRequests;
use RuntimeException;
use Slim\Exception\HttpMethodNotAllowedException;
use Slim\Exception\HttpNotFoundException;
use Virtue\Api\Routing\RoutingResults;

class FastRouteMiddleware implements ServerMiddleware
{
    /** @var FastRoute\RouteCollector */
    private $fastRouteCollector;

    public function __construct(
        FastRoute\RouteCollector $fastRouteCollector
    ) {
        $this->fastRouteCollector = $fastRouteCollector;
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

    public function dispatch(string $method, string $uri): array
    {
        $dispatcher = new FastRoute\Dispatcher\GroupCountBased($this->fastRouteCollector->getData());
        return $dispatcher->dispatch($method, $uri);
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
        $routingResults = new RoutingResults($this->dispatch($request->getMethod(), $request->getUri()->getPath()));

        $request = $request->withAttribute(RoutingResults::REQUEST_PARAM, $routingResults->getRoutingResults());

        switch ($routingResults->getResult()) {
            case RoutingResults::FOUND:
                return $request;

            case RoutingResults::NOT_FOUND:
                throw new HttpNotFoundException($request);

            case RoutingResults::METHOD_NOT_ALLOWED:
                $exception = new HttpMethodNotAllowedException($request);
                $exception->setAllowedMethods($routingResults[1]);
                throw $exception;

            default:
                throw new RuntimeException('An unexpected error occurred while performing routing.');
        }
    }
}
