<?php

namespace Vesp\CoreTests\Units\Models;

use Illuminate\Database\Eloquent\Relations\Relation;
use Vesp\Models\User;
use Vesp\Models\UserRole;
use Vesp\CoreTests\TestCase;

class UserTest extends TestCase
{
    public function testHasScope(): void
    {
        $role = new UserRole(['title' => 'test', 'scope' => ['global_scope', 'local_scope/get']]);
        $user = new User(['username' => 'username', 'passwrod' => 'password', 'role_id' => 1]);
        $user->setRelation('role', $role);

        self::assertTrue($user->hasScope('global_scope'));
        self::assertTrue($user->hasScope('global_scope/anything'));
        self::assertTrue($user->hasScope('local_scope/get'));
        self::assertFalse($user->hasScope('local_scope'));
        self::assertFalse($user->hasScope('local_scope/anything'));
    }

    public function testSetAttribute(): void
    {
        $model = new User();
        $model->setAttribute('username', 'username');
        $model->setAttribute('password', 'password');

        self::assertEquals('username', $model->username);
        self::assertNotEquals('password', $model->password);
    }

    public function testVerifyPassword(): void
    {
        $model = new User(['password' => 'test']);
        self::assertTrue($model->verifyPassword('test'));
    }

    public function testRole(): void
    {
        $model = new User();
        self::assertInstanceOf(Relation::class, $model->role());
    }
}
