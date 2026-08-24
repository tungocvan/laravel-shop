<?php

use Illuminate\Support\Facades\Route;
use Modules\Request\Http\Controllers\Api\V1\RequestDecisionController;
use Modules\Request\Http\Controllers\Api\V1\RequestInboxController;
use Modules\Request\Http\Controllers\Api\V1\RequestSubmissionController;

Route::middleware('auth:sanctum')->prefix('request/v1')->name('request.api.v1.')->group(function (): void {
    Route::post('/requests/{publicId}/submit', RequestSubmissionController::class)->whereUlid('publicId')->middleware('throttle:request-submit')->name('requests.submit');
    Route::get('/inbox', RequestInboxController::class)->name('inbox');
    Route::post('/tasks/{publicId}/decisions', RequestDecisionController::class)->whereUlid('publicId')->middleware('throttle:request-decide')->name('tasks.decide');
});
