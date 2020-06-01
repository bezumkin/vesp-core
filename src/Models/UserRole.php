<?php

declare(strict_types=1);

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

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'role_id');
    }
}
