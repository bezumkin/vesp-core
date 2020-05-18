<?php

namespace Vesp\Tests\Units\Controllers;

use Vesp\Helpers\Jwt;
use Vesp\Middlewares\Auth;
use Vesp\Models\User;
use Vesp\Models\UserRole;
use Vesp\Services\Eloquent;
use Vesp\Tests\Mock\ScopedModelController;
use Vesp\Tests\TestCase;

class ControllerTest extends TestCase
{
    protected const URI = '/api/users';

    public function testNoScopeFailure()
    {
        $request = $this->createRequest('GET', self::URI, ['id' => 1]);
        $response = $this->app->handle($request);

        $this->assertEquals(401, $response->getStatusCode(), $response->getBody());
    }

    public function testWrongScopeFailure()
    {
        (new User(['username' => 'username', 'password' => 'password', 'role_id' => 2]))->save();

        $request = $this->createRequest('DELETE', self::URI, ['id' => 1])
            ->withHeader('Authorization', 'Bearer ' . Jwt::makeToken(1));
        $response = $this->app->handle($request);

        $this->assertEquals(403, $response->getStatusCode(), $response->getBody());
    }

    public function testWrongMethodFailure()
    {
        (new User(['username' => 'username', 'password' => 'password', 'role_id' => 1]))->save();
        $request = $this->createRequest('POST', self::URI)
            ->withHeader('Authorization', 'Bearer ' . Jwt::makeToken(1));
        $response = $this->app->handle($request);

        $this->assertEquals(404, $response->getStatusCode(), $response->getBody());
    }

    public function testFatalFailure()
    {
        (new User(['username' => 'username', 'password' => 'password', 'role_id' => 1]))->save();
        $request = $this->createRequest('PATCH', self::URI, ['id' => 1, 'test_exception' => true])
            ->withHeader('Authorization', 'Bearer ' . Jwt::makeToken(1));
        $response = $this->app->handle($request);

        $this->assertEquals(500, $response->getStatusCode(), $response->getBody());
    }

    public function testOptions()
    {
        $request = $this->createRequest('OPTIONS', self::URI);
        $response = $this->app->handle($request);

        $this->assertEquals(200, $response->getStatusCode(), $response->getBody());
        $this->assertIsString($response->getHeaderLine('Access-Control-Allow-Methods'), $response->getBody());
    }

    public function testProperties()
    {
        $controller = new ScopedModelController(new Eloquent());

        $controller->setProperty('test', 'value');
        $this->assertEquals('value', $controller->getProperty('test'));

        $controller->unsetProperty('test');
        $this->assertNull($controller->getProperty('test'));
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->any(self::URI, [ScopedModelController::class, 'process'])
            ->add(Auth::class);

        (new UserRole(['title' => 'admin', 'scope' => ['users']]))->save();
        (new UserRole(['title' => 'user', 'scope' => ['users/get']]))->save();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        User::query()->truncate();
        UserRole::query()->truncate();
    }
}
