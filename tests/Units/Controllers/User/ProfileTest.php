<?php

namespace Vesp\Tests\Units;

use Vesp\Controllers\User\Profile;
use Vesp\Helpers\Jwt;
use Vesp\Middlewares\Auth;
use Vesp\Models\User;
use Vesp\Models\UserRole;
use Vesp\Tests\TestCase;

class ProfileTest extends TestCase
{
    protected const URI = '/api/user/profile';

    public function testGetSuccess()
    {
        $model = new User(['username' => 'username', 'password' => 'password', 'role_id' => 1]);
        $model->save();

        $request = $this->createRequest('GET', self::URI)
            ->withHeader('Authorization', 'Bearer ' . Jwt::makeToken(1));
        $response = $this->app->handle($request);
        $body = $response->getBody();

        $this->assertEquals(200, $response->getStatusCode(), $body);
        $this->assertArrayHasKey('user', json_decode($body, true), $body);
    }

    public function testGetFailure()
    {
        $request = $this->createRequest('GET', self::URI)
            ->withCookieParams(['auth._token.local' => 'Bearer wrongtoken']);
        $response = $this->app->handle($request);
        $body = $response->getBody();

        $this->assertEquals(401, $response->getStatusCode(), $body);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->any(self::URI, [Profile::class, 'process'])
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
