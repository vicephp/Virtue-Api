<?php

namespace Virtue\Api\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Psr\Http\Server\MiddlewareInterface as ServerMiddleware;
use Psr\Http\Server\RequestHandlerInterface as HandlesServerRequests;
use Virtue\Api\ServerRequest\AcceptHeadersResults;

class AcceptHeaders implements ServerMiddleware
{
    public function process(ServerRequest $request, HandlesServerRequests $handler): Response
    {
        $headers = array_filter(
            $request->getHeaders(),
            function ($key) { return substr($key, 0, 6) == 'Accept'; },
            ARRAY_FILTER_USE_KEY
        );
        $headers = array_map(
            function ($lines) {
                return array_map(
                    function ($line) { return $this->parseRange($line); },
                    $lines
                );
            },
            $headers
        );

        $results = new AcceptHeadersResults($headers);

        return $handler->handle($results->withRequest($request));
    }

    /**
     * @link https://www.xml.com/pub/a/2005/06/08/restful.html
     * @param string $accept
     * @return array
     */
    private function parse(string $accept):array
    {
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
    }

    /**
     * @param string $line
     * @return array
     */
    private function parseRange(string $line): array
    {
        return array_map(
            function ($accept) {
                return $this->parse($accept);
            },
            explode(',', $line)
        );
    }
}
