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

## 16. Bàn giao theo Module khi kết thúc branch

Mỗi Module phải có một file bàn giao duy nhất theo quy ước:

```text
docs/modules/<Module>/COLLABORATION_HANDOFF.md
```

Trước khi kết thúc một feature/fix branch, ChatGPT phải cập nhật file này trong chính branch đó. Nội dung tối thiểu gồm:

- repository, base branch, working branch, PR và commit checkpoint mới nhất
- mục tiêu và phạm vi đã hoàn thành
- lịch sử các batch/branch quan trọng
- lỗi hoặc root cause đã phát hiện và cách đã sửa
- quyết định kiến trúc, phân quyền và ranh giới an toàn cần giữ
- migrations, seeders, runtime storage hoặc lệnh vận hành liên quan
- kết quả focused test, module regression, UI smoke và Git clean
- việc còn lại, blocker và bước tiếp theo được phép thực hiện

File handoff là bản tóm tắt tiếp tục công việc, không thay thế requirements, runbook hoặc acceptance document. Chỉ giữ thông tin còn hữu ích cho lần làm việc tiếp theo; không sao chép log dài hoặc output test đầy đủ.

Khi bắt đầu chat mới và người dùng yêu cầu áp dụng workflow này kèm tên Module, ChatGPT phải thực hiện theo thứ tự:

1. đọc toàn bộ `docs/GITHUB_COLLABORATION_WORKFLOW.md`
2. tự tìm và đọc toàn bộ `docs/modules/<Module>/COLLABORATION_HANDOFF.md`
3. kiểm tra branch/PR/checkpoint thực tế trên GitHub
4. đối chiếu code và tests hiện tại trước khi đề xuất hoặc sửa đổi
5. tiếp tục từ mục “Việc còn lại / Bước tiếp theo”, không khởi động lại từ đầu

Nếu file handoff chưa tồn tại, ChatGPT phải ghi rõ điều đó, đọc các tài liệu Module liên quan và tạo file trong branch đang làm việc trước khi kết thúc batch.

## 17. Prompt dùng khi mở chat mới

Có thể dùng khung ngắn sau:

```text
Repository:
git@github.com:tungocvan/laravel-shop.git

Hãy làm việc trực tiếp qua GitHub theo docs/GITHUB_COLLABORATION_WORKFLOW.md.

Yêu cầu:
[TASK HIỆN TẠI]

Module: [TÊN MODULE]

Trước tiên đọc code/tests thực tế, phân tích hiện trạng và đề xuất plan.
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
