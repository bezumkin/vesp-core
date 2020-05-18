<?php

declare(strict_types=1);

namespace Vesp\Traits;

use ReflectionClass;

trait ModelDtoTrait
{
    public function toDto(string $dtoClass, array $data)
    {
        $dto = new $dtoClass();
        if (empty($data)) {
            return $dto;
        }

        $reflection = new ReflectionClass($dto);
        foreach ($data as $name => $value) {
            if ($reflection->hasProperty($name)) {
                $property = $reflection->getProperty($name);
                $property->setValue($dto, $value);
            }
        }

        return $dto;
    }
}
