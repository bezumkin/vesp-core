<?php

namespace Vesp\Tests\Units\Helpers;

use Vesp\Helpers\Env;
use Vesp\Tests\TestCase;

class EnvTest extends TestCase
{
    public function testFailure()
    {
        $res = Env::loadFile('wrong_file');
        $this->assertIsString($res);
    }

    public function testOk()
    {
        $tmp = tempnam(sys_get_temp_dir(), 'test_');
        file_put_contents($tmp, 'VESP_CORE_VARIABLE_FOR_TEST=1');
        $res = Env::loadFile($tmp);
        $this->assertTrue($res);
        $this->assertEquals('1', getenv('VESP_CORE_VARIABLE_FOR_TEST'));
        unlink($tmp);
    }
}
