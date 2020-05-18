<?php

namespace Vesp\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Vesp\Helpers\Filesystem;
use Vesp\Dto\File as FileDto;
use Throwable;
use Vesp\Traits\ModelDtoTrait;

/**
 * @property int $id
 * @property string $file
 * @property string $path
 * @property string|null $title
 * @property string|null $type
 * @property int|null $width
 * @property int|null $height
 * @property array|null $metadata
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property-read string $file_path
 * @property-read string $full_file_path
 */
class File extends Model
{
    use ModelDtoTrait;

    protected $fillable = ['file', 'path', 'title', 'type', 'width', 'height', 'metadata'];
    protected $casts = [
        'metadata' => 'array',
    ];

    /** @var Filesystem $filesystem */
    protected $filesystem;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->filesystem = new Filesystem();
    }

    public function uploadFile($file, array $metadata = null, $replace = true)
    {
        $dto = $this->filesystem->uploadFile(
            $file,
            $this->toDto(FileDto::class, $this->toArray()),
            $metadata,
            $replace
        );

        $this->title = $dto->title;
        $this->path = $dto->path;
        $this->file = $dto->file;
        $this->type = $dto->type;
        $this->metadata = $dto->metadata;

        $this->save();

        return $this->full_file_path;
    }

    public function getFile()
    {
        return $this->filesystem->getFile($this->file_path);
    }

    public function getFullFilePathAttribute(): string
    {
        return $this->filesystem->getFullPath($this->file_path);
    }

    public function getFilePathAttribute(): string
    {
        return $this->path . '/' . $this->file;
    }

    public function deleteFile()
    {
        $this->filesystem->deleteFile($this->file_path);
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

    public function getFilesystem(): Filesystem
    {
        return $this->filesystem;
    }
}
