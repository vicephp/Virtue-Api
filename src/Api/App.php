<?php

namespace Virtue\Api;

use Psr\Container\ContainerInterface as Locator;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Psr\Http\Server\RequestHandlerInterface as HandlesServerRequests;
use Slim\Interfaces\MiddlewareDispatcherInterface;
use Slim\ResponseEmitter;
use Virtue\Api\Routing\Api;
use Virtue\Api\Routing\RouteCollector;

class App extends Api implements HandlesServerRequests
{
    /** @var string */
    public const VERSION = '0.0.0';
    /** @var MiddlewareDispatcherInterface */
    protected $middlewareStack;
    /** @var Locator */
    private $services;

    public function __construct(Locator $services) {
        parent::__construct(
            $services,
            $services->get(RouteCollector::class)
        );
        $this->services = $services;
        $this->middlewareStack = $services->get(MiddlewareDispatcherInterface::class);
    }

    public function add(string $middleware): void
    {
        $this->middlewareStack->append($this->services->get($middleware));
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
