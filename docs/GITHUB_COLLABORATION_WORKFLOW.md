# Quy trình làm việc ChatGPT ↔ GitHub ↔ Local

## Mục tiêu

Tài liệu này chuẩn hóa cách làm việc giữa người dùng, ChatGPT và repository GitHub của dự án.

Repository chính:

`git@github.com:tungocvan/laravel-shop.git`

Mục tiêu là để ChatGPT trực tiếp phân tích/sửa code trên GitHub, còn người dùng chủ yếu `git pull`, chạy CLI/test/manual UI và gửi output để debug theo từng bước.

## 1. Vai trò

ChatGPT chịu trách nhiệm:

- đọc code thực tế trên GitHub trước khi đề xuất
- phân tích kiến trúc, dependency và tests liên quan
- tạo feature branch khi task đủ lớn
- sửa/tạo file trực tiếp trên branch GitHub
- chia công việc thành MR/Phase nhỏ, coherent
- thêm/cập nhật automated tests
- hướng dẫn người dùng pull/test
- phân tích output rồi mới quyết định bước tiếp theo

Người dùng chịu trách nhiệm:

- giữ local repository đồng bộ đúng branch
- chạy các lệnh CLI được yêu cầu
- chạy manual UI smoke khi cần
- gửi output nguyên văn để debug
- phê duyệt plan/architecture trước thay đổi lớn

## 2. Quy tắc trước khi sửa code

Trước khi code, ChatGPT phải:

1. kiểm tra branch/base branch
2. đọc code hiện tại trên GitHub
3. đọc tests liên quan
4. xác định dependency và backward compatibility
5. xác định nguyên nhân hoặc phạm vi thay đổi
6. đề xuất plan/MR nếu task lớn

Không sửa trực tiếp `main` nếu chưa thống nhất.

Không force-push, rewrite history hoặc thay đổi source ngoài phạm vi khi không cần thiết.

## 3. Workflow chuẩn

Luồng mặc định:

```text
GitHub inspection
    ↓
Architecture / root-cause analysis
    ↓
Plan / MR checklist
    ↓
User approval nếu cần
    ↓
Feature branch
    ↓
Batch implementation
    ↓
User git pull
    ↓
Focused tests
    ↓
Debug nếu fail
    ↓
Module/System regression
    ↓
Full regression
    ↓
Manual UI smoke
    ↓
Git-clean verification
    ↓
Merge main
    ↓
Push main
    ↓
Delete feature branch
```

## 4. Làm việc theo batch

Không yêu cầu `git pull` sau từng file nhỏ.

Một MR nên được hoàn thành theo batch hợp lý gồm:

- code
- tests
- docs liên quan

Sau đó mới yêu cầu người dùng `git pull` và test một lần.

## 5. Quy tắc yêu cầu CLI

Nếu cần 1 lệnh, chỉ đưa đúng 1 lệnh.

Nếu cần 2 lệnh, nói rõ `Chạy 2 lệnh` và đưa đúng 2 lệnh.

Nếu cần 3 lệnh, nói rõ `Chạy 3 lệnh` và đưa đúng 3 lệnh.

Sau đó dừng và chờ output.

Không đưa trước một chuỗi dài lệnh cho nhiều bước chưa tới.

### 5.1 Pull và kiểm thử theo hai tầng

Khi ChatGPT yêu cầu cập nhật một batch mới, hướng dẫn phải cung cấp trong cùng một lần:

1. lệnh `git pull --ff-only`
2. **Test 1**: kiểm thử tập trung cho phần vừa thay đổi
3. **Test 2**: Request/module regression phù hợp

Người dùng thực hiện theo điều kiện:

- nếu Test 1 **FAIL**, dừng, không chạy Test 2 và gửi nguyên output lỗi
- nếu Test 1 **PASS**, chạy ngay Test 2 rồi gửi kết quả của cả hai tầng

Không tách ba bước trên thành ba lượt trao đổi nếu các lệnh kiểm thử đã xác định được tại thời điểm yêu cầu pull.

## 6. Khi GitHub write bị chặn

Nếu thao tác write GitHub thất bại:

1. nói rõ thao tác nào bị lỗi
2. không chuyển ngay sang bắt người dùng tự viết code
3. yêu cầu CLI kiểm tra tối thiểu
4. chờ output
5. xác định nguyên nhân
6. tiếp tục write qua GitHub khi có thể

## 7. Không có background work

Nếu ChatGPT nói sẽ tiếp tục xử lý thì phải thực hiện ngay trong cùng turn khi tool cho phép.

Không để người dùng chờ một tiến trình nền không tồn tại.

Nếu cần user kích hoạt bước tiếp theo, nói rõ ví dụ:

`Tiếp tục MR-2`

## 8. Test strategy

Test theo tầng:

1. Syntax / lint cần thiết
2. Focused tests cho phần vừa sửa
3. Module regression
4. System/shared-infrastructure regression khi liên quan
5. Full project regression trước merge

