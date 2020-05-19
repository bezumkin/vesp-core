<?php

declare(strict_types=1);

namespace Vesp\Helpers;

use League\Flysystem\Adapter\Local;
use League\Flysystem\FileExistsException;
use League\Flysystem\Filesystem as BaseFilesystem;
use Slim\Psr7\Stream;
use Slim\Psr7\UploadedFile;
use Throwable;
use InvalidArgumentException;
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

    /**
     * @return string
     */
    protected function getRoot()
    {
        return rtrim(getenv('UPLOAD_DIR'), '/') ?: (sys_get_temp_dir() . '/upload');
    }

    /**
     * @param string $filename
     * @param string $mime
     * @return string
     */
    public function getSaveName($filename = null, $mime = null)
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

    /**
     * @param string $filename
     * @return string
     */
    public function getSavePath($filename)
    {
        return strlen($filename) >= 3
            ? implode('/', [$filename[0], $filename[1], $filename[2]])
            : '';
    }

    /**
     * @param string $path
     * @return bool
     */
    public function deleteFile(string $path)
    {
        try {
            return $this->filesystem->delete($path);
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * @param string $path
     * @return string
     */
    public function getFullPath(string $path)
    {
        return implode('/', [$this->getRoot(), $path]);
    }

    /**
     * @param string $path
     * @return string|false
     */
    public function getFile(string $path)
    {
        try {
            return $this->filesystem->read($path);
        } catch (Throwable $e) {
            return false;
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
     */
    public function uploadFile($file, FileDto $fileDto, array $metadata = null, $replace = true): FileDto
    {
        $file = $this->normalizeFile($file);
        $type = $file->getClientMediaType();
        $title = $file->getClientFilename();

        $filename = $this->getSaveName($title, $type);
        $path = $this->getSavePath($filename);

        $contents = $file->getStream()->getContents();
        $stream = $file->getStream()->detach();

        if ($replace && $fileDto->file) {
            $this->deleteFile($fileDto->path . '/' . $fileDto->file);
        }

        $this->filesystem->writeStream($path . '/' . $filename, $stream);
        $fileDto = $this->getImageSize($contents, $type, $fileDto);
        fclose($stream);

        $fileDto->title = $title;
        $fileDto->path = $path;
        $fileDto->file = $filename;
        $fileDto->type = $type;
        $fileDto->metadata = $metadata;

        return $fileDto;
    }

    private function getImageSize(string $contents, string $type, FileDto $fileDto): FileDto
    {
        if (strpos($type, 'image/') !== 0) {
            return $fileDto;
        }

        $size = getimagesizefromstring($contents);
        $fileDto->width = (int)$size[0];
        $fileDto->height = (int)$size[1];

        return $fileDto;
    }

    private function normalizeFile($file): UploadedFile
    {
        if (is_string($file)) {
            if (!strpos($file, ';base64,')) {
                throw new InvalidArgumentException('Could not parse base64 string');
            }
            $stream = new Stream(fopen($file, 'r'));

            [$mime, $data] = explode(',', $file);
            $mime = str_replace(['data:', ';base64'], '', $mime);
            $data = base64_decode($data);

            $file = new UploadedFile($stream, !empty($metadata['name']) ? $metadata['name'] : '', $mime, strlen($data));
        }

        return $file;
    }
}
