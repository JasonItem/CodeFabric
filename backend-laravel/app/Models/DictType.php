<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DictType extends Model
{
    use HasFactory;

    protected $table = 'DictType';
    public const CREATED_AT = 'createdAt';
    public const UPDATED_AT = 'updatedAt';

    protected $fillable = ['name', 'code', 'description', 'status', 'sort'];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(DictItem::class, 'dictTypeId');
    }
}
