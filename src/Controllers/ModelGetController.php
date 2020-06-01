<?php

declare(strict_types=1);

namespace Vesp\Controllers;

use Psr\Http\Message\ResponseInterface;

abstract class ModelGetController extends ModelController
{
    public function put(): ResponseInterface
    {
        return $this->failure(null, 405);
    }

    public function patch(): ResponseInterface
    {
        return $this->failure(null, 405);
    }

    public function delete(): ResponseInterface
    {
        return $this->failure(null, 405);
    }
}
