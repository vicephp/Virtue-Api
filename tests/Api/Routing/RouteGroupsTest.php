<?php

namespace Virtue\Api\Routing;

use Fig\Http\Message\StatusCodeInterface as StatusCode;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Psr\Http\Server\RequestHandlerInterface as HandlesServerRequests;
use Virtue\Api\App;
use Virtue\Api\AppTestCase;
use Virtue\Api\Middleware\FastRouteMiddleware;
use Virtue\Api\Routing;

class RouteGroupsTest extends AppTestCase
{
    public function testNestedRouteGroups()
    {
        $this->container->addDefinitions(
            [
                'set400' => $this->mockMiddleware(
                    function (ServerRequest $request, HandlesServerRequests $next) {
                        return $next->handle($request)->withStatus(StatusCode::STATUS_BAD_REQUEST);
                    }
                ),
                'set301' => $this->mockMiddleware(
                    function (ServerRequest $request, HandlesServerRequests $next) {
                        return $next->handle($request)->withStatus(StatusCode::STATUS_MOVED_PERMANENTLY);
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
                });
            })->add('set400');
        })->add('set301');
        $request = $kernel->get(ServerRequest::class);
        $request = $request->withUri($request->getUri()->withPath('/foo/bar/baz'));

        $response = $app->handle($request);
        $this->assertEquals(301, $response->getStatusCode());
        $this->assertEquals('Moved Permanently', $response->getReasonPhrase());
    }

    public function testRouteGroupWithRouteMiddleware()
    {
        $this->container->addDefinitions(
            [
                'set400' => $this->mockMiddleware(
                    function (ServerRequest $request, HandlesServerRequests $next) {
                        return $next->handle($request)->withStatus(StatusCode::STATUS_BAD_REQUEST);
                    }
                ),
                'set301' => $this->mockMiddleware(
                    function (ServerRequest $request, HandlesServerRequests $next) {
                        return $next->handle($request)->withStatus(StatusCode::STATUS_MOVED_PERMANENTLY);
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
            })->add('set301');
        });
        $request = $kernel->get(ServerRequest::class);
        $request = $request->withUri($request->getUri()->withPath('/foo/bar'));

        $response = $app->handle($request);
        $this->assertEquals(301, $response->getStatusCode());
        $this->assertEquals('Moved Permanently', $response->getReasonPhrase());
    }
}
