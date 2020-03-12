<?php

namespace Virtue\Api\Testing;

use Virtue\Api\Middleware\MiddlewareContainer;

class MiddlewareContainerStub extends MiddlewareContainer
{
    public function contains(string $className): int
    {
        return array_sum(
            array_map(function ($item) use ($className) {
                return (int)($item instanceof $className);
            }, $this->stack)
        );
    }
}
