<?php

namespace Virtue\Api\Middleware;

use Psr\Container\ContainerInterface as Locator;
use Psr\Http\Message\ResponseFactoryInterface as ResponseFactory;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Virtue\Api\App;
use Virtue\Api\Middleware;
use Virtue\Api\Routing;
use Virtue\Api\Testing;
use Virtue\Api\TestCase;

class RoutingTest extends TestCase
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

    public function testRoutingMiddlewareTwice()
    {
        $kernel = $this->container->build();
        $app = $kernel->get(App::class);
        $app->add(Middleware\Routing::class);
        $path = '/run';
        $app->get($path, function (ServerRequest $request, Response $response, array $args) {
            return $response;
        });
        $request = $kernel->get(ServerRequest::class);

        $request = $request->withUri($request->getUri()->withPath($path));
        $app->run($request); // run twice, application should be stateless
        $app->run($request);
        /** @var Testing\RequestHandlerStub $handler */
        $handler = $kernel->get(Routing\RouteRunner::class);
        $this->assertNotNull($handler->last()->getAttribute(Routing\Route::class));
    }

    public function testNotFound()
    {
        $kernel = $this->container->build();
        $app = $kernel->get(App::class);
        $app->add(Middleware\Routing::class);
        $request = $kernel->get(ServerRequest::class);

        $this->expectException(\Slim\Exception\HttpNotFoundException::class);
        $app->run($request->withUri($request->getUri()->withPath('/notfound')));
    }

    public function testMethodNotAllowed()
    {
        $kernel = $this->container->build();
        $app = $kernel->get(App::class);
        $app->add(Middleware\Routing::class);
        $app->get('/books', function (ServerRequest $request, Response $response, array $args) {
            // Create new book
            return $response;
        });
        $request = $kernel->get(ServerRequest::class);

        $this->expectException(\Slim\Exception\HttpMethodNotAllowedException::class);
        $app->run(
            $request->withUri($request->getUri()->withPath('/books'))->withMethod('POST')
        );
    }
}
