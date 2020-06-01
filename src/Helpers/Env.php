<?php

declare(strict_types=1);

namespace Vesp\Helpers;

use Symfony\Component\Dotenv\Dotenv;
use Throwable;

class Env
{
    /**
     * @param string $file
     * @return string
     */
    public static function loadFile(string $file)
    {
        try {
            $dotenv = new Dotenv(true);
            $dotenv->loadEnv($file);

            return true;
        } catch (Throwable $e) {
            return $e->getMessage();
        }
    }
}
