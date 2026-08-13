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

Project đã có service chuyên dụng trong `compose.yaml`:

```yaml
queue-admission-documents:
  build: *app-build
  restart: unless-stopped
  command:
    - php
    - artisan
    - queue:work
    - --queue=admission-documents
    - --sleep=${ADMISSION_QUEUE_SLEEP:-2}
    - --tries=${ADMISSION_QUEUE_TRIES:-3}
    - --timeout=${ADMISSION_QUEUE_TIMEOUT:-180}
    - --max-jobs=${ADMISSION_QUEUE_MAX_JOBS:-100}
    - --max-time=${ADMISSION_QUEUE_MAX_TIME:-3600}
```

Worker dùng cùng image PHP, cùng `.env`, cùng `app_storage` và cùng thư mục `Modules`, vì vậy file DOCX/PDF do worker tạo sẽ được app nhìn thấy ngay.

Docker image `app` hiện cài sẵn `libreoffice-core`, `libreoffice-writer` và `libreoffice-calc`, nên worker có thể convert PDF khi admin chọn PDF.

Worker `admission-documents` nên bắt đầu với 1 container vì tạo DOCX/PDF có thể sử dụng nhiều CPU/RAM, đặc biệt khi LibreOffice convert PDF.

Các biến có thể điều chỉnh trong `.env`:

```dotenv
ADMISSION_QUEUE_SLEEP=2
ADMISSION_QUEUE_TRIES=3
ADMISSION_QUEUE_TIMEOUT=180
ADMISSION_QUEUE_MAX_JOBS=100
ADMISSION_QUEUE_MAX_TIME=3600
ADMISSION_QUEUE_MEMORY_LIMIT=768m
ADMISSION_QUEUE_CPU_LIMIT=1.0
```

## Enable / Disable Module

Không start/stop container từ thao tác Enable Module.

- Module OFF: worker vẫn online nhưng idle vì không có job mới.
- Module ON: queue được registry nhận diện và worker xử lý job ngay.
- System Queue Manager dùng Module config để biết queue nào đang được yêu cầu.

Cách này tránh process orphan, race condition, permission shell và phụ thuộc Docker socket trong Laravel container.

## Worker Health Probe

Nút **Kiểm tra worker** dispatch `QueueProbeJob` vào chính queue được chọn. Khi worker xử lý job, Queue Manager lưu thời điểm probe thành công trong cache.

Nếu probe không cập nhật và pending tăng, kiểm tra container:

```bash
docker compose ps queue-admission-documents
docker compose logs --tail=100 queue-admission-documents
```

## Deploy VPS Docker

Lần đầu sau khi pull code có service queue mới:

```bash
git pull origin main

docker compose config

docker compose build app queue queue-admission-documents

docker compose up -d app queue queue-admission-documents scheduler socket web db redis

docker compose exec app php artisan migrate --force
docker compose exec app php artisan optimize:clear
```

Kiểm tra worker:

```bash
docker compose ps queue-admission-documents
docker compose logs --tail=100 queue-admission-documents
```

Sau deploy source mới, worker là long-running process. Nếu chỉ bind-mount source mà không recreate container, chạy:

```bash
docker compose exec app php artisan queue:restart
```

Với image deployment, ưu tiên recreate worker:

```bash
docker compose up -d --build queue-admission-documents
```

## PM2 và Docker

Trên VPS cũ có thể tiếp tục dùng PM2 khi project chạy trực tiếp trên host. Khi production đã chuyển hoàn toàn sang Docker Compose, không cần PM2 chạy thêm worker `admission-documents` bên ngoài container, tránh xử lý cùng một queue bằng hai hệ quản lý process khác nhau.
