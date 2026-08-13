# System Queue Manager

## Mục tiêu

Queue Manager cung cấp registry chung cho các queue do từng Module khai báo, hiển thị pending/reserved/failed jobs và cho phép gửi probe để xác nhận worker đang xử lý queue.

Queue Manager **không spawn `queue:work` từ HTTP/Livewire**. Process lifecycle phải do Docker Compose, Supervisor, systemd hoặc PM2 quản lý.

## Khai báo queue trong Module

Mỗi Module có thể khai báo `queues` trong `config/module.php`:

```php
'queues' => [
    [
        'name' => 'admission-documents',
        'workers' => 1,
        'timeout' => 180,
        'tries' => 3,
        'sleep' => 2,
        'max_jobs' => 100,
        'max_time' => 3600,
        'description' => 'Tạo DOCX/PDF cho hồ sơ tuyển sinh đã duyệt.',
    ],
],
```

Khi Module có `enabled => false`, Queue Registry không hiển thị queue của Module đó. Khi bật lại, queue tự xuất hiện trong System Queue Manager.

## Docker Production

Khuyến nghị chạy worker như service riêng, không dùng PM2 bên trong Laravel container nếu deployment chính là Docker Compose.

```yaml
services:
  queue-default:
    build: .
    restart: unless-stopped
    command: php artisan queue:work --queue=default --sleep=2 --tries=3 --timeout=120 --max-jobs=500 --max-time=3600

  queue-admission-documents:
    build: .
    restart: unless-stopped
    command: php artisan queue:work --queue=admission-documents --sleep=2 --tries=3 --timeout=180 --max-jobs=100 --max-time=3600
```

Worker `admission-documents` nên bắt đầu với 1 replica vì tạo DOCX/PDF có thể sử dụng nhiều CPU/RAM, đặc biệt khi LibreOffice convert PDF.

## Enable / Disable Module

Không start/stop container từ thao tác Enable Module.

- Module OFF: worker có thể vẫn online nhưng idle vì không có job mới.
- Module ON: queue được registry nhận diện và worker có thể xử lý job ngay.
- System Queue Manager dùng Module config để biết queue nào đang được yêu cầu.

Cách này tránh process orphan, race condition, permission shell và phụ thuộc Docker socket trong Laravel container.

## Worker Health Probe

Nút **Kiểm tra worker** dispatch `QueueProbeJob` vào chính queue được chọn. Khi worker xử lý job, Queue Manager lưu thời điểm probe thành công trong cache.

Nếu probe không cập nhật và pending tăng, kiểm tra container/worker tương ứng.

## Deploy

Sau deploy source mới:

```bash
php artisan migrate --force
php artisan optimize:clear
php artisan queue:restart
```

Với Docker image deployment, ưu tiên recreate worker containers để worker nạp source mới.
