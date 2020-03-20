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

    public function bestMatch(string $header, array $accepts): string
    {
        $accepts = array_map(function (string $accept) { return [$accept, []]; }, $accepts);
        $ranges = $this->headers[$header] ?? [];
        $accepts = array_map(
            function ($accept) use ($ranges) {
                $score = 0;
                foreach ($ranges as $acceptable) {
                    $score += $this->matchRange($accept, $acceptable);
                }
                $accept[2] = $score;
                return $accept;
            },
            $accepts
        );
        return array_reduce(
            $accepts,
            function(array $match, array $current){
                return $match[2] >= $current[2] ? $match : $current;
            },
            $accepts[0]
        )[0];
    }

    private function matchRange(array $accept, array $acceptables): float
    {
        $sum = 0;
        foreach ($acceptables as $acceptable) {
            $sum += ($this->matches($accept, $acceptable) * ($acceptable[1]['q'] ?? 1.0));
        }
        return $sum;
    }

    private function matches(array $accept, array $acceptable): float
    {
        unset($accept[1]['q']);
        $params = count(array_intersect($accept[1], $acceptable[1]));
        $pattern = sprintf('/%s/', str_replace(['*', '/'], ['.*', '\/'], strtolower($acceptable[0])));
        $factor = pow(10, 2 - substr_count($acceptable[0], '*'));
        $fitness = preg_match($pattern, strtolower($accept[0])) * $factor;

        return $params + $fitness;
    }
}
