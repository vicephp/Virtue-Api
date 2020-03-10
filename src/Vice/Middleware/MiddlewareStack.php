<?php

namespace Vice\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Psr\Http\Server\MiddlewareInterface as ServerMiddleware;
use Psr\Http\Server\RequestHandlerInterface as HandlesServerRequests;

class MiddlewareStack implements HandlesServerRequests
{
    /** @var ServerMiddleware[] */
    protected $stack = [];
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
        $this->stack[] = $middleware;
    }

    public function prepend(ServerMiddleware $middleware): void
    {
        array_unshift($this->stack, $middleware);
    }

    public function stack(self $stack): self
    {
        return new self($stack, $this->stack);
    }

    public function handle(ServerRequest $request): Response
    {
        return $this->buildHandlerStack()->handle($request);
    }

    private function buildHandlerStack($index = 0): HandlesServerRequests
    {
        return isset($this->stack[$index]) ? new RequestHandler($this->stack[$index], $this->buildHandlerStack($index + 1)) : $this->bottom;
    }
}
