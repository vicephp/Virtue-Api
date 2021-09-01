<?php

namespace Virtue\Api\Middleware;

use Psr\Container\ContainerInterface as Locator;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Psr\Http\Message\ResponseFactoryInterface as ResponseFactory;
use Slim\Exception\HttpUnauthorizedException;
use Virtue\Access;
use Virtue\Api\Routing;
use Virtue\Api\TestCase;
use Virtue\Api\Testing;

class AuthorizationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->container->addDefinitions(
            [
                Routing\RouteRunner::class => function (Locator $kernel) {
                    return new Testing\RequestHandler(
                        [$kernel->get(ResponseFactory::class)->createResponse()]
                    );
                },
            ]
        );
    }

    public function testAuthorized()
    {
        $this->container->addDefinitions(
            [
                Access\Identity::class => new Access\Identities\Root()
            ]
        );
        $kernel = $this->container->build();

        $authentication = $kernel->get(Authorization::class);
        $runner = $kernel->get(Routing\RouteRunner::class);

        $this->assertInstanceOf(
            Response::class,
            $authentication->process($kernel->get(ServerRequest::class), $runner)
        );
    }

    public function testNotAuthorized()
    {
        $this->container->addDefinitions(
            [
                Access\Identity::class => new Access\Identities\Guest()
            ]
        );
        $kernel = $this->container->build();

        $authentication = $kernel->get(Authorization::class);
        $runner = $kernel->get(Routing\RouteRunner::class);

        $this->expectException(HttpUnauthorizedException::class);
        $authentication->process($kernel->get(ServerRequest::class), $runner);
    }
}
