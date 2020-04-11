<?php

namespace Vesp\Controllers\User;

use Psr\Http\Message\ResponseInterface;
use Vesp\Controllers\Controller;

class Profile extends Controller
{
    /**
     * @return ResponseInterface
     */
    public function get()
    {
        if ($this->user) {
            return $this->success(['user' => $this->user->toArray()]);
        }

        return $this->failure('Authentication required', 401);
    }
}
