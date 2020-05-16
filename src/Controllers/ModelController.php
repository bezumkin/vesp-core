<?php

namespace Vesp\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Psr\Http\Message\ResponseInterface;
use Throwable;

abstract class ModelController extends Controller
{
    /** @var Model $model */
    protected $model;
    protected $primaryKey = 'id';

    /**
     * @return ResponseInterface
     */
    public function get()
    {
        /** @var Model $class */
        $class = new $this->model();
        $c = $class->newQuery();

        if ($key = $this->getPrimaryKey()) {
            $c = $this->beforeGet($c);
            if (is_array($key)) {
                $c->where(
                    function (Builder $c) use ($key) {
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
                'total' => isset($total) ? $total : count($rows),
                'rows' => $rows,
            ]
        );

        return $this->success($data);
    }

    /**
     * Add conditions before get an object by id
     *
     * @param Builder $c
     * @return Builder
     */
    protected function beforeGet(Builder $c)
    {
        return $c;
    }

    /**
     * @param Model $object
     * @return array
     */
    public function prepareRow(Model $object)
    {
        return $object->toArray();
    }

    /**
     * @param array $array
     * @return array
     */
    public function prepareList(array $array)
    {
        return $array;
    }

    /**
     * Add joins and search filter
     *
     * @param Builder $c
     * @return Builder
     */
    protected function beforeCount(Builder $c)
    {
        return $c;
    }

    /**
     * @param Builder $c
     * @return int
     */
    protected function getCount(Builder $c)
    {
        return $c->count();
    }

    /**
     * Add selects to query after total count
     *
     * @param Builder $c
     * @return Builder
     */
    protected function afterCount(Builder $c)
    {
        return $c;
    }

    /**
     * @return ResponseInterface
     */
    public function put()
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
     * @param $record
     * @return bool|string
     */
    protected function beforeSave(Model $record)
    {
        return ($record instanceof Model)
            ? true
            : 'Could not save the object';
    }

    /**
     * @param Model $record
     * @return Model
     */
    protected function afterSave(Model $record)
    {
        return $record;
    }

    /**
     * @return ResponseInterface
     */
    public function patch()
    {
        if (!$id = $this->getPrimaryKey()) {
            return $this->failure('You must specify the primary key of object', 422);
        }
        if (!$record = $this->model::query()->find($id)) {
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
        if (!$record = $this->model::query()->find($id)) {
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
                if (!is_string($item) || !$value = $this->route->getArgument($item, $this->getProperty($item))) {
                    return null;
                }
                $key[$item] = $value;
            }

            return $key;
        }

        return $this->route->getArgument($this->primaryKey, $this->getProperty($this->primaryKey));
    }
}
