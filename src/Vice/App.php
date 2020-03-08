<?php

namespace Vice;

use Psr\Container\ContainerInterface as Container;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\CallableResolver;
use Slim\Interfaces\MiddlewareDispatcherInterface;
use Slim\Interfaces\RouteCollectorInterface;
use Slim\Interfaces\RouteResolverInterface;
use Slim\Middleware\BodyParsingMiddleware;
use Slim\Middleware\ErrorMiddleware;
use Slim\Middleware\RoutingMiddleware;
use Slim\ResponseEmitter;
use Slim\Routing\RouteCollectorProxy;
use Vice\Routing\RouteRunner;

class App extends RouteCollectorProxy implements RequestHandlerInterface
{
    /** @var string */
    public const VERSION = '0.0.0';

    /** @var MiddlewareDispatcherInterface */
    protected $middlewareStack;

    public function __construct(Container $container) {
        parent::__construct(
            $container->get(ResponseFactoryInterface::class),
            $container->get(CallableResolver::class),
            $container,
            $container->get(RouteCollectorInterface::class)
        );
        $this->middlewareStack = $container->get(MiddlewareDispatcherInterface::class);
        $this->middlewareStack->seedMiddlewareStack(
            new RouteRunner($container->get(RouteResolverInterface::class), $this->routeCollector->getRouteParser(), $this)
        );
    }

    /**
     * @return MiddlewareDispatcherInterface
     */
    public function getMiddlewareDispatcher(): MiddlewareDispatcherInterface
    {
        return $this->middlewareStack;
    }

    /**
     * @param MiddlewareInterface|string|callable $middleware
     * @return self
     */
//    public function add($middleware): self
//    {
//        $this->middlewareStack->add($middleware);
//
//        return $this;
//    }

    /**
     * @param MiddlewareInterface $middleware
     * @return self
     */
    public function addMiddleware(MiddlewareInterface $middleware): self
    {
        $this->middlewareStack->addMiddleware($middleware);

        return $this;
    }

    /**
     * Add the Slim built-in routing middleware to the app middleware stack
     *
     * This method can be used to control middleware order and is not required for default routing operation.
     *
     * @return RoutingMiddleware
     */
    public function addRoutingMiddleware(): RoutingMiddleware
    {
        $routingMiddleware = new RoutingMiddleware(
            $this->container->get(RouteResolverInterface::class),
            $this->container->get(RouteCollectorInterface::class)->getRouteParser()
        );

        $this->addMiddleware($routingMiddleware);

        return $routingMiddleware;
    }

    /**
     * Add the Slim built-in error middleware to the app middleware stack
     *
     * @param bool $displayErrorDetails
     * @param bool $logErrors
     * @param bool $logErrorDetails
     *
     * @return ErrorMiddleware
     */
    public function addErrorMiddleware(
        bool $displayErrorDetails,
        bool $logErrors,
        bool $logErrorDetails
    ): ErrorMiddleware {
        $errorMiddleware = new ErrorMiddleware(
            $this->container->get(CallableResolver::class),
            $this->container->get(ResponseFactoryInterface::class),
            $displayErrorDetails,
            $logErrors,
            $logErrorDetails
        );
        $this->addMiddleware($errorMiddleware);

        return $errorMiddleware;
    }

    /**
     * Add the Slim body parsing middleware to the app middleware stack
     *
     * @param callable[] $bodyParsers
     *
     * @return BodyParsingMiddleware
     */
    public function addBodyParsingMiddleware(array $bodyParsers = []): BodyParsingMiddleware
    {
        $bodyParsingMiddleware = new BodyParsingMiddleware($bodyParsers);
        $this->addMiddleware($bodyParsingMiddleware);

        return $bodyParsingMiddleware;
    }

    /**
     * Run application
     *
     * This method traverses the application middleware stack and then sends the
     * resultant Response object to the HTTP client.
     *
     * @param ServerRequestInterface|null $request
     * @return void
     */
    public function run(?ServerRequestInterface $request = null): void
    {
        if (!$request) {
            $request = $this->container->get(ServerRequestInterface::class);
        }

        $response = $this->handle($request);
        $responseEmitter = new ResponseEmitter();
        $responseEmitter->emit($response);
    }

    /**
     * Handle a request
     *
     * This method traverses the application middleware stack and then returns the
     * resultant Response object.
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $response = $this->middlewareStack->handle($request);

        /**
         * This is to be in compliance with RFC 2616, Section 9.
         * If the incoming request method is HEAD, we need to ensure that the response body
         * is empty as the request may fall back on a GET route handler due to FastRoute's
         * routing logic which could potentially append content to the response body
         * https://www.w3.org/Protocols/rfc2616/rfc2616-sec9.html#sec9.4
         */
        $method = strtoupper($request->getMethod());
        if ($method === 'HEAD') {
            $emptyBody = $this->responseFactory->createResponse()->getBody();
            return $response->withBody($emptyBody);
        }

        return $response;
    }
}
