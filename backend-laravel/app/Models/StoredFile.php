<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoredFile extends Model
{
    use HasFactory;

    protected $table = 'StoredFile';
    public const CREATED_AT = 'createdAt';
    public const UPDATED_AT = 'updatedAt';

    protected $fillable = [
        'folderId',
        'source',
        'kind',
        'name',
        'originalName',
        'ext',
        'mimeType',
        'size',
        'relativePath',
        'url',
        'createdById',
        'createdByName',
    ];

    public function folder(): BelongsTo
    {
        return $this->belongsTo(FileFolder::class, 'folderId');
    }
}
