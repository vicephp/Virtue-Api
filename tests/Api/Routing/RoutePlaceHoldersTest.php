<?php

namespace Virtue\Api\Routing;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Virtue\Api\App;
use Virtue\Api\AppTestCase;
use Virtue\Api\Middleware\RoutingMiddleware;

class RoutePlaceHoldersTest extends AppTestCase
{
    public function testFormat()
    {
        $kernel = $this->container->build();
        $app = $kernel->get(App::class);
        $app->add(RoutingMiddleware::class);
        $app->get('/hello/{name}', function (ServerRequest $request, Response $response, $args) {
            $response->getBody()->write("Hello, {$args['name']}");
            return $response;
        });
        $request = $kernel->get(ServerRequest::class);

        $response = $app->handle($request->withUri($request->getUri()->withPath('/hello/world'))->withMethod('GET'));
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("Hello, world", (string) $response->getBody());
    }

    public function testOptionalSegments()
    {
        $kernel = $this->container->build();
        $app = $kernel->get(App::class);
        $app->add(RoutingMiddleware::class);
        $app->get('/users[/{id}]', function (ServerRequest $request, Response $response, $args) {
            // responds to both `/users` and `/users/123`
            // but not to `/users/`
            return $response;
        });
        $request = $kernel->get(ServerRequest::class);

        $response = $app->handle($request->withUri($request->getUri()->withPath('/users'))->withMethod('GET'));
        $this->assertEquals(200, $response->getStatusCode());
        $response = $app->handle($request->withUri($request->getUri()->withPath('/users/world'))->withMethod('GET'));
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testMultipleOptionalSegments()
    {
        $kernel = $this->container->build();
        $app = $kernel->get(App::class);
        $app->add(RoutingMiddleware::class);

        $app->get('/news[/{year}[/{month}]]', function (ServerRequest $request, Response $response, array $args) {
            // responds to `/news`, `/news/2016` and `/news/2016/03`
            $response->getBody()->write(implode('/', $args));
            return $response;
        });
        $request = $kernel->get(ServerRequest::class);

        $response = $app->handle($request->withUri($request->getUri()->withPath('/news')));
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('', (string) $response->getBody());
        $response = $app->handle($request->withUri($request->getUri()->withPath('/news/2016')));
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('2016', (string) $response->getBody());
        $response = $app->handle($request->withUri($request->getUri()->withPath('/news/2016/03')));
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('2016/03', (string) $response->getBody());
    }

    public function testUnlimitedOptionalSegments()
    {
        $this->markTestSkipped('Not Supported!');
        $kernel = $this->container->build();
        $app = $kernel->get(App::class);
        $app->add(RoutingMiddleware::class);
        $app->get('/news[/{params:.*}]', function (ServerRequest $request, Response $response, $args) {
            $response->getBody()->write($args['params']);

            return $response;
        });
        $request = $kernel->get(ServerRequest::class);

        $response = $app->handle($request->withUri($request->getUri()->withPath('/news'))->withMethod('GET'));
        $this->assertEquals(200, $response->getStatusCode());
        $response = $app->handle($request->withUri($request->getUri()->withPath('/news/2016'))->withMethod('GET'));
        $this->assertEquals(200, $response->getStatusCode());
        $response = $app->handle($request->withUri($request->getUri()->withPath('/news/2016/03'))->withMethod('GET'));
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testRegexMatch()
    {
        $kernel = $this->container->build();
        $app = $kernel->get(App::class);
        $app->add(RoutingMiddleware::class);
        $app->get('/users/{id:[0-9]+}', function (ServerRequest $request, Response $response, $args) {
            // user identified by $args['id']
            $response->getBody()->write($args['id']);
            return $response;
        });
        $request = $kernel->get(ServerRequest::class);

        $response = $app->handle($request->withUri($request->getUri()->withPath('/users/123'))->withMethod('GET'));
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('123', (string) $response->getBody());
    }
}
