<?php

/** @noinspection PhpUnhandledExceptionInspection */

namespace Vesp\CoreTests\Units\Controllers\Data;

use Vesp\Controllers\Data\Image;
use Vesp\Models\File;
use Vesp\CoreTests\TestCase;

class ImageTest extends TestCase
{
    protected const URI = '/api/image';
    // @codingStandardsIgnoreStart
    protected const PNG = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';
    protected const GIF = 'data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==';
    protected const SVG = 'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiPz4KPHN2ZyB3aWR0aD0iMSIgaGVpZ2h0PSIxIiB2aWV3Qm94PSIwIDAgMSAxIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciLz4K';
    protected const TXT = 'data:text/plain;base64,dGVzdA==';

    // @codingStandardsIgnoreEnd

    public function testNotFoundGetFailure(): void
    {
        $request = $this->createRequest('GET', self::URI, ['id' => 1]);
        $response = $this->app->handle($request);

        self::assertEquals(404, $response->getStatusCode(), $response->getBody());
    }

    public function testWrongTypeFailure(): void
    {
        $file = new File();
        $file->uploadFile(self::TXT, ['name' => 'test.txt']);

        $request = $this->createRequest('GET', self::URI, ['id' => $file->id]);
        $response = $this->app->handle($request);

        self::assertEquals(422, $response->getStatusCode(), $response->getBody());
    }

    public function testGetSuccess(): void
    {
        $file = new File();
        $file->uploadFile(self::PNG, ['name' => 'test.png']);

        $request = $this->createRequest('GET', self::URI, ['id' => $file->id]);
        $response = $this->app->handle($request);
        $file->delete();

        self::assertEquals(200, $response->getStatusCode(), $response->getBody());
    }

    public function testGetGifSuccess(): void
    {
        $file = new File();
        $file->uploadFile(self::GIF, ['name' => 'test.gif']);

        $request = $this->createRequest('GET', self::URI, ['id' => $file->id, 'fm' => 'gif']);
        $response = $this->app->handle($request);
        $file->delete();

        self::assertEquals(200, $response->getStatusCode(), $response->getBody());
    }

    public function testGetSvgSuccess(): void
    {
        $file = new File();
        $file->uploadFile(self::SVG, ['name' => 'test.svg']);

        $request = $this->createRequest('GET', self::URI, ['id' => $file->id]);
        $response = $this->app->handle($request);
        $file->delete();

        self::assertEquals(200, $response->getStatusCode(), $response->getBody());
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->get(self::URI, Image::class);
    }
}
