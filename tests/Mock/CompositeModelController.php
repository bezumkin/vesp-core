<?php

namespace Vesp\CoreTests\Mock;

use Vesp\Controllers\ModelController;
use Vesp\Models\User;

class CompositeModelController extends ModelController
{
    protected string|array $primaryKey = ['id', 'active'];
    protected string $model = User::class;
}
