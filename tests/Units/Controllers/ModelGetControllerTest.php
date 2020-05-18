<?php

namespace Vesp\Tests\Units\Controllers;

use Vesp\Tests\Mock\ModelGetController;
use Vesp\Tests\TestCase;

class ModelGetControllerTest extends TestCase
{
    protected const URI = '/api/users';

    public function testPut()
    {
        $request = $this->createRequest('PUT', self::URI);
        $response = $this->app->handle($request);

        $this->assertEquals(405, $response->getStatusCode(), $response->getBody());
    }

    public function testPatch()
    {
        $request = $this->createRequest('PATCH', self::URI);
        $response = $this->app->handle($request);

        $this->assertEquals(405, $response->getStatusCode(), $response->getBody());
    }

    public function testDelete()
    {
        $request = $this->createRequest('DELETE', self::URI);
        $response = $this->app->handle($request);

        $this->assertEquals(405, $response->getStatusCode(), $response->getBody());
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->any(self::URI, [ModelGetController::class, 'process']);
    }
}
