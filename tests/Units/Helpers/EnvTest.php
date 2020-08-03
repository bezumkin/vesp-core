<?php

namespace Vesp\CoreTests\Units\Helpers;

use Vesp\Helpers\Env;
use Vesp\CoreTests\TestCase;

class EnvTest extends TestCase
{
    public function testFailure(): void
    {
        $res = Env::loadFile('wrong_file');
        self::assertIsString($res);
    }

    public function testOk(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'test_');
        file_put_contents($tmp, 'VESP_CORE_VARIABLE_FOR_TEST=1');
        $res = Env::loadFile($tmp);
        self::assertNull($res);
        self::assertEquals('1', getenv('VESP_CORE_VARIABLE_FOR_TEST'));
        unlink($tmp);
    }
}
