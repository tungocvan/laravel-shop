<?php

use Illuminate\Support\Facades\Route;
use Modules\Request\Http\Controllers\Api\V1\RequestActivationRetryController;
use Modules\Request\Http\Controllers\Api\V1\RequestCancellationController;
use Modules\Request\Http\Controllers\Api\V1\RequestDecisionController;
use Modules\Request\Http\Controllers\Api\V1\RequestInboxController;
use Modules\Request\Http\Controllers\Api\V1\RequestResubmissionController;
use Modules\Request\Http\Controllers\Api\V1\RequestSubmissionController;
use Modules\Request\Http\Controllers\Api\V1\RequestTaskReassignmentController;

Route::middleware('auth:sanctum')->prefix('request/v1')->name('request.api.v1.')->group(function (): void {
    Route::post('/requests/{publicId}/submit', RequestSubmissionController::class)->whereUlid('publicId')->middleware('throttle:request-submit')->name('requests.submit');
    Route::post('/requests/{publicId}/resubmit', RequestResubmissionController::class)->whereUlid('publicId')->middleware('throttle:request-submit')->name('requests.resubmit');
    Route::post('/requests/{publicId}/cancel', RequestCancellationController::class)->whereUlid('publicId')->middleware('throttle:request-submit')->name('requests.cancel');
    Route::post('/requests/{publicId}/retry-activation', RequestActivationRetryController::class)->whereUlid('publicId')->middleware('throttle:request-decide')->name('requests.retry-activation');
    Route::get('/inbox', RequestInboxController::class)->name('inbox');
    Route::post('/tasks/{publicId}/decisions', RequestDecisionController::class)->whereUlid('publicId')->middleware('throttle:request-decide')->name('tasks.decide');
    Route::post('/tasks/{publicId}/reassign', RequestTaskReassignmentController::class)->whereUlid('publicId')->middleware('throttle:request-decide')->name('tasks.reassign');
});
