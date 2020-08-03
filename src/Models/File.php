<?php

declare(strict_types=1);

namespace Vesp\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
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

    protected $fillable = ['file', 'path', 'title', 'type', 'width', 'height', 'size', 'metadata'];
    protected $casts = [
        'metadata' => 'array',
    ];
}
