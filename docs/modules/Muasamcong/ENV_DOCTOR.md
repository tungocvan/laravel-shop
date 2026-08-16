# Muasamcong ENV Doctor

## Mục tiêu

Trang quản trị:

```text
/admin/muasamcong/config
```

có ENV Doctor để kiểm tra các biến `MUASAMCONG_*` bắt buộc trong `.env`.

## Biến được kiểm tra

```text
MUASAMCONG_ORIGIN
MUASAMCONG_VERIFY_SSL
MUASAMCONG_TIMEOUT
MUASAMCONG_USER_AGENT
MUASAMCONG_SMART_TOKEN
MUASAMCONG_SESSION_COOKIE
MUASAMCONG_PRICING_ENDPOINT
MUASAMCONG_CONTRACTOR_ENDPOINT
MUASAMCONG_PORTAL_REFERER
MUASAMCONG_PRICING_REFERER
MUASAMCONG_PAGE_SIZE
```

ENV Doctor chỉ kiểm tra key có tồn tại hay không. Token/cookie có thể tồn tại với giá trị rỗng; hệ thống không tự sinh credential.

## Local / VPS thường

- Mở trang config sẽ chỉ scan `.env`, không tự ghi file.
- Nút **Kiểm tra lại** thực hiện scan read-only.
- Khi thiếu biến, nút **Bổ sung biến còn thiếu** sẽ thêm đúng các key chưa tồn tại với giá trị mặc định.
- Giá trị của key đã tồn tại không bị ghi đè.
- Sau khi repair thành công, ứng dụng chạy `config:clear`.

## Docker VPS

Docker runtime được nhận diện qua `/.dockerenv` hoặc biến môi trường `container=docker`.

Trong Docker:

- ENV Doctor chỉ scan.
- Không cho phép `MuasamcongConfigService::update()` ghi `.env` bên trong container.
- UI hiển thị danh sách biến thiếu và block copy-ready để cập nhật vào `.env` ở Docker host/source.
- Sau khi cập nhật `.env`, rebuild/redeploy container theo quy trình Docker đang sử dụng.

Ví dụ với Docker Compose:

```bash
docker compose build --no-cache
docker compose up -d
```

Sau deploy, mở lại `/admin/muasamcong/config` và bấm **Kiểm tra lại**.

## Lưu ý deployment hiện tại

Dockerfile hiện dùng:

```dockerfile
COPY . .
```

và `.dockerignore` hiện không loại `.env`. Vì vậy nếu `.env` tồn tại trong build context, nó có thể được đưa vào image.

Đây là lý do ENV Doctor không sửa `.env` bên trong container: thay đổi đó không bền khi image/container được tạo lại.

Về dài hạn, nên cân nhắc chuyển secret/runtime configuration sang cơ chế Docker runtime như `env_file`, environment injection hoặc secret manager thay vì bake `.env` vào image.
