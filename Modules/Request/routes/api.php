<?php

use Illuminate\Support\Facades\Route;
use Modules\Request\Http\Controllers\Api\V1\RequestActivationRetryController;
use Modules\Request\Http\Controllers\Api\V1\RequestAttachmentUploadController;
use Modules\Request\Http\Controllers\Api\V1\RequestCancellationController;
use Modules\Request\Http\Controllers\Api\V1\RequestCommentController;
use Modules\Request\Http\Controllers\Api\V1\RequestDecisionController;
use Modules\Request\Http\Controllers\Api\V1\RequestInboxController;
use Modules\Request\Http\Controllers\Api\V1\RequestResubmissionController;
use Modules\Request\Http\Controllers\Api\V1\RequestSubmissionController;
use Modules\Request\Http\Controllers\Api\V1\RequestTaskReassignmentController;
use Modules\Request\Http\Controllers\RequestAttachmentController;
use Modules\Request\Http\Middleware\UseRequestAuthorizationGuard;

Route::middleware(['auth:sanctum', UseRequestAuthorizationGuard::class.':admin'])->prefix('request/v1')->name('request.api.v1.')->group(function (): void {
    Route::post('/requests/{publicId}/submit', RequestSubmissionController::class)->whereUlid('publicId')->middleware('throttle:request-submit')->name('requests.submit');
    Route::post('/requests/{publicId}/resubmit', RequestResubmissionController::class)->whereUlid('publicId')->middleware('throttle:request-submit')->name('requests.resubmit');
    Route::post('/requests/{publicId}/cancel', RequestCancellationController::class)->whereUlid('publicId')->middleware('throttle:request-submit')->name('requests.cancel');
    Route::post('/requests/{publicId}/retry-activation', RequestActivationRetryController::class)->whereUlid('publicId')->middleware('throttle:request-decide')->name('requests.retry-activation');
    Route::post('/requests/{publicId}/comments', RequestCommentController::class)->whereUlid('publicId')->middleware('throttle:request-comment')->name('requests.comments.store');
    Route::post('/requests/{publicId}/attachments', RequestAttachmentUploadController::class)->whereUlid('publicId')->middleware('throttle:request-upload')->name('requests.attachments.store');
    Route::get('/requests/{requestPublicId}/attachments/{attachmentPublicId}', RequestAttachmentController::class)->whereUlid('requestPublicId')->whereUlid('attachmentPublicId')->middleware('throttle:request-download')->name('requests.attachments.download');
    Route::get('/inbox', RequestInboxController::class)->name('inbox');
    Route::post('/tasks/{publicId}/decisions', RequestDecisionController::class)->whereUlid('publicId')->middleware('throttle:request-decide')->name('tasks.decide');
    Route::post('/tasks/{publicId}/reassign', RequestTaskReassignmentController::class)->whereUlid('publicId')->middleware('throttle:request-decide')->name('tasks.reassign');
});
