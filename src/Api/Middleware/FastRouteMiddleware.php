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
use Slim\Routing\RouteContext;

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
        $routingResults = $this->dispatch($request->getMethod(), $request->getUri()->getPath());

        $request = $request->withAttribute(RouteContext::ROUTING_RESULTS, $routingResults);

        switch ($routingResults[0]) {
            case FastRoute\Dispatcher::FOUND:
                return $request;

            case FastRoute\Dispatcher::NOT_FOUND:
                throw new HttpNotFoundException($request);

            case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
                $exception = new HttpMethodNotAllowedException($request);
                $exception->setAllowedMethods($routingResults[0]);
                throw $exception;

            default:
                throw new RuntimeException('An unexpected error occurred while performing routing.');
        }
    }
}
