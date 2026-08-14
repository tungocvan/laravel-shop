<?php

use Illuminate\Support\Facades\Route;
use Modules\Ebook\Http\Controllers\EbookController;

Route::middleware(['web', 'auth:admin'])
    ->prefix('admin/ebook')
    ->name('admin.ebook.')
    ->group(function (): void {
        Route::get('/', [EbookController::class, 'index'])
            ->middleware('permission:ebook.view,admin')
            ->name('index');

        Route::get('/document/{document}', [EbookController::class, 'show'])
            ->middleware('permission:ebook.view,admin')
            ->whereNumber('document')
            ->name('document.show');

        Route::get('/document/{document}/asset', [EbookController::class, 'asset'])
            ->middleware('permission:ebook.view,admin')
            ->whereNumber('document')
            ->name('asset');
    });
