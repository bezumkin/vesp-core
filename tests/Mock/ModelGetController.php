<?php

namespace Vesp\CoreTests\Mock;

use Vesp\Models\User;

class ModelGetController extends \Vesp\Controllers\ModelGetController
{
    protected string $model = User::class;
}
