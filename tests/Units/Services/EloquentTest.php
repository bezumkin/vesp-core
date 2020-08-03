<?php

namespace Vesp\CoreTests\Units\Services;

use Vesp\Services\Eloquent;
use Vesp\CoreTests\TestCase;

class EloquentTest extends TestCase
{
    public function testConstruct(): void
    {
        $eloquent = new Eloquent();
        $config = $eloquent->getDatabaseManager()->getConfig();

        self::assertArrayHasKey('driver', $config, print_r($config, true));
    }
}
