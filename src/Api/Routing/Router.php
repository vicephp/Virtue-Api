<?php

namespace Virtue\Api\Routing;

interface Router
{
    public function route($httpMethod, $uri): array;
}
