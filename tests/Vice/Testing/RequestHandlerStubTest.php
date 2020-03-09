<?php

namespace Vice\Testing;

use Mockery\Adapter\Phpunit\MockeryTestCase;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;

class RequestHandlerStubTest extends MockeryTestCase
{
    public function testHandle()
    {
        $response = \Mockery::mock(Response::class);
        $request = \Mockery::mock(ServerRequest::class);
        $stub = new RequestHandlerStub($response);

        $this->assertSame($response, $stub->handle($request));
        $this->assertSame($request, $stub->last());
    }
}
