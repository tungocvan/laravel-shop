# Chuyển máy đã cấu hình và tiếp tục Module

## Mục đích

File này là entrypoint ngắn khi người dùng chuyển qua lại giữa các máy tính đã cấu hình sẵn Git/GitHub và repository, ví dụ máy tính công ty và máy tính ở nhà, rồi muốn tiếp tục công việc của một Module.

File này không thay thế canonical workflow:

```text
docs/GITHUB_COLLABORATION_WORKFLOW.md
```

Nếu có mâu thuẫn, canonical workflow và repository reality được ưu tiên.

## Cách sử dụng

Gửi trong chat mới:

```text
Áp dụng `docs/chuyen_may.md`
Module: Request
Mục tiêu hiện tại: HANDOFF
```

Có thể thay `Request` bằng Module khác.

Nếu đã có output, có thể gửi thêm:

```text
Output git status:

[DÁN OUTPUT GIT STATUS]
```

Nếu người dùng chỉ gửi ba dòng gọi entrypoint mà chưa có output `git status`, phản hồi đầu tiên phải:

1. xác nhận đây là chuyển giữa hai máy đã cấu hình, không phải máy tính mới
2. yêu cầu đúng một lệnh:

```bash
git status
```

3. dừng và chờ output trước khi hướng dẫn fetch/switch/pull hoặc phân tích Module

## Input contract

### Module

`Module: <Tên Module>` xác định Module cần tiếp tục.

Phải xác minh chính xác:

```text
Modules/<Tên Module>
docs/modules/<Tên Module>
docs/modules/<Tên Module>/COLLABORATION_HANDOFF.md
```

Không tự chọn Module có tên gần giống nếu path không tồn tại.

### Mục tiêu hiện tại

`Mục tiêu hiện tại: HANDOFF` có nghĩa:

- tiếp tục từ handoff hiện hành của Module
- không coi `HANDOFF` là tên phase, MR hoặc capability
- đọc handoff rồi đối chiếu với source, tests, GitHub và `origin/main`
- chỉ đề xuất next authorized step được chứng minh
- không tự đặt tên hoặc đánh số MR/phase tiếp theo

Nếu `Mục tiêu hiện tại` chứa nội dung khác `HANDOFF`, coi đó là task hiện tại nhưng vẫn phải bootstrap và đối chiếu handoff trước khi đề xuất.

## Execution contract

### 1. Đọc workflow trước

Phải đọc toàn bộ:

```text
docs/GITHUB_COLLABORATION_WORKFLOW.md
```

Áp dụng đặc biệt:

- mục 17 cho chuyển giữa hai máy đã cấu hình
- mục 16 cho bootstrap và handoff theo Module
- các safety/test/merge gates applicable khác

### 2. Hiểu đúng bối cảnh chuyển máy

Mặc định:

- máy đích đã có repository
- GitHub authentication/remote đã được cấu hình
- output `git status` đến từ máy người dùng muốn tiếp tục làm việc
- mục tiêu trước tiên là bảo toàn local source rồi đồng bộ an toàn

Không hướng dẫn clone repository, tạo/copy SSH key, cài đặt máy mới hoặc chuyển `.env`/database/storage trừ khi người dùng nói rõ hoặc output chứng minh cần thiết.

### 3. Kiểm tra an toàn Git trước Module

Từ output người dùng và kiểm tra GitHub chỉ đọc, phải xác định:

- working tree clean/dirty
- current branch
- upstream tracking
- ahead/behind/diverged
- local-only commits
- branch head đã nằm trong `main` hay chưa
- `origin/main` checkpoint hiện tại

Quan hệ branch với upstream và quan hệ branch với `main` phải được đánh giá riêng.

Nếu output chưa đủ, chỉ yêu cầu các lệnh tối thiểu theo quy tắc số lượng lệnh trong canonical workflow. Không pull trước khi phân loại xong.

Stop gates:

- working tree dirty: dừng, không switch/pull
- local-only commit chưa được bảo toàn: dừng
- diverged: dừng, không merge/rebase theo phỏng đoán
- branch cũ đã merge và head là ancestor của `main`: an toàn, không push/pull riêng branch cũ

Khi an toàn và tiếp tục từ `main`, dùng:

```bash
git switch main
git pull --ff-only origin main
git rev-parse --short HEAD
git status --short
```

Chỉ đưa số lệnh cần thiết. Nếu đã ở `main`, không bắt buộc switch lại.

### 4. Chỉ tiếp tục Module sau khi Git gate PASS

Sau khi xác nhận:

- local HEAD bằng `origin/main` hoặc đúng active branch đã được phê duyệt
- working tree clean
- không bỏ lại local-only commit

thì mới thực hiện bootstrap Module:

1. kiểm tra quyền repository
2. xác minh Module source, docs, handoff, branch/PR/checkpoint và working scope
3. đọc handoff nếu tồn tại
4. đối chiếu source, tests và GitHub hiện tại
5. áp dụng cây fallback tài liệu trong canonical workflow nếu handoff không tồn tại

Không sử dụng SHA, branch, PR hoặc trạng thái từ chat cũ theo giả định.

### 5. Báo cáo bắt buộc

Trước khi đề xuất hành động, báo rõ:

- máy đích đã đồng bộ an toàn hay chưa
- current branch và checkpoint
- có local-only commit hay không
- phần Module đã hoàn thành
- trạng thái gates và blocker
- documentation drift
- post-merge acceptance
- production enablement boundary
- việc còn lại
- next authorized step

Không tự đặt tên hoặc đánh số MR/phase. Nếu source, roadmap và handoff chưa định nghĩa, ghi:

```text
NOT DETERMINED
```

## Mutation boundary

Việc gọi file này chỉ cấp quyền kiểm tra, đồng bộ Git an toàn và đề xuất bước tiếp theo.

Chưa được tự:

- sửa file hoặc code
- tạo branch/commit/PR
- merge hoặc xóa branch
- thay đổi runtime state
- enable Module trong production

Nếu người dùng phê duyệt implementation, trước tiên phải hướng dẫn `git switch` và `git pull --ff-only` đúng branch theo canonical workflow.
