<?php

declare(strict_types=1);

namespace Vesp\Traits;

use Vesp\Services\Filesystem;

/**
 * @property string $file
 * @property string $path
 * @property string|null $title
 * @property string|null $type
 * @property int|null $width
 * @property int|null $height
 * @property array|null $metadata
 * @property Filesystem $filesystem
 */
trait ModelDtoTrait
{
    /** @var Filesystem $filesystem */
    protected $filesystem;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->filesystem = new Filesystem();
    }

    public function uploadFile($data, ?array $metadata = null, bool $replace = true): string
    {
        if ($replace && $this->exists) {
            $this->deleteFile();
        }
        $result = $this->filesystem->uploadFile($data, $metadata);
        $this->fill($result);
        $this->save();

        return $this->getFullFilePathAttribute();
    }

    public function getFile(): ?string
    {
        return $this->filesystem->getFile($this->file_path);
    }

    public function getFullFilePathAttribute(): string
    {
        return $this->filesystem->getFullPath($this->getFilePathAttribute());
    }

    public function getFilePathAttribute(): string
    {
        return $this->path . '/' . $this->file;
    }

    public function deleteFile(): void
    {
        $this->filesystem->deleteFile($this->getFilePathAttribute());
    }

    public function getFilesystem(): Filesystem
    {
        return $this->filesystem;
    }

    public function delete(): ?bool
    {
        $this->deleteFile();

        return parent::delete();
    }
}
