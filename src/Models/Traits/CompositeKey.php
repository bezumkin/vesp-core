<?php

namespace Vesp\Models\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * @method array getKeyName
 * @method Builder newQuery
 */
trait CompositeKey
{
    public function getIncrementing(): bool
    {
        return false;
    }

    public function getKey(): array
    {
        $key = [];
        foreach ($this->getKeyName() as $item) {
            $key[$item] = $this->getAttribute($item);
        }

        return $key;
    }

    protected function setKeysForSaveQuery($query): Builder
    {
        foreach ($this->getKey() as $key => $value) {
            $query->where($key, $value);
        }

        return $query;
    }

    public static function find(array $keys, array $columns = ['*']): ?Model
    {
        $self = new self();
        $query = $self->newQuery();
        foreach ($self->getKeyName() as $key) {
            $query->where($key, $keys[$key]);
        }

        return $query->first($columns);
    }

    public static function findOrFail(array $ids): Model
    {
        if (!$record = self::find($ids)) {
            throw new ModelNotFoundException();
        }

        return $record;
    }
}
