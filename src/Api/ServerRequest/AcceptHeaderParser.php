<?php

namespace Virtue\Api\ServerRequest;

/**
 * @link https://www.xml.com/pub/a/2005/06/08/restful.html
 */
class AcceptHeaderParser
{
    public function bestMatch(array $supported, string $header): string
    {
        $supported = array_map(function (string $supported) { return [$supported, []]; }, $supported);
        $ranges = $this->parseRange($header);
        $supported = array_map(
            function ($supported) use ($ranges) {
                $score = 0;
                foreach ($ranges as $acceptable) {
                    $score += $this->matchRange($supported, $acceptable);
                }
                $supported[2] = $score;
                return $supported;
            },
            $supported
        );
        return array_reduce(
            $supported,
            function(array $match, array $current){
                return $match[2] >= $current[2] ? $match : $current;
            },
            $supported[0]
        )[0];
    }

    private function matchRange(array $supported, array $acceptable): float
    {
        return ($this->matches($supported, $acceptable) * ($acceptable[1]['q'] ?? 1.0));
    }

    private function matches(array $supported, array $acceptable): float
    {
        unset($supported[1]['q']);
        $params = count(array_intersect($supported[1], $acceptable[1]));
        $pattern = sprintf('/%s/', str_replace(['*', '/'], ['.*', '\/'], strtolower($acceptable[0])));
        $factor = pow(10, 2 - substr_count($acceptable[0], '*'));
        $fitness = preg_match($pattern, strtolower($supported[0])) * $factor;

        return $params + $fitness;
    }

    public function parseRange(string $line): array
    {
        return array_map(function ($accept) { return $this->parse($accept); }, explode(',', $line));
    }

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
}
