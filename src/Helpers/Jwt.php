<?php

declare(strict_types=1);

namespace Vesp\Helpers;

use Firebase\JWT\JWT as FirebaseJWT;
use Firebase\JWT\Key;
use Throwable;

class Jwt
{
    public static function makeToken(int $id, array $add = []): string
    {
        $time = time();
        $data = [
            'id' => $id,
            'iat' => $time,
            'exp' => $time + getenv('JWT_EXPIRE'),
        ];

        return FirebaseJWT::encode(array_merge($data, $add), getenv('JWT_SECRET'), 'HS256');
    }

    public static function decodeToken(string $token): ?object
    {
        try {
            return FirebaseJWT::decode($token, new Key(getenv('JWT_SECRET'), 'HS256'));
        } catch (Throwable $e) {
            return null;
        }
    }
}
