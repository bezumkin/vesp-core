<?php

namespace Vesp\Tests\Units\Models;

use Illuminate\Database\Eloquent\Relations\Relation;
use Vesp\Models\UserRole;
use Vesp\Tests\TestCase;

class UserRoleTest extends TestCase
{
    public function testUsers()
    {
        $model = new UserRole();
        $this->assertInstanceOf(Relation::class, $model->users());
    }
}
