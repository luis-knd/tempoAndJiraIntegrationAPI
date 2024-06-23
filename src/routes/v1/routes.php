<?php

use App\Http\Controllers\v1\Auth\AuthController;
use App\Http\Controllers\v1\HealthCheckController;
use App\Http\Controllers\v1\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/health', [HealthCheckController::class, 'health']);
Route::post('/login', [AuthController::class, 'login']);
Route::apiResource('users', UserController::class);
