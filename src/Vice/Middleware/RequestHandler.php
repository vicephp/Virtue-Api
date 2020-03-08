<?php

namespace Vice\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Psr\Http\Server\MiddlewareInterface as ServerMiddleware;
use Psr\Http\Server\RequestHandlerInterface as HandlesServerRequests;

class RequestHandler implements HandlesServerRequests
{
    /** @var ServerMiddleware */
    private $middleware;
    /** @var HandlesServerRequests */
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
}

