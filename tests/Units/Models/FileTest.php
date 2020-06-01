<?php

/** @noinspection PhpUnhandledExceptionInspection */

namespace Vesp\Tests\Units\Models;

use InvalidArgumentException;
use Slim\Psr7\Stream;
use Slim\Psr7\UploadedFile;
use Vesp\Models\File;
use Vesp\Tests\TestCase;

class FileTest extends TestCase
{
    // @codingStandardsIgnoreStart
    protected const JPG = 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAZABkAAD/2wCEABQQEBkSGScXFycyJh8mMi4mJiYmLj41NTU1NT5EQUFBQUFBREREREREREREREREREREREREREREREREREREREQBFRkZIBwgJhgYJjYmICY2RDYrKzZERERCNUJERERERERERERERERERERERERERERERERERERERERERERERERERP/AABEIAAEAAQMBIgACEQEDEQH/xABMAAEBAAAAAAAAAAAAAAAAAAAABQEBAQAAAAAAAAAAAAAAAAAABQYQAQAAAAAAAAAAAAAAAAAAAAARAQAAAAAAAAAAAAAAAAAAAAD/2gAMAwEAAhEDEQA/AJQA9Yv/2Q==';
    protected const PNG = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

    // @codingStandardsIgnoreEnd

    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();

        $root = (new \Vesp\Helpers\Filesystem())->getBaseFilesystem()->getAdapter()->getPathPrefix();
        (new \Symfony\Component\Filesystem\Filesystem())->remove($root);
    }

    public function testUploadBase64Failure()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(0);

        $file = new File();
        $file->uploadFile('wrong_base64_string', ['name' => 'Test']);
    }

    public function testUploadBase64Success()
    {
        $file = new File();
        $path = $file->uploadFile(self::JPG, ['name' => 'test_with_no_extenstion']);
        $this->assertIsString($path);
        $this->assertEquals(1, $file->id);
    }

    public function testUploadBase64Replace()
    {
        $file = File::query()->find(1);
        $old_file = $file->getFile();
        $new_file = $file->uploadFile(self::PNG, ['name' => 'test.png']);

        $this->assertIsString($new_file);
        $this->assertNotEquals($old_file, $new_file);
    }

    public function testDeleteSuccess()
    {
        /** @var File $file */
        $file = File::query()->find(1);
        $this->assertFileExists($file->full_file_path);

        $file->delete();
        $this->assertFileNotExists($file->full_file_path);
        $this->assertFalse($file->exists);
    }

    public function testDeleteNoFileSuccess()
    {
        $file = new File();
        $file->uploadFile(self::PNG, ['name' => 'test.png']);

        $file->deleteFile();
        $this->assertNull($file->getFile());

        $file->delete();
        $this->assertFalse($file->exists);
    }

    public function testUploadFile()
    {
        $stream = new Stream(fopen(self::PNG, 'rb'));
        $data = new UploadedFile($stream, 'test.png', 'image/png', strlen(self::PNG));
        putenv('UPLOAD_DIR='); // Clear upload path to use PHP temporary dir

        $file = new File();
        $file->uploadFile($data);

        $this->assertTrue($file->exists);
        $this->assertFileExists($file->full_file_path);
        $this->assertIsString($file->getFile());
        $file->delete();
    }
}
