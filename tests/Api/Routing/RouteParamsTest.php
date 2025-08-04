<?php

namespace Virtue\Api\Routing;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

class RouteParamsTest extends TestCase
{
    public function testUrlDecode()
    {
        $value = 'https://some.url';
        $params = new RouteParams(['key' => urlencode($value)]);

        Assert::assertEquals($value, $params->get('key'), 'Getting of urldecoded parameter failed.');
        Assert::assertEquals(['key' => urlencode($value)], $params->asArray());
    }
}
