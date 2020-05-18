<?php

namespace Vesp\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $username
 * @property string $password
 * @property int $role_id
 * @property bool $active
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property-read UserRole $role
 */
class User extends Model
{
    protected $fillable = ['username', 'password', 'role_id', 'active'];
    protected $hidden = ['password'];
    protected $casts = ['active' => 'boolean'];

    /**
     * @param string $key
     * @param mixed $value
     *
     * @return mixed|void
     */
    public function setAttribute($key, $value)
    {
        if ($key === 'password') {
            $value = password_hash($value, PASSWORD_DEFAULT);
        }
        parent::setAttribute($key, $value);
    }


    /**
     * @param $password
     *
     * @return bool
     */
    public function verifyPassword($password)
    {
        return password_verify($password, $this->getAttribute('password'));
    }

    /**
     * @param array|string $scopes
     *
     * @return bool
     */
    public function hasScope($scopes)
    {
        if (!is_array($scopes)) {
            $scopes = [$scopes];
        }
        $user_scopes = $this->role->scope;

        foreach ($scopes as $scope) {
            if (strpos($scope, '/') !== false) {
                if (!in_array($scope, $user_scopes, true) && !in_array(preg_replace('#/.*#', '', $scope), $user_scopes, true)) {
                    return false;
                }
            } elseif (!in_array($scope, $user_scopes, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return BelongsTo
     */
    public function role()
    {
        return $this->belongsTo(UserRole::class);
    }
}
