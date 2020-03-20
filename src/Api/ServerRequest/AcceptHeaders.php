<?php

namespace Virtue\Api;

use Psr\Http\Message\ServerRequestInterface as ServerRequest;

class AcceptHeaders
{
    private const REQUEST_ATTR = '__acceptHeaders__';

    private $headers;

    public function __construct($headers)
    {
        $this->headers = $headers;
    }

    public static function ofRequest(ServerRequest $request)
    {
        $headers = $request->getAttribute(self::REQUEST_ATTR);

        if ($headers === null) {
            throw new \RuntimeException('Cannot create AcceptHeaders before parsing has been completed.');
        }

        return new self($headers);
    }

    public function withRequest(ServerRequest $request): ServerRequest
    {
        return $request->withAttribute(self::REQUEST_ATTR, $this->headers);
    }

    private function quantify(array $range): array
    {
        return array_map(
            function (array $accept) {
                $accept[1]['q'] = (float)$accept[1]['q'] ?? 1.0;
            },
            $range
        );
    }
}
