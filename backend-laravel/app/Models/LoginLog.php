<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoginLog extends Model
{
    use HasFactory;

    protected $table = 'LoginLog';

    public $timestamps = false;

    protected $fillable = [
        'userId',
        'username',
        'client',
        'device',
        'browser',
        'os',
        'ip',
        'location',
        'userAgent',
        'success',
        'message',
        'createdAt',
    ];

    protected $casts = [
        'success' => 'boolean',
        'createdAt' => 'datetime',
    ];
}
