<?php

declare(strict_types=1);

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
     * @return mixed|void
     */
    public function setAttribute($key, $value)
    {
        if ($key === 'password') {
            $value = password_hash($value, PASSWORD_DEFAULT);
        }
        parent::setAttribute($key, $value);
    }

    public function verifyPassword(string $password): bool
    {
        return password_verify($password, $this->getAttribute('password'));
    }

    /**
     * @param array|string $scopes
     * @return bool
     */
    public function hasScope($scopes): bool
    {
        if (!is_array($scopes)) {
            $scopes = [$scopes];
        }
        $user = $this->role->scope;

        foreach ($scopes as $scope) {
            if (strpos($scope, '/') !== false) {
                if (!in_array($scope, $user, true) && !in_array(preg_replace('#/.*#', '', $scope), $user, true)) {
                    return false;
                }
            } elseif (!in_array($scope, $user, true)) {
                return false;
            }
        }

        return true;
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(UserRole::class);
    }
}
