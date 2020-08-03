<?php

namespace Vesp\CoreTests\Mock;

use Illuminate\Database\Eloquent\Model;
use Psr\Http\Message\ResponseInterface;
use Vesp\Models\User;

class ModelController extends \Vesp\Controllers\ModelController
{
    protected $model = User::class;

    protected function beforeSave(Model $record): ?ResponseInterface
    {
        if ($this->getProperty('test_failure')) {
            return $this->failure('Test Failure!');
        }

        return parent::beforeSave($record);
    }

    protected function beforeDelete(Model $record): ?ResponseInterface
    {
        if ($this->getProperty('test_failure')) {
            return $this->failure('Test Failure!');
        }

        return parent::beforeDelete($record);
    }
}
