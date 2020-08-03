<?php

namespace Vesp\CoreTests\Units\Controllers\Security;

use Vesp\Controllers\Security\Login;
use Vesp\Models\User;
use Vesp\Models\UserRole;
use Vesp\CoreTests\TestCase;

class LoginTest extends TestCase
{
    protected const URI = '/api/security/login';

    public function testPostSuccess(): void
    {
        $model = new User(['username' => 'username', 'password' => 'password', 'role_id' => 1]);
        $model->save();

        $data = ['username' => 'username', 'password' => 'password'];
        $request = $this->createRequest('POST', self::URI, $data);
        $response = $this->app->handle($request);
        $body = $response->getBody();

        self::assertEquals(200, $response->getStatusCode(), $body);
        self::assertIsString(json_decode($body, false)->token, $body);
    }

    public function testPostFailure(): void
    {
        $model = new User(['username' => 'username', 'password' => 'password', 'role_id' => 1]);
        $model->save();

        $data = ['username' => 'username', 'password' => 'wrongpassword'];
        $request = $this->createRequest('POST', self::URI, $data);
        $response = $this->app->handle($request);
        $body = $response->getBody();

        self::assertEquals(422, $response->getStatusCode(), $body);
    }

    public function testPostNotActive(): void
    {
        $model = new User(['username' => 'username', 'password' => 'password', 'role_id' => 1, 'active' => false]);
        $model->save();

        $data = ['username' => 'username', 'password' => 'password'];
        $request = $this->createRequest('POST', self::URI, $data);
        $response = $this->app->handle($request);
        $body = $response->getBody();

        self::assertEquals(403, $response->getStatusCode(), $body);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->any(self::URI, Login::class);

        (new UserRole(['title' => 'title', 'scope' => ['test']]))->save();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        User::query()->truncate();
        UserRole::query()->truncate();
    }
}
