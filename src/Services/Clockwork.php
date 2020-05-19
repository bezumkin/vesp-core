<?php

namespace Vesp\Services;

use Clockwork\Authentication\NullAuthenticator;
use Clockwork\DataSource\EloquentDataSource;
use Clockwork\Storage\FileStorage;

class Clockwork extends \Clockwork\Clockwork
{
    public function __construct()
    {
        parent::__construct();
        $this->initStorage();
        $this->initService();
    }

    public function initStorage(): void
    {
        $dir = rtrim(getenv('CACHE_DIR') ?: sys_get_temp_dir(), '/') . '/clockwork';
        $this->setStorage(new FileStorage($dir));
    }

    public function initService(): void
    {
        $this->setAuthenticator(new NullAuthenticator());

        $eloquent = new Eloquent();
        $source = new EloquentDataSource($eloquent->getDatabaseManager(), $eloquent->getEventDispatcher());
        $source->listenToEvents();
        $this->addDataSource($source);
    }
}
