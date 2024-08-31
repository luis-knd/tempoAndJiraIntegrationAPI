<?php

use App\Http\Controllers\v1\Auth\AuthController;
use App\Http\Controllers\v1\Basic\HealthCheckController;
use App\Http\Controllers\v1\Basic\UserController;
use App\Http\Controllers\v1\Jira\JiraIssueController;
use App\Http\Controllers\v1\Jira\JiraProjectController;
use App\Http\Controllers\v1\Jira\JiraTeamController;
use App\Http\Controllers\v1\Jira\JiraUserController;
use App\Http\Controllers\v1\Tempo\TempoUserController;
use App\Http\Controllers\v1\Tempo\TimeEntryController;
use Illuminate\Support\Facades\Route;

Route::get('/health', [HealthCheckController::class, 'health']);

Route::group(['prefix' => 'auth'], static function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/reset-password', [AuthController::class, 'sendPassword']);
    Route::patch('/reset-password', [AuthController::class, 'resetPassword']);
    Route::middleware('auth:api')->patch('/password-update/{user}', [AuthController::class, 'passwordUpdate']);
});

Route::middleware('auth:api')->group(function () {
    Route::apiResource('users', UserController::class);

    Route::group(['prefix' => 'jira'], static function () {
        Route::apiResource('users', JiraUserController::class);
        Route::apiResource('teams', JiraTeamController::class);
        Route::apiResource('issues', JiraIssueController::class);
        Route::apiResource('projects', JiraProjectController::class);
    });

    Route::group(['prefix' => 'tempo'], static function () {
        Route::apiResource('users', TempoUserController::class);
        Route::apiResource('time-entries', TimeEntryController::class);
    });
});
