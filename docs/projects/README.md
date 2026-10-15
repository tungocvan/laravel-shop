# Production project profiles

Mục tiêu: ghi nhận runtime Docker **một lần theo từng project** để AI debug Laravel/Livewire nhanh hơn, không cần khảo sát lại mọi lớp khi chưa có thay đổi.

## Thu thập

Từ **worktree kiểm thử** có script mới, chạy trên cùng VPS:

```bash
bash ./production-debug.sh --profile --project-dir /opt/projects/tnv
bash ./production-debug.sh --profile --project-dir /opt/projects/ntd
```

Script chỉ đọc Docker/Git, không chạy migration, deploy, restart, không đọc .env hoặc logs. Nó dùng Compose project = basename của project-dir và chọn app container theo Docker Compose labels. Không gọi `docker compose config` (có thể làm lộ secret). Output chỉ có metadata allowlisted.

Muốn lưu snapshot ra file ngoài production Git checkout:

```bash
bash ./production-debug.sh --profile --project-dir /opt/projects/tnv --output /tmp/tnv-profile.md
```

Output file chỉ được tạo khi đường dẫn chưa tồn tại; không tự ghi vào `docs/projects` hoặc commit trên Production. **Rà soát nội dung** trước khi chia sẻ hoặc commit. Không thêm raw logs, env, DB credentials, tokens, private key hoặc URL chứa token.

## Hồ sơ được duyệt trong Git

Sau khi operator gửi báo cáo đã rà soát, AI cập nhật trên **branch riêng**:

- `docs/projects/tnv.md`
- `docs/projects/ntd.md`
- `docs/projects/<site>.md`

Hồ sơ phải có thời điểm snapshot, đường dẫn project, Compose project, các service, image identity, mount destination, PHP/Laravel versions, cảnh báo chưa xác minh và thời điểm cần làm mới. Không được coi snapshot là runtime hiện tại nếu đã deploy/recreate/reload sau snapshot.

## Debug incident lần sau

1. Đọc `docs/PRODUCTION_DEBUG_PROMPT.md`, production guardrails và hồ sơ project tương ứng.
2. Đối chiếu ngày snapshot với lần deploy/container recreate gần nhất.
3. Nếu snapshot còn phù hợp, ưu tiên kiểm tra **route/exception/code** theo symptom, không lặp lại full Docker inventory.
4. Nếu snapshot cũ hoặc runtime thay đổi, chạy lại `--profile` và cập nhật hồ sơ qua PR.
5. Không suy ra HTTP/UI PASS từ container healthy; luôn xác minh route và logs cho incident thực tế.

**Giới hạn:** profile không chứng minh Git host HEAD trùng image code; mount có thể che image. CLI OPcache setting không chứng minh PHP-FPM OPcache. Kết quả UNKNOWN cần được xử lý trước khi đưa ra kết luận.
