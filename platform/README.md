# Platform commands trong project

## Build tự động

```bash
./platform/build.sh
```

Script tự phát hiện:

- PHP/Laravel/Blade thay đổi → build `app`, `queue`, `scheduler`; recreate `web`.
- JS/CSS/Vite thay đổi → build `app` và `web`.
- Socket.IO thay đổi → chỉ build `socket`.
- Dockerfile/Compose/docker config thay đổi → build toàn bộ service đang bật.
- `.env` thay đổi → recreate runtime container, không build image.
- Không thay đổi → không build.

## Tùy chọn

```bash
./platform/build.sh --dry-run
./platform/build.sh --status
./platform/build.sh --all
./platform/build.sh --no-cache
./platform/build.sh --reset
```

Trạng thái được lưu tại:

```text
.docker-platform/state/
```

Không commit thư mục này lên Git.
