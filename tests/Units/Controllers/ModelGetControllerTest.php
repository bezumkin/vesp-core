<?php

namespace Vesp\CoreTests\Units\Controllers;

use Vesp\CoreTests\Mock\ModelGetController;
use Vesp\CoreTests\TestCase;

class ModelGetControllerTest extends TestCase
{
    protected const URI = '/api/users';

    public function testPut(): void
    {
        $request = $this->createRequest('PUT', self::URI);
        $response = $this->app->handle($request);

        self::assertEquals(405, $response->getStatusCode(), $response->getBody());
    }

    public function testPatch(): void
    {
        $request = $this->createRequest('PATCH', self::URI);
        $response = $this->app->handle($request);

        self::assertEquals(405, $response->getStatusCode(), $response->getBody());
    }

    public function testDelete(): void
    {
        $request = $this->createRequest('DELETE', self::URI);
        $response = $this->app->handle($request);

        self::assertEquals(405, $response->getStatusCode(), $response->getBody());
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->any(self::URI, ModelGetController::class);
    }
}
