<?php

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


// Shared sidebar configuration API; demo data and browser state remain isolated per slug.
foreach (['sales-employees' => 'doanh-so-nhan-vien', 'warehouse-inventory' => 'quan-ly-xuat-nhap-ton'] as $slug => $segment) {
    Route::get('/demo/'.$segment.'/menu', fn () => app(SharedSidebarMenuController::class)->show($slug));
    Route::post('/demo/'.$segment.'/menu', fn (\Illuminate\Http\Request $request) => app(SharedSidebarMenuController::class)->store($request, $slug))->middleware('throttle:5,1');
    Route::get('/demo/'.$segment.'/menu/presets', fn () => app(SharedSidebarMenuController::class)->index($slug));
    Route::get('/demo/'.$segment.'/menu/presets/{preset}', fn (string $preset) => app(SharedSidebarMenuController::class)->preset($slug, $preset));
    Route::post('/demo/'.$segment.'/menu/presets', fn (\Illuminate\Http\Request $request) => app(SharedSidebarMenuController::class)->savePreset($request, $slug))->middleware('throttle:5,1');
    Route::delete('/demo/'.$segment.'/menu/presets/{preset}', fn (\Illuminate\Http\Request $request, string $preset) => app(SharedSidebarMenuController::class)->deletePreset($request, $slug, $preset))->middleware('throttle:5,1');
}

Route::fallback(function (Request $request) {
    if ($request->path() === '/') {
        $routeName = app(ApplicationRootRedirectService::class)->configuredRoute();

        return redirect()->route($routeName);
    }

    abort(404);
});
