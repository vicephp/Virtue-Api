<?php

namespace Virtue\Api\Handler;

use DI\ContainerBuilder;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Virtue\Api\Testing;

class CallableResolverTest extends TestCase
{
    public function testResolve()
    {
        $container = new ContainerBuilder();
        $container->addDefinitions([
            'klaatu' => 'barada',
            Testing\RequestHandler::class => new Testing\RequestHandler(new Response()),
        ]);
        $kernel = $container->build();

        $callable = new CallableResolver($kernel);

        $handler = $callable->resolve(Testing\HomeController::class);
        $this->assertInstanceOf(Testing\HomeController::class, $handler[CallableResolver::INSTANCE]);
        $this->assertEquals('__invoke', $handler[CallableResolver::METHOD]);

        $handler = $callable->resolve(sprintf('%s::%s', Testing\HomeController::class, 'home'));
        $this->assertInstanceOf(Testing\HomeController::class, $handler[CallableResolver::INSTANCE]);
        $this->assertEquals('home', $handler[CallableResolver::METHOD]);

        $handler = $callable->resolve(function () { return $this->get('klaatu'); });
        $this->assertEquals('barada', $handler());

        $this->expectException(\RuntimeException::class);
        $callable->resolve(Testing\RequestHandler::class);
    }
}
