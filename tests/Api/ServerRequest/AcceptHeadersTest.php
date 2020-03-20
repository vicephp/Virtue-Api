<?php

namespace Virtue\Api\ServerRequest;

use Psr\Container\ContainerInterface as Locator;
use Psr\Http\Message\ResponseFactoryInterface as ResponseFactory;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Virtue\Api\App;
use Virtue\Api\Middleware;
use Virtue\Api\Routing;
use Virtue\Api\TestCase;
use Virtue\Api\Testing\RequestHandlerStub;

class AcceptHeadersTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->container->addDefinitions(
            [
                Routing\RouteRunner::class => function (Locator $kernel) {
                    return new RequestHandlerStub(
                        $kernel->get(ResponseFactory::class)->createResponse()
                    );
                },
            ]
        );
    }

    public function testAccept()
    {
        $kernel = $this->container->build();
        /** @var App $app */
        $app = $kernel->get(App::class);
        $app->add(Middleware\AcceptHeaders::class);
        $request = $kernel->get(ServerRequest::class);
        $accept = array (
            'text/html',
            'application/xhtml+xml',
            'application/xml;q=0.9',
            '*/*;q=0.8',
            'application/signed-exchange;v=b3;q=0.9',
        );
        $app->handle(
            $request->withHeader('Accept', implode(', ', $accept))
        );
        /** @var ServerRequest $request */
        $request = $kernel->get(Routing\RouteRunner::class)->last();

        $headers = AcceptHeadersResults::ofRequest($request);
        $this->assertEquals('text/html', $headers->bestMatch('Accept', ['text/html', 'application/xml']));
        $this->assertEquals('text/html', $headers->bestMatch('Accept', ['text/html', 'application/xhtml+xml']));
        $this->assertEquals('klaatu/barada', $headers->bestMatch('Accept', ['klaatu/barada']));
    }

    public function testAcceptCharset()
    {
        $kernel = $this->container->build();
        /** @var App $app */
        $app = $kernel->get(App::class);
        $app->add(Middleware\AcceptHeaders::class);
        $request = $kernel->get(ServerRequest::class);
        $app->handle(
            $request->withHeader('Accept-Charset', 'ISO-8859-1,utf-8;q=0.7,*;q=0.7')
        );
        /** @var ServerRequest $request */
        $request = $kernel->get(Routing\RouteRunner::class)->last();
        $headers = AcceptHeadersResults::ofRequest($request);

        $this->assertEquals('ISO-8859-1', $headers->bestMatch('Accept-Encoding', ['ISO-8859-1', 'utf-8']));
        $this->assertEquals('iso-8859-1', $headers->bestMatch('Accept-Encoding', ['iso-8859-1', 'utf-8']));
        $this->assertEquals('utf-8', $headers->bestMatch('Accept-Encoding', ['utf-8']));
        $this->assertEquals('klaatu-barada-nikto', $headers->bestMatch('Accept-Encoding', ['klaatu-barada-nikto']));
    }

    public function testAcceptLanguage()
    {
        $kernel = $this->container->build();
        /** @var App $app */
        $app = $kernel->get(App::class);
        $app->add(Middleware\AcceptHeaders::class);
        $request = $kernel->get(ServerRequest::class);
        $app->handle(
            $request->withHeader('Accept-Language', 'en-us,en;q=0.5')
        );
        /** @var ServerRequest $request */
        $request = $kernel->get(Routing\RouteRunner::class)->last();

        $headers = AcceptHeadersResults::ofRequest($request);
        $this->assertEquals('en-us', $headers->bestMatch('Accept-Language', ['en-us', 'en']));
        $this->assertEquals('en', $headers->bestMatch('Accept-Language', ['en']));
    }
}
