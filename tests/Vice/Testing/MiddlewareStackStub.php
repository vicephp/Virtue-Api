<?php

namespace Vice\Testing;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as HandlesRequests;
use Slim\Interfaces\MiddlewareDispatcherInterface;

class MiddlewareStackStub implements MiddlewareDispatcherInterface
{
    /** @var array */
    private $stack = [];
    /** @var HandlesRequests */
    private $kernel;

    public function add($middleware): MiddlewareDispatcherInterface
    {
        // TODO: Implement add() method.
    }

    public function addMiddleware(MiddlewareInterface $middleware): MiddlewareDispatcherInterface
    {
        $this->stack[] = $middleware;

        return $this;
    }

    public function seedMiddlewareStack(HandlesRequests $kernel): void
    {
        $this->kernel = $kernel;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->kernel->handle($request);
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
