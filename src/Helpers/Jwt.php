<?php

namespace Vesp\Helpers;

use Firebase\JWT\JWT as FirebaseJWT;

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

        return FirebaseJWT::encode($data, getenv('JWT_SECRET'));
    }
}
