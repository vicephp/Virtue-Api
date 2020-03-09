<?php

namespace Vice\Testing;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as ServerResponse;
use Psr\Http\Server\MiddlewareInterface as ServerMiddleware;
use Psr\Http\Server\RequestHandlerInterface as HandlesServerRequests;
use Slim\Interfaces\MiddlewareDispatcherInterface;

class MiddlewareStackStub implements MiddlewareDispatcherInterface
{
    /** @var array */
    private $stack = [];
    /** @var HandlesServerRequests */
    private $kernel;

    public function add($middleware): MiddlewareDispatcherInterface
    {
        $this->stack[] = $middleware;

        return $this;
    }

    public function addMiddleware(ServerMiddleware $middleware): MiddlewareDispatcherInterface
    {
        $this->stack[] = $middleware;

        return $this;
    }

    public function seedMiddlewareStack(HandlesServerRequests $kernel): void
    {
        $this->kernel = $kernel;
    }

    public function handle(ServerResponse $request): Response
    {
        return $this->kernel->handle($request);
    }

    public function append(ServerMiddleware $middleware): void
    {
        $this->stack[] = $middleware;
    }

    public function prepend(ServerMiddleware $middleware): void
    {
        array_unshift($this->stack, $middleware);
    }

    public function contains(string $className, $times = 1)
    {
        return $times == array_sum(
            array_map(function ($item) use ($className) {
                return (int)($item instanceof $className);
            }, $this->stack)
        );
    }
}