Không chạy full regression sau mọi chỉnh sửa nhỏ.

## 9. Debug khi test fail

Không sửa code theo phỏng đoán.

Phân loại nguyên nhân trước:

- code mới
- test
- environment
- permission/ownership
- database
- runtime state
- dependency
- config/cache
- module khác

Chỉ refactor module khác khi đã chứng minh nó là nguyên nhân.

## 10. Manual UI smoke

Nếu task có UI, kiểm tra tối thiểu:

- trang load bình thường
- action thành công
- refresh giữ đúng state
- validation đúng
- permission đúng
- không 404/500
- không lỗi Livewire quan trọng
- browser console không có lỗi quan trọng

## 11. Git working tree

Sau runtime operation có khả năng ghi file, phải kiểm tra:

```bash
git status
```

Mục tiêu:

`nothing to commit, working tree clean`

Runtime state/cache/user settings trên production không được làm tracked source dirty nếu kiến trúc không yêu cầu.

## 12. Docker / production

Nếu feature tạo runtime file/directory:

- kiểm tra `Dockerfile`
- kiểm tra `docker/entrypoint.sh`
- kiểm tra volume persistence
- kiểm tra ownership của `www-data`
- phân biệt CLI chạy bằng `root` với PHP-FPM chạy bằng `www-data`
- không dùng `chmod 777`

## 13. Working tree đang dirty

Nếu local có thay đổi chưa commit:

- dừng trước khi switch/merge/pull có rủi ro
- xem `git diff`
- xác định thay đổi cần giữ hay bỏ
- không dùng `reset --hard`, `clean -fd`, `restore` bừa bãi

## 14. Trước khi merge

Chỉ merge khi các gate applicable đã PASS:

- focused tests
- module regression
- full regression
- manual UI smoke
- Git clean
- Docker/production check nếu liên quan
- docs cập nhật
- không còn debug/temp files

## 15. Quy trình merge chuẩn

```bash
git switch main
git pull --ff-only origin main
git merge --no-ff <feature-branch> -m "merge: <description>"
```

Sau merge:

- `git status`
- chạy regression cần thiết
- kiểm tra log/commit

Chỉ khi PASS mới:

```bash
git push origin main
```

Sau push xác nhận:

- `main == origin/main`
- working tree clean

Sau đó mới xóa branch:

```bash
git branch -d <feature-branch>
git push origin --delete <feature-branch>
```

## 16. Khởi động và bàn giao công việc theo Module

### 16.1 Phản hồi đầu tiên và giới hạn hành động

Khi người dùng yêu cầu áp dụng workflow này kèm tên Module, phản hồi đầu tiên phải xác nhận rằng ChatGPT **chưa sửa code hoặc thay đổi GitHub**. Trước tiên ChatGPT thực hiện kiểm tra chỉ đọc về quyền repository, branch/PR/checkpoint, Module source, tài liệu và handoff; sau đó báo dữ liệu đã xác minh trước khi tiếp tục.

Việc yêu cầu “áp dụng workflow” không tự cấp quyền sửa code, merge, xóa dữ liệu hoặc thực hiện thao tác phá hủy. Phạm vi phải được phân loại rõ là: phân tích, diagnose, review, implementation, kiểm thử/acceptance hay chuẩn bị merge.

### 16.2 Kiểm tra quyền truy cập GitHub trước tiên

Trước khi đọc handoff hoặc phân tích Module, phải xác nhận bằng thao tác chỉ đọc:

1. repository chính xác và tài khoản GitHub đang kết nối
2. mức quyền hiện tại: không truy cập, chỉ đọc, có thể ghi hoặc quản trị khi thực sự cần
3. branch, PR, base branch và commit HEAD liên quan
4. repository/branch có khớp yêu cầu người dùng không

Không tạo commit, branch hoặc file thử chỉ để kiểm tra quyền ghi.

- Không truy cập được: dừng và yêu cầu kết nối lại.
- Chỉ đọc: được phân tích nhưng không được hứa sửa hoặc push.
- Repository, branch hoặc PR không khớp: dừng và yêu cầu xác nhận.

### 16.3 Bảng xác nhận dữ liệu bootstrap

Sau kiểm tra chỉ đọc và trước khi thực hiện công việc, phải báo lại tối thiểu:

| Dữ liệu | Nội dung |
|---|---|
| Repository và GitHub access | Repository, tài khoản/mức quyền đã xác minh |
| Module | Tên Module yêu cầu |
| Module source | `Modules/<Module>` có tồn tại không |
| Module docs | `docs/modules/<Module>` có tồn tại không |
| Handoff | Có/không có `COLLABORATION_HANDOFF.md` |
| Branch / PR / checkpoint | Trạng thái thực tế trên GitHub |
| Working scope | Loại công việc được yêu cầu |
| Remaining work | Lấy từ handoff/tài liệu hoặc ghi chưa xác định |

