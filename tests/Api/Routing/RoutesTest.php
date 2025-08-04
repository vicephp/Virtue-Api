<?php

namespace Virtue\Api\Routing;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Virtue\Api\App;
use Virtue\Api\TestCase;
use Virtue\Api\Middleware\Routing;
use Virtue\Api\Routing\Route;
use Virtue\Api\Routing\RouteCollector;

class RoutesTest extends TestCase
{
    public function testGetRoute()
    {
        $kernel = $this->container->build();
        $app = $kernel->get(App::class);
        $app->add(Routing::class);
        $app->get('/books/{id}', function (ServerRequest $request, Response $response, array $args) {
            // Show book identified by $args['id']
            return $response;
        });
        $request = $kernel->get(ServerRequest::class);

        $response = $app->handle($request->withUri($request->getUri()->withPath('/books/id'))->withMethod('GET'));
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testPostRoute()
    {
        $kernel = $this->container->build();
        $app = $kernel->get(App::class);
        $app->add(Routing::class);
        $app->post('/books', function (ServerRequest $request, Response $response, array $args) {
            // Create new book
            return $response;
        });
        $request = $kernel->get(ServerRequest::class);

        $response = $app->handle($request->withUri($request->getUri()->withPath('/books'))->withMethod('POST'));
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testPutRoute()
    {
        $kernel = $this->container->build();
        $app = $kernel->get(App::class);
        $app->add(Routing::class);
        $app->put('/books/{id}', function (ServerRequest $request, Response $response, array $args) {
            // Update book identified by $args['id']
            return $response;
        });
        $request = $kernel->get(ServerRequest::class);

        $response = $app->handle($request->withUri($request->getUri()->withPath('/books/id'))->withMethod('PUT'));
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testDeleteRoute()
    {
        $kernel = $this->container->build();
        $app = $kernel->get(App::class);
        $app->add(Routing::class);
        $app->delete('/books/{id}', function (ServerRequest $request, Response $response, array $args) {
            // Delete book identified by $args['id']
            return $response;
        });
        $request = $kernel->get(ServerRequest::class);

        $response = $app->handle($request->withUri($request->getUri()->withPath('/books/id'))->withMethod('DELETE'));
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testOptionsRoute()
    {
        $kernel = $this->container->build();
        $app = $kernel->get(App::class);
        $app->add(Routing::class);
        $app->options('/books/{id}', function (ServerRequest $request, Response $response, array $args) {
            // Return response headers
            return $response;
        });
        $request = $kernel->get(ServerRequest::class);

        $response = $app->handle($request->withUri($request->getUri()->withPath('/books/id'))->withMethod('OPTIONS'));
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testPatchRoute()
    {
        $kernel = $this->container->build();
        $app = $kernel->get(App::class);
        $app->add(Routing::class);
        $app->patch('/books/{id}', function (ServerRequest $request, Response $response, array $args) {
            // Apply changes to book identified by $args['id']
            return $response;
        });
        $request = $kernel->get(ServerRequest::class);

        $response = $app->handle($request->withUri($request->getUri()->withPath('/books/id'))->withMethod('PATCH'));
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testAnyRoute()
    {
        $kernel = $this->container->build();
        $app = $kernel->get(App::class);
        $app->add(Routing::class);
        $app->any('/books/{id}', function (ServerRequest $request, Response $response, array $args) {
            // Apply changes to book identified by $args['id']
            return $response;
        });
        $request = $kernel->get(ServerRequest::class);

        $response = $app->handle($request->withUri($request->getUri()->withPath('/books/id'))->withMethod('PATCH'));
        $this->assertEquals(200, $response->getStatusCode());

        $response = $app->handle($request->withUri($request->getUri()->withPath('/books/id'))->withMethod('GET'));
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testCustomRoute()
    {
        $kernel = $this->container->build();
        $app = $kernel->get(App::class);
        $app->add(Routing::class);
        $app->map(['GET', 'POST'], '/books', function (ServerRequest $request, Response $response, array $args) {
            // Create new book or list all books
            return $response;
        });
        $request = $kernel->get(ServerRequest::class);

        $response = $app->handle($request->withUri($request->getUri()->withPath('/books'))->withMethod('GET'));
        $this->assertEquals(200, $response->getStatusCode());

        $response = $app->handle($request->withUri($request->getUri()->withPath('/books'))->withMethod('POST'));
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testRedirect()
    {
        $kernel = $this->container->build();
        $app = $kernel->get(App::class);
        $app->add(Routing::class);
        $app->redirect('/books', '/library', 301);
        $request = $kernel->get(ServerRequest::class);

        $response = $app->handle($request->withUri($request->getUri()->withPath('/books'))->withMethod('GET'));
        $this->assertEquals(301, $response->getStatusCode());
        $this->assertEquals(['/library'], $response->getHeader('Location'));
    }

    public function testClosureBinding()
    {
        $kernel = $this->container->build();
        $app = $kernel->get(App::class);
        $app->add(Routing::class);
        $app->get('/hello/{name}', function (ServerRequest $request, Response $response, array $args) {
            /** @var Route $route */
            foreach ($this->get(RouteCollector::class)->getRoutes() as $route) {
                $response->getBody()->write($route->getPattern());
            }

            return $response;
        });
        $request = $kernel->get(ServerRequest::class);

        $response = $app->handle($request->withUri($request->getUri()->withPath('/hello/world'))->withMethod('GET'));
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('/hello/{name}', (string) $response->getBody());
    }
}
