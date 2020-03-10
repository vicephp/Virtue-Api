<?php
namespace Virtue\Api\Routing;

use Psr\Container\ContainerInterface as Locator;
use Psr\Http\Server\MiddlewareInterface;
use Slim\Interfaces\AdvancedCallableResolverInterface;
use Virtue\Api\Middleware\MiddlewareStack;

class RouteGroup
{
    /** @var string */
    private $pattern;
    /** @var callable|string */
    private $callable;
    /** @var Locator */
    private $kernel;
    /** @var Api */
    private $api;
    /** @var MiddlewareInterface[] */
    private $middleware = [];

    public function __construct(
        string $pattern,
        $callable,
        Locator $kernel,
        Api $api
    ) {
        $this->pattern = $pattern;
        $this->callable = $callable;
        $this->kernel = $kernel;
        $this->api = $api;
    }

    public function collectRoutes(): void
    {
        $callableResolver = $this->kernel->get(AdvancedCallableResolverInterface::class);
        $callable = $callableResolver->resolveRoute($this->callable);

        $callable($this->api);
    }

    public function add(string $middleware): void
    {
        $this->middleware[] = $this->kernel->get($middleware);
    }

    public function appendMiddlewareToDispatcher(MiddlewareStack $stack): void
    {
        foreach ($this->middleware as $middleware) {
            $stack->append($middleware);
        }
    }

    public function getPattern(): string
    {
        return $this->pattern;
    }
}
