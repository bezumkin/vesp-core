<?php

namespace Vesp\Tests\Units\Services;

use Vesp\Services\Eloquent;
use Vesp\Tests\TestCase;

class EloquentTest extends TestCase
{
    public function testConstruct()
    {
        $eloquent = new Eloquent();
        $config = $eloquent->getDatabaseManager()->getConfig();

        $this->assertArrayHasKey('driver', $config, print_r($config, true));
    }
}
