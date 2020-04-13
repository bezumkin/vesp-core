<?php

namespace Vesp\Helpers;

use Firebase\JWT\JWT as FirebaseJWT;
use Throwable;

class Jwt
{
    /**
     * @param $id
     * @param array $add
     * @return string
     */
    public static function makeToken($id, $add = [])
    {
        $time = time();
        $data = [
            'id' => $id,
            'iat' => $time,
            'exp' => $time + getenv('JWT_EXPIRE'),
        ];
        $data += $add;

        return FirebaseJWT::encode($data, getenv('JWT_SECRET'), 'HS256');
    }

    /**
     * @param $token
     * @return false|object
     */
    public static function decodeToken($token)
    {
        try {
            return FirebaseJWT::decode($token, getenv('JWT_SECRET'), ['HS256']);
        } catch (Throwable $e) {
            return false;
        }
    }
}
