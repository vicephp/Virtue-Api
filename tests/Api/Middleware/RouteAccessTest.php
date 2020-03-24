<?php

namespace Virtue\Api\Middleware;

use Psr\Container\ContainerInterface as Locator;
use Psr\Http\Message\ResponseFactoryInterface as ResponseFactory;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Slim\Exception\HttpForbiddenException;
use Virtue\Access;
use Virtue\Api\App;
use Virtue\Api\Middleware;
use Virtue\Api\Routing;
use Virtue\Api\TestCase;
use Virtue\Api\Testing;

class RouteAccessTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->container->addDefinitions(
            [
                Routing\RouteRunner::class => function (Locator $kernel) {
                    return new Testing\RequestHandlerStub(
                        $kernel->get(ResponseFactory::class)->createResponse()
                    );
                },
            ]
        );
    }

    public function testGranted()
    {
        $this->container->addDefinitions(
            [
                Access\GrantsAccess::class => new Access\GrantsAccess\AlwaysGranted()
            ]
        );
        $kernel = $this->container->build();
        $app = $kernel->get(App::class);
        $app->get('/run', function ($request, $response, $args) {
            return $response;
        });
        $app->add(Middleware\Routing::class);
        $app->add(RouteAccess::class);
        $request = $kernel->get(ServerRequest::class);

        $this->assertInstanceOf(
            Response::class,
            $app->handle(
                $request->withUri($request->getUri()->withPath('/run'))
            )
        );
    }

    public function testDenied()
    {
        $this->container->addDefinitions(
            [
                Access\GrantsAccess::class => new Access\GrantsAccess\AlwaysDenied()
            ]
        );
        $kernel = $this->container->build();
        $app = $kernel->get(App::class);
        $app->get('/run', function ($request, $response, $args) {
            return $response;
        });
        $app->add(Middleware\Routing::class);
        $app->add(RouteAccess::class);
        $request = $kernel->get(ServerRequest::class);

        $this->expectException(HttpForbiddenException::class);
        $app->handle(
            $request->withUri($request->getUri()->withPath('/run'))
        );
    }
}
