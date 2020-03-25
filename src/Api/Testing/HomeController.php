<?php

namespace Virtue\Api\Testing;

use Psr\Container\ContainerInterface as Locator;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;

class HomeController
{
    public function __invoke(ServerRequest $request, Response $response, array $args): Response
    {
        // your code
        // to access items in the container... $this->services->get('');
        return $response;
    }

    public function home(ServerRequest $request, Response $response, array $args): Response
    {
        // your code
        // to access items in the container... $this->container->get('');
        return $response;
    }

    public function contact(ServerRequest $request, Response $response, array $args): Response
    {
        // your code
        // to access items in the container... $this->container->get('');
        return $response;
    }
}
