<?php

declare(strict_types=1);

namespace Vesp\Dto;

class File
{
    /** @var string|null */
    public $title;

    /** @var string */
    public $path;

    /** @var string */
    public $file;

    /** @var string|null */
    public $type;

    public $metadata = [];

    /** @var int|null */
    public $height;

    /** @var int|null */
    public $width;
}
