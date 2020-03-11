<?php

namespace Virtue\Api\Testing;

use Mockery\Adapter\Phpunit\MockeryTestCase;
use Psr\Http\Message\ResponseInterface as Response;

class ResponseEmitterStubTest extends MockeryTestCase
{
    public function testEmit()
    {
        $response = \Mockery::mock(Response::class);
        $stub = new ResponseEmitterStub();
        $stub->emit($response);

        $this->assertSame($response, $stub->last());
    }
}
