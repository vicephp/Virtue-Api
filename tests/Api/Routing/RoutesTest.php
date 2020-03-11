<?php

namespace Api\Routing;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Virtue\Api\App;
use Virtue\Api\AppTestCase;
use Virtue\Api\Middleware\FastRouteMiddleware;

class RoutesTest extends AppTestCase
{
    public function testGetRoute()
    {
        $kernel = $this->container->build();
        $app = $kernel->get(App::class);
        $app->add(FastRouteMiddleware::class);
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
        $app->add(FastRouteMiddleware::class);
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
        $app->add(FastRouteMiddleware::class);
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
        $app->add(FastRouteMiddleware::class);
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
        $app->add(FastRouteMiddleware::class);
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
        $app->add(FastRouteMiddleware::class);
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
        $app->add(FastRouteMiddleware::class);
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
        $app->add(FastRouteMiddleware::class);
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
        $app->add(FastRouteMiddleware::class);
        $app->redirect('/books', '/library', 301);
        $request = $kernel->get(ServerRequest::class);

        $response = $app->handle($request->withUri($request->getUri()->withPath('/books'))->withMethod('GET'));
        $this->assertEquals(301, $response->getStatusCode());
        $this->assertEquals(['/library'], $response->getHeader('Location'));
    }

    public function testClosureBinding()
    {
        $this->markTestSkipped('Inject Locator instead of binding');
        $kernel = $this->container->build();
        $app = $kernel->get(App::class);
        $app->add(FastRouteMiddleware::class);
        $app->get('/hello/{name}', function (ServerRequest $request, Response $response, array $args) {
            // Use app HTTP cookie service
            $this->get('cookies')->set('name', [
                'value' => $args['name'],
                'expires' => '7 days'
            ]);

            return $response;
        });
        $request = $kernel->get(ServerRequest::class);

        $response = $app->handle($request->withUri($request->getUri()->withPath('/hello/world'))->withMethod('GET'));
        $this->assertEquals(200, $response->getStatusCode());
    }
}