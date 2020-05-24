<?php

/** @noinspection PhpUnhandledExceptionInspection */

namespace Vesp\Tests\Units\Controllers\Data;

use Clockwork\Storage\FileStorage;
use Vesp\Controllers\Data\Clockwork;
use Vesp\Tests\TestCase;

class ClockworkTest extends TestCase
{
    protected const URI = '/api/clockwork';
    /** @var FileStorage */
    protected $storage;

    // @codingStandardsIgnoreEnd

    public function testNotFoundGetFailure()
    {
        $request = $this->createRequest('GET', self::URI . '/12345-12345');
        $response = $this->app->handle($request);

        $this->assertEquals(404, $response->getStatusCode(), $response->getBody());
    }

    public function testIdGetSuccess()
    {
        $request = $this->createRequest('GET', self::URI . '/latest');
        $response = $this->app->handle($request);

        $this->assertEquals(200, $response->getStatusCode(), $response->getBody());
    }

    public function testPreviousGetSuccess()
    {
        $reports = $this->storage->all();
        $last = array_pop($reports);
        $request = $this->createRequest('GET', self::URI . '/' . $last->id . '/previous/1');
        $response = $this->app->handle($request);

        $this->assertEquals(200, $response->getStatusCode(), $response->getBody());
    }

    public function testNextGetSuccess()
    {
        $reports = $this->storage->all();
        $first = array_shift($reports);

        $request = $this->createRequest('GET', self::URI . '/' . $first->id . '/next');
        $response = $this->app->handle($request);

        $this->assertEquals(200, $response->getStatusCode(), $response->getBody());
    }

    public function testExtendedGetSuccess()
    {
        $reports = $this->storage->all();
        $first = array_shift($reports);

        $request = $this->createRequest('GET', self::URI . '/' . $first->id . '/extended');
        $response = $this->app->handle($request);

        $this->assertEquals(200, $response->getStatusCode(), $response->getBody());
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->storage = (new \Vesp\Services\Clockwork())->getStorage();

        $pattern = $this::URI . '/{id:(?:[0-9-]+|latest)}[/{direction:(?:next|previous)}[/{count:\d+}]]';
        $this->app->get($pattern, [Clockwork::class, 'process'])
            ->add(\Vesp\Middlewares\Clockwork::class);
        $this->app->get($this::URI . '/{id:[0-9-]+}/extended', [Clockwork::class, 'process']);
    }
}
