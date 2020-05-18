<?php

namespace Vesp\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $title
 * @property array $scope
 *
 * @property-read User[] $users
 */
class UserRole extends Model
{
    protected $fillable = ['title', 'scope'];
    protected $casts = [
        'scope' => 'array',
    ];

    /**
     * @return HasMany
     */
    public function users()
    {
        return $this->hasMany(User::class, 'role_id');
    }
}
