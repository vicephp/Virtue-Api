<?php

namespace Virtue\Api\Testing;

use Psr\Http\Message\ResponseInterface as Response;

class ResponseEmitter extends \Slim\ResponseEmitter
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
