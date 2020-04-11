<?php

namespace Vesp\Controllers;

use Psr\Http\Message\ResponseInterface;

abstract class ModelGetController extends ModelController
{
    /**
     * @return ResponseInterface
     */
    public function put()
    {
        return $this->failure(null, 405);
    }

    /**
     * @return ResponseInterface
     */
    public function patch()
    {
        return $this->failure(null, 405);
    }

    /**
     * @return ResponseInterface
     */
    public function delete()
    {
        return $this->failure(null, 405);
    }
}
