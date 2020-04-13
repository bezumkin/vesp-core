<?php

namespace Vesp\Tests\Units;

use Vesp\Models\User;
use Vesp\Models\UserRole;
use Vesp\Tests\Mock\ModelController;
use Vesp\Tests\TestCase;

class ModelControllerTest extends TestCase
{
    protected const URI = '/api/users';

    public function testPutSuccess()
    {
        $data = ['username' => 'username', 'password' => 'password', 'role_id' => 1];
        $request = $this->createRequest('PUT', self::URI, $data);
        $response = $this->app->handle($request);
        $body = $response->getBody();

        $this->assertEquals(200, $response->getStatusCode(), $body);
    }

    public function testPutFailure()
    {
        $data = ['username' => 'username', 'password' => 'password', 'role_id' => 1, 'test_failure' => true];
        $request = $this->createRequest('PUT', self::URI, $data);
        $response = $this->app->handle($request);
        $body = $response->getBody();

        $this->assertEquals(422, $response->getStatusCode(), $body);
    }

    public function testPutFatalFailure()
    {
        $data = ['username' => 'username', 'password' => 'password'];
        $request = $this->createRequest('PUT', self::URI, $data);
        $response = $this->app->handle($request);
        $body = $response->getBody();

        $this->assertEquals(500, $response->getStatusCode(), $body);
    }

    public function testGetList()
    {
        (new User(['username' => 'username', 'password' => 'password', 'role_id' => 1]))->save();

        $request = $this->createRequest('GET', self::URI, ['limit' => 10, 'sort' => 'id', 'dir' => 'asc']);
        $response = $this->app->handle($request);
        $body = $response->getBody();

        $this->assertEquals(1, json_decode($body)->total, $body);
    }

    public function testGet()
    {
        (new User(['username' => 'username', 'password' => 'password', 'role_id' => 1]))->save();

        $request = $this->createRequest('GET', self::URI . '/1');
        $response = $this->app->handle($request);
        $body = $response->getBody();

        $this->assertEquals('username', json_decode($body)->username, $body);
    }

    public function testNotFoundGet()
    {
        $data = ['id' => 2];
        $request = $this->createRequest('GET', self::URI, $data);
        $response = $this->app->handle($request);

        $this->assertEquals(404, $response->getStatusCode(), $response->getBody());
    }

    public function testPatch()
    {
        (new User(['username' => 'username', 'password' => 'password', 'role_id' => 1]))->save();

        $request = $this->createRequest('PATCH', self::URI, ['id' => 1, 'username' => 'newusername']);
        $response = $this->app->handle($request);
        $body = $response->getBody();

        $this->assertEquals('newusername', json_decode($body)->username, $body);
    }

    public function testPatchKeyNotFoundFailure()
    {
        $request = $this->createRequest('PATCH', self::URI);
        $response = $this->app->handle($request);
        $body = $response->getBody();

        $this->assertEquals(422, $response->getStatusCode(), $body);
    }

    public function testPatchKeyWrongFailure()
    {
        $request = $this->createRequest('PATCH', self::URI, ['id' => 2]);
        $response = $this->app->handle($request);
        $body = $response->getBody();

        $this->assertEquals(404, $response->getStatusCode(), $body);
    }

    public function testPatchBeforeSaveFailure()
    {
        (new User(['username' => 'username', 'password' => 'password', 'role_id' => 1]))->save();

        $request = $this->createRequest('PATCH', self::URI, ['id' => 1, 'test_failure' => true]);
        $response = $this->app->handle($request);
        $body = $response->getBody();

        $this->assertEquals(422, $response->getStatusCode(), $body);
    }

    public function testPatchFatalFailure()
    {
        (new User(['username' => 'username', 'password' => 'password', 'role_id' => 1]))->save();

        $request = $this->createRequest('PATCH', self::URI, ['id' => 1, 'password' => null, 'role_id' => 3]);
        $response = $this->app->handle($request);
        $body = $response->getBody();

        $this->assertEquals(500, $response->getStatusCode(), $body);
    }

    public function testDeleteKeyWrongFailure()
    {
        $request = $this->createRequest('DELETE', self::URI);
        $response = $this->app->handle($request);

        $this->assertEquals(422, $response->getStatusCode(), $response->getBody());
    }

    public function testDeleteNotFoundFailure()
    {
        $request = $this->createRequest('DELETE', self::URI, ['id' => 1]);
        $response = $this->app->handle($request);

        $this->assertEquals(404, $response->getStatusCode(), $response->getBody());
    }

    public function testDeleteBeforeDeleteFailure()
    {
        (new User(['username' => 'username', 'password' => 'password', 'role_id' => 1]))->save();

        $request = $this->createRequest('DELETE', self::URI, ['id' => 1, 'test_failure' => 1]);
        $response = $this->app->handle($request);

        $this->assertEquals(422, $response->getStatusCode(), $response->getBody());
    }

    public function testDeleteSuccess()
    {
        (new User(['username' => 'username', 'password' => 'password', 'role_id' => 1]))->save();
        $request = $this->createRequest('DELETE', self::URI, ['id' => 1]);
        $response = $this->app->handle($request);

        $this->assertEquals(200, $response->getStatusCode(), $response->getBody());
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->any(self::URI, [ModelController::class, 'process']);
        $this->app->get(self::URI . '/{id}', [ModelController::class, 'process']);

        (new UserRole(['title' => 'title', 'scope' => ['test']]))->save();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        User::query()->truncate();
        UserRole::query()->truncate();
    }
}
