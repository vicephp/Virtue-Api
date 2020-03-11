<?php
namespace Virtue\Api\Routing;

use Psr\Container\ContainerInterface as Locator;
use Psr\Http\Server\MiddlewareInterface as ServerMiddleware;
use Virtue\Api\Middleware\MiddlewareStack;

class RouteGroup
{
    /** @var callable|string */
    private $callable;
    /** @var Locator */
    private $kernel;
    /** @var Api */
    private $api;
    /** @var ServerMiddleware[] */
    private $middleware = [];

    public function __construct($callable, Locator $kernel, Api $api) {
        $this->callable = $callable;
        $this->kernel = $kernel;
        $this->api = $api;
    }

    public function collectRoutes(): void
    {
        ($this->callable)($this->api);
    }

    public function add(string $middleware): self
    {
        $this->middleware[] = $this->kernel->get($middleware);

        return $this;
    }

    public function appendTo(MiddlewareStack $stack): void
    {
        foreach ($this->middleware as $middleware) {
            $stack->append($middleware);
        }
    }
}
