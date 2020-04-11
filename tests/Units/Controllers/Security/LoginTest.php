<?php

namespace Vesp\Tests\Units\Controllers;

use Vesp\Controllers\Security\Login;
use Vesp\Models\User;
use Vesp\Models\UserRole;
use Vesp\Tests\TestCase;

class LoginTest extends TestCase
{
    protected const URI = '/api/security/login';

    public function testPostSuccess()
    {
        $model = new User(['username' => 'username', 'password' => 'password', 'role_id' => 1]);
        $model->save();

        $data = ['username' => 'username', 'password' => 'password'];
        $request = $this->createRequest('POST', self::URI, $data);
        $response = $this->app->handle($request);
        $body = $response->getBody();

        $this->assertEquals(200, $response->getStatusCode(), $body);
        $this->assertIsString(json_decode($body)->token, $body);
    }

    public function testPostFailure()
    {
        $model = new User(['username' => 'username', 'password' => 'password', 'role_id' => 1]);
        $model->save();

        $data = ['username' => 'username', 'password' => 'wrongpassword'];
        $request = $this->createRequest('POST', self::URI, $data);
        $response = $this->app->handle($request);
        $body = $response->getBody();

        $this->assertEquals(422, $response->getStatusCode(), $body);
    }

    public function testPostNotActive()
    {
        $model = new User(['username' => 'username', 'password' => 'password', 'role_id' => 1, 'active' => false]);
        $model->save();

        $data = ['username' => 'username', 'password' => 'password'];
        $request = $this->createRequest('POST', self::URI, $data);
        $response = $this->app->handle($request);
        $body = $response->getBody();

        $this->assertEquals(403, $response->getStatusCode(), $body);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->any(self::URI, [Login::class, 'process']);

        (new UserRole(['title' => 'title', 'scope' => ['test']]))->save();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        User::query()->truncate();
        UserRole::query()->truncate();
    }
}
