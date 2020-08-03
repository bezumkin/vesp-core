<?php

namespace Vesp\CoreTests\Units\Models;

use Illuminate\Database\Eloquent\Relations\Relation;
use Vesp\Models\UserRole;
use Vesp\CoreTests\TestCase;

class UserRoleTest extends TestCase
{
    public function testUsers(): void
    {
        $model = new UserRole();
        self::assertInstanceOf(Relation::class, $model->users());
    }
}
