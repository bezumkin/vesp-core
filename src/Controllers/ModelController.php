<?php

namespace Vesp\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Psr\Http\Message\ResponseInterface;
use Slim\Routing\RouteContext;
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
        /** @var Builder $c */
        $c = $class->query();

        if ($id = $this->getPrimaryKey()) {
            $c = $this->beforeGet($c);
            if ($record = $c->find($id)) {
                $data = $this->prepareRow($record);

                return $this->success($data);
            }

            return $this->failure('Could not find a record', 404);
        }
        $c = $this->beforeCount($c);
        if ($limit = (int)$this->getProperty('limit')) {
            $page = (int)$this->getProperty('page', 1);
            $total = $c->count();
            $c->forPage($page, $limit);
        }
        $c = $this->afterCount($c);
        $query = $c->getQuery();
        if (empty($query->{$query->unions ? 'unionOrders' : 'orders'}) && $sort = $this->getProperty('sort')) {
            $c->orderBy($class->getTable() . '.' . $sort, $this->getProperty('dir') === 'desc' ? 'desc' : 'asc');
        }
        $rows = [];
        foreach ($c->get() as $object) {
            $rows[] = $this->prepareRow($object);
        }

        return $this->success(
            [
                'total' => isset($total) ? $total : count($rows),
                'rows' => $rows,
            ]
        );
    }

    /**
     * Add conditions before get an object by id
     *
     * @param Builder $c
     * @return Builder
     */
    protected function beforeGet($c)
    {
        return $c;
    }

    /**
     * @param Model $object
     * @return array
     */
    public function prepareRow($object)
    {
        return $object->toArray();
    }

    /**
     * Add joins and search filter
     *
     * @param Builder $c
     * @return Builder
     */
    protected function beforeCount($c)
    {
        return $c;
    }

    /**
     * Add selects to query after total count
     *
     * @param Builder $c
     * @return Builder
     */
    protected function afterCount($c)
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
                return $this->failure($check, 422);
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
    protected function beforeSave($record)
    {
        return ($record instanceof Model)
            ? true
            : 'Could not save the object';
    }

    /**
     * @param Model $record
     * @return Model
     */
    protected function afterSave($record)
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
                return $this->failure($check, 422);
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
            return $this->failure($check);
        }
        $record->delete();

        return $this->success();
    }

    /**
     * @param Model $record
     * @return bool|string
     */
    protected function beforeDelete($record)
    {
        return ($record instanceof Model)
            ? true
            : 'Could not save the object';
    }

    /**
     * @return string|null
     */
    protected function getPrimaryKey()
    {
        $routeContext = RouteContext::fromRequest($this->request);
        $route = $routeContext->getRoute();

        return $route->getArgument($this->primaryKey, $this->getProperty($this->primaryKey));
    }
}
