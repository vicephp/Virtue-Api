<?php

namespace Virtue\Api\Routing;

class MethodNotAllowed extends Result
{
    public function getAllowedMethods(): array
    {
        return $this->results[1];
    }
}
