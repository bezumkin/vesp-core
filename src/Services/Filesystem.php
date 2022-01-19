<?php

declare(strict_types=1);

namespace Vesp\Services;

use InvalidArgumentException;
use League\Flysystem\Filesystem as BaseFilesystem;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\FilesystemException;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Slim\Psr7\Stream;
use Slim\Psr7\UploadedFile;

class Filesystem
{
    protected $filesystem;

    public function __construct()
    {
        $this->filesystem = new BaseFilesystem($this->getAdapter());
    }

    public function getBaseFilesystem(): BaseFilesystem
    {
        return $this->filesystem;
    }

    public function deleteFile(string $path): bool
    {
        try {
            $this->filesystem->delete($path);

            return true;
        } catch (FilesystemException $e) {
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
        } catch (FilesystemException $e) {
            return null;
        }
    }

    /**
     * @param UploadedFile|string $data
     * @param ?array $metadata
     * @return ?array
     * @throws FilesystemException
     * @noinspection NullPointerExceptionInspection
     */
    public function uploadFile($data, ?array $metadata = []): array
    {
        $data = $this->normalizeFile($data, $metadata);
        $stream = $data->getStream();
        $stream->rewind();
        $contents = $stream->getContents();
        $stream = $stream->detach();

        $type = $data->getClientMediaType();
        $title = $data->getClientFilename();
        $filename = $this->getSaveName($title, $type);
        $path = $this->getSavePath($filename);

        $this->filesystem->writeStream($path . '/' . $filename, $stream);
        fclose($stream);

        $result = [
            'title' => $title,
            'path' => $path,
            'file' => $filename,
            'type' => $type,
            'size' => strlen($contents),
            'metadata' => $metadata,
        ];
        if (strpos($type, 'image/') === 0 && $size = getimagesizefromstring($contents)) {
            $result['width'] = (int)$size[0];
            $result['height'] = (int)$size[1];
        }

        return $result;
    }

    protected function getAdapter(): FilesystemAdapter
    {
        return new LocalFilesystemAdapter($this->getRoot());
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

    protected function normalizeFile($file, ?array $metadata = []): UploadedFile
    {
        if (is_string($file)) {
            if (!strpos($file, ';base64,')) {
                throw new InvalidArgumentException('Could not parse base64 string');
            }
            $stream = fopen('php://temp', 'rb+');
            [$mime, $data] = explode(',', $file);
            fwrite($stream, base64_decode($data));
            fseek($stream, 0);
            $stream = new Stream($stream);

            $file = new UploadedFile(
                $stream,
                !empty($metadata['name']) ? $metadata['name'] : '',
                str_replace(['data:', ';base64'], '', $mime),
                $stream->getSize()
            );
        }

        return $file;
    }
}
