<?php

namespace Vice\Testing;

use Vice\Middleware\MiddlewareStack;

class MiddlewareStackStub extends MiddlewareStack
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
