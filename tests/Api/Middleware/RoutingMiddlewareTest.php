<?php

namespace Virtue\Api\Middleware;

use Psr\Container\ContainerInterface as Locator;
use Psr\Http\Message\ResponseFactoryInterface as ResponseFactory;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Virtue\Api\App;
use Virtue\Api\Routing;
use Virtue\Api\ServerRequest\RoutingResults;
use Virtue\Api\Testing;
use Virtue\Api\AppTestCase;

class RoutingMiddlewareTest extends AppTestCase
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
        $app->add(RoutingMiddleware::class);
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
        $context = RoutingResults::fromRequest($handler->last());
        $this->assertNotNull($context->getRoute());
    }

    public function testNotFound()
    {
        $kernel = $this->container->build();
        $app = $kernel->get(App::class);
        $app->add(RoutingMiddleware::class);
        $request = $kernel->get(ServerRequest::class);

        $this->expectException(\Slim\Exception\HttpNotFoundException::class);
        $app->run($request->withUri($request->getUri()->withPath('/notfound')));
    }

    public function testMethodNotAllowed()
    {
        $kernel = $this->container->build();
        $app = $kernel->get(App::class);
        $app->add(RoutingMiddleware::class);
        $app->get('/books', function (ServerRequest $request, Response $response, array $args) {
            // Create new book
            return $response;
        });
        $request = $kernel->get(ServerRequest::class);

        $this->expectException(\Slim\Exception\HttpMethodNotAllowedException::class);
        $app->run($request->withUri($request->getUri()->withPath('/books'))->withMethod('POST'));
    }
}
