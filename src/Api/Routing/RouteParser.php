<?php

namespace Virtue\Api\Routing;

use Psr\Http\Message\UriInterface;
use Slim\Interfaces\RouteParserInterface;

class RouteParser implements RouteParserInterface
{

    public function __construct()
    {
    }

    public function relativeUrlFor(string $routeName, array $data = [], array $queryParams = []): string
    {

    }

    public function urlFor(string $routeName, array $data = [], array $queryParams = []): string
    {

    }

    public function fullUrlFor(UriInterface $uri, string $routeName, array $data = [], array $queryParams = []): string
    {
    }
}
