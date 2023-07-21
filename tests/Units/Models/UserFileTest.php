<?php

namespace Models;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Slim\Psr7\Stream;
use Slim\Psr7\UploadedFile;
use Vesp\CoreTests\TestCase;
use Vesp\CoreTests\Units\Models\FileTest;
use Vesp\Models\File;
use Vesp\Models\User;
use Vesp\Models\UserFile;
use Vesp\Models\UserRole;

class UserFileTest extends TestCase
{
    public function testCreate(): void
    {
        $userRole = new UserRole();
        $userRole->title = 'role';
        $userRole->scope = ['scope'];
        $userRole->save();

        $user = new User();
        $user->username = 'user';
        $user->role_id = $userRole->id;
        $user->save();

        $stream = new Stream(fopen(FileTest::PNG, 'rb'));
        $data = new UploadedFile($stream, 'test.png', 'image/png', strlen(FileTest::PNG));
        putenv('UPLOAD_DIR='); // Clear upload path to use PHP temporary dir

        $file = new File();
        $file->uploadFile($data);

        $userFile = new UserFile();
        $userFile->user_id = $user->id;
        $userFile->file_id = $file->id;
        $userFile->save();

        self::assertTrue($userFile->exists);
    }

    public function testFind(): void
    {
        $userFile = UserFile::find(['user_id' => 1, 'file_id' => 1]);

        self::assertInstanceOf(UserFile::class, $userFile);
        self::assertInstanceOf(User::class, $userFile->user);
        self::assertInstanceOf(File::class, $userFile->file);
    }

    public function testFindFail(): void
    {
        $this->expectException(ModelNotFoundException::class);

        UserFile::findOrFail(['user_id' => 10, 'file_id' => 10]);
    }

    public function testUpdate(): void
    {
        UserFile::query()->where(['user_id' => 1, 'file_id' => 1])->update(['active' => false]);
        /** @var UserFile $userFile */
        $userFile = UserFile::find(['user_id' => 1, 'file_id' => 1]);

        self::assertFalse($userFile->active);
    }

    public function testKey(): void
    {
        $userFile = UserFile::findOrFail(['user_id' => 1, 'file_id' => 1]);

        self::assertArrayHasKey('user_id', $userFile->getKey());
        self::assertArrayHasKey('file_id', $userFile->getKey());
    }

    public function testDelete(): void
    {
        $userFile = UserFile::find(['user_id' => 1, 'file_id' => 1]);
        $userFile->delete();

        self::assertFalse($userFile->exists);
    }
}
