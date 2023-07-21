<?php

declare(strict_types=1);

namespace Vesp\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $file
 * @property string $path
 * @property ?string $title
 * @property ?string $type
 * @property ?int $width
 * @property ?int $height
 * @property ?int $size
 * @property ?array $metadata
 * @property Carbon $created_at
 * @property ?Carbon $updated_at
 *
 * @property-read string $file_path
 * @property-read string $full_file_path
 */
class File extends Model
{
    use Traits\FileModel;

    protected $fillable = ['file', 'path', 'title', 'type', 'width', 'height', 'size', 'metadata'];
    protected $casts = ['metadata' => 'array'];
}
