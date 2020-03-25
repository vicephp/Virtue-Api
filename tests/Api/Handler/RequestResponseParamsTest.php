<?php

namespace Virtue\Api\Handler;

use Psr\Container\ContainerInterface as Locator;
use Psr\Http\Message\ResponseFactoryInterface as ResponseFactory;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Psr\Http\Server\RequestHandlerInterface as HandlesServerRequests;
use Slim\Interfaces\CallableResolverInterface;
use Virtue\Api\App;
use Virtue\Api\Middleware\Routing;
use Virtue\Api\Routing\RouteParams;
use Virtue\Api\TestCase;
use Virtue\Api\Testing\KlaatuBaradaNword;

class RequestResponseParamsTest extends TestCase
{
    public function testHandling()
    {
        $this->container->addDefinitions([
            HandlesServerRequests::class => function (Locator $kernel) {
                return new RequestResponseParams(
                    $kernel->get(CallableResolverInterface::class),
                    $kernel->get(ResponseFactory::class)
                );
            },
        ]);

        $kernel = $this->container->build();
        $app = $kernel->get(App::class);
        $app->get('/run', function (ServerRequest $request, Response $response, RouteParams $args) {
            $response->getBody()->write('Klaatu Barada Nikto');

            return $response;
        });
        $app->add(Routing::class);
        $request = $kernel->get(ServerRequest::class);

        $response = $app->handle(
            $request->withUri($request->getUri()->withPath('/run'))
        );

        $this->assertEquals('Klaatu Barada Nikto', (string) $response->getBody(), new KlaatuBaradaNword());
    }
}
