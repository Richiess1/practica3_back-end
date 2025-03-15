<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\PostController;
use Illuminate\Support\Facades\Route;

// Authentication routes
Route::post('/v1/register', [AuthController::class, 'register']);
Route::post('/v1/login', [AuthController::class, 'login']);
Route::post('/v1/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

// Protected routes
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    Route::apiResource('posts', PostController::class)->only(['store', 'index']);
});
