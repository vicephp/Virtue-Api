<?php

namespace Virtue\Api\Handler;

use Psr\Container\ContainerInterface as Locator;
use Psr\Http\Message\ResponseFactoryInterface as ResponseFactory;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Psr\Http\Server\RequestHandlerInterface as HandlesServerRequests;
use Slim\Interfaces\CallableResolverInterface;
use Slim\Views\PhpRenderer;
use Virtue\Api\App;
use Virtue\Api\Middleware\Routing;
use Virtue\Api\Routing\RouteParams;
use Virtue\Api\TestCase;
use Virtue\Api\Testing\KlaatuBaradaNword;
use Virtue\View\ProvidesViews;
use Virtue\View\View;

class RequestViewParamsTest extends TestCase
{
    public function testHandling()
    {
        $this->container->addDefinitions([
            HandlesServerRequests::class => function (Locator $kernel) {
                return new RequestViewParams(
                    $kernel->get(CallableResolverInterface::class),
                    $kernel->get(ProvidesViews::class)
                );
            },
            ProvidesViews::class => function (Locator $kernel) {
                return new View(
                    $kernel->get(ResponseFactory::class),
                    new PhpRenderer()
                );
            }
        ]);

        $kernel = $this->container->build();
        $app = $kernel->get(App::class);
        $app->get('/run', function (ServerRequest $request, ProvidesViews $response, RouteParams $args) {
            return $response->withText('Klaatu Barada Nikto');
        });
        $app->add(Routing::class);
        $request = $kernel->get(ServerRequest::class);
        $response = $app->handle(
            $request->withUri($request->getUri()->withPath('/run'))
        );

        $this->assertEquals('Klaatu Barada Nikto', (string) $response->getBody(), new KlaatuBaradaNword());
    }
}