Nếu dữ liệu không nhất quán, dừng để người dùng xác nhận thay vì tự phỏng đoán.

### 16.4 Xác minh Module và cây fallback tài liệu

Phải xác minh chính xác `Modules/<Module>` trước. Nếu không tồn tại, không tự chọn Module có tên gần giống; phải kiểm tra sai tên, chữ hoa/thường, branch thiếu source, tài liệu orphan hoặc yêu cầu tạo Module mới.

#### A. Có handoff

Nếu có `docs/modules/<Module>/COLLABORATION_HANDOFF.md`:

1. đọc toàn bộ handoff
2. đối chiếu checkpoint/branch/PR với GitHub
3. kiểm tra các file source và tests liên quan
4. tiếp tục từ “Remaining work / Next step”, không phân tích lại từ đầu

Handoff không được ghi đè bằng chứng hiện tại. Khi khác source, source/schema/config là nguồn sự thật và phải báo documentation drift.

#### B. Có thư mục docs nhưng không có handoff

Không đọc toàn bộ `.md` một cách máy móc. Trước tiên lập inventory và ưu tiên:

1. `README.md`, `REQUIREMENTS.md`
2. `ANALYSIS.md`, `INFORMATION.md` khi có
3. master spec, domain invariants và architecture contract
4. implementation completion/summary/addendum
5. acceptance, release evidence và release notes
6. runbook liên quan trực tiếp
7. phase/kế hoạch lịch sử chỉ khi nhiệm vụ hoặc source dẫn tới

Tài liệu phải được kiểm chứng với source/tests hiện tại. Đề xuất tạo handoff khi branch hoàn thành.

#### C. Có Module source nhưng chưa có thư mục docs

Đề xuất áp dụng `.codex/tasks/analyze-module.md`. Task `/analyze` chỉ phân tích, không sửa application code và chỉ được tạo/cập nhật:

```text
docs/modules/<Module>/ANALYSIS.md
docs/modules/<Module>/INFORMATION.md
docs/modules/<Module>/README.md
```

Tạo `COLLABORATION_HANDOFF.md` là batch riêng sau baseline analysis, không trộn vào contract `/analyze`.

#### D. Có docs nhưng không có Module source

Báo tài liệu có thể orphan/stale và dừng để xác nhận.

#### E. Không có cả source lẫn docs

Dừng và yêu cầu xác nhận tên Module, branch hoặc đây có phải Module mới không.

### 16.5 Thứ tự nguồn sự thật

1. Source code, schema và configuration hiện tại.
2. Branch, PR và checkpoint thực tế trên GitHub.
3. `.codex/bootstrap/*`, standards và `ROADMAP.md`.
4. Handoff đã được kiểm chứng.
5. Requirements/analysis/tài liệu Module.
6. Tài liệu lịch sử hoặc kế hoạch cũ.

### 16.6 File handoff bắt buộc khi kết thúc branch

Mỗi Module dùng một file duy nhất:

```text
docs/modules/<Module>/COLLABORATION_HANDOFF.md
```

Trước khi kết thúc feature/fix branch, cập nhật trong chính branch đó: repository/base/branch/PR/checkpoint, phạm vi hoàn thành, batch quan trọng, root cause và cách sửa, quyết định kiến trúc/phân quyền/ranh giới an toàn, migration/seeder/storage/lệnh vận hành, focused test, module regression, UI smoke, Git clean, blocker, việc còn lại và bước tiếp theo được phép.

Chỉ ghi thông tin hữu ích để tiếp tục; không sao chép log dài. Handoff không thay thế requirements, runbook hoặc acceptance document.

Chỉ ghi branch `COMPLETED` khi mọi gate applicable đã PASS. Nếu chưa hoàn tất phải ghi `IN PROGRESS`. Không tự cập nhật handoff ngoài phạm vi đã được người dùng phê duyệt.

## 17. Prompt dùng khi mở chat mới

Có thể dùng khung ngắn sau:

```text
Repository:
git@github.com:tungocvan/laravel-shop.git

Áp dụng docs/GITHUB_COLLABORATION_WORKFLOW.md.

Module: [TÊN MODULE]

Yêu cầu:
[TASK HIỆN TẠI]

Trước tiên kiểm tra quyền repository và báo bảng xác nhận dữ liệu bootstrap. Sau đó đọc handoff hoặc áp dụng cây fallback tài liệu, kiểm tra code/tests thực tế và đề xuất plan.
Chưa sửa code nếu tôi chưa duyệt thay đổi kiến trúc lớn.
Khi cần CLI, nói rõ số lệnh và chờ output trước khi tiếp tục.
```

## 18. Nguyên tắc an toàn

Không thực hiện các thao tác phá hủy như:

- force push
- reset hard
- clean dữ liệu
- migrate fresh
- xóa volume
- xóa database/runtime data

nếu chưa phân tích và được người dùng đồng ý.
