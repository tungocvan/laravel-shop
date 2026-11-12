# Laravel route cache và PHP-FPM OPcache — Docker deployment contract

## Incident 2026-11-11

Khi `Modules/ClientPortal/Applications/LoanManagement/routes.php` chỉ khai báo route bên trong điều kiện `config('modules.registry.LoanManagement.enabled')`, lệnh `route:cache` chạy lúc module tắt sẽ tạo cache thiếu `client.loans.dashboard`. Bật module sau đó không tự tái tạo route cache; ClientPortal gọi `route('client.loans.dashboard')` gây HTTP 500. Trong runtime có `opcache.validate_timestamps=0`, PHP-FPM có thể tiếp tục sử dụng PHP bytecode cũ sau khi thay file/cache. Production đã được khôi phục thủ công và người dùng xác nhận UI PASS; điều đó **không** đồng nghĩa bản sửa source đã được deploy.

## Invariants cho dự án mới

1. Route có thể được gọi bằng tên từ một UI/manifest đang hoạt động phải được đăng ký ổn định khi Laravel bootstrap; không bọc khai báo route bằng cờ `enabled` thay đổi ở runtime. Middleware/policy xử lý module disabled và quyền tại **request time**.
2. Không tạo `config:cache` hoặc `route:cache` từ cấu hình build-image khác với `.env` của project đích. `.env` runtime được mount riêng theo Compose project.
3. Khi thay đổi source, routes hoặc config, cần xác định source nằm trong **image** hay **bind mount**; `git pull` trên host không chứng minh container đã chạy source mới.
4. Sau khi triển khai code mới, refresh đúng Laravel cache liên quan và đảm bảo PHP-FPM sử dụng bytecode mới. Với `opcache.validate_timestamps=0`, việc chỉ chạy Artisan CLI không tự làm mới OPcache của PHP-FPM. Không tự ý restart/reload Production; operator xác minh process/container và phê duyệt thao tác.
5. Khi bật/tắt module, kiểm tra effective `config('modules.registry')`, dependency, permission, route và HTTP/UI. Không dùng `optimize:clear` đại trà như cách che lỗi kiến trúc.

## Audit source hiện tại (phạm vi xác nhận)

- `Dockerfile`: Composer production dependencies và Vite build trong các stage riêng; không có lệnh `php artisan route:cache` hoặc `config:cache` trong Dockerfile đã kiểm tra.
- `docker/entrypoint.sh`: tạo runtime directories và storage link, rồi `exec "$@"`; không tạo route/config cache.
- `compose.yaml`: `.env` và `Modules` được bind mount vào `app`; `storage` dùng named volume. Không suy luận image hoặc PHP-FPM đã nhận code mới chỉ từ host Git.
- `docker/php/opcache.ini`: `opcache.validate_timestamps=0` là cấu hình Production có chủ đích, yêu cầu deploy lifecycle phù hợp.
- LoanManagement: fix route guard ở branch `fix/loanmanagement-stable-pwa-routes-20261111`; middleware `client.application:loans` vẫn bảo vệ truy cập.

## Quy trình operator cho project mới (không tự chạy trên Production)

1. Kiểm tra project directory, `docker compose -p <project> ps --all`, bind mounts, runtime `.env` và effective config (không in secrets).
2. Build/reconcile theo tài liệu vận hành riêng, chỉ sau phê duyệt; không mang cache được tạo từ project khác sang.
3. Tạo/refresh đúng cache trong container của project **sau** khi runtime config sẵn sàng, dùng helper `./run-docker-artisan.sh` và lệnh cụ thể đã review.
4. Đảm bảo PHP-FPM nạp code mới theo cơ chế deploy đã duyệt; xác minh HTTP và log sau thao tác. Nếu thất bại, rollback về image/source/cache checkpoint trước đó theo topology đã xác nhận.
5. Xác nhận module enabled/disabled, named route, authorization, UI; không đánh dấu PASS chỉ vì `route:list` trả về route.

## Giới hạn và bước tiếp theo

Đây là guardrail cho các bản build mới, **không** phải một script tự động thay đổi Production. Cần audit riêng toàn bộ route provider/module registration và deployment scripts trước khi áp dụng thay đổi hạ tầng rộng. Focused/module tests và UI acceptance vẫn là gate bắt buộc trước merge. Không sửa `.env`, DB, queue, Docker services hoặc untracked overlays trong batch này.
