<?php

namespace Vesp\Tests\Units\Models;

use Illuminate\Database\Eloquent\Relations\Relation;
use Vesp\Models\User;
use Vesp\Models\UserRole;
use Vesp\Tests\TestCase;

class UserTest extends TestCase
{

    public function testHasScope()
    {
        $role = new UserRole(['title' => 'test', 'scope' => ['global_scope', 'local_scope/get']]);
        $user = new User(['username' => 'username', 'passwrod' => 'password', 'role_id' => 1]);
        $user->setRelation('role', $role);

        $this->assertTrue($user->hasScope('global_scope'));
        $this->assertTrue($user->hasScope('global_scope/anything'));
        $this->assertTrue($user->hasScope('local_scope/get'));
        $this->assertFalse($user->hasScope('local_scope'));
        $this->assertFalse($user->hasScope('local_scope/anything'));
    }

    public function testSetAttribute()
    {
        $model = new User();
        $model->setAttribute('username', 'username');
        $model->setAttribute('password', 'password');

        $this->assertEquals('username', $model->username);
        $this->assertNotEquals('password', $model->password);
    }

    public function testVerifyPassword()
    {
        $model = new User(['password' => 'test']);
        $this->assertTrue($model->verifyPassword('test'));
    }

    public function testRole()
    {
        $model = new User();
        $this->assertInstanceOf(Relation::class, $model->role());
    }
}
