<?php

namespace Virtue\Api\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Psr\Http\Server\MiddlewareInterface as ServerMiddleware;
use Psr\Http\Server\RequestHandlerInterface as HandlesServerRequests;

class MiddlewareContainer implements HandlesServerRequests, Stackable
{
    /** @var ServerMiddleware[] */
    protected $middlewares = [];
    /** @var HandlesServerRequests */
    protected $bottom;

    public function __construct(HandlesServerRequests $bottom, array $stack = [])
    {
        foreach ($stack as $middleware) {
            $this->append($middleware);
        }
        $this->bottom = $bottom;
    }

    public function append(ServerMiddleware $middleware): void
    {
        $this->middlewares[] = $middleware;
    }

    public function prepend(ServerMiddleware $middleware): void
    {
        array_unshift($this->middlewares, $middleware);
    }

    public function stack(HandlesServerRequests $bottom): self
    {
        return new self($bottom, $this->middlewares);
    }

    public function handle(ServerRequest $request): Response
    {
        return $this->buildHandlerStack()->handle($request);
    }

    private function buildHandlerStack($index = 0): HandlesServerRequests
    {
        return isset($this->middlewares[$index]) ? new RequestHandler($this->middlewares[$index], $this->buildHandlerStack($index + 1)) : $this->bottom;
    }
}
