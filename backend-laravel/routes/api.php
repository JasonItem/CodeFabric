<?php

use App\Support\ApiResponse;
use Illuminate\Support\Facades\Route;

Route::get('/ping', fn () => ApiResponse::success(['pong' => true]));
