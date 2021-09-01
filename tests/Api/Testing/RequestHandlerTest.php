<?php

namespace Virtue\Api\Testing;

use PHPUnit\Framework\Assert;
use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class RequestHandlerTest extends TestCase
{
    public function testHandle()
    {
        $response = new Response();
        $request = new ServerRequest('POST', '/');
        $handler = new RequestHandler([$response]);

        $this->assertSame($response, $handler->handle($request));
        $this->assertSame($request, $handler->lastRequest());
    }

    public function testEmptyQueueThrowsException()
    {
        $this->expectException(\OutOfBoundsException::class);
        $handler = new RequestHandler();
        $handler->handle(new ServerRequest('POST', '/'));
    }

    public function testAppendThrowsTypeError()
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Expected a ResponseInterface, Throwable or callable. Found array');
        $handler = new RequestHandler();
        $handler->append([]);
    }

    public function testCallableAsHandler()
    {
        $handler = new RequestHandler();
        $handler->append(function (ServerRequest $request) {
            Assert::assertEquals('/', (string) $request->getUri());

            return new Response();
        });

        $handler->handle(new ServerRequest('POST', '/'));
        Assert::assertCount(0, $handler);
    }

    public function testThrowableIsThrown()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('<message>');
        $handler = new RequestHandler();
        $handler->append(new \Exception('<message>'));
        $handler->handle(new ServerRequest('POST', '/'));
    }

    public function testCount()
    {
        $handler = new RequestHandler();
        $handler->append(new Response());
        $handler->append(new Response());
        Assert::assertCount(2, $handler);
    }

    public function testReset()
    {
        $handler = new RequestHandler();
        $handler->append(new Response());
        $handler->reset();
        Assert::assertCount(0, $handler);
    }
}
