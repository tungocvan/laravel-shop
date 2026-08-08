<?php

use Illuminate\Support\Facades\Route;
use Modules\PromptEngine\Http\Controllers\PromptEngineController;

Route::get('/prompt-engine', [PromptEngineController::class, 'index'])
    ->name('prompt-engine.index');
