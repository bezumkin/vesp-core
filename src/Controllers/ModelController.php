<?php

declare(strict_types=1);

namespace Vesp\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Psr\Http\Message\ResponseInterface;

abstract class ModelController extends Controller
{
    protected string $model;
    protected string|array $primaryKey = 'id';
    protected int $maxLimit = 1000;

    public function get(): ResponseInterface
    {
        $c = (new $this->model())->newQuery();
        if ($key = $this->getPrimaryKey()) {
            $c = $this->beforeGet($c);
            $record = is_array($key) ? $c->where($key)->first() : $c->find($key);
            if ($record) {
                $data = $this->prepareRow($record);

                return $this->success($data);
            }

            return $this->failure('Could not find a record', 404);
        }
        $c = $this->beforeCount($c);

        $limit = abs((int)$this->getProperty('limit'));
        if ($this->maxLimit && (!$limit || $limit > $this->maxLimit)) {
            $limit = $this->maxLimit;
        }
        $page = abs((int)$this->getProperty('page', 1));
        $offset = abs((int)$this->getProperty('offset', ($page - 1) * $limit));

        if ($offset || $limit) {
            $total = $this->getCount($c);
            if ($offset) {
                $c->offset($offset);
            }
            if ($limit) {
                $c->limit($limit);
            }
        }

        $c = $this->afterCount($c);
        $c = $this->addSorting($c);
        $rows = [];
        foreach ($c->get() as $object) {
            $rows[] = $this->prepareRow($object);
        }

        $data = $this->prepareList([
            'total' => $total ?? count($rows),
            'rows' => $rows,
        ]);

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

    protected function afterCount(Builder $c): Builder
    {
        return $c;
    }

    protected function addSorting(Builder $c): Builder
    {
        $query = $c->getQuery();
        if (empty($query->{$query->unions ? 'unionOrders' : 'orders'}) && $sort = $this->getProperty('sort')) {
            $c->orderBy(
                preg_replace('#\W+#m', '', $sort),
                strtolower($this->getProperty('dir', '')) === 'desc' ? 'desc' : 'asc'
            );
        }

        return $c;
    }

    public function put(): ResponseInterface
    {
        /** @var Model $record */
        $record = new $this->model();
        $record->fill($this->getProperties());
        if ($check = $this->beforeSave($record)) {
            return $check;
        }
        $record->save();
        $record = $this->afterSave($record);

        return $this->success($this->prepareRow($record));
    }

    protected function beforeSave(Model $record): ?ResponseInterface
    {
        return null;
    }

    protected function afterSave(Model $record): Model
    {
        return $record;
    }

    public function patch(): ResponseInterface
    {
        if (!$key = $this->getPrimaryKey()) {
            return $this->failure('You must specify the primary key of object');
        }
        $c = (new $this->model())->newQuery();
        /** @var Model $record */
        if (!$record = is_array($key) ? $c->where($key)->first() : $c->find($key)) {
            return $this->failure('Could not find a record', 404);
        }

        $record->fill($this->getProperties());
        if ($check = $this->beforeSave($record)) {
            return $check;
        }
        $record->save();
        $record = $this->afterSave($record);

        return $this->success($this->prepareRow($record));
    }

    public function delete(): ResponseInterface
    {
        if (!$key = $this->getPrimaryKey()) {
            return $this->failure('You must specify the primary key of object');
        }
        $c = (new $this->model())->newQuery();
        /** @var Model $record */
        if (!$record = is_array($key) ? $c->where($key)->first() : $c->find($key)) {
            return $this->failure('Could not find a record', 404);
        }
        if ($check = $this->beforeDelete($record)) {
            return $check;
        }
        $record->delete();

        return $this->success();
    }

    protected function beforeDelete(Model $record): ?ResponseInterface
    {
        return null;
    }

    protected function getPrimaryKey(): null|string|array
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
