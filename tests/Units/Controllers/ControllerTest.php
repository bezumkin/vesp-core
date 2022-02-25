<?php

namespace Vesp\CoreTests\Units\Controllers;

use Vesp\Helpers\Jwt;
use Vesp\Middlewares\Auth;
use Vesp\Models\User;
use Vesp\Models\UserRole;
use Vesp\Services\Eloquent;
use Vesp\CoreTests\Mock\ScopedModelController;
use Vesp\CoreTests\TestCase;

class ControllerTest extends TestCase
{
    protected const URI = '/api/users';

    public function testNoScopeFailure(): void
    {
        $request = $this->createRequest('GET', self::URI, ['id' => 1]);
        $response = $this->app->handle($request);

        self::assertEquals(401, $response->getStatusCode(), $response->getBody());
    }

    public function testWrongScopeFailure(): void
    {
        (new User(['username' => 'username', 'password' => 'password', 'role_id' => 2]))->save();

        $request = $this->createRequest('DELETE', self::URI, ['id' => 1])
            ->withHeader('Authorization', 'Bearer ' . Jwt::makeToken(1));
        $response = $this->app->handle($request);

        self::assertEquals(403, $response->getStatusCode(), $response->getBody());
    }

    public function testWrongMethodFailure(): void
    {
        (new User(['username' => 'username', 'password' => 'password', 'role_id' => 1]))->save();
        $request = $this->createRequest('POST', self::URI)
            ->withHeader('Authorization', 'Bearer ' . Jwt::makeToken(1));
        $response = $this->app->handle($request);

        self::assertEquals(405, $response->getStatusCode(), $response->getBody());
    }

    public function testFatalFailure(): void
    {
        (new User(['username' => 'username', 'password' => 'password', 'role_id' => 1]))->save();
        $request = $this->createRequest('PATCH', self::URI, ['id' => 1, 'test_exception' => true])
            ->withHeader('Authorization', 'Bearer ' . Jwt::makeToken(1));
        $response = $this->app->handle($request);

        self::assertEquals(500, $response->getStatusCode(), $response->getBody());
    }

    public function testOptions(): void
    {
        $request = $this->createRequest('OPTIONS', self::URI);
        $response = $this->app->handle($request);

        self::assertEquals(200, $response->getStatusCode(), $response->getBody());
        self::assertIsString($response->getHeaderLine('Access-Control-Allow-Methods'), $response->getBody());
    }

    public function testProperties(): void
    {
        $controller = new ScopedModelController(new Eloquent());

        $controller->setProperty('test', 'value');
        self::assertEquals('value', $controller->getProperty('test'));

        $controller->unsetProperty('test');
        self::assertNull($controller->getProperty('test'));
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->any(self::URI, ScopedModelController::class)
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
