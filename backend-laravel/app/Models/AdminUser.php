<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * 后台用户模型。
 */
class AdminUser extends Authenticatable
{
    use HasFactory;

    protected $table = 'AdminUser';
    public const CREATED_AT = 'createdAt';
    public const UPDATED_AT = 'updatedAt';

    protected $fillable = [
        'username',
        'passwordHash',
        'nickname',
        'status',
    ];

    protected $hidden = ['passwordHash'];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'UserRole', 'userId', 'roleId');
    }
}
