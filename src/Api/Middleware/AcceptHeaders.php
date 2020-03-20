<?php

namespace Virtue\Api\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Psr\Http\Server\MiddlewareInterface as ServerMiddleware;
use Psr\Http\Server\RequestHandlerInterface as HandlesServerRequests;
use Virtue\Api\ServerRequest\AcceptHeaderParser;
use Virtue\Api\ServerRequest\AcceptHeadersResults;

class AcceptHeaders implements ServerMiddleware
{
    /** @var AcceptHeaderParser */
    private $parser;
    /** @var array|array[] */
    private $supported = [];

    public function __construct(array $supported)
    {
        $this->parser = new AcceptHeaderParser();
        $this->supported = $supported;
    }

    public function process(ServerRequest $request, HandlesServerRequests $handler): Response
    {
        $headers = array_intersect_key($request->getHeaders(), $this->supported);
        foreach ($headers as $type => $lines) {
            $line = implode(',', $lines);
            $headers[$type] = $this->parser->bestMatch($this->supported[$type] ?? [], $line);
        }

        $results = new AcceptHeadersResults($headers);

        return $handler->handle($results->withRequest($request));
    }
}
