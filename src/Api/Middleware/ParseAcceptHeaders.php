<?php

namespace Virtue\Api\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Psr\Http\Server\MiddlewareInterface as ServerMiddleware;
use Psr\Http\Server\RequestHandlerInterface as HandlesServerRequests;

class ParseAcceptHeaders implements ServerMiddleware
{

    private $headers = ['Accept-Charset'];

    public function process(ServerRequest $request, HandlesServerRequests $handler): Response
    {
        $headers = array_reduce(
            $this->headers,
            function (array $headers, string $name) use ($request) {
                $headers[$name] = $request->getHeader($name);

                return $headers;
            },
            []
        );

        $headers = array_map(
            function ($accepts) {
                return array_map(
                    function ($accept) {return $this->parse($accept); },
                    $accepts
                );
            },
            $headers
        );

        return $handler->handle($request->withAttribute('parsed', $headers));
    }

    private function parse(string $line): array
    {
        return array_map(
            function ($accept) {
                $parts = explode(";", $accept);
                $params = array_reduce(
                    array_slice($parts, 1),
                    function (array $params, string $pair) {
                        $pair = explode('=', $pair);
                        $params[trim($pair[0])] = trim($pair[1]);
                        return $params;
                    },
                    []
                );
                return [trim($parts[0]), $params];
            },
            explode(',', $line)
        );
    }
}
