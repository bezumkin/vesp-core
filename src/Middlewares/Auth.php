<?php

declare(strict_types=1);

namespace Vesp\Middlewares;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Vesp\Helpers\Jwt;
use Vesp\Models\User;
use Vesp\Services\Eloquent;

class Auth
{
    protected Eloquent $eloquent;
    protected string $model = User::class;

    public function __construct(Eloquent $eloquent)
    {
        $this->eloquent = $eloquent;
    }

    public function __invoke(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($token = $this->getToken($request)) {
            /** @var User|null $user */
            $user = (new $this->model())->newQuery()->where('active', true)->find($token->id);
            if ($user) {
                $request = $request->withAttribute('user', $user);
            }
        }

        return $handler->handle($request);
    }

    protected function getToken(ServerRequestInterface $request): ?object
    {
        $pcre = '#Bearer\s+(.*)$#i';
        $token = null;

        $header = $request->getHeaderLine('Authorization');
        $query = $request->getQueryParams();
        if ($header && preg_match($pcre, $header, $matches)) {
            $token = $matches[1];
        } elseif (!empty($query['token'])) {
            $token = $query['token'];
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

        return null;
    }
}
