<?php

declare(strict_types=1);

namespace Vesp\Helpers;

use InvalidArgumentException;
use League\Flysystem\Adapter\Local;
use League\Flysystem\FileExistsException;
use League\Flysystem\Filesystem as BaseFilesystem;
use RuntimeException;
use Slim\Psr7\Stream;
use Slim\Psr7\UploadedFile;
use Throwable;
use Vesp\Dto\File as FileDto;

class Filesystem
{
    protected $filesystem;

    public function __construct()
    {
        $adapter = new Local($this->getRoot());

        $this->filesystem = new BaseFilesystem($adapter);
    }

    public function getBaseFilesystem(): BaseFilesystem
    {
        return $this->filesystem;
    }

    public function deleteFile(string $path): bool
    {
        try {
            return $this->filesystem->delete($path);
        } catch (Throwable $e) {
            return false;
        }
    }

    public function getFullPath(string $path): string
    {
        return implode('/', [$this->getRoot(), $path]);
    }

    public function getFile(string $path): ?string
    {
        try {
            return $this->filesystem->read($path);
        } catch (Throwable $e) {
            return null;
        }
    }

    /**
     * @param UploadedFile|string $file
     * @param FileDto $fileDto
     * @param array $metadata
     * @param bool $replace
     * @return FileDto
     * @throws InvalidArgumentException
     * @throws FileExistsException
     * @throws RuntimeException
     */
    public function uploadFile($file, FileDto $fileDto, array $metadata = null, bool $replace = true): FileDto
    {
        $file = $this->normalizeFile($file, $metadata);
        $type = $file->getClientMediaType();
        $title = $file->getClientFilename();
        $filename = $this->getSaveName($title, $type);
        $path = $this->getSavePath($filename);

        /** @noinspection NullPointerExceptionInspection */
        $contents = $file->getStream()->getContents();
        /** @noinspection NullPointerExceptionInspection */
        $stream = $file->getStream()->detach();

        if ($replace && $fileDto->file) {
            $this->deleteFile($fileDto->path . '/' . $fileDto->file);
        }

        $this->filesystem->writeStream($path . '/' . $filename, $stream);
        fclose($stream);

        $fileDto->title = $title;
        $fileDto->path = $path;
        $fileDto->file = $filename;
        $fileDto->type = $type;
        $fileDto->metadata = $metadata;
        if (strpos($type, 'image/') === 0) {
            $size = getimagesizefromstring($contents);
            $fileDto->width = (int)$size[0];
            $fileDto->height = (int)$size[1];
        }

        return $fileDto;
    }

    protected function getRoot(): string
    {
        return rtrim(getenv('UPLOAD_DIR'), '/') ?: (sys_get_temp_dir() . '/upload');
    }

    protected function getSaveName(?string $filename = null, ?string $mime = null): string
    {
        $ext = null;
        if ($filename && $tmp = pathinfo($filename, PATHINFO_EXTENSION)) {
            $ext = strtolower($tmp);
        }
        if (!$ext && $mime && ($tmp = explode('/', strtolower($mime))) && count($tmp) === 2) {
            $ext = $tmp[1];
        }

        $name = uniqid('', true);
        if ($ext) {
            if ($ext === 'jpeg') {
                $ext = 'jpg';
            }
            $name .= '.' . $ext;
        }

        return $name;
    }

    protected function getSavePath(string $filename): string
    {
        return strlen($filename) >= 3
            ? implode('/', [$filename[0], $filename[1], $filename[2]])
            : '';
    }

    private function normalizeFile($file, ?array $metadata = []): UploadedFile
    {
        if (is_string($file)) {
            if (!strpos($file, ';base64,')) {
                throw new InvalidArgumentException('Could not parse base64 string');
            }
            $stream = new Stream(fopen($file, 'rb'));

            [$mime, $data] = explode(',', $file);
            $mime = str_replace(['data:', ';base64'], '', $mime);
            $data = base64_decode($data);

            $file = new UploadedFile($stream, !empty($metadata['name']) ? $metadata['name'] : '', $mime, strlen($data));
        }

        return $file;
    }
}
