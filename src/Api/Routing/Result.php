<?php

namespace Virtue\Api\Routing;

class Result
{
    const NOT_FOUND = 0;
    const FOUND = 1;
    const METHOD_NOT_ALLOWED = 2;
    /** @var array */
    protected $results = [];

    private function __construct(array $results)
    {
        if ($results[0] == self::FOUND) {
            $results[2] =  new RouteParams($results[2]);
        }
        $this->results = $results;
    }

    public static function of(array $results): Result
    {
        switch ($results[0]) {
            case self::NOT_FOUND:
                return new NotFound($results);

            case self::FOUND:
                return new Found($results);

            case self::METHOD_NOT_ALLOWED:
                return new MethodNotAllowed($results);

            default:
                throw new \RuntimeException('An unexpected error occurred while performing routing.');
        }
    }
}
