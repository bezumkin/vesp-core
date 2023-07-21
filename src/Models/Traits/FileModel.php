<?php

declare(strict_types=1);

namespace Vesp\Models\Traits;

use InvalidArgumentException;
use League\Flysystem\FilesystemException;
use Slim\Psr7\Stream;
use Slim\Psr7\UploadedFile;
use Vesp\Services\Filesystem;

/**
 * @property string $file
 * @property string $path
 * @property ?string $title
 * @property ?string $type
 * @property ?int $width
 * @property ?int $height
 * @property ?array $metadata
 * @property Filesystem $filesystem
 */
trait FileModel
{
    protected Filesystem $filesystem;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->filesystem = new Filesystem();
    }

    public function getFile(): ?string
    {
        $path = $this->getFilePathAttribute();
        try {
            return $this->filesystem->getBaseFilesystem()->read($path);
        } catch (FilesystemException $e) {
            return null;
        }
    }

    public function uploadFile($data, ?array $metadata = null, bool $replace = true): string
    {
        if ($replace && $this->exists) {
            $this->deleteFile();
        }

        $data = $this->normalizeFile($data, $metadata);
        $stream = $data->getStream();
        $stream?->rewind();
        $contents = $stream?->getContents();
        $stream = $stream?->detach();

        $type = $data->getClientMediaType();
        $title = $data->getClientFilename();
        $filename = $this->getSaveName($title, $type);
        $path = $this->getSavePath($filename);

        $this->filesystem->getBaseFilesystem()->writeStream($path . '/' . $filename, $stream);
        fclose($stream);

        $result = [
            'title' => $title,
            'path' => $path,
            'file' => $filename,
            'type' => $type,
            'size' => strlen($contents),
            'metadata' => $metadata,
        ];
        if (str_starts_with($type, 'image/') && $size = getimagesizefromstring($contents)) {
            $result['width'] = (int)$size[0];
            $result['height'] = (int)$size[1];
        }

        $this->fill($result);
        $this->save();

        return $this->getFullFilePathAttribute();
    }

    public function getFullFilePathAttribute(): string
    {
        return $this->filesystem->getFullPath($this->getFilePathAttribute());
    }

    public function getFilePathAttribute(): string
    {
        return $this->path . '/' . $this->file;
    }

    public function getFilesystem(): Filesystem
    {
        return $this->filesystem;
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

    public function deleteFile(): bool
    {
        $path = $this->getFilePathAttribute();
        try {
            $this->filesystem->getBaseFilesystem()->delete($path);

            return true;
        } catch (FilesystemException $e) {
            return false;
        }
    }

    public function delete(): ?bool
    {
        $this->deleteFile();

        return parent::delete();
    }
}
