<?php

declare(strict_types=1);

namespace Vesp\Services;

use Clockwork\Storage\FileStorage;

class Clockwork extends \Clockwork\Clockwork
{
    public function __construct()
    {
        parent::__construct();

        $dir = rtrim(getenv('TMP_DIR') ?: sys_get_temp_dir(), '/') . '/clockwork';
        $storage = new FileStorage($dir, 0700, getenv('CLOCKWORK_CACHE_TIME') ?: 60);
        $this->setStorage($storage);
    }
}
