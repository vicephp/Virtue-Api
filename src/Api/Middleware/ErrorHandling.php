<?php

namespace Virtue\Api\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Psr\Http\Server\MiddlewareInterface as ServerMiddleware;
use Psr\Http\Server\RequestHandlerInterface as HandlesServerRequests;
use Virtue\Http\Message\RequestParser;

class ErrorHandling implements ServerMiddleware
{
    /** @var RequestParser */
    private $parser;
    /** @var array|ServerMiddleware[] */
    protected $errorRenderers = [];

    public function __construct(
        RequestParser $parser,
        array $errorRenderers
    ) {
        $this->parser = $parser;
        $this->errorRenderers = $errorRenderers;
    }

    public function process(ServerRequest $request, HandlesServerRequests $handler): Response
    {
        return $this->handleException($request)->process($request, $handler);
    }

    private function handleException(ServerRequest $request): ServerMiddleware
    {
        $contentType = $this->parser->header($request)->bestAccept(array_keys($this->errorRenderers), 'text/plain');
        return $this->errorRenderers[$contentType];
    }
}
