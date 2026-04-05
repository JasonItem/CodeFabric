<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OperationLog extends Model
{
    use HasFactory;

    protected $table = 'OperationLog';

    public $timestamps = false;

    protected $fillable = [
        'userId',
        'username',
        'module',
        'action',
        'method',
        'path',
        'statusCode',
        'success',
        'message',
        'ip',
        'location',
        'userAgent',
        'durationMs',
        'requestBody',
        'responseBody',
        'createdAt',
    ];

    protected $casts = [
        'success' => 'boolean',
        'createdAt' => 'datetime',
    ];
}
