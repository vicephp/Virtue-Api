<?php

namespace Virtue\Api\Routing;

use Psr\Container\ContainerInterface as Locator;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Psr\Http\Server\RequestHandlerInterface as HandlesServerRequests;
use Virtue\Api\Handler\CallableInvoker;
use Virtue\Api\Middleware\MiddlewareContainer;
use Virtue\Api\Middleware\Stackable;

class Route implements HandlesServerRequests
{
    /** @var Locator */
    protected $kernel;
    /** @var MiddlewareContainer */
    protected $middlewares;
    /** @var string[] */
    protected $methods = [];
    /** @var string */
    protected $identifier;
    /** @var string */
    protected $name = '';
    /** @var Stackable[] */
    protected $groups;
    /** @var callable|string */
    protected $handler;
    /** @var string */
    protected $pattern;

    public function __construct(
        Locator $kernel,
        array $methods,
        string $pattern,
        $handler,
        array $groups = [],
        int $identifier = 0
    ) {
        $this->kernel = $kernel;
        $this->methods = $methods;
        $this->pattern = $pattern;
        $this->handler = $handler;
        $this->groups = $groups;
        $this->identifier = "route::{$identifier}";
        $this->middlewares = new MiddlewareContainer(
            $kernel->get(CallableInvoker::class)
        );
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getMethods(): array
    {
        return $this->methods;
    }

    public function getPattern(): string
    {
        return $this->pattern;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getHandler()
    {
        return $this->handler;
    }

    public function add(string $middleware): self
    {
        $this->middlewares->append($this->kernel->get($middleware));

        return $this;
    }

    public function run(ServerRequest $request): Response
    {
        return $this->buildHandlerStack()->handle($request);
    }

    protected function buildHandlerStack(): HandlesServerRequests
    {
        return array_reduce(
            array_reverse($this->groups),
            function (HandlesServerRequests $bottom, Stackable $stack) { return $stack->stack($bottom); },
            $this->middlewares
        );
    }

    public function handle(ServerRequest $request): Response
    {
        return $this->buildHandlerStack()->handle($request);
    }
}
