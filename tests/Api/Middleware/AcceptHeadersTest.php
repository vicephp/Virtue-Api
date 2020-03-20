<?php

namespace Virtue\Api\Middleware;

use Psr\Container\ContainerInterface as Locator;
use Psr\Http\Message\ResponseFactoryInterface as ResponseFactory;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Virtue\Api\Routing;
use Virtue\Api\ServerRequest\AcceptHeadersResults;
use Virtue\Api\TestCase;
use Virtue\Api\Testing;

class AcceptHeadersTest extends TestCase
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
        $acceptHeaders = new AcceptHeaders(['Accept' => ['text/html']]);
        $runner = $kernel->get(Routing\RouteRunner::class);
        $acceptHeaders->process($request->withHeader('Accept', implode(', ', $accept)), $runner);

        $results = AcceptHeadersResults::ofRequest($runner->last());
        $this->assertEquals('text/html', $results->bestMatch('Accept'));
    }

    public function testParseAcceptCharset()
    {
        $kernel = $this->container->build();
        $request = $kernel->get(ServerRequest::class);
        $runner = $kernel->get(Routing\RouteRunner::class);
        $acceptHeaders = new AcceptHeaders(['Accept-Charset' => ['ISO-8859-1']]);
        $acceptHeaders->process(
            $request->withHeader('Accept-Charset', 'ISO-8859-1,utf-8;q=0.7,*;q=0.7'),
            $runner
        );

        $results = AcceptHeadersResults::ofRequest($runner->last());
        $this->assertEquals('ISO-8859-1', $results->bestMatch('Accept-Charset'));
    }

    public function testParseAcceptEncoding()
    {
        $kernel = $this->container->build();
        $request = $kernel->get(ServerRequest::class);
        $runner = $kernel->get(Routing\RouteRunner::class);
        $acceptHeaders = new AcceptHeaders(['Accept-Encoding' => ['gzip', 'deflate']]);
        $acceptHeaders->process(
            $request->withHeader('Accept-Encoding', 'gzip,deflate'),
            $runner
        );

        $results = AcceptHeadersResults::ofRequest($runner->last());
        $this->assertEquals('gzip', $results->bestMatch('Accept-Encoding'));
    }

    public function testParseAcceptLanguage()
    {
        $kernel = $this->container->build();
        $request = $kernel->get(ServerRequest::class);
        $runner = $kernel->get(Routing\RouteRunner::class);
        $acceptHeaders = new AcceptHeaders(['Accept-Language' => ['en-us', 'en']]);
        $acceptHeaders->process(
            $request->withHeader('Accept-Language', 'en-us,en;q=0.5'),
            $runner
        );

        $results = AcceptHeadersResults::ofRequest($runner->last());
        $this->assertEquals('en-us', $results->bestMatch('Accept-Language'));
    }
}
