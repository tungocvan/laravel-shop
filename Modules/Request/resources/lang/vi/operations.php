<?php

return [
    'title' => 'Vận hành Đề nghị',
    'description' => 'Theo dõi các lỗi có thể xử lý lại và chỉ chạy các thao tác đã được hệ thống cho phép.',
    'empty' => 'Hiện không có lỗi nào cần xử lý lại.',
    'kind' => 'Loại thao tác',
    'target' => 'Đối tượng',
    'error' => 'Mã lỗi',
    'attempts' => 'Số lần thử',
    'updated_at' => 'Cập nhật',
    'retry' => 'Thử lại',
    'retry_started' => 'Đã gửi yêu cầu thử lại an toàn.',
    'allowlist_help' => 'Chỉ các thao tác nằm trong danh sách cho phép mới có thể chạy lại; hệ thống không hỗ trợ thực thi lệnh tùy ý.',
    'kinds' => [
        'stage_activation' => 'Kích hoạt cấp phê duyệt',
        'outbox_dispatch' => 'Phân phối outbox',
        'export_generation' => 'Tạo tệp xuất',
    ],
];
