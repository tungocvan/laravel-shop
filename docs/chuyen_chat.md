# Chuyển chat và tiếp tục Module

## Mục đích

File này là entrypoint ngắn khi người dùng mở chat mới và muốn tiếp tục công việc của một Module từ trạng thái đã lưu trong repository.

File này không dùng cho chuyển máy tính. Nếu người dùng đang chuyển giữa máy công ty và máy ở nhà, sử dụng:

```text
docs/chuyen_may.md
```

Canonical workflow vẫn là nguồn quy trình chính:

```text
docs/GITHUB_COLLABORATION_WORKFLOW.md
```

Nếu có mâu thuẫn, canonical workflow và repository reality được ưu tiên.

## Cách sử dụng

Gửi trong chat mới:

```text
Áp dụng `docs/chuyen_chat.md`
Module: Request
Mục tiêu hiện tại: HANDOFF
```

Có thể thay `Request` bằng Module khác.

Không cần dán lại prompt dài hoặc tóm tắt toàn bộ chat cũ. Repository, GitHub và handoff hiện tại phải được dùng để bootstrap lại chính xác.

## Input contract

### Module

`Module: <Tên Module>` xác định Module cần tiếp tục.

Phải xác minh chính xác:

```text
Modules/<Tên Module>
docs/modules/<Tên Module>
docs/modules/<Tên Module>/COLLABORATION_HANDOFF.md
```

Không tự chọn Module gần giống nếu source/docs không khớp.

### Mục tiêu hiện tại

`Mục tiêu hiện tại: HANDOFF` có nghĩa:

- tiếp tục từ handoff hiện hành
- đọc “Remaining work / Next authorized step” nhưng phải kiểm chứng lại
- không coi `HANDOFF` là tên phase, MR hoặc task
- không phân tích lại từ đầu nếu handoff còn hợp lệ
- không tự đặt tên hoặc đánh số MR/phase tiếp theo

Nếu `Mục tiêu hiện tại` là một nội dung cụ thể khác `HANDOFF`, coi đó là task hiện tại và đối chiếu task với handoff/source trước khi đề xuất.

## Execution contract

### 1. Đọc canonical workflow

Phải đọc toàn bộ:

```text
docs/GITHUB_COLLABORATION_WORKFLOW.md
.codex/standards/ADMIN_UI_STANDARD.md
```

Áp dụng đặc biệt mục 16 về bootstrap và bàn giao công việc theo Module, cùng các safety/test/merge gates applicable.

### 2. Kiểm tra quyền và bootstrap

Trước khi đề xuất hoặc thay đổi:

1. kiểm tra quyền repository bằng thao tác chỉ đọc
2. xác minh default branch và `origin/main` hiện tại
3. xác minh Module source
4. xác minh Module docs và handoff
5. đối chiếu branch, PR và checkpoint trên GitHub
6. xác định working scope: analysis, diagnose, review, implementation, acceptance hay merge preparation

Không dùng SHA, branch, PR, test result hoặc trạng thái từ chat trước theo giả định.

Nếu task có Admin UI, phải đọc `.codex/standards/ADMIN_UI_STANDARD.md` theo canonical workflow.

### 3. Đọc và kiểm chứng handoff

Nếu có:

```text
docs/modules/<Tên Module>/COLLABORATION_HANDOFF.md
```

phải:

- đọc toàn bộ
- đối chiếu checkpoint/branch/PR với GitHub
- kiểm tra source và tests liên quan
- phát hiện documentation drift
- tiếp tục từ next authorized step đã được kiểm chứng

Source/schema/config và trạng thái GitHub hiện tại được ưu tiên hơn handoff stale.

Nếu không có handoff, áp dụng đúng cây fallback tài liệu trong canonical workflow; không đọc toàn bộ docs một cách máy móc và không tự tạo handoff khi chưa được phê duyệt.

### 4. Báo cáo bắt buộc trước hành động

Báo ngắn gọn nhưng đầy đủ:

- repository/main checkpoint hiện tại
- Module source/docs/handoff đã xác minh
- phần đã hoàn thành
- trạng thái branch/PR/checkpoint
- test/UI/Git-clean evidence hiện có
- blocker và documentation drift
- post-merge acceptance
- production enablement boundary
- remaining work
- next authorized step

Phân biệt rõ:

- corrective/documentation closeout
- maintenance
- post-merge acceptance
- production enablement
- capability/MR/phase mới

Không tự đặt tên hoặc đánh số MR/phase tiếp theo. Chỉ sử dụng tên/số khi source, roadmap hoặc handoff hiện tại định nghĩa rõ. Nếu chưa có căn cứ, ghi:

```text
NOT DETERMINED
```

### 5. Đề xuất trước implementation

Nếu mục tiêu yêu cầu thay đổi:

- đề xuất scope/plan trước
- nêu file/component/tests/docs dự kiến
- nêu safety và backward-compatibility boundary
- chờ người dùng xác nhận

Không biến câu “áp dụng” hoặc `HANDOFF` thành quyền sửa code/merge/xóa branch.

## Test strategy theo vòng đời branch

Để giữ vòng lặp phát triển nhanh nhưng vẫn có regression gate đầy đủ, áp dụng quy ước sau:

### Feature branch / implementation checkpoint

- Chỉ chạy các test liên quan trực tiếp đến phạm vi vừa thay đổi.
- Với Unit tests, chỉ chạy các file/test suite trong `Tests/Unit` có liên quan đến feature hoặc bug đang xử lý.
- Không yêu cầu chạy toàn bộ test suite của Module ở mỗi checkpoint trên feature branch.
- Nếu focused test fail, dừng tại checkpoint và xử lý lỗi trước khi tiếp tục.
- Có thể chạy thêm test liên quan khi thay đổi chạm vào dependency dùng chung hoặc có nguy cơ regression rõ ràng.

Ví dụ:

```bash
php artisan test Modules/<Module>/Tests/Unit/<RelevantTest>.php
```

### Sau khi merge vào `main`

- Sau khi PR/feature branch đã merge vào `main`, mới chạy regression test toàn bộ Module.
- Phải cập nhật local `main` bằng `git pull --ff-only` trước khi chạy.
- Full Module regression là post-merge integration gate, dùng để xác nhận thay đổi đã tích hợp an toàn với toàn bộ Module.
- Nếu Module regression fail sau merge, phân loại lỗi là regression mới hay lỗi pre-existing trước khi quyết định corrective action.

Ví dụ:

```bash
git switch main
git pull --ff-only
php artisan test Modules/<Module>/Tests
```

Quy ước này không loại bỏ focused test bắt buộc trước merge và không thay thế các test/UI acceptance gate khác được yêu cầu riêng bởi task hoặc canonical workflow.

## Mutation boundary

Trước khi người dùng phê duyệt đề xuất, chưa được tự:

- sửa file hoặc code
- tạo branch/commit/PR
- merge hoặc xóa branch
- thay đổi runtime state
- enable Module trong production

Nếu người dùng phê duyệt implementation, trước tiên phải hướng dẫn `git switch` và `git pull --ff-only` đúng branch theo canonical workflow.

Trước khi tạo/merge PR, phải áp dụng handoff gates hiện hành trong canonical workflow.
