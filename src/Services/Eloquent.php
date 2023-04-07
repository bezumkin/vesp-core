<?php

declare(strict_types=1);

namespace Vesp\Services;

use Illuminate\Database\Capsule\Manager;
use Illuminate\Events\Dispatcher;

class Eloquent extends Manager
{
    public function __construct(array $config = [])
    {
        parent::__construct();

        $this->addConnection(
            array_merge(
                [
                    'driver' => getenv('DB_DRIVER'),
                    'host' => getenv('DB_HOST'),
                    'port' => getenv('DB_PORT'),
                    'prefix' => getenv('DB_PREFIX'),
                    'database' => getenv('DB_DATABASE'),
                    'username' => getenv('DB_USERNAME'),
                    'password' => getenv('DB_PASSWORD'),
                    'charset' => getenv('DB_CHARSET'),
                    'collation' => getenv('DB_COLLATION'),
                    'foreign_key_constraints' => getenv('DB_FOREIGN_KEYS'),
                    'prefix_indexes' => getenv('DB_PREFIX_INDEXES'),
                ],
                $config
            )
        );
        $this->setEventDispatcher(new Dispatcher());
        $this->setAsGlobal();
        $this->bootEloquent();
    }
}
