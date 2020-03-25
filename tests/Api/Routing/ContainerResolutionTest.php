<?php

namespace Virtue\Api\Routing;

use Psr\Container\ContainerInterface as Locator;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Virtue\Api\App;
use Virtue\Api\TestCase;
use Virtue\Api\Middleware\Routing;
use Virtue\Api\Testing\HomeController;

class ContainerResolutionTest extends TestCase
{
    public function testRegisteringAController()
    {
        $this->container->addDefinitions(
            [
                'HomeController' => function (Locator $kernel) {
                    return new HomeController($kernel);
                }
            ]
        );

        $kernel = $this->container->build();
        $app = $kernel->get(App::class);
        $app->add(Routing::class);
        $app->get('/', 'HomeController');
        $app->get('/home', 'HomeController:home');
        $request = $kernel->get(ServerRequest::class);

        $response = $app->handle($request->withUri($request->getUri()->withPath('/'))->withMethod('GET'));
        $this->assertEquals(200, $response->getStatusCode());

        $response = $app->handle($request->withUri($request->getUri()->withPath('/home'))->withMethod('GET'));
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testAllowSlimToInstantiateTheController()
    {
        $kernel = $this->container->build();
        $app = $kernel->get(App::class);
        $app->add(Routing::class);
        $app->get('/', \Virtue\Api\Testing\HomeController::class . ':home');
        $app->get('/contact', \Virtue\Api\Testing\HomeController::class . ':contact');
        $request = $kernel->get(ServerRequest::class);

        $response = $app->handle($request->withUri($request->getUri()->withPath('/'))->withMethod('GET'));
        $this->assertEquals(200, $response->getStatusCode());

        $response = $app->handle($request->withUri($request->getUri()->withPath('/contact'))->withMethod('GET'));
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testUsingAnInvokableClass()
    {
        $kernel = $this->container->build();
        $app = $kernel->get(App::class);
        $app->add(Routing::class);
        $app->get('/', \Virtue\Api\Testing\HomeController::class);

        $request = $kernel->get(ServerRequest::class);

        $response = $app->handle($request->withUri($request->getUri()->withPath('/'))->withMethod('GET'));
        $this->assertEquals(200, $response->getStatusCode());
    }
}
