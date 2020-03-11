<?php

namespace Virtue\Api\Testing;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\ResponseEmitter;

class ResponseEmitterStub extends ResponseEmitter
{
    /** @var Response */
    private $response;

    public function emit(Response $response): void
    {
        $this->response = $response;
    }

    public function last(): Response
    {
        return $this->response;
    }
}
