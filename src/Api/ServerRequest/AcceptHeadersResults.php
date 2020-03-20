<?php

namespace Virtue\Api\ServerRequest;

use Psr\Http\Message\ServerRequestInterface as ServerRequest;

class AcceptHeadersResults
{
    const REQUEST_ATTR = '__acceptHeaders__';
    /** @var array|array[] */
    private $headers = [];

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

    public function bestMatch(string $header): string
    {
        return $this->headers[$header] ?? '';
    }
}
