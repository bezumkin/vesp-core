<?php

namespace Vesp\Middlewares;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Vesp\Helpers\Jwt;
use Vesp\Models\User;

class Auth
{
    /** @var Request $request */
    protected $request;

    /**
     * @param Request $request
     * @param RequestHandler $handler
     * @return ResponseInterface
     */
    public function __invoke(Request $request, RequestHandler $handler)
    {
        if ($token = $this->getToken($request)) {
            /** @var User $user */
            if ($user = User::query()->where('active', true)->find($token->id)) {
                $request = $request->withAttribute('user', $user);
            }
        }

        return $handler->handle($request);
    }

    /**
     * @param Request $request
     * @return false|object
     */
    protected function getToken($request)
    {
        $pcre = '#Bearer\s+(.*)$#i';
        $token = null;

        $header = $request->getHeaderLine('Authorization');
        if ($header && preg_match($pcre, $header, $matches)) {
            $token = $matches[1];
        } else {
            $cookies = $request->getCookieParams();
            if (isset($cookies['auth._token.local'])) {
                $token = preg_match($pcre, $cookies['auth._token.local'], $matches)
                    ? $matches[1]
                    : $cookies['auth._token.local'];
            }
        }

        if ($token && $decoded = JWT::decodeToken($token)) {
            $decoded->token = $token;

            return $decoded;
        }

        return false;
    }
}
