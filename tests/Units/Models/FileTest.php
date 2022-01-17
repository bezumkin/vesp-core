<?php

/** @noinspection PhpUnhandledExceptionInspection */

namespace Vesp\CoreTests\Units\Models;

use InvalidArgumentException;
use Slim\Psr7\Stream;
use Slim\Psr7\UploadedFile;
use Vesp\CoreTests\TestCase;
use Vesp\Models\File;
use Vesp\Services\Filesystem;

class FileTest extends TestCase
{
    // @codingStandardsIgnoreStart
    protected const JPG = 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAZABkAAD/2wCEABQQEBkSGScXFycyJh8mMi4mJiYmLj41NTU1NT5EQUFBQUFBREREREREREREREREREREREREREREREREREREREQBFRkZIBwgJhgYJjYmICY2RDYrKzZERERCNUJERERERERERERERERERERERERERERERERERERERERERERERERERP/AABEIAAEAAQMBIgACEQEDEQH/xABMAAEBAAAAAAAAAAAAAAAAAAAABQEBAQAAAAAAAAAAAAAAAAAABQYQAQAAAAAAAAAAAAAAAAAAAAARAQAAAAAAAAAAAAAAAAAAAAD/2gAMAwEAAhEDEQA/AJQA9Yv/2Q==';
    protected const PNG = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

    // @codingStandardsIgnoreEnd

    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();

        (new Filesystem())->getBaseFilesystem()->deleteDirectory('/');
    }

    public function testUploadBase64Failure(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(0);

        $file = new File();
        $file->uploadFile('wrong_base64_string', ['name' => 'Test']);
    }

    public function testUploadBase64Success(): void
    {
        $file = new File();
        $path = $file->uploadFile(self::JPG, ['name' => 'test_with_no_extension']);
        self::assertIsString($path);
        self::assertEquals(1, $file->id);
    }

    public function testUploadBase64Replace(): void
    {
        /** @var File $file */
        $file = File::query()->find(1);
        $old_file = $file->getFile();
        $new_file = $file->uploadFile(self::PNG, ['name' => 'test.png']);

        self::assertIsString($new_file);
        self::assertNotEquals($old_file, $new_file);
    }

    public function testDeleteSuccess(): void
    {
        /** @var File $file */
        $file = File::query()->find(1);
        self::assertFileExists($file->full_file_path);

        $file->delete();
        self::assertFileDoesNotExist($file->full_file_path);
        self::assertFalse($file->exists);
    }

    public function testDeleteNoFileSuccess(): void
    {
        $file = new File();
        $file->uploadFile(self::PNG, ['name' => 'test.png']);

        $file->deleteFile();
        self::assertNull($file->getFile());

        $file->delete();
        self::assertFalse($file->exists);
    }

    public function testDeleteNoFileError(): void
    {
        $filesystem = new Filesystem();
        $filesystem->getBaseFilesystem()->createDirectory('/directory');
        $res = $filesystem->deleteFile('/directory');
        self::assertFalse($res);

        $filesystem->getBaseFilesystem()->deleteDirectory('/directory');
    }

    public function testUploadFile(): void
    {
        $stream = new Stream(fopen(self::PNG, 'rb'));
        $data = new UploadedFile($stream, 'test.png', 'image/png', strlen(self::PNG));
        putenv('UPLOAD_DIR='); // Clear upload path to use PHP temporary dir

        $file = new File();
        $file->uploadFile($data);

        self::assertTrue($file->exists);
        self::assertFileExists($file->full_file_path);
        self::assertIsString($file->getFile());
        $file->delete();
    }
}
