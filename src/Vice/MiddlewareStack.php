<?php

namespace Vice;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Psr\Http\Server\MiddlewareInterface as ServerMiddleware;
use Psr\Http\Server\RequestHandlerInterface as HandlesServerRequests;
use Slim\Interfaces\MiddlewareDispatcherInterface;

class MiddlewareStack implements MiddlewareDispatcherInterface
{
    /** @var ServerMiddleware[] */
    private $stack = [];
    /** @var HandlesServerRequests */
    private $kernel;

    public function __construct(array $middlewares = [])
    {
        foreach ($middlewares as $middleware) {
            $this->add($middleware);
        }
    }

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

    public function prependMiddleware(ServerMiddleware $middleware): self
    {
        array_unshift($this->stack, $middleware);

        return $this;
    }

    public function seedMiddlewareStack(HandlesServerRequests $kernel): void
    {
        $this->kernel = $kernel;
    }

    public function handle(ServerRequest $request): Response
    {
        return $this->handlerWithNextMiddleware()->handle($request);
    }

    private function handlerWithNextMiddleware($index = 0)
    {
        if (!isset($this->stack[$index])) {
            return $this->kernel;
        }

        $middleware = $this->stack[$index];

        $next = $this->handlerWithNextMiddleware($index + 1);

        return new class (
            $middleware,
            $next
        ) implements HandlesServerRequests {
            /** @var ServerMiddleware */
            private $middleware;
            private $next;

            public function __construct(ServerMiddleware $middleware, HandlesServerRequests $next)
            {
                $this->middleware = $middleware;
                $this->next = $next;
            }

            public function handle(ServerRequest $request): Response
            {
                return $this->middleware->process($request, $this->next);
            }
        };
    }
}
