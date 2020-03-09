<?php

namespace Vice;

use Psr\Container\ContainerInterface as Locator;
use Psr\Http\Message\ResponseFactoryInterface as ResponseFactory;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as HandlesServerRequests;
use Slim\CallableResolver;
use Slim\Interfaces\MiddlewareDispatcherInterface;
use Slim\Interfaces\RouteCollectorInterface;
use Slim\Middleware\BodyParsingMiddleware;
use Slim\ResponseEmitter;
use Vice\Routing\RouteCollectorProxy;

class App extends RouteCollectorProxy implements HandlesServerRequests
{
    /** @var string */
    public const VERSION = '0.0.0';
    /** @var MiddlewareDispatcherInterface */
    protected $middlewareStack;
    /** @var Locator */
    private $services;

    public function __construct(Locator $services) {
        parent::__construct(
            $services->get(ResponseFactory::class),
            $services->get(CallableResolver::class),
            $services->get(RouteCollectorInterface::class)
        );
        $this->services = $services;
        $this->middlewareStack = $services->get(MiddlewareDispatcherInterface::class);
    }

    public function add(string $middleware)
    {
        $this->addMiddleware($this->services->get($middleware));
    }

    public function addMiddleware(MiddlewareInterface $middleware): void
    {
        $this->middlewareStack->addMiddleware($middleware);
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
     * @param ServerRequest|null $request
     * @return void
     */
    public function run(?ServerRequest $request = null): void
    {
        if (!$request) {
            $request = $this->services->get(ServerRequest::class);
        }

        $response = $this->handle($request);
        $responseEmitter = new ResponseEmitter();
        $responseEmitter->emit($response);
    }

    public function handle(ServerRequest $request): Response
    {
        return $this->middlewareStack->handle($request);
    }
}
