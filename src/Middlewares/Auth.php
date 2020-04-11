<?php

namespace Vesp\Middlewares;

use Firebase\JWT\JWT;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Throwable;
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
     * @return object|bool
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

        if ($token) {
            try {
                $decoded = JWT::decode($token, getenv('JWT_SECRET'), ['HS256', 'HS512', 'HS384']);
                $decoded->token = $token;

                return $decoded;
            } catch (Throwable $e) {
            }
        }

        return false;
    }
}
