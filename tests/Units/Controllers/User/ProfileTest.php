<?php

namespace Vesp\CoreTests\Units\Controllers\User;

use Vesp\Controllers\User\Profile;
use Vesp\Helpers\Jwt;
use Vesp\Middlewares\Auth;
use Vesp\Models\User;
use Vesp\Models\UserRole;
use Vesp\CoreTests\TestCase;

class ProfileTest extends TestCase
{
    protected const URI = '/api/user/profile';

    public function testGetSuccess(): void
    {
        $model = new User(['username' => 'username', 'password' => 'password', 'role_id' => 1]);
        $model->save();

        $request = $this->createRequest('GET', self::URI)
            ->withHeader('Authorization', 'Bearer ' . Jwt::makeToken($model->id));
        $response = $this->app->handle($request);
        $body = $response->getBody();

        self::assertEquals(200, $response->getStatusCode(), $body);
        self::assertArrayHasKey('user', json_decode($body, true), $body);
    }

    public function testGetFailure(): void
    {
        $request = $this->createRequest('GET', self::URI)
            ->withCookieParams(['auth._token.local' => 'Bearer wrongtoken']);
        $response = $this->app->handle($request);
        $body = $response->getBody();

        self::assertEquals(401, $response->getStatusCode(), $body);
    }

    public function testPatchSuccess(): void
    {
        $model = new User(['username' => 'username', 'password' => 'password', 'role_id' => 1]);
        $model->save();

        $request = $this->createRequest('PATCH', self::URI, ['password' => 'newpassword'])
            ->withHeader('Authorization', 'Bearer ' . Jwt::makeToken($model->id));
        $response = $this->app->handle($request);
        $body = $response->getBody();

        self::assertEquals(200, $response->getStatusCode(), $body);
        self::assertArrayHasKey('user', json_decode($body, true), $body);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->any(self::URI, Profile::class)
            ->add(Auth::class);
        (new UserRole(['title' => 'title', 'scope' => ['test']]))->save();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        User::query()->truncate();
        UserRole::query()->truncate();
    }
}
