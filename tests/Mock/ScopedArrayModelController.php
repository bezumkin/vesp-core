<?php

namespace Vesp\CoreTests\Mock;

class ScopedArrayModelController extends ScopedModelController
{
    protected string|array $scope = ['users', 'admins'];
}
