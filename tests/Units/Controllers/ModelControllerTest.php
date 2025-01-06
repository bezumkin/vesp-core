<?php

namespace Vesp\CoreTests\Units\Controllers;

use Vesp\Models\User;
use Vesp\Models\UserRole;
use Vesp\CoreTests\Mock\CompositeModelController;
use Vesp\CoreTests\Mock\ModelController;
use Vesp\CoreTests\TestCase;

class ModelControllerTest extends TestCase
{
    protected const URI = '/api/users';

    public function testPutSuccess(): void
    {
        $data = ['username' => 'username', 'password' => 'password', 'role_id' => 1];
        $request = $this->createRequest('PUT', self::URI, $data);
        $response = $this->app->handle($request);
        $body = $response->getBody();

        self::assertEquals(200, $response->getStatusCode(), $body);
    }

    public function testPutFailure(): void
    {
        $data = ['username' => 'username', 'password' => 'password', 'role_id' => 1, 'test_failure' => true];
        $request = $this->createRequest('PUT', self::URI, $data);
        $response = $this->app->handle($request);
        $body = $response->getBody();

        self::assertEquals(422, $response->getStatusCode(), $body);
    }

    public function testPutFatalFailure(): void
    {
        $data = ['username' => 'username', 'password' => 'password'];
        $request = $this->createRequest('PUT', self::URI, $data);
        $response = $this->app->handle($request);
        $body = $response->getBody();

        self::assertEquals(500, $response->getStatusCode(), $body);
    }

    public function testGetList(): void
    {
        (new User(['username' => 'username', 'password' => 'password', 'role_id' => 1]))->save();

        $request = $this->createRequest('GET', self::URI, ['limit' => 10, 'sort' => 'id', 'dir' => 'asc']);
        $response = $this->app->handle($request);
        $body = $response->getBody();

        self::assertEquals(1, json_decode($body, false)->total, $body);
    }

    public function testGetListWithOffset(): void
    {
        (new User(['username' => 'username', 'password' => 'password', 'role_id' => 1]))->save();

        $request = $this->createRequest('GET', self::URI, ['offset' => 1]);
        $response = $this->app->handle($request);
        $body = $response->getBody();

        self::assertEquals([], json_decode($body, false)->rows, $body);
    }

    public function testGet(): void
    {
        (new User(['username' => 'username', 'password' => 'password', 'role_id' => 1]))->save();

        $request = $this->createRequest('GET', self::URI . '/1');
        $response = $this->app->handle($request);
        $body = $response->getBody();

        self::assertEquals('username', json_decode($body, false)->username, $body);
    }

    public function testNotFoundGet(): void
    {
        $data = ['id' => 2];
        $request = $this->createRequest('GET', self::URI, $data);
        $response = $this->app->handle($request);

        self::assertEquals(404, $response->getStatusCode(), $response->getBody());
    }

    public function testPatch(): void
    {
        (new User(['username' => 'username', 'password' => 'password', 'role_id' => 1]))->save();

        $request = $this->createRequest('PATCH', self::URI, ['id' => 1, 'username' => 'newusername']);
        $response = $this->app->handle($request);
        $body = $response->getBody();

        self::assertEquals('newusername', json_decode($body, false)->username, $body);
    }

    public function testPatchKeyNotFoundFailure(): void
    {
        $request = $this->createRequest('PATCH', self::URI);
        $response = $this->app->handle($request);
        $body = $response->getBody();

        self::assertEquals(422, $response->getStatusCode(), $body);
    }

    public function testPatchKeyWrongFailure(): void
    {
        $request = $this->createRequest('PATCH', self::URI, ['id' => 2]);
        $response = $this->app->handle($request);
        $body = $response->getBody();

        self::assertEquals(404, $response->getStatusCode(), $body);
    }

    public function testPatchBeforeSaveFailure(): void
    {
        (new User(['username' => 'username', 'password' => 'password', 'role_id' => 1]))->save();

        $request = $this->createRequest('PATCH', self::URI, ['id' => 1, 'test_failure' => true]);
        $response = $this->app->handle($request);
        $body = $response->getBody();

        self::assertEquals(422, $response->getStatusCode(), $body);
    }

    public function testPatchFatalFailure(): void
    {
        (new User(['username' => 'username', 'password' => 'password', 'role_id' => 1]))->save();

        $request = $this->createRequest('PATCH', self::URI, ['id' => 1, 'password' => null, 'role_id' => 3]);
        $response = $this->app->handle($request);
        $body = $response->getBody();

        self::assertEquals(500, $response->getStatusCode(), $body);
    }

    public function testDeleteKeyWrongFailure(): void
    {
        $request = $this->createRequest('DELETE', self::URI);
        $response = $this->app->handle($request);

        self::assertEquals(422, $response->getStatusCode(), $response->getBody());
    }

    public function testDeleteNotFoundFailure(): void
    {
        $request = $this->createRequest('DELETE', self::URI, ['id' => 1]);
        $response = $this->app->handle($request);

        self::assertEquals(404, $response->getStatusCode(), $response->getBody());
    }

    public function testDeleteBeforeDeleteFailure(): void
    {
        (new User(['username' => 'username', 'password' => 'password', 'role_id' => 1]))->save();

        $request = $this->createRequest('DELETE', self::URI, ['id' => 1, 'test_failure' => 1]);
        $response = $this->app->handle($request);

        self::assertEquals(422, $response->getStatusCode(), $response->getBody());
    }

    public function testDeleteSuccess(): void
    {
        (new User(['username' => 'username', 'password' => 'password', 'role_id' => 1]))->save();
        $request = $this->createRequest('DELETE', self::URI, ['id' => 1]);
        $response = $this->app->handle($request);

        self::assertEquals(200, $response->getStatusCode(), $response->getBody());
    }

    public function testCompositeModelSuccess(): void
    {
        (new User(['username' => 'username', 'password' => 'password', 'role_id' => 1]))->save();
        $request = $this->createRequest('GET', self::URI . '/composite', ['id' => 1, 'active' => true]);
        $response = $this->app->handle($request);
        $body = $response->getBody();

        self::assertEquals(200, $response->getStatusCode(), $body);
        self::assertNotNull(json_decode($body, false)->username, $body);
    }

    public function testCompositeModelNotFound(): void
    {
        $request = $this->createRequest('GET', self::URI . '/composite', ['id' => 1, 'active' => true]);
        $response = $this->app->handle($request);

        self::assertEquals(404, $response->getStatusCode(), $response->getBody());
    }

    public function testCompositeModelWrongKey(): void
    {
        $request = $this->createRequest('GET', self::URI . '/composite', ['active' => true]);
        $response = $this->app->handle($request);
        $body = $response->getBody();

        self::assertEquals(200, $response->getStatusCode(), $body);
        self::assertNotNull(json_decode($body, false)->rows, $body);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->any(self::URI, ModelController::class);
        $this->app->get(self::URI . '/{id:\d+}', ModelController::class);
        $this->app->get(self::URI . '/composite', CompositeModelController::class);

        (new UserRole(['title' => 'title', 'scope' => ['test']]))->save();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        User::query()->truncate();
        UserRole::query()->truncate();
    }
}
