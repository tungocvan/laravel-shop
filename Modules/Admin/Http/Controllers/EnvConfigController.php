<?php

namespace Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;

use Livewire\Mechanisms\ComponentRegistry;

class EnvConfigController extends Controller
{
    public function index()
    {
        $registry = app(ComponentRegistry::class);

        // Danh sách các tab dự kiến
        $rawTabs = [
            ['id' => 'database', 'label' => 'Cơ sở dữ liệu', 'icon' => 'M4 7v10c0 2.21 3.58 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.58 4 8 4s8-1.79 8-4M4 7c0-2.21 3.58-4 8-4s8 1.79 8 4m0 5c0 2.21-3.58 4-8 4s-8-1.79-8-4', 'component' => 'admin.settings.database-config'],
            ['id' => 'mail', 'label' => 'Cấu hình Email', 'icon' => 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z', 'component' => 'admin.settings.mail-config'],
            ['id' => 'advanced', 'label' => 'Hệ thống & Queue', 'icon' => 'M13 10V3L4 14h7v7l9-11h-7z', 'component' => 'admin.settings.advanced-config'],
            ['id' => 'payment', 'label' => 'Thanh toán (Momo)', 'icon' => 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z', 'component' => 'admin.settings.momo-config'],
            ['id' => 'storage', 'label' => 'Lưu trữ Cloud', 'icon' => 'M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z', 'component' => 'admin.settings.storage-config'],
            ['id' => 'social', 'label' => 'SEO & Social', 'icon' => 'M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.65l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1', 'component' => 'admin.settings.social-config']        
        ];

        $tabs = collect($rawTabs)->map(function ($tab) use ($registry) {
            // Kiểm tra Class tồn tại thực tế
            $tab['is_ready'] = !is_null($registry->getClass($tab['component']));
            return $tab;
        });

        return view('Admin::pages.settings.env', compact('tabs'));
    }

}
