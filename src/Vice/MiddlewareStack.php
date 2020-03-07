<?php

namespace Vice;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Interfaces\MiddlewareDispatcherInterface;

class MiddlewareStack implements MiddlewareDispatcherInterface
{
    /** @var MiddlewareInterface[] */
    private $stack = [];
    /** @var RequestHandlerInterface */
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

    public function addMiddleware(MiddlewareInterface $middleware): MiddlewareDispatcherInterface
    {
        $this->stack[] = $middleware;

        return $this;
    }

    public function prependMiddleware(RequestHandlerInterface $middleware): self
    {
        array_unshift($this->stack, $middleware);

        return $this;
    }

    public function seedMiddlewareStack(RequestHandlerInterface $kernel): void
    {
        $this->kernel = $kernel;
    }

    /**
     * Invoke the middleware stack
     *
     * @param  ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
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
        ) implements RequestHandlerInterface {
            /** @var MiddlewareInterface */
            private $middleware;
            private $next;

            public function __construct($middleware, $next)
            {
                $this->middleware = $middleware;
                $this->next = $next;
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return $this->middleware->process($request, $this->next);
            }
        };
    }
}
