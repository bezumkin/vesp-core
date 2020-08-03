<?php

namespace Vesp\CoreTests\Mock;

use Illuminate\Database\Eloquent\Model;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Vesp\Controllers\ModelController;
use Vesp\Models\User;

class ScopedModelController extends ModelController
{
    protected $model = User::class;
    protected $scope = 'users';

    protected function beforeSave(Model $record): ?ResponseInterface
    {
        if ($this->getProperty('test_exception')) {
            throw new RuntimeException('Test Exception');
        }

        return parent::beforeSave($record);
    }
}
