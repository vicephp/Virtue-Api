<?php

namespace Virtue\Api;

use Psr\Container\ContainerInterface as Locator;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Psr\Http\Server\RequestHandlerInterface as HandlesServerRequests;
use Slim\ResponseEmitter;
use Virtue\Api\Middleware\MiddlewareContainer;
use Virtue\Api\Routing\Api;
use Virtue\Api\Routing\RouteCollector;

class App extends Api implements HandlesServerRequests
{
    /** @var string */
    public const VERSION = '0.0.0';
    /** @var MiddlewareContainer */
    protected $middlewares;

    public function __construct(
        Locator $kernel,
        RouteCollector $routeCollector,
        MiddlewareContainer $middlewares
    ) {
        parent::__construct($kernel, $routeCollector);
        $this->middlewares = $middlewares;
    }

    public function add(string $middleware): void
    {
        $this->middlewares->append($this->kernel->get($middleware));
    }

    public function run(?ServerRequest $request = null): void
    {
        $request = $request ?? $this->kernel->get(ServerRequest::class);

        $this->kernel->get(ResponseEmitter::class)->emit($this->handle($request));
    }

    public function handle(ServerRequest $request): Response
    {
        return $this->middlewares->handle($request);
    }
}
