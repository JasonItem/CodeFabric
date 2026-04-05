<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DictItem extends Model
{
    use HasFactory;

    protected $table = 'DictItem';
    public const CREATED_AT = 'createdAt';
    public const UPDATED_AT = 'updatedAt';

    protected $fillable = [
        'dictTypeId',
        'label',
        'value',
        'tagType',
        'tagClass',
        'status',
        'sort',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function dictType(): BelongsTo
    {
        return $this->belongsTo(DictType::class, 'dictTypeId');
    }
}
