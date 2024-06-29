<?php

use App\Http\Controllers\v1\Auth\AuthController;
use App\Http\Controllers\v1\Basic\HealthCheckController;
use App\Http\Controllers\v1\Basic\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/health', [HealthCheckController::class, 'health']);
Route::post('/login', [AuthController::class, 'login']);
Route::apiResource('users', UserController::class);
