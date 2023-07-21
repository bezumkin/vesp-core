<?php

declare(strict_types=1);

namespace Vesp\Services;

use League\Flysystem\Filesystem as BaseFilesystem;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\Local\LocalFilesystemAdapter;

class Filesystem
{
    protected BaseFilesystem $filesystem;

    public function __construct()
    {
        $this->filesystem = new BaseFilesystem($this->getAdapter());
    }

    public function getBaseFilesystem(): BaseFilesystem
    {
        return $this->filesystem;
    }

    public function getFullPath(string $path): string
    {
        return implode('/', [$this->getRoot(), $path]);
    }

    protected function getAdapter(): FilesystemAdapter
    {
        return new LocalFilesystemAdapter($this->getRoot());
    }

    protected function getRoot(): string
    {
        return rtrim(getenv('UPLOAD_DIR'), '/') ?: (sys_get_temp_dir() . '/upload');
    }
}
