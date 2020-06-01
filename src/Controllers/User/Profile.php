<?php

declare(strict_types=1);

namespace Vesp\Controllers\User;

use Psr\Http\Message\ResponseInterface;
use Vesp\Controllers\Controller;

class Profile extends Controller
{
    public function get(): ResponseInterface
    {
        if ($this->user) {
            $data = $this->user->toArray();
            $data += ['scope' => $this->user->role->scope];

            return $this->success(['user' => $data]);
        }

        return $this->failure('Authentication required', 401);
    }

    public function patch(): ResponseInterface
    {
        if ($password = trim($this->getProperty('password'))) {
            $this->user->password = $password;
        }
        $this->user->save();

        return $this->get();
    }
}
