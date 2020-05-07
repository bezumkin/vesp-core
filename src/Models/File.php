<?php

namespace Vesp\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use League\Flysystem\Adapter\Local;
use League\Flysystem\FileExistsException;
use League\Flysystem\Filesystem;
use Slim\Psr7\Stream;
use Slim\Psr7\UploadedFile;
use Throwable;

/**
 * @property int $id
 * @property string $file
 * @property string $path
 * @property string $title
 * @property string $type
 * @property int $width
 * @property int $height
 * @property array $metadata
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class File extends Model
{
    protected $fillable = ['file', 'path', 'title', 'type', 'width', 'height', 'metadata'];
    protected $casts = [
        'metadata' => 'array',
    ];
    /** @var Filesystem $filesystem */
    protected $filesystem;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->filesystem = $this->getFilesystem();
    }

    /**
     *
     */
    public function getFilesystem()
    {
        $adapter = new Local($this->getRoot());

        return new Filesystem($adapter);
    }

    /**
     * @return string
     */
    protected static function getRoot()
    {
        return rtrim(getenv('UPLOAD_DIR'), '/') ?: (sys_get_temp_dir() . '/upload');
    }

    /**
     * @param UploadedFile|string $file
     * @param array $metadata
     * @param bool $replace
     * @return string
     * @throws InvalidArgumentException
     * @throws FileExistsException
     */
    public function uploadFile($file, array $metadata = null, $replace = true)
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

        $type = $file->getClientMediaType();
        $title = $file->getClientFilename();

        $filename = $this->getSaveName($title, $type);
        $path = $this->getSavePath($filename);

        $contents = $file->getStream()->getContents();
        $stream = $file->getStream()->detach();

        if ($replace && $this->file) {
            $this->deleteFile();
        }
        $this->filesystem->writeStream($path . '/' . $filename, $stream);
        if (strpos($type, 'image/') === 0 && $size = getimagesizefromstring($contents)) {
            $this->width = $size[0];
            $this->height = $size[1];
        }
        fclose($stream);

        $this->title = $title;
        $this->path = $path;
        $this->file = $filename;
        $this->type = $type;
        $this->metadata = $metadata;
        $this->save();

        return $this->getFullPath();
    }

    /**
     * @param string $filename
     * @param string $mime
     * @return string
     */
    protected static function getSaveName($filename = null, $mime = null)
    {
        $ext = null;
        if ($filename && $tmp = pathinfo($filename, PATHINFO_EXTENSION)) {
            $ext = strtolower($tmp);
        }
        if (!$ext && $mime && $tmp = explode('/', strtolower($mime))) {
            if (count($tmp) === 2) {
                $ext = $tmp[1];
            }
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
    protected static function getSavePath($filename)
    {
        return strlen($filename) >= 3
            ? implode('/', [$filename[0], $filename[1], $filename[2]])
            : '';
    }

    /**
     * @return bool
     */
    protected function deleteFile()
    {
        try {
            return $this->filesystem->delete($this->path . '/' . $this->file);
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * @return string
     */
    public function getFullPath()
    {
        return implode('/', [$this->getRoot(), $this->path, $this->file]);
    }

    /**
     * @return string|false
     */
    public function getFile()
    {
        try {
            return $this->filesystem->read($this->path . '/' . $this->file);
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * @return bool|null
     * @throws Throwable
     */
    public function delete()
    {
        $this->deleteFile();

        return parent::delete();
    }
}
