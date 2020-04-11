<?php

namespace Vesp\Tests\Mock;

use Exception;
use Vesp\Controllers\ModelController;
use Vesp\Models\User;

class ScopedModelController extends ModelController
{
    protected $model = User::class;
    protected $scope = 'users';

    protected function beforeSave($record)
    {
        if ($this->getProperty('test_exception')) {
            /** @noinspection PhpUnhandledExceptionInspection */
            throw new Exception('Test Exception');
        }

        return parent::beforeSave($record);
    }
}
