<?php

namespace Modules\Website\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Website\Models\Setting;
// php artisan db:seed --class="Modules\Website\database\Seeders\SettingSeeder"
class SettingSeeder extends Seeder
{
    public function run(): void
    {
        // Danh sách dữ liệu mẫu cho Header
        $settings = [
            [
                'key' => 'header.topbar.hotline',
                'value' => '0903971949',
                'group_name' => 'header',
                'type' => 'text',
                'label' => 'Hotline Topbar'
            ],
            [
                'key' => 'header.topbar.email',
                'value' => 'tungocvan@gmail.com',
                'group_name' => 'header',
                'type' => 'text',
                'label' => 'Email Hỗ trợ'
            ],
            [
                'key' => 'header.topbar.help_url',
                'value' => '/help',
                'group_name' => 'header',
                'type' => 'text',
                'label' => 'Link Trợ giúp'
            ],
            [
                'key' => 'header.topbar.order_tracking_url',
                'value' => 'account/orders',
                'group_name' => 'header',
                'type' => 'text',
                'label' => 'Link Theo dõi đơn hàng'
            ],
            [
                'key' => 'header.brand_name',
                'value' => 'FlexBiz',
                'group_name' => 'header',
                'type' => 'text',
                'label' => 'Tên thương hiệu'
            ],
        ];

        foreach ($settings as $item) {
            Setting::updateOrCreate(
                ['key' => $item['key']], // Tránh trùng lặp key khi chạy lại seeder
                [
                    'value' => $item['value'],
                    'group_name' => $item['group_name'],
                    'type' => $item['type'],
                    'label' => $item['label'],
                ]
            );
        }
    }
}
