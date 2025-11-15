<?php

use App\Http\Controllers\Api\VideoController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

/**
 * Video API endpoints.
 *
 * Routes:
 * - POST   /api/videos              - Initiate upload (returns pre-signed URL)
 * - POST   /api/videos/{id}/complete - Complete upload, start processing
 * - GET    /api/videos              - List videos
 * - GET    /api/videos/{id}         - Get video details
 * - PUT    /api/videos/{id}         - Update video metadata
 * - DELETE /api/videos/{id}         - Delete video
 *
 * Authentication: All routes require 'auth:sanctum' middleware
 * Rate Limiting: All routes limited to 60 requests per minute
 */
Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    // Video resource routes
    Route::apiResource('videos', VideoController::class);

    // Video upload completion endpoint
    Route::post('videos/{video}/complete', [VideoController::class, 'complete'])
        ->name('videos.complete');
});

/**
 * Public API endpoints (no authentication required).
 *
 * These endpoints allow public access to published videos and their metadata.
 */
Route::middleware(['throttle:100,1'])->group(function () {
    // Get published videos list
    Route::get('videos', [VideoController::class, 'index'])
        ->name('videos.index');

    // Get video details
    Route::get('videos/{video}', [VideoController::class, 'show'])
        ->name('videos.show');
});
