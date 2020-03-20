<?php

namespace Virtue\Api\Middleware;

use Psr\Container\ContainerInterface as Locator;
use Psr\Http\Message\ResponseFactoryInterface as ResponseFactory;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Virtue\Api\App;
use Virtue\Api\AppTestCase;
use Virtue\Api\Routing;
use Virtue\Api\Testing;

class AcceptHeadersTest extends AppTestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->container->addDefinitions(
            [
                Routing\RouteRunner::class => function (Locator $kernel) {
                    return new Testing\RequestHandlerStub(
                        $kernel->get(ResponseFactory::class)->createResponse()
                    );
                },
            ]
        );
    }

    public function testParseAccept()
    {
        $kernel = $this->container->build();

        $app = $kernel->get(App::class);
        $app->add(AcceptHeaders::class);
        $request = $kernel->get(ServerRequest::class);

        $accept = array (
            'text/html',
            'application/xhtml+xml',
            'application/xml;q=0.9',
            'image/webp',
            'image/apng',
            '*/*;q=0.8',
            'application/signed-exchange;v=b3;q=0.9',
        );
        $app->handle(
            $request->withHeader('Accept', implode(', ',$accept))
        );

        /** @var ServerRequest $request */
        $request = $kernel->get(Routing\RouteRunner::class)->last();

        $expected = [
            'Accept' => [
                [
                    ['text/html', []],
                    ['application/xhtml+xml', []],
                    ['application/xml', ['q' => '0.9']],
                    ['image/webp', []],
                    ['image/apng', []],
                    ['*/*', ['q' => '0.8']],
                    ['application/signed-exchange', ['v' => 'b3', 'q' => '0.9']],
                ],
            ]
        ];

        $this->assertEquals($expected, $request->getAttribute('parsed'));
    }

    public function testParseAcceptCharset()
    {
        $kernel = $this->container->build();

        $app = $kernel->get(App::class);
        $app->add(AcceptHeaders::class);
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


    public function testParseAcceptEncoding()
    {
        $kernel = $this->container->build();

        $app = $kernel->get(App::class);
        $app->add(AcceptHeaders::class);
        $request = $kernel->get(ServerRequest::class);

        $app->handle(
            $request->withHeader('Accept-Encoding', 'gzip,deflate')
        );

        /** @var ServerRequest $request */
        $request = $kernel->get(Routing\RouteRunner::class)->last();

        $expected = [
            'Accept-Encoding' => [
                [['gzip', []], ['deflate', []],],
            ]
        ];

        $this->assertEquals($expected, $request->getAttribute('parsed'));
    }

    public function testParseAcceptLanguage()
    {
        $kernel = $this->container->build();

        $app = $kernel->get(App::class);
        $app->add(AcceptHeaders::class);
        $request = $kernel->get(ServerRequest::class);

        $app->handle(
            $request->withHeader('Accept-Language', 'en-us,en;q=0.5')
        );

        /** @var ServerRequest $request */
        $request = $kernel->get(Routing\RouteRunner::class)->last();

        $expected = [
            'Accept-Language' => [
                [['en-us', []], ['en', ['q' => '0.5']],],
            ]
        ];

        $this->assertEquals($expected, $request->getAttribute('parsed'));
    }
}
