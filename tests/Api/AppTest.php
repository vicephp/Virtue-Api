<?php

namespace Virtue\Api;

use Prophecy\Argument;
use Psr\Container\ContainerInterface;
use Psr\Container\ContainerInterface as Locator;
use Psr\Http\Message\ResponseFactoryInterface as ResponseFactory;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Slim\ResponseEmitter;
use Virtue\Api\Middleware\MiddlewareContainer;
use Virtue\Api;
use Virtue\Api\Routing;
use Virtue\Api\Testing;

class AppTest extends TestCase
{
    public function testDoesNotUseContainerAsServiceLocator()
    {
        $routeCollector = $this->prophesize(Routing\RouteCollector::class);
        $kernel = $this->prophesize(ContainerInterface::class);
        $middlewares = $this->prophesize(MiddlewareContainer::class);
        new App($kernel->reveal(), $routeCollector->reveal(), $middlewares->reveal());

        $kernel->has(Argument::type('string'))->shouldNotHaveBeenCalled();
        $kernel->get(Argument::type('string'))->shouldNotHaveBeenCalled();
    }

    public function testRun()
    {
        $kernel = $this->container->build();
        $app = $kernel->get(App::class);
        $app->add(Api\Middleware\Routing::class);
        $app->get('/run', function ($request, $response, $args) {
            return $response;
        });
        $request = $kernel->get(ServerRequest::class);
        $request = $request->withUri($request->getUri()->withPath('/run'));
        $app->run($request);
        /** @var Testing\ResponseEmitter $emitter */
        $emitter = $kernel->get(ResponseEmitter::class);
        $response = $emitter->last();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getReasonPhrase());
    }

    public function testHandle()
    {
        $this->container->addDefinitions(
            [
                Routing\RouteRunner::class => function (Locator $kernel) {
                    return new Testing\RequestHandler(
                        $kernel->get(ResponseFactory::class)->createResponse()
                    );
                },
            ]
        );
        $kernel = $this->container->build();
        /** @var App $app */
        $app = $kernel->get(App::class);
        $app->add(Api\Middleware\Routing::class);
        $app->get('/handle', function ($request, $response, $args) {
            return $response;
        });
        $request = $kernel->get(ServerRequest::class);
        $request = $request->withUri($request->getUri()->withPath('/handle'));
        $response = $app->handle($request);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getReasonPhrase());
    }
}
