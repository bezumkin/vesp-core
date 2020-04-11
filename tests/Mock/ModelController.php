<?php

namespace Vesp\Tests\Mock;

use Vesp\Models\User;

class ModelController extends \Vesp\Controllers\ModelController
{
    protected $model = User::class;

    protected function beforeSave($record)
    {
        if ($this->getProperty('test_failure')) {
            return 'Test Failure!';
        }

        return parent::beforeSave($record);
    }

    protected function beforeDelete($record)
    {
        if ($this->getProperty('test_failure')) {
            return 'Test Failure!';
        }

        return parent::beforeDelete($record);
    }
}
