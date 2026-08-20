<?php

return [
    'general' => [
        'application_name' => 'INAFO Client Portal',
        'short_name' => 'INAFO',
        'browser_title' => 'Đăng nhập · INAFO Client Portal',
        'theme_color' => '#0f172a',
        'background_color' => '#0f172a',
        'apple_title' => 'INAFO',
        'start_url' => '/my-apps',
        'display' => 'standalone',
    ],

    'login' => [
        'badge' => 'INAFO · Progressive Web App',
        'heading' => 'Một nơi để mở tất cả ứng dụng công việc của bạn.',
        'description' => 'Tra cứu, đồng bộ dữ liệu, quản lý danh sách quan tâm, lập bảng giá và sử dụng các ứng dụng được cấp quyền ngay trên điện thoại.',
        'show_intro_panel' => true,
        'back_to_website_text' => '← Về website',
        'web_mode_label' => 'Web App',
        'standalone_mode_label' => 'PWA đã cài đặt',
        'feature_cards' => [
            ['enabled' => true, 'title' => 'Cài như ứng dụng', 'description' => 'Mở nhanh từ màn hình chính.'],
            ['enabled' => true, 'title' => 'Phân quyền riêng', 'description' => 'Chỉ thấy chức năng được cấp.'],
            ['enabled' => true, 'title' => 'Tối ưu Mobile', 'description' => 'Giao diện dành cho thao tác nhanh.'],
            ['enabled' => true, 'title' => 'Queue nền', 'description' => 'Tiếp tục làm việc khi tác vụ đang xử lý.'],
        ],
    ],
];
