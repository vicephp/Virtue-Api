<?php

namespace Virtue\Api\Middleware;

use Psr\Container\ContainerInterface as Locator;
use Psr\Http\Message\ResponseFactoryInterface as ResponseFactory;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Virtue\Api\App;
use Virtue\Api\AppTestCase;
use Virtue\Api\Routing;
use Virtue\Api\Testing;

class ParseAcceptHeadersTest extends AppTestCase
{
    public function testParse()
    {
        $this->container->addDefinitions(
            [
                Routing\RouteRunner::class => function (Locator $kernel) {
                    return new Testing\RequestHandlerStub(
                        $kernel->get(ResponseFactory::class)->createResponse()
                    );
                },
            ]
        );

        $kernel = $this->container->build();

        $app = $kernel->get(App::class);
        $app->add(ParseAcceptHeaders::class);

        $request = $kernel->get(ServerRequest::class);

        $app->handle(
            $request->withHeader('Accept-Charset', 'ISO-8859-1,utf-8;q=0.7,*;q=0.7')
        );

        /** @var ServerRequest $request */
        $request = $kernel->get(Routing\RouteRunner::class)->last();

        $expected = [
            'Accept-Charset' => [
                [['ISO-8859-1', []], ['utf-8', ['q' => '0.7']], ['*', ['q' => '0.7']],],
            ]
        ];

        $this->assertEquals($expected, $request->getAttribute('parsed'));
    }
}
