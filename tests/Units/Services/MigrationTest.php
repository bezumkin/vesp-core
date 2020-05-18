<?php

namespace Vesp\Tests\Units\Services;

use Illuminate\Database\Schema\Builder;
use Vesp\Services\Migration;
use Vesp\Tests\TestCase;

class MigrationTest extends TestCase
{
    public function testInit()
    {
        $migration = new Migration('test', '1.0');
        $migration->init();
        $this->assertInstanceOf(Builder::class, $migration->schema);
    }
}
