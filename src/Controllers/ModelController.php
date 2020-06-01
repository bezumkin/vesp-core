<?php

declare(strict_types=1);

namespace Vesp\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Psr\Http\Message\ResponseInterface;
use Throwable;

abstract class ModelController extends Controller
{
    /** @var string $model */
    protected $model;
    protected $primaryKey = 'id';

    public function get(): ResponseInterface
    {
        /** @var Model $class */
        $c = (new $this->model())->newQuery();

        if ($key = $this->getPrimaryKey()) {
            $c = $this->beforeGet($c);
            if (is_array($key)) {
                $c->where(
                    static function (Builder $c) use ($key) {
                        foreach ($key as $item => $value) {
                            $c->where($item, $value);
                        }
                    }
                );
                $record = $c->first();
            } else {
                $record = $c->find($key);
            }
            if ($record) {
                $data = $this->prepareRow($record);

                return $this->success($data);
            }

            return $this->failure('Could not find a record', 404);
        }
        $c = $this->beforeCount($c);
        if ($limit = (int)$this->getProperty('limit')) {
            $total = $this->getCount($c);
            $c->forPage((int)$this->getProperty('page', 1), $limit);
        }
        $c = $this->afterCount($c);
        $query = $c->getQuery();
        if (empty($query->{$query->unions ? 'unionOrders' : 'orders'}) && $sort = $this->getProperty('sort')) {
            $c->orderBy($sort, $this->getProperty('dir') === 'desc' ? 'desc' : 'asc');
        }
        $rows = [];
        foreach ($c->get() as $object) {
            $rows[] = $this->prepareRow($object);
        }

        $data = $this->prepareList(
            [
                'total' => $total ?? count($rows),
                'rows' => $rows,
            ]
        );

        return $this->success($data);
    }

    protected function beforeGet(Builder $c): Builder
    {
        return $c;
    }

    public function prepareRow(Model $object): array
    {
        return $object->toArray();
    }

    public function prepareList(array $array): array
    {
        return $array;
    }

    protected function beforeCount(Builder $c): Builder
    {
        return $c;
    }

    protected function getCount(Builder $c): int
    {
        return $c->count();
    }

    /**
     * Add selects to query after total count
     *
     * @param Builder $c
     * @return Builder
     */
    protected function afterCount(Builder $c): Builder
    {
        return $c;
    }

    public function put(): ResponseInterface
    {
        try {
            /** @var Model $record */
            $record = new $this->model();
            $record->fill($this->getProperties());
            $check = $this->beforeSave($record);
            if ($check !== true) {
                return $check instanceof ResponseInterface ? $check : $this->failure($check, 422);
            }
            $record->save();
            $record = $this->afterSave($record);

            return $this->success($this->prepareRow($record));
        } catch (Throwable $e) {
            return $this->failure($e->getMessage(), 500);
        }
    }

    /**
     * @param Model $record
     * @return bool|string
     */
    protected function beforeSave(Model $record)
    {
        return ($record instanceof Model)
            ? true
            : 'Could not save the object';
    }

    protected function afterSave(Model $record): Model
    {
        return $record;
    }

    public function patch(): ResponseInterface
    {
        if (!$id = $this->getPrimaryKey()) {
            return $this->failure('You must specify the primary key of object', 422);
        }
        if (!$record = (new $this->model())->newQuery()->find($id)) {
            return $this->failure('Could not find a record', 404);
        }
        try {
            $record->fill($this->getProperties());
            $check = $this->beforeSave($record);
            if ($check !== true) {
                return $check instanceof ResponseInterface ? $check : $this->failure($check, 422);
            }
            $record->save();
            $record = $this->afterSave($record);

            return $this->success($this->prepareRow($record));
        } catch (Throwable $e) {
            return $this->failure($e->getMessage(), 500);
        }
    }

    /**
     * @return ResponseInterface
     * @throws Throwable
     */
    public function delete()
    {
        if (!$id = $this->getPrimaryKey()) {
            return $this->failure('You must specify the primary key of object', 422);
        }
        /** @var Model $record */
        if (!$record = (new $this->model())->newQuery()->find($id)) {
            return $this->failure('Could not find a record', 404);
        }
        $check = $this->beforeDelete($record);
        if ($check !== true) {
            return $check instanceof ResponseInterface ? $check : $this->failure($check, 422);
        }
        $record->delete();

        return $this->success();
    }

    /**
     * @param Model $record
     * @return bool|string
     */
    protected function beforeDelete(Model $record)
    {
        return ($record instanceof Model)
            ? true
            : 'Could not save the object';
    }

    /**
     * @return string|array|null
     */
    protected function getPrimaryKey()
    {
        if (is_array($this->primaryKey)) {
            $key = [];
            foreach ($this->primaryKey as $item) {
                if (!$value = $this->route->getArgument($item, (string)$this->getProperty($item))) {
                    return null;
                }
                $key[$item] = $value;
            }

            return $key;
        }

        return $this->route->getArgument($this->primaryKey, (string)$this->getProperty($this->primaryKey));
    }
}
