<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';

putenv('CORS=1');
putenv('DB_DRIVER=sqlite');
putenv('DB_DATABASE=' . __DIR__ . '/test.sqlite3');
putenv('DB_FOREIGN_KEYS=1');
putenv('CONTROLLERS_STAT=1');
putenv('CONTROLLERS_DEBUG=1');
putenv('JWT_SECRET=' . uniqid('', true));
putenv('JWT_EXPIRE=1800');
putenv('PHPUNIT=1');
putenv('TMP_DIR=' . rtrim(dirname(__DIR__), '/') . '/tmp');
putenv('UPLOAD_DIR=' . rtrim(dirname(__DIR__), '/') . '/tmp/upload');
