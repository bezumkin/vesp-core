<?php

/** @noinspection PhpUnhandledExceptionInspection */

namespace Vesp\Tests\Units\Controllers;

use Vesp\Controllers\Data\Image;
use Vesp\Models\File;
use Vesp\Tests\TestCase;

class ImageTest extends TestCase
{
    protected const URI = '/api/image';
    // @codingStandardsIgnoreStart
    protected const PNG = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';
    protected const TXT = 'data:text/plain;base64,dGVzdA==';

    // @codingStandardsIgnoreEnd

    public function testNotFoundGetFailure()
    {
        $request = $this->createRequest('GET', self::URI, ['id' => 1]);
        $response = $this->app->handle($request);

        $this->assertEquals(404, $response->getStatusCode(), $response->getBody());
    }

    public function testWrongTypeFailure()
    {
        $file = new File();
        $file->uploadFile(self::TXT, ['name' => 'test.txt']);

        $request = $this->createRequest('GET', self::URI, ['id' => $file->id]);
        $response = $this->app->handle($request);

        $this->assertEquals(422, $response->getStatusCode(), $response->getBody());
    }

    public function testGetSuccess()
    {
        $file = new File();
        $file->uploadFile(self::PNG, ['name' => 'test.png']);

        $request = $this->createRequest('GET', self::URI, ['id' => $file->id]);
        $response = $this->app->handle($request);
        $file->delete();

        $this->assertEquals(200, $response->getStatusCode(), $response->getBody());
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->get(self::URI, [Image::class, 'process']);
    }
}
