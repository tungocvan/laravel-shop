<?php

namespace Modules\Website\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\System\Services\SettingsService;

class ProductSettingsController extends Controller
{
    public const FILTERS = [
        'category' => 'Nhóm dược phẩm',
        'supplier' => 'Nhà cung cấp',
        'product_type' => 'Loại sản phẩm',
        'circular_group' => 'Nhóm thuốc theo thông tư',
    ];

    public function index(SettingsService $settings): View
    {
        return view('Website::pages.admin.product-settings.index', [
            'filters' => self::FILTERS,
            'visibility' => array_replace(array_fill_keys(array_keys(self::FILTERS), true), (array) $settings->get('website.pharma_catalog.filters', [])),
        ]);
    }

    public function update(Request $request, SettingsService $settings): RedirectResponse
    {
        $request->validate(['filters' => ['sometimes', 'array'], 'filters.*' => ['in:1']]);
        $selected = array_intersect_key((array) $request->input('filters', []), self::FILTERS);
        $visibility = [];
        foreach (self::FILTERS as $key => $label) {
            $visibility[$key] = isset($selected[$key]);
        }
        $settings->set('website.pharma_catalog.filters', $visibility, 'website', 'json');

        return redirect()->route('admin.product.settings')->with('success', 'Đã lưu cấu hình bộ lọc Pharma.');
    }
}
