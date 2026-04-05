<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Menu extends Model
{
    use HasFactory;

    protected $table = 'Menu';
    public const CREATED_AT = 'createdAt';
    public const UPDATED_AT = 'updatedAt';

    protected $fillable = [
        'parentId',
        'name',
        'path',
        'component',
        'icon',
        'type',
        'permissionKey',
        'sort',
        'visible',
    ];

    protected $casts = [
        'visible' => 'boolean',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parentId');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parentId');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'RoleMenu', 'menuId', 'roleId');
    }
}
