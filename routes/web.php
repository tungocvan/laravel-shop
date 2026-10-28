<?php

use App\Http\Controllers\Demo\HumanResourcesController;
use App\Http\Controllers\Demo\SalesEmployeesController;
use App\Http\Controllers\Demo\DebtManagementController;
use App\Http\Controllers\Demo\DebtManagementDatasetController;
use App\Http\Controllers\Demo\DebtManagementMenuController;
use App\Http\Controllers\Demo\SharedSidebarMenuController;
use App\Http\Controllers\Demo\SalesDemoDatasetController;
use App\Http\Controllers\Demo\WarehouseInventoryController;
use App\Http\Controllers\Demo\WarehouseDemoDatasetController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\System\Services\ApplicationRootRedirectService;

Route::get('/demo/quan-ly-nhan-su', HumanResourcesController::class)->name('demo.human-resources');

// Standalone debt management demo (one GET route, browser-only persistence).
Route::get('/demo/quan-ly-cong-no', DebtManagementController::class)
    ->name('demo.debt-management');

Route::get('/demo/quan-ly-cong-no/menu/presets', [DebtManagementMenuController::class, 'presets'])->name('demo.debt-management.menu.presets');
Route::get('/demo/quan-ly-cong-no/menu/presets/{preset}', [DebtManagementMenuController::class, 'preset'])->name('demo.debt-management.menu.presets.show');
Route::delete('/demo/quan-ly-cong-no/menu/presets/{preset}', [DebtManagementMenuController::class, 'deletePreset'])->middleware('throttle:5,1')->name('demo.debt-management.menu.presets.delete');
Route::put('/demo/quan-ly-cong-no/menu/presets/{preset}', [DebtManagementMenuController::class, 'updatePreset'])->middleware('throttle:5,1')->name('demo.debt-management.menu.presets.update');
Route::post('/demo/quan-ly-cong-no/menu/presets', [DebtManagementMenuController::class, 'savePreset'])->middleware('throttle:5,1')->name('demo.debt-management.menu.presets.store');

Route::get('/demo/quan-ly-cong-no/menu', [DebtManagementMenuController::class, 'show'])->name('demo.debt-management.menu');
Route::post('/demo/quan-ly-cong-no/menu', [DebtManagementMenuController::class, 'store'])->middleware('throttle:5,1')->name('demo.debt-management.menu.store');

Route::get('/demo/quan-ly-cong-no/datasets', [DebtManagementDatasetController::class, 'index'])->name('demo.debt-management.datasets');
Route::get('/demo/quan-ly-cong-no/datasets/{dataset}', [DebtManagementDatasetController::class, 'show'])->name('demo.debt-management.datasets.show');
Route::post('/demo/quan-ly-cong-no/datasets', [DebtManagementDatasetController::class, 'store'])->middleware('throttle:5,1')->name('demo.debt-management.datasets.store');

// Standalone demo: a single GET route, with browser-only persistence.
Route::get('/demo/doanh-so-nhan-vien', SalesEmployeesController::class)
    ->name('demo.sales-employees');

Route::get('/demo/doanh-so-nhan-vien/datasets', [SalesDemoDatasetController::class, 'index'])->name('demo.sales-employees.datasets');
Route::get('/demo/doanh-so-nhan-vien/datasets/{dataset}', [SalesDemoDatasetController::class, 'show'])->name('demo.sales-employees.datasets.show');
Route::post('/demo/doanh-so-nhan-vien/datasets', [SalesDemoDatasetController::class, 'store'])->middleware('throttle:5,1')->name('demo.sales-employees.datasets.store');

Route::get('/demo/quan-ly-xuat-nhap-ton', WarehouseInventoryController::class)
    ->name('demo.warehouse-inventory');

// Public demo datasets are read-only. Server backups require an operator-only secret.
Route::get('/demo/quan-ly-xuat-nhap-ton/datasets', [WarehouseDemoDatasetController::class, 'index'])
    ->name('demo.warehouse-inventory.datasets');
Route::get('/demo/quan-ly-xuat-nhap-ton/datasets/{dataset}', [WarehouseDemoDatasetController::class, 'show'])
    ->name('demo.warehouse-inventory.datasets.show');

Route::post('/demo/quan-ly-xuat-nhap-ton/datasets', [WarehouseDemoDatasetController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('demo.warehouse-inventory.datasets.store');


// Route-cache-safe shared sidebar endpoints for two standalone demos.
Route::prefix('/demo/{demo}/menu')->group(function () {
    Route::get('/', [SharedSidebarMenuController::class, 'show'])->where('demo', 'doanh-so-nhan-vien|quan-ly-xuat-nhap-ton');
    Route::post('/', [SharedSidebarMenuController::class, 'store'])->middleware('throttle:5,1')->where('demo', 'doanh-so-nhan-vien|quan-ly-xuat-nhap-ton');
    Route::get('/presets', [SharedSidebarMenuController::class, 'index'])->where('demo', 'doanh-so-nhan-vien|quan-ly-xuat-nhap-ton');
    Route::get('/presets/{preset}', [SharedSidebarMenuController::class, 'preset'])->where('demo', 'doanh-so-nhan-vien|quan-ly-xuat-nhap-ton');
    Route::post('/presets', [SharedSidebarMenuController::class, 'savePreset'])->middleware('throttle:5,1')->where('demo', 'doanh-so-nhan-vien|quan-ly-xuat-nhap-ton');
    Route::delete('/presets/{preset}', [SharedSidebarMenuController::class, 'deletePreset'])->middleware('throttle:5,1')->where('demo', 'doanh-so-nhan-vien|quan-ly-xuat-nhap-ton');
});

Route::fallback(function (Request $request) {
    if ($request->path() === '/') {
        $routeName = app(ApplicationRootRedirectService::class)->configuredRoute();

        return redirect()->route($routeName);
    }

    abort(404);
});
