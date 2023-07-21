<?php

declare(strict_types=1);

namespace Vesp\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $user_id
 * @property int $file_id
 * @property bool $active
 * @property Carbon $created_at
 *
 * @property-read User $user
 * @property-read File $file
 */
class UserFile extends Model
{
    use Traits\CompositeKey;

    protected $keyType = 'array';
    protected $primaryKey = ['user_id', 'file_id'];
    protected $casts = [
        'active' => 'boolean',
        'created_at' => 'datetime',
    ];
    protected $fillable = ['active'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function file(): BelongsTo
    {
        return $this->belongsTo(File::class);
    }
}
