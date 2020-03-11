<?php

namespace Virtue\Api\Routing;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Psr\Http\Server\RequestHandlerInterface as HandlesServerRequests;
use Virtue\Api\App;
use Virtue\Api\AppTestCase;
use Virtue\Api\Middleware\CallableMiddleware;
use Virtue\Api\Middleware\FastRouteMiddleware;
use Virtue\Api\Routing;

class RouteGroupsTest extends AppTestCase
{
    public function testNestedRouteGroups()
    {
        $this->container->addDefinitions(
            [
                'foo' => new CallableMiddleware(
                    function (ServerRequest $request, HandlesServerRequests $next) {
                        $response = $next->handle($request);
                        $response->getBody()->write('foo');
                        return $response;
                    }
                ),
                'bar' => new CallableMiddleware(
                    function (ServerRequest $request, HandlesServerRequests $next) {
                        $response = $next->handle($request);
                        $response->getBody()->write('bar');
                        return $response;
                    }
                ),
                'baz' => new CallableMiddleware(
                    function (ServerRequest $request, HandlesServerRequests $next) {
                        $response = $next->handle($request);
                        $response->getBody()->write('baz');
                        return $response;
                    }
                )
            ]
        );
        $kernel = $this->container->build();
        $app = $kernel->get(App::class);
        $app->add(FastRouteMiddleware::class);
        $app->group('/foo', function (Routing\Api $group) {
            $group->group('/bar', function (Routing\Api $group) {
                $group->get('/baz', function (ServerRequest $request, Response $response, array $args) {
                    return $response;
                })->add('bar')->add('foo');
            })->add('foo')->add('baz');
        })->add('baz')->add('bar');
        $request = $kernel->get(ServerRequest::class);
        $request = $request->withUri($request->getUri()->withPath('/foo/bar/baz'));

        $response = $app->handle($request);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('foobarbazfoobarbaz', (string) $response->getBody());
    }

    public function testRouteGroupWithRouteMiddleware()
    {
        $this->container->addDefinitions(
            [
                'foo' => new CallableMiddleware(
                    function (ServerRequest $request, HandlesServerRequests $next) {
                        $response = $next->handle($request);
                        $response->getBody()->write('foo');
                        return $response;
                    }
                ),
                'bar' => new CallableMiddleware(
                    function (ServerRequest $request, HandlesServerRequests $next) {
                        $response = $next->handle($request);
                        $response->getBody()->write('bar');
                        return $response;
                    }
                )
            ]
        );
        $kernel = $this->container->build();
        $app = $kernel->get(App::class);
        $app->add(FastRouteMiddleware::class);
        $app->group('/foo', function (Routing\Api $group) {
            $group->get('/bar', function (ServerRequest $request, Response $response, array $args) {
                return $response;
            })->add('bar')->add('foo');
        });
        $request = $kernel->get(ServerRequest::class);
        $request = $request->withUri($request->getUri()->withPath('/foo/bar'));

        $response = $app->handle($request);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('foobar', (string)$response->getBody());
    }
}
