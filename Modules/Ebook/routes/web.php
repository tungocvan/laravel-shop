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
    });
