<?php

namespace Virtue\Api\Routing;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Psr\Http\Server\RequestHandlerInterface as HandlesServerRequests;
use Virtue\Api\App;
use Virtue\Api\AppTestCase;
use Virtue\Api\Middleware\CallableMiddleware;
use Virtue\Api\Middleware\RoutingMiddleware;
use Virtue\Api\Routing;

class RouteGroupsTest extends AppTestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->container->addDefinitions(
            [
                'klaatu' => new CallableMiddleware(
                    function (ServerRequest $request, HandlesServerRequests $next) {
                        $response = $next->handle($request);
                        $response->getBody()->write('klaatu ');
                        return $response;
                    }
                ),
                'barada' => new CallableMiddleware(
                    function (ServerRequest $request, HandlesServerRequests $next) {
                        $response = $next->handle($request);
                        $response->getBody()->write('barada ');
                        return $response;
                    }
                ),
                'nikto' => new CallableMiddleware(
                    function (ServerRequest $request, HandlesServerRequests $next) {
                        $response = $next->handle($request);
                        $response->getBody()->write('nikto ');
                        return $response;
                    }
                )
            ]
        );
    }

    public function testNestedRouteGroups()
    {
        $kernel = $this->container->build();
        $app = $kernel->get(App::class);
        $app->add(RoutingMiddleware::class);
        $app->group('/klaatu', function (Routing\Api $group) {
            $group->group('/barada', function (Routing\Api $group) {
                $group->get('/nikto', function (ServerRequest $request, Response $response, array $args) {
                    return $response;
                })->add('barada')->add('klaatu');
            })->add('klaatu')->add('nikto');
        })->add('nikto')->add('barada');
        $request = $kernel->get(ServerRequest::class);
        $request = $request->withUri($request->getUri()->withPath('/klaatu/barada/nikto'));

        $response = $app->handle($request);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('klaatu barada nikto klaatu barada nikto ', (string) $response->getBody());
    }

    public function testRouteGroupWithRouteMiddleware()
    {
        $kernel = $this->container->build();
        $app = $kernel->get(App::class);
        $app->add(RoutingMiddleware::class);
        $app->add('nikto');
        $app->group('/klaatu', function (Routing\Api $group) {
            $group->get('/barada', function (ServerRequest $request, Response $response, array $args) {
                return $response;
            })->add('barada')->add('klaatu');
        });
        $request = $kernel->get(ServerRequest::class);
        $request = $request->withUri($request->getUri()->withPath('/klaatu/barada'));

        $response = $app->handle($request);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('klaatu barada nikto ', (string)$response->getBody());
    }
}
