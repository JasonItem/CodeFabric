<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    use HasFactory;

    protected $table = 'Role';
    public const CREATED_AT = 'createdAt';
    public const UPDATED_AT = 'updatedAt';

    protected $fillable = ['name', 'code', 'description'];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(AdminUser::class, 'UserRole', 'roleId', 'userId');
    }

    public function menus(): BelongsToMany
    {
        return $this->belongsToMany(Menu::class, 'RoleMenu', 'roleId', 'menuId');
    }
}
